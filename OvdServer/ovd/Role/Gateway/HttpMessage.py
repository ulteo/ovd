# -*- coding: utf-8 -*-

# Copyright (C) 2011-2013 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
# Author David LECHEVALIER <david@ulteo.com> 2012
# Author Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2013
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

import httplib
import re
import urlparse

from Config import Config, Protocol
from ovd.Logger import Logger
from Utils import gunzip, gzip


HTTP_RESPONSES = {
	httplib.FORBIDDEN : "This page is forbidden by the administrator",
	httplib.NOT_FOUND : "This page does not exists",
	httplib.INTERNAL_SERVER_ERROR : "the server crashed for an unknown reason, try again..."
}



def page_error(code, addr=None):
	header = "HTTP/1.1 %d %s\r\n" % (code, httplib.responses[code])
	body = "\r\n"
	
	if code is httplib.FOUND and Config.root_redirection is not None and addr is not None:
		header += "Location: https://%s/%s\r\n" % (addr, Config.root_redirection)
	
	if HTTP_RESPONSES.has_key(code):
		body += "<head></head><body>%s</body>" % HTTP_RESPONSES[code]
	
	return header + body



class Service(object):

	(SESSION_MANAGER, ADMINISTRATION, WEB_CLIENT, ROOT, WEBAPPS) = range(0, 5)

	@staticmethod
	def get(path):
		if path.startswith("/ovd/client/"):
			return Service.SESSION_MANAGER
		elif path == "/ovd/admin" or path.startswith("/ovd/admin/"):
			return Service.ADMINISTRATION
		elif path == '/ovd' or path.startswith("/ovd/"):
			return Service.WEB_CLIENT
		elif path.startswith("/webapps/") or path.startswith("/webapps-"):
			return Service.WEBAPPS
		elif path == '/':
			return Service.ROOT



class HttpException(Exception):

	def __init__(self, code, path):
		if not HTTP_RESPONSES.has_key(code):
			self.code = httplib.INTERNAL_SERVER_ERROR
		else:
			self.code = code
		self.path = path


	def __str__(self):
		return "%s (%s)" % (httplib.responses[self.code], self.path)


