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

import json

from ovd.Logger import Logger
from ApplicationRequestProcessor import ApplicationRequestProcessor
import re

from ovd.SMRequestManager import SMRequestManager as GenericSMRequestManager


class SMRequestManager(GenericSMRequestManager):
	def get_webapps(self):
		response = self.send_packet("/applications/webapps")
		if response is False:
			Logger.warn("SMRequest::get_webapps Unable to send packet")
			return None
		
		document = self.get_response_xml(response)
		if document is None:
			Logger.warn("SMRequest:get_webapps not XML response")
			return None
		
		rootNode = document.documentElement
		
		if rootNode.nodeName != "webapps":
			return None
		
		return rootNode


def setup_app(config, app_id, app_name):
    from ApplicationsDispatcher import ApplicationsDispatcher
    from ApplicationsRepository import ApplicationDefinition
    from handlers import ClientHandler, ServerHandler, DispatchHandler, ChainHandler, RedirectHandler
    from filters import StaticRequestFilter, CookieFilter, HTTPBasicAuthFilter, NTLMFilter

    try:
        config = config[app_name]
        app_config = {'app_id': app_id, 'app_name': app_name, 'start_path': ''}
        for key in config['Configuration']:
            if config['Configuration'][key]['type'] == 'user_login':
                app_config[key] = '{USE_CURRENT_USER_LOGIN}'
            elif config['Configuration'][key]['type'] == 'user_passwd':
                app_config[key] = '{USE_CURRENT_USER_PASSWD}'
            else:
                app_config[key] = config['Configuration'][key]['value']
        
    except KeyError, e:
        Logger.exception("Incorrent configuration file. Key %s not found." % e)
        return
    except TypeError:
        Logger.exception("Incorect config dict. Check your configuration file.")
        return

    handlers = config.get('Handlers') or {}
    app_req_proc_config = {}
    try:
        for handler, handler_dict in handlers.items():
            params = dict([(key, val) for key, val in handler_dict.items()
                            if key not in ['filters', 'type']])
            filters = []
            filters_dict = handler_dict.get('filters')
            filter_items = filters_dict or []
            for f_dict in filter_items:
                f_params = dict([(key, val) for key, val in f_dict.items()
                                if key != 'type'])
                filters.append(locals()[f_dict['type']](app_config, f_params))

            app_req_proc_config[handler] = locals()[handler_dict['type']](app_config, params, filters)
    except KeyError, e:
        Logger.error("Key %s not found. Correct your configuration file" % e)
        return

    app_request_processor = ApplicationRequestProcessor(app_req_proc_config)
    app = ApplicationDefinition(app_id, app_name, re.compile('^' + app_name + '\.'), '',
        app_request_processor, app_config['start_path'])
    return app

    
def setup_apps(reset=False):
    """
    """
    from ApplicationsRepository import ApplicationsRepository
    if reset:
        ApplicationsRepository.reset()
    
    sm_request_manager = SMRequestManager()
    webapps_dom = sm_request_manager.get_webapps()
    
    for webapp_dom in webapps_dom.childNodes:
        config_nodes = webapp_dom.getElementsByTagName('configuration')
        if len(config_nodes) < 1:
            continue
            
        config = json.loads(config_nodes[0].firstChild.data)
        if config.keys() < 1:
            continue
        
        app_id = webapp_dom.getAttribute('id')
        app_name = config.keys()[0]
        try:
            appl = setup_app(config, app_id, app_name)
        except:
            Logger.exception("Setting up an application failed. Correct its configuration.")
            continue
        if appl is None:
            continue
        ApplicationsRepository.register(appl)

    Logger.info("Configured %s webapp(s)" % len(ApplicationsRepository.list()))
    return True


class Protocol:
    HTTP = 80
    HTTPS = 443
    RDP = 3389


class Config:

    general = None
    address = "0.0.0.0"
    port = 8443
    max_process = 10
    max_connection = 100
    process_timeout = 60
    connection_timeout = 10
    http_max_header_size = 2048
    mime_type = (
        'text/html',
        'text/xhtml',
        'text/xml',
        'application/xml',
        'application/rss+xml',
        'application/rdf+xml',
        'application/atom+xml',
        'text/css',
        'text/javascript',
        'application/javascript',
        'application/x-javascript',
        'text/x-json',
        'application/json',
        'application/json-p',
    )
    chunk_size = 8192
    ulteo_session_cookie = 'ulteo_sess'
    connection_secure = False
    timeout_page = "etc/ulteo/ovd/timeout_page.html"
    invalid_cert_page = "etc/ulteo/ovd/invalid_cert_page.html"

    @classmethod
    def init(cls, infos):
        if infos.has_key("address"):
            cls.address = infos["address"]

        if infos.has_key("port") and infos["port"].isdigit():
            try:
                cls.port = int(infos["port"])
            except ValueError:
                Logger.error("Invalid int number for port")

        if infos.has_key("connection_timeout") and infos["connection_timeout"].isdigit():
            try:
                cls.connection_timeout = int(infos["connection_timeout"])
            except ValueError:
                Logger.error("Invalid int number for connection_timeout")

        if infos.has_key("http_max_header_size") and infos["http_max_header_size"].isdigit():
            try:
                cls.http_max_header_size = int(infos["http_max_header_size"])
            except ValueError:
                Logger.error("Invalid int number for http_max_header_size")

        if infos.has_key("max_process"):
            try:
                cls.max_process = int(infos["max_process"])
            except ValueError:
                Logger.error("Invalid int number for max_process")

        if infos.has_key("max_connection"):
            try:
                cls.max_connection = int(infos["max_connection"])
            except ValueError:
                Logger.error("Invalid int number for max_process")

        if infos.has_key("process_timeout"):
            try:
                cls.process_timeout = int(infos["process_timeout"])
            except ValueError:
                Logger.error("Invalid int number for process_timeout")

        if infos.has_key('chunk_size') and infos['chunk_size'].isdigit():
            try:
                cls.chunk_size = int(infos['chunk_size'])
            except ValueError:
                Logger.error("Invalid int number for port")

        if infos.has_key('mime_type'):
            cls.mime_type = tuple([elem[2:-1] for elem in infos['mime_type'].split(',') if elem])

        if infos.has_key('ulteo_session_cookie'):
            cls.ulteo_session_cookie = infos['ulteo_session_cookie']

        if infos.has_key('connection_secure'):
            cls.connection_secure = True if infos['connection_secure'] == 'true' else False
        
        if infos.has_key('timeout_page'):
            cls.timeout_page = infos['timeout_page']

        if infos.has_key('invalid_cert_page'):
            cls.invalid_cert_page = infos['invalid_cert_page']

        return True