# -*- coding: utf-8 -*-

# Copyright (C) 2011-2012 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
# Author David LECHEVALIER <david@ulteo.com> 2012
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

from Config import Config, Protocol


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

	http_req_ptn = re.compile("((?:HEAD)|(?:GET)|(?:POST)) (.*) HTTP/(.\..)")
	http_res_ptn = re.compile("HTTP/(?P<protocol>1\..) (?P<code>\d{3}) (?P<text>.*)\r")
	chunk_ptn = re.compile("^(?P<size>[a-fA-F\d]+)\r\n(?P<data>.*)$", re.S)
	DEFLATE = 1
	CHUNKED = 2

	def __init__(self):
		self.headers = ''
		self.body = ''

		self.path = ''
		self.TE = HttpMessage.DEFLATE
		self.len_body = 0
		self.xml_rewrited = False


	def _get_re_header(self, header):
		re_header = header.lower().replace('-', '_') + '_ptn'
		if not HttpMessage.__dict__.has_key(re_header):
			HttpMessage.__dict__[re_header] = re.compile("^%s *:(.*)\r$" % header, re.I | re.U | re.M)
		return HttpMessage.__dict__[re_header]


	def is_headers(self):
		return bool(self.headers)


	def get_header(self, header):
		if isinstance(header, str):
			_re = self._get_re_header(header)
		elif isinstance(header, re._pattern_type):
			_re = header

		value = _re.search(self.headers)
		if value:
			return value.group(1).strip()
		else:
			return None
	
	
	def set_header(self, header, value):
		new_header = "%s: %s\r" % (header, value)
		_re = self._get_re_header(header)
		if self.get_header(_re) is None:
			self.headers += new_header + '\n'
		else:
			self.headers = _re.sub(new_header, self.headers)


	def put_headers(self, headers):
		self.headers = headers

		first_line = self.headers.split('\r\n', 1)[0]
		res = HttpMessage.http_req_ptn.search(first_line)
		if res is not None:
			self.path = res.group(2)
		
		TE = self.get_header('Transfer-Encoding')
		if TE is not None and TE in 'chunked':
			self.TE = HttpMessage.CHUNKED
		else:
			self.TE = HttpMessage.DEFLATE
			len_body = self.get_header('Content-Length')
			if len_body is not None:
				self.len_body = int(len_body)
	
	
	def is_body(self):
		if self.TE is HttpMessage.DEFLATE:
			return len(self.body) == self.len_body
		elif self.TE is HttpMessage.CHUNKED:
			return bool(self.get_header('Content-Length'))
	
	
	def put_body(self, body):
		if self.TE is HttpMessage.DEFLATE:
			_len_body = len(self.body)
			self.body += body[:(self.len_body - _len_body)]
			return len(self.body) - _len_body
		
		# http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.4.6
		# HTTP chunked mode (experimental support)
		elif self.TE is HttpMessage.CHUNKED:
			self.chunk_body_tmp = body
			self.len_chunk_body_tmp = len(self.chunk_body_tmp)
			len_body = len(self.body)

			def find_chunck_size(self):
				while self.len_chunk_body_tmp > 0:
					(chunck_size, s, self.chunk_body_tmp) = self.chunk_body_tmp.partition('\r\n')
					self.len_chunk_body_tmp = len(self.chunk_body_tmp)
					try:
						chunck_size = int(chunck_size, 16)
					except ValueError:
						continue
					else:
						self.len_body += chunck_size
						if chunck_size is 0:
							self.set_header('Content-Length', self.len_body)
							self.set_header('Transfer-Encoding', 'deflate')
						return chunck_size
				return -1

			if len_body == self.len_body:
				if find_chunck_size(self) is 0:
					return

			while len_body < self.len_body and self.len_chunk_body_tmp > 0:
				self.body += self.chunk_body_tmp[:(self.len_body - len_body)]
				len_body = len(self.body)
				
				self.chunk_body_tmp = self.chunk_body_tmp[self.chunk_body_tmp.find('\r\n')+2:]
				self.len_chunk_body_tmp = len(self.chunk_body_tmp)
				
				if find_chunck_size(self) is 0:
					return
	
	
	def set_body(self, content):
		self.body = content
		self.len_body = len(self.body)
		self.set_header('Content-Length', self.len_body)


	def auth(self):
		# Session Manager
		if self.path.startswith("/ovd/client/"):
			return httplib.OK

		# Administration
		elif self.path == "/ovd/admin" or self.path.startswith("/ovd/admin/"):
			if Config.admin_redirection is True:
				return httplib.OK
			else:
				return httplib.FORBIDDEN

		# Web Client
		elif self.path == '/ovd' or self.path.startswith("/ovd/"):
			if Config.web_client is not None:
				return httplib.OK
			else:
				return httplib.FORBIDDEN

		# root redirection
		elif self.path == '/':
			print Config.root_redirection
			if Config.root_redirection is not None:
				return httplib.FOUND
			else:
				return httplib.NOT_FOUND

		# Unknown URL
		else:
			return httplib.NOT_FOUND


	def is_redirection(self):
		top_header = HttpMessage.http_res_ptn.match(self.headers)
		if top_header is not None:
			code = int(top_header.group("code"))
			return code >= 300 and code < 310
		return False
	
	
	def redirect(self, addr):
		# Session Manager and administration
		if self.path.startswith("/ovd/client/") or \
			self.path == "/ovd/admin" or self.path.startswith("/ovd/admin/"):
			if Config.general.session_manager != addr:
				return Protocol.HTTPS, (Config.general.session_manager, Protocol.HTTPS)

		# Web Client
		elif self.path == '/ovd' or self.path.startswith("/ovd/"):
			if Config.web_client[1] != addr:
				return Config.web_client[0], Config.web_client[1:3]

		# Unknown URL
		else:
			return Protocol.HTTPS, (Config.general.session_manager, Protocol.HTTPS)


	def show(self):
		return self.headers + '\r\n' + self.body
