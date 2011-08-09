# -*- coding: utf-8 -*-

# Copyright (C) 2008-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011
# Author Samuel BOVEE <samuel@ulteo.com> 2011
# Author Gaëtan COLLET <gaetan@ulteo.com> 2011
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
from ovd.Logger import Logger
from Config import Config
from DialogHypVM import DialogHypVM

import cgi
import httplib

class HttpServer2(HTTPServer):
	
	def __init__(self, server_address, role):
	
		# On Windows, if we authorize the 'allow_reuse_address' parameter. Two servers can bind on the same ip
		self.allow_reuse_address = Config.general.server_allow_reuse_address
		
		self.serverHasBeLanched = False
		
		self.role = role
		
		self.comm_interne = DialogHypVM(self.role)
		
		HTTPServer.__init__(self, server_address, HttpRequestHandler)
	
	
	def serve_forever(self):
		
		self.serverHasBeLanched = True
		HTTPServer.serve_forever(self)
	
	
	def server_close(self):
		
		if self.serverHasBeLanched:
			try:
				HTTPServer.shutdown(self)
			except Exception, err:
				# with python 2.5 shutdown does not exist
				pass
			
			HTTPServer.server_close(self)
		
		
class HttpRequestHandler(SimpleHTTPRequestHandler):
			
	def log_message(self, format, *args):
		""" Override the parent log function"""
		pass
	
	
	def log_error(self, format, *args):
		""" Override the parent log function"""
		Logger.debug("HTTPRequestHandler Error: %s"%(format%args))
	
	
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
		
		response  = self.server.comm_interne.process(req)
		
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
		
		length = int(self.headers["Content-Length"])
		if length > 0:
			req["data"] = self.rfile.read(length)
							
		#return false ? why ?		
		response = self.server.comm_interne.process(req)
				
		if response is None:
			self.send_error(httplib.NOT_FOUND)
			return
		
		
		self.send_response(httplib.OK)
		self.send_header("Content-Type", response["Content-Type"])
		self.end_headers()
		self.wfile.write(response["data"])
