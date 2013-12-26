# -*- coding: utf-8 -*-

# Copyright (C) 2012-2014 Ulteo SAS
# http://www.ulteo.com
# Author Miguel Angel Garcia <mgarcia@pressenter.com.ar> 2012
# Author Ania WSZEBOROWSKA <anna.wszeborowska@stxnext.pl> 2013
# Author Maciej SKINDZIER <maciej.skindzier@stxnext.pl> 2013
# Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2013, 2014
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
import ssl
import OpenSSL
import urlparse
from socket import timeout, SHUT_WR
from time import sleep

from Config import Config
from ovd.Logger import Logger
from SessionsRepository import SessionsRepository
from headers_utils import *
from HttpMessage import HttpMessage
from Utils import replace_params, CurlConnection


class Handler(object):
	"""
	Base class for all handlers.
	"""
	def __init__(self, app_config, options, filters=[]):
		for opt in options.keys():
			if isinstance(options[opt], basestring):
				options[opt] = replace_params(options[opt], app_config)
		
		self.filters = filters
		self.config = app_config
		self.options = options
	
	def handle(self, context, post_processors):
		for f in self.filters:
			f.pre_process(context)
			post_processors.append(f)
		return self.__process__(context, post_processors)
		# post_process on filters are executed in ClientHandler tunnel_request
		# before sending buffer
	
	
	def __process__(self, context, post_processors):
		raise NotImplementedError


class ServerHandler(Handler):
	"""
	Apply filters and forward processing to next handler.
	"""
	
	def __init__(self, app_config, options, filters=[]):
		super(ServerHandler, self).__init__(app_config, options, filters)
		self.config['target'] = urlparse.urlparse(self.options['baseURI'])
		app_config['start_path'] = self.config['target'].path
		
	def __process__(self, context, post_processors):
		return self.options['next_handler']


