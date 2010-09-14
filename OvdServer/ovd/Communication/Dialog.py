# -*- coding: utf-8 -*-

# Copyright (C) 2008-2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
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

class Dialog:
	@staticmethod
	def getName():
		raise NotImplementedError('must be redeclared')
	
	
	def process(self, request):
		raise NotImplementedError('must be redeclared')
	
	def req_answer(self, content, code=httplib.OK):
		response = {}
		response["code"] = code
		response["Content-Type"] = "text/xml"
		response["data"] = content.toxml("utf-8")
		
		return response

	def req_answerText(self, content, code=httplib.OK):
		response = {}
		response["code"] = code
		response["Content-Type"] = "text/plain"
		response["data"] = content
		
		return response

	def req_unauthorized(self):
		response = {}
		response["code"] = httplib.UNAUTHORIZED
		response["Content-Type"] = "text/plain"
		response["data"] = "Unauthorized"
		
		return response

	def req_forbidden(self):
		response = {}
		response["code"] = httplib.FORBIDDEN
		response["Content-Type"] = "text/plain"
		response["data"] = "Forbidden"
		
		return response

	def req_not_found(self):
		response = {}
		response["code"] = httplib.NOT_FOUND
		response["Content-Type"] = "text/plain"
		response["data"] = "Not Found"
		
		return response
