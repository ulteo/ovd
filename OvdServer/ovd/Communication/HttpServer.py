# -*- coding: UTF-8 -*-

# Copyright (C) 2008,2009 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2008,2009
# Author Julien LANGLOIS <julien@ulteo.com> 2009
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


from BaseHTTPServer import HTTPServer
from SimpleHTTPServer import SimpleHTTPRequestHandler
import cgi
import httplib
import socket

from ovd.Logger import Logger
from ovd.Config import Config
from ovd.Communication import Communication as AbstractCommunication

class HttpServer(AbstractCommunication):
	def __init__(self, dialogInterfaces):
		AbstractCommunication.__init__(self, dialogInterfaces)
		
		self.webserver = None
		self.tcp_port = Config.SLAVE_SERVER_PORT
	
	def initialize(self):
		try:
			self.webserver = HTTPServer( ("", self.tcp_port), HttpRequestHandler)
		except socket.error, e:
			Logger.error("Unable to initialize Communication: %s"%(str(e)))
			return False
		
		self.webserver.comm_instance = self
		return True
	
	def run(self):
		self.webserver.serve_forever()
		
	def stop(self):
		if self.webserver is not None:
			self.webserver.server_close()



class HttpRequestHandler(SimpleHTTPRequestHandler):
	def log_request(self, l):
		""" Override the parent log function"""
		Logger.debug("HTTPRequestHandler %s"%(str(l)))
	
	def do_GET(self):
		req = {}
		req["client"] = self.client_address[0]
		req["method"] = "GET"
		req["domain"] = self.path.split("/", 2)[1]
		
		req["path"] = self.path
		req["args"] = {}
		
		if "?" in self.path:
			req["path"], args = self.path.split("?", 1)
			
			args = cgi.parse_qsl(args)
			for (k,v) in args:
				req["args"][k] = v
	
	
		response = self.server.comm_instance.process(req)
		if response is None:
			self.send_error(httplib.NOT_FOUND)
			return
		if response is False:
			self.send_error(httplib.UNAUTHORIZED)
			return
		
		self.send_response(httplib.OK)
		self.send_header("Content-Type", response["Content-Type"])
		self.end_headers()
		self.wfile.write(response["data"])
	
	
	def do_POST(self):
		req = {}
		req["client"] = self.client_address[0]
		req["method"] = "POST"
		
		req["path"] = self.path
		req["args"] = {}
		
		#if "?" in self.path:
			#req["path"], args = self.path.split("?", 1)
			
			#args = cgi.parse_qsl(args)
			#for (k,v) in args:
				#req["args"][k] = base64.decodestring(v).decode('utf-8')
				
		length = int(self.headers["Content-Length"])
		if length > 0:
			req["data"] = self.rfile.read(length)
			
		print req
	
		response = self.server.comm_instance.process(req)
		if response is None:
			self.send_error(httplib.NOT_FOUND)
			return
		
		
		self.send_response(httplib.OK)
		self.send_header("Content-Type", response["Content-Type"])
		self.end_headers()
		self.wfile.write(response["data"])
