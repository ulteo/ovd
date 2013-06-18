# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Miguel Angel Garcia <mgarcia@pressenter.com.ar> 2012
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

def parse_request_headers(communicator):
	headers = {}
	for header in communicator.http.headers.split("\n"):
		header_item = header.split(":", 1)
		if len(header_item) == 2:
			header_name, header_value = header_item
			headers[header_name] = header_value.strip()
	return headers

def response_headers_get_cookies(headers):
	cookies = {}
	for header in headers:
		if header[0].lower() == 'set-cookie':
			cookie_params = header[1].split(";")
			tmp = cookie_params[0].split("=")
			cookie_name = tmp[0].strip()
			if len(tmp) == 2:
				cookie_val = tmp[1]
			else:
				cookie_val = ''	
			cookies[cookie_name] = cookie_val
	return cookies


def request_headers_get_cookies(headers):
	cookies = {}
	if 'Cookie' in headers:
		for cookie in headers['Cookie'].split(";"):
			tmp = cookie.split("=")
			cookie_name = tmp[0].strip()
			if len(tmp) == 2:
				cookie_val = tmp[1]
			else:
				cookie_val = ''
			cookies[cookie_name] = cookie_val
	return cookies
