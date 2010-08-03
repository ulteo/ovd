# -*- coding: UTF-8 -*-

# Copyright (C) 2008,2009 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
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


from twisted.web import server, resource
from twisted.internet import reactor

from BaseHTTPServer import HTTPServer
from SimpleHTTPServer import SimpleHTTPRequestHandler
import cgi
import httplib
import socket

from ovd.Logger import Logger
from ovd.Config import Config
from ovd.Communication import Communication as AbstractCommunication

from threading import Thread
from Queue import Queue

class TwistedHttpServer(AbstractCommunication):
	def __init__(self, dialogInterfaces):
		AbstractCommunication.__init__(self, dialogInterfaces)
		
		self.tcp_port = Config.SLAVE_SERVER_PORT
		
		self.handler = HttpRequestHandler(self)
	
	def initialize(self):
		s = HttpRequestHandler(self)
		self.site = server.Site(s)
		
		return True
	
	def run(self):
		reactor.listenTCP(self.tcp_port, self.site)
		reactor.run()
		
	def stop(self):
		reactor.stop()


class HttpRequestHandler(resource.Resource):
	isLeaf = True
	
	def __init__(self, comm_instance):
		resource.Resource.__init__(self)
		self.comm_instance = comm_instance
	
	def send_error(self, request, code):
		request.setResponseCode(code)
		return ""
	
	def render_GET(self, request):
		req = {}
		req["client"] = request.client.host
		req["method"] = "GET"
		req["domain"] = request.postpath[0]
		
		req["path"] = request.path
		req["args"] = {}
		
		for (k,v) in request.args.items():
			req["args"][k] = v[-1]
	
	
		response = self.comm_instance.process(req)
		if response is None:
			return self.send_error(request, httplib.NOT_FOUND)
		
		if response is False:
			return self.send_error(request, httplib.UNAUTHORIZED)
		
		request.setResponseCode(httplib.OK)
		request.setHeader("content-type", response["Content-Type"])
		return response["data"]
	
	
	def render_POST(self, request):
		req = {}
		req["client"] = request.client.host
		req["method"] = "POST"
		
		req["path"] = request.path
		req["args"] = {}
		
		try:
			length = int(request.requestHeaders.getRawHeaders("content-length")[-1])
		except:
			length = 0
		if length > 0:
			req["data"] = request.content.read(length)
		
		response = self.comm_instance.process(req)
		if response is None:
			self.send_error(httplib.NOT_FOUND)
			return
		
		request.setResponseCode(httplib.OK)
		request.setHeader("content-type", response["Content-Type"])
		return response["data"]

