# -*- coding: utf-8 -*-

# Copyright (C) 2012-2013 Ulteo SAS
# http://www.ulteo.com
# Author Miguel Angel Garcia <mgarcia@pressenter.com.ar> 2012
# Author Ania WSZEBOROWSKA <anna.wszeborowska@stxnext.pl> 2013
# Author Maciej SKINDZIER <maciej.skindzier@stxnext.pl> 2013
# Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

import re
import httplib
import urllib2
import socket
from Config import Config
from ovd.Logger import Logger
from SessionsRepository import SessionsRepository
from headers_utils import *
from Utils import replace_params
from ntlm import HTTPNtlmAuthHandler

import mechanize

COOKIE_RE = re.compile('([^=]*)[=]([^;]*)[; ]*')
SET_COOKIE_RE = re.compile('set-cookie: ([^=]*)[=]([^;]*)', re.IGNORECASE)


class Filter(object):
    """
    Base class for all filters.
    """
    def __init__(self, app_config, options):
        for opt in options.keys():
            if isinstance(options[opt], basestring):
                options[opt] = replace_params(options[opt], app_config)
                
        self.config = app_config
        self.options = options
    
    def pre_process(self, context):
        """
        actions to perform before processing
        """
        raise NotImplementedError

    def post_process(self, context, result):
        """
        """
        raise NotImplementedError
        
    def get_value(self, value, session):
        value = replace_params(value, self.config)
        return value.format(**session.credentials())
        

class StaticRequestFilter(Filter):
    """
    StaticRequestFilter handles filling and submitting login form.
    options:
        uri: login uri
        form: dict with form fields
    """
    def pre_process(self, context):
        Logger.debug("StaticRequestFilter pre_process")
        session = context.session
        if not session.get('ulteo_autologin'):
            return
        
        target = self.config['target']
        login_url = '{0}://{1}/{2}'.format(target.scheme, target.netloc, self.options['path'])
        needed_fields = set(self.options['form'])
        
        br = mechanize.Browser()
        br.open(login_url)
        to_select = None
        for i, form in enumerate(br.forms()):
            available_fields = set([control.name for control in form.controls])
            if needed_fields.issubset(available_fields):
                to_select = i
        
        if to_select is None:
            Logger.error('Necessary fields not found in any form!')
            return
        
        br.select_form(nr=to_select)
        for name, value in self.options['form'].items():
            br.form[name] = self.get_value(value, session)

        response = br.submit()
        
        if response.code != 200:
            Logger.error("Couldn't log in:")
            Logger.error(response.get_data())
            return False
        session['auth_cookies'] = {}
        for value in response.info().getheaders('set-cookie'):
            m = COOKIE_RE.search(value)
            if m:
                session['auth_cookies'][m.group(1)] = value

        session['ulteo_autologin'] = True

    def post_process(self, context, result):
        """
        Check if server responsed 302 to login page,
        if yes - login and send request again.

        When server responsed 200, parse result content with
        content_regexp. If match, login and send request again.
        """
        Logger.debug("StaticRequestFilter post_process")
        session = context.session

        if result[0].startswith('HTTP/1.1 200'):
            index = 0
            for line in result:
                if line == '':
                    index = result.index(line)
                    break
            resp_body = result[index:]
            resp_body = '\n'.join(resp_body)
            if hasattr(self.options, 'content_regexp') and re.search(self.options['content_regexp'], resp_body, re.DOTALL):
                session['ulteo_autologin'] = False
                send_buffer = """
HTTP/1.1 301 Moved Permanently
Location: {0}
Content-Type: text/html
Content-Length: 174

<html>
<head>
<title>Moved</title>
</head>
<body>
<h1>Moved</h1>
</body>
</html>
                """.format(context.requested_path).strip()
                sent = context.communicator.send(send_buffer)


        if result[0].startswith('HTTP/1.1 302'):
            for line in result:
                if line.startswith('Location: '):
                    host = line[10:]
                    if hasattr(self.options, 'regexp') and re.search(self.options['regexp'], host):
                        session['ulteo_autologin'] = False

                        Logger.debug("Found login location in redirect, redirecting to: " + context.requested_path)
                        send_buffer = """
HTTP/1.1 301 Moved Permanently
Location: {0}
Content-Type: text/html
Content-Length: 174

<html>
<head>
<title>Moved</title>
</head>
<body>
<h1>Moved</h1>
</body>
</html>
                        """.format(context.requested_path).strip()
                        sent = context.communicator.send(send_buffer)