class ClientHandler(Handler):
	"""
	Connect to proxied app and transfer data to response.
	"""

	def __init__(self, app_config, options, filters=[]):
		Handler.__init__(self, app_config, options, filters)
		self.links_re = re.compile('http[s]?://'+ app_config['target'].netloc +'[/]?')
	
	def __process__(self, context, post_processors):
		self.tunnel_request(context, post_processors)
		return ''
	
	def tunnel_request(self, context, post_processors):
		communicator, session, path = context.communicator, context.session, context.requested_path
		Logger.debug("------------------- tunneling request")
		
		target = self.config['target']
		if target.scheme == 'https' and self.config.get('check_ssl_certificates', False):
			if not self.check_ssl_certs():
				self.display_err_page(Config.invalid_cert_page, communicator)
				self.close_connection(None, communicator)
				return
		
		if 'ntlm_auth' in context.options:
			conn = CurlConnection(target.scheme, target.netloc, auth=context.options['ntlm_auth'])
		else:
			if target.scheme == 'https':
				conn = httplib.HTTPSConnection(target.netloc, timeout=Config.connection_timeout)
			else:
				conn = httplib.HTTPConnection(target.netloc, timeout=Config.connection_timeout)
		method = communicator.http.http_req_ptn.match(communicator.http.headers).group(1)
		req_headers = parse_request_headers(communicator)
		req_headers['Host'] = target.netloc
		# override accept-encoding headers, because we couldn't handle gzip'ed data
		if 'Accept-Encoding' in req_headers:
			req_headers['Accept-Encoding'] = 'deflate'
		
		Logger.debug("Requesting path: " + path)
		conn.request(method, path, communicator.http.body, req_headers)
		try:
			response = conn.getresponse()
		except (timeout, ssl.SSLError), exc:
			if isinstance(exc, ssl.SSLError) and not (exc.args and 'timed out' in exc.args[0]):
				#TODO: handling other exceptions; same as below
				Logger.error("Error getting requested site: %s" % exc)
				return
			self.display_err_page(Config.timeout_page, communicator)
			self.close_connection(conn, communicator)
			return
		except Exception, exc:
			Logger.error("Error getting requested site: %s" % exc)
			return
		
		resp_headers = response.getheaders()
		
		code = response.status
		result = ["HTTP/1.1 %d %s" % (code, httplib.responses[code])]
		rewrite_links = False
		content_length = None
		for name, value in resp_headers:
			if name.lower() == 'content-type':
				ct = value.split(';')[0].lower()
				if ct in Config.mime_type:
					rewrite_links = True
			elif name.lower() == 'transfer-encoding':
				# drop header
				continue
			elif name.lower() == 'content-length':
				content_length = value
				# ignore header, will be set later
				continue
			elif name.lower() == 'location':
				value = self.rewrite_links(value)
			# rewrite header
			result.append('{0}: {1}'.format(name, value))
		
		body = None
		if rewrite_links:
			# read all chunks
			chunks = [response.read(Config.chunk_size)]
			while chunks[-1] != '':
				chunks.append(response.read())
			
			# concatenate chunks and rewrite links
			body = ''.join(chunks)
			body = self.rewrite_links(body)
			
			# update content length, because links rewrite may change size
			content_length = str(len(body))
		
		if content_length:
			result.append('Content-Length: ' + content_length)
		result.extend(['', '']) # empty lines (separator between headers and body)
		
		# post_process of filters
		
		for f in post_processors:
			f.post_process(context, result)
		## send result
		send_buffer = '\r\n'.join(result)
		
		while send_buffer != '':
			try:
				sent = communicator.send(send_buffer)
				send_buffer = send_buffer[sent:]
			except OpenSSL.SSL.WantWriteError:
				sleep(0.1)
				pass
			
			# if buffer is empty add we still have body now is time to send it
			if not send_buffer and body != '':
				send_buffer, body = body, ''
			
			# if no links rewrite is needed we can transfer data chunk by chunk,
			# this should decrease memory usage for large files and user
			# should see progress when downloading it
			if not send_buffer and not rewrite_links:
				send_buffer = response.read(Config.chunk_size)
		
		self.close_connection(conn, communicator)
	
	def rewrite_links(self, body):
		"""
		Remove domains from links.
		"""
		if Config.mode == Config.MODE_PATH:
			return self.links_re.sub('/webapps/'+self.config['app_name']+'/', body)
		else:
			return self.links_re.sub('/', body)
	
	
	def close_connection(self, conn, communicator):
		if conn:
			conn.close()
		if hasattr(communicator.socket, 'sock_shutdown'):
			communicator.socket.sock_shutdown(SHUT_WR)
		else:
			communicator.socket.shutdown(SHUT_WR)
		communicator.handle_close()
	
	def check_ssl_certs(self):
		"""
		Validate SSL Certificates
		"""
		target_host = self.config['target'].netloc
		try:
			cert = ssl.get_server_certificate((target_host, 443))
			x509 = OpenSSL.crypto.load_certificate(OpenSSL.crypto.FILETYPE_PEM, cert)
		except:
			Logger.exception('Error when loading certificate')
			return False
		# Check if certificate has expired
		if x509.has_expired():
			Logger.debug('Certificate has expired')
			return False
		# Check if certificate is self-signed
		if x509.get_issuer() == x509.get_subject():
			Logger.debug('Certificate is self-signed')
			return False
		# Check domain
		try:
			cn = [comp[1] for comp in x509.get_subject().get_components() if comp[0] == 'CN'][0]
		except Exception:
			Logger.debug('CN not found')
			return False
		if cn.startswith('*'):
			if not re.match(r'(\w+\.)*' + cn.lstrip('*.'), target_host):
				Logger.debug('CN of the certificate does not match target host')
				return False
		elif not target_host == cn:
			Logger.debug('CN of the certificate does not match target host')
			return False
		return True
	
	def display_err_page(self, page_path, communicator):
		"""
		Display page different than requested
		"""
		send_buffer = open(page_path, 'r').read()
		
		page = HttpMessage()
		page.set_header('HTTP/1.1', '200 OK')
		page.set_header('Location', '/')
		page.set_header('Content-Type', 'text/html')
		page.set_body(send_buffer)
		communicator.send(page.show())


class DispatchHandler(Handler):
	"""
	Chains the query to other handlers depending on conditions
	"""
	def __process__(self, context, post_processors):
		try:
			next_handler = self.options['bindings']['next_handler']
			for condition in self.options['bindings']:
				if condition == 'next_handler':
					continue
				locals_dict = {'request_path': context.requested_path, 'request_headers': parse_request_headers(context.communicator),}
				exec('result = ' + self.options['bindings'][condition]['cond'], {}, locals_dict)
				if locals_dict['result']:
					next_handler = self.options['bindings'][condition]['next_handler']
					break
		except KeyError, e:
			Logger.error('Key %s was not found. Correct your configuration file.' % e)
			return ''
		Logger.debug('DispatchHandler::Next handler: %s' % next_handler)
		return next_handler


class ChainHandler(Handler):
	"""
	Connects between two handlers
	"""
	def __process__(self, context, post_processors):
		return self.options['next_handler']


class RedirectHandler(Handler):
	"""
	Redirects on attempt to access forbidden page.
	"""
	def __process__(self, context, post_processors):
		Logger.debug("RedirectHandler::processing...")
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
		""".format(self.options['location']).strip()
		sent = context.communicator.send(send_buffer)
		return ''