class HttpMessage():

	http_req_ptn = re.compile("(?P<method>(?:HEAD)|(?:GET)|(?:POST)) (?P<url>.*) HTTP/(?P<protocol>.\..)")
	http_res_ptn = re.compile("HTTP/(?P<protocol>1\..) (?P<code>\d{3}) (?P<text>.*)\r")
	chunk_ptn = re.compile("^(?P<size>[a-fA-F\d]+)\r\n(?P<data>.*)$", re.S)
	DEFLATE = 1
	CHUNKED = 2

	def __init__(self, communicator):
		self.communicator = communicator
		self.headers = ''
		self.body = ''

		self.path = ''
		self.service = None
		self.TE = None
		self.len_body = 0
		self.force_full_buffering = False
		self.chunked_end = False


	def _get_re_header(self, header):
		re_header = header.lower().replace('-', '_') + '_ptn'
		if not HttpMessage.__dict__.has_key(re_header):
			HttpMessage.__dict__[re_header] = re.compile("^(?P<key>%s) *:(?P<value>.*)\r$" % header, re.I | re.U | re.M)
		return HttpMessage.__dict__[re_header]


	def have_headers(self):
		return bool(self.headers)


	def get_header(self, header):
		if isinstance(header, str):
			_re = self._get_re_header(header)
		elif isinstance(header, re._pattern_type):
			_re = header

		value = _re.search(self.headers)
		if value:
			return value.group("value").strip()
		else:
			return None
	
	
	def set_header(self, header, value):
		new_header = "%s: %s\r" % (header, value)
		_re = self._get_re_header(header)
		if self.get_header(_re) is None:
			self.headers += new_header + '\n'
		else:
			self.headers = _re.sub(new_header, self.headers)


	def del_header(self, header):
		if isinstance(header, str):
			_re = self._get_re_header(header)
		elif isinstance(header, re._pattern_type):
			_re = header

		match = _re.search(self.headers)
		if match is not None:
			self.headers = self.headers[0:match.start("key")]+self.headers[match.end("value")+2:]


	def put_headers(self):
		headers, separator, body = self.communicator._buffer.partition("\r\n\r\n")

		if separator is '':
			# No or incomplete header chunk
			return None

		self.headers = headers + "\r\n"
		first_line = self.headers.split('\r\n', 1)[0]

		# Get requested service
		res = HttpMessage.http_req_ptn.search(first_line)
		if res is not None:
			self.path = res.group("url")
			self.service = Service.get(self.path)
		
		# Get Transfert encoding
		TE = self.get_header('Transfer-Encoding')
		if TE is not None and TE in 'chunked':
			self.TE = HttpMessage.CHUNKED
		else:
			self.TE = HttpMessage.DEFLATE
			len_body = self.get_header('Content-Length')
			if len_body is not None:
				self.len_body = int(len_body)

		if self.path is not '':
			Logger.debug("Gateway:: HTTP request: " + self.path)

		self.communicator._buffer = body
		return body;
	
	
	def have_body(self):
		return len(self.body) == self.len_body


	def get_body(self):
		if self.get_header('Content-Encoding') == 'gzip':
			return gunzip(self.body)
		else:
			return self.body
	
	
	def put_body(self):
		if self.TE is HttpMessage.DEFLATE:
			_len_body = len(self.body)
			self.body += self.communicator._buffer[:(self.len_body - _len_body)]
			self.communicator._buffer = ''
			return len(self.body) - _len_body
		
		elif self.TE is HttpMessage.CHUNKED:
			if self.force_full_buffering:
				self.body += self.communicator._buffer
				self.len_body = len(self.body);
				if self.communicator._buffer[-5:] == '0\r\n\r\n' :
					# Convert it
					self.body = self.unChunk()
					self.len_body = len(self.body)
					self.set_header('Content-Length', self.len_body)
					self.del_header('Transfer-Encoding')
				
					self.force_full_buffering = False
					self.chunked_end = True
			else:
				# Stream the data
				self.body += self.communicator._buffer
				self.len_body = len(self.body);

				if self.communicator._buffer[-5:] == '0\r\n\r\n' :
					self.chunked_end = True

			self.communicator._buffer = ''
			return self.len_body


	def set_body(self, content):
		if content is None:
			return

		if self.get_header('Content-Encoding') == 'gzip':
			content = gzip(content)

		self.body = content
		self.len_body = len(self.body)
		self.set_header('Content-Length', self.len_body)


	def unChunk(self):
		if self.TE is not HttpMessage.CHUNKED:
			return

		body_buffer = self.body
		ret = ""

		while True:
			match = HttpMessage.chunk_ptn.match(body_buffer)

			if match is not None:
				size = int(match.group("size"), 16)
				data = body_buffer[match.end("size")+2:match.end("size")+size+2]
				body_buffer = body_buffer[match.end("size")+size+4:]

				ret += data

				if size == 0:
					return ret


	def auth(self):
		if   self.service is Service.SESSION_MANAGER:
			return httplib.OK

		elif self.service is Service.ADMINISTRATION:
			if Config.admin_redirection is True:
				return httplib.OK
			else:
				return httplib.FORBIDDEN

		elif self.service is Service.WEB_CLIENT:
			if Config.web_client is not None:
				return httplib.OK
			else:
				return httplib.FORBIDDEN

		elif self.service is Service.WEBAPPS:
			if Config.webapps_redirection is True:
				return httplib.OK
			else:
				return httplib.FORBIDDEN
		
		elif self.service is Service.ROOT:
			if Config.root_redirection is not None:
				return httplib.FOUND
			else:
				return httplib.NOT_FOUND

		else:
			return httplib.NOT_FOUND


	def have_redirection(self):
		top_header = HttpMessage.http_res_ptn.match(self.headers)
		if top_header is not None:
			code = int(top_header.group("code"))
			return code >= 300 and code < 310
		return False
	
	
	def redirect(self, addr):
		if self.service in [Service.SESSION_MANAGER, Service.ADMINISTRATION]:
			if Config.general.session_manager != addr:
				return Protocol.HTTPS, (Config.general.session_manager, Protocol.HTTPS)

		elif self.service is Service.WEB_CLIENT:
			if Config.web_client[1] != addr:
				return Config.web_client[0], Config.web_client[1:3]
		
		elif self.service is Service.WEBAPPS:
			header = self.get_header("x-ovd-webappsserver")
			if header is not None:
				url = urlparse.urlparse(header)
				token = url.path[len("/webapps-"):]
			
			else:
				components = self.path.split("/")
				token = components[1][len("webapps-"):]
			
			# get the token
                        address = self.communicator.f_ctrl.send(("get_token", token))
			url = urlparse.urlparse(address)

			proto = Protocol.HTTPS
			if url.scheme == "http":
				proto = Protocol.HTTP
			return proto, (url.hostname, url.port)

		else:
			return Protocol.HTTPS, (Config.general.session_manager, Protocol.HTTPS)


	def is_ready(self):
		if self.TE is HttpMessage.DEFLATE:
			return  (self.have_headers() and self.have_body())
		elif self.TE is HttpMessage.CHUNKED:
			if self.force_full_buffering == True:
				return self.have_headers() and self.chunked_end
			else:
				return self.have_headers()
		else:
			return False


	def is_complete(self):
		if self.TE is HttpMessage.DEFLATE:
			return self.is_ready()
		elif self.TE is HttpMessage.CHUNKED:
			return self.chunked_end
		else:
			return False


	def show(self):
		return self.headers + '\r\n' + self.body