class CookieFilter(Filter):
    """
    CookieFilter performs different actions on cookies, based on app config.

    """
    def __init__(self, app_config, options):
        super(CookieFilter, self).__init__(app_config, options)
        self.options.setdefault('managed', [])
        self.options.setdefault('relayed', [])
        self.options.setdefault('suppressed', [])
        self.options['suppressed'].append(Config.ulteo_session_cookie)

    def pre_process(self, context):
        """
        Get auth_cookies and cookies form session and inject to request headers.
        """
        Logger.debug("CookieFilter pre_process")
        session = context.session
        headers = context.communicator.http.headers.split('\r\n')[:-1]
        cookies = ''
        if session.get('auth_cookies'):
            for cookie in session['auth_cookies'].values():
                cookie_match = COOKIE_RE.search(cookie)
                cookies += cookie_match.group(1) + '=' + cookie_match.group(2) + '; '
        if session.get('cookies'):
            for name, value in session['cookies'].items():
                cookies += name + '=' + value + '; '
        if cookies:
            Logger.debug("Injecting following cookies: " + cookies)
        
        req_headers = parse_request_headers(context.communicator)
        if 'Cookie' in req_headers:
            req_cookies = request_headers_get_cookies(req_headers)
            modified = bool(cookies)
            for name, value in req_cookies.items():
                if name in self.options['suppressed']:
                    modified = True
                    Logger.debug("Suppressing cookies: " + name)
                else:
                    cookies += name + '=' + value + '; '
            
            if modified:
                for header in headers:
                    if header.startswith('Cookie: '):
                        newheader = 'Cookie: ' + cookies
                        headers[headers.index(header)] = newheader
        elif cookies:
            headers.append('Cookie: ' + cookies)
        context.communicator.http.headers = '\r\n'.join(headers)

    def post_process(self, context, result):
        """
        Based on application configuration
        supressing, managing and relaying cookies
        """
        Logger.debug("CookieFilter post_process")
        session = context.session

        for i, line in enumerate(result):
            if line.lower().startswith('set-cookie'):
                line_match = SET_COOKIE_RE.search(line)
                cookie_name = line_match.group(1)
                cookie_val = line_match.group(2)
                if cookie_name in self.options['relayed']:
                    result[i] = self.rewrite_domain(line, context)
                else:
                    Logger.debug("Cookie " + cookie_name + " removed from response!")
                    result[i] = None ## mark to delete
                    if cookie_name in self.options['managed']:
                        Logger.debug("Cookie " + cookie_name + " is stored in session!")
                        session['cookies'][cookie_name] = cookie_val
        
        ## delete marked lines
        result[:] = [i for i in result if i is not None]
                

    def rewrite_domain(self, text, context):
        """
        Remove domains from links.
        """
        host = context.communicator.http.get_header('Host')
        return text.replace(self.config['target'].netloc, host)

class HTTPBasicAuthFilter(Filter):
    def pre_process(self, context):
        from base64 import b64encode
        session = context.session
        headers = context.communicator.http.headers.split('\r\n')[:-1]
        username = self.get_value(self.options['user'], session)
        password = self.get_value(self.options['pass'], session)
        credentials = b64encode('{0}:{1}'.format(username, password))
        headers.append('Authorization: Basic ' + credentials)
        context.communicator.http.headers = '\r\n'.join(headers)

    def post_process(self, context, result):
        pass

class NTLMFilter(Filter):
    def pre_process(self, context):
        """
        Inform ClientHandler that should use NTLM for this request.
        """
        session = context.session
        username = self.get_value(self.options['user'], session)
        password = self.get_value(self.options['pass'], session)
        context.options['ntlm_auth'] = '{0}:{1}'.format(username, password)

    def post_process(self, context, result):
        pass
