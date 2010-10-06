# -*- coding: UTF-8 -*-

# Copyright (C) 2008,2009 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010
# Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
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
from twisted.internet import reactor, threads
from twisted.internet.error import BindError

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
		try:
			reactor.listenTCP(self.tcp_port, self.site)
		except BindError, exc:
			Logger.error("Unable to bind port %d, system is going to stop"%(self.tcp_port))
			Logger.debug("Unable to bind port %d: "%(self.tcp_port)+str(exc))
			self.status = AbstractCommunication.STATUS_ERROR
			return
		
		self.status = AbstractCommunication.STATUS_RUNNING
		reactor.run(installSignalHandlers=0)
		self.status = AbstractCommunication.STATUS_STOP
	
	def stop(self):
		if reactor.running:
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
		def render_GET_internal(response):
			if response is None:
				Logger.error("HttpRequestHandler::render_GET_internal response is none %s sending httplib.NOT_FOUND"%(request))
				return self.send_error(request, httplib.NOT_FOUND)
			if response is False:
				Logger.error("HttpRequestHandler::render_GET_internal response is False %s sending httplib.UNAUTHORIZED"%(request))
				return self.send_error(request, httplib.UNAUTHORIZED)
				
			request.setResponseCode(httplib.OK)
			request.setHeader("content-type", response["Content-Type"])
			request.write(response["data"])
			try:
				request.finish()
			except:
				pass
			
		req = {}
		req["client"] = request.client.host
		req["method"] = "GET"
		req["domain"] = request.postpath[0]
		
		req["path"] = request.path
		req["args"] = {}
		
		for (k,v) in request.args.items():
			req["args"][k] = v[-1]
			
		d = threads.deferToThread(self.comm_instance.process, req)
		d.addCallback(render_GET_internal)
		return server.NOT_DONE_YET
	
	
	
	
	def render_POST(self, request):
		def render_POST_internal(response):
			if response is None:
				Logger.error("HttpRequestHandler::render_POST_internal return None sending httplib.NOT_FOUND")
				self.send_error(request, httplib.NOT_FOUND)
				return
			
			request.setResponseCode(httplib.OK)
			request.setHeader("content-type", response["Content-Type"])
			request.write(response["data"])
			try:
				request.finish()
			except:
				pass
		
		req = {}
		req["client"] = request.client.host
		req["method"] = "POST"
		
		req["path"] = request.path
		req["args"] = {}
		
		try:
			length = int(request.received_headers["content-length"])
		except:
			length = 0
		if length > 0:
			req["data"] = request.content.read(length)
		
		d = threads.deferToThread(self.comm_instance.process, req)
		d.addCallback(render_POST_internal)
		return server.NOT_DONE_YET
