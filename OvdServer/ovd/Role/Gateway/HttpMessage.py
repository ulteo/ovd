# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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

from Config import Config


HTTP_RESPONSES = {
	httplib.FORBIDDEN : "This page is forbidden by the administrator",
	httplib.NOT_FOUND : "This page does not exists",
	httplib.INTERNAL_SERVER_ERROR : "the server crashed for an unknown reason, try again..."
}



def page_error(code):
	if not HTTP_RESPONSES.has_key(code):
		code = httplib.INTERNAL_SERVER_ERROR
	return ("HTTP/1.1 %d %s\r\n\r\n<head></head><body>%s</body>") % \
		(code, httplib.responses[code], HTTP_RESPONSES[code])



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

	http_ptn = re.compile('((?:HEAD)|(?:GET)|(?:POST)) (.*) HTTP/(.\..)')

	def __init__(self):
		self.headers = ''
		self.body = ''

		self.path = ''
		self.len_body = -1
		self.xml_rewrited = False


	def _get_re_header(self, header):
		re_header = header.lower().replace('-', '_') + '_ptn'
		if not HttpMessage.__dict__.has_key(re_header):
			HttpMessage.__dict__[re_header] = re.compile("^%s *:(.*)\r$" % header, re.I | re.U | re.M)
		return HttpMessage.__dict__[re_header]


	def is_headers(self):
		return bool(self.headers)


	def is_body(self):
		return len(self.body) == self.len_body


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
		res = HttpMessage.http_ptn.search(first_line)
		if res is not None:
			self.path = res.group(2)

		len_body = self.get_header('Content-Length')
		if len_body is not None:
			self.len_body = int(len_body)
		else:
			self.len_body = 0

	
	def put_body(self, body):
		_len_body = len(self.body)
		self.body += body[:(self.len_body - _len_body)]
		return len(self.body) - _len_body


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

		# Unknown URL
		else:
			return httplib.NOT_FOUND


	def redirect(self, addr):
		# Session Manager and administration
		if self.path.startswith("/ovd/client/") or \
		   self.path == "/ovd/admin" or self.path.startswith("/ovd/admin/"):
			if Config.general.session_manager != addr:
				return Config.general.session_manager

		# Web Client
		elif self.path == '/ovd' or self.path.startswith("/ovd/"):
			if Config.web_client != addr:
				return Config.web_client

		# Unknown URL
		else:
			return Config.general.session_manager


	def show(self):
		return self.headers + '\r\n' + self.body
