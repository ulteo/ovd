# -*- coding: UTF-8 -*-

# Copyright (C) 2008-2012 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2008-2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011, 2012
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


from BaseHTTPServer import HTTPServer
from SimpleHTTPServer import SimpleHTTPRequestHandler
import cgi
import httplib
import socket
import time

from ovd.Logger import Logger
from ovd.Config import Config
from ovd.Communication import Communication as AbstractCommunication

from threading import Thread
from Queue import Queue

class HttpServer(AbstractCommunication):
	def __init__(self, dialogInterfaces):
		AbstractCommunication.__init__(self, dialogInterfaces)
		
		self.webserver = None
		self.tcp_port = Config.SLAVE_SERVER_PORT
	
	def initialize(self):
		try:
			self.webserver = ThreadPoolingHttpServer( ("", self.tcp_port), HttpRequestHandler, 5)
		except socket.error, e:
			Logger.error("Unable to initialize Communication: %s"%(str(e)))
			return False
		
		self.webserver.comm_instance = self
		return True
	
	def run(self):
		self.status = AbstractCommunication.STATUS_RUNNING
		self.webserver.serve_forever()
		self.status = AbstractCommunication.STATUS_STOP
	
	
	def stop(self):
		if self.webserver is not None:
			self.webserver.server_close()


class ThreadPoolingHttpServer(HTTPServer):
	# request_queue_size defines the number of concurrent connections
	# By default, request_queue_size is 5.
	request_queue_size = 255
	
	def __init__(self, server_address, RequestHandlerClass, numberOfThread):
		# On Windows, if we authorize the 'allow_reuse_address' parameter. Two servers can bind on the same ip
		self.allow_reuse_address = Config.server_allow_reuse_address
		
		self.serverHasBeLanched = False
		HTTPServer.__init__(self, server_address, RequestHandlerClass)
		self.threadNumber = numberOfThread
		
		self.spooler = Queue()
		self.threads = []
		
		for _ in xrange(self.threadNumber):
			t = Thread(name = "HTTPRequestHandler", target = self.thread_run)
			self.threads.append(t)
			t.start()
	
	def thread_run(self):
		while True:
			(request, client_address) = self.spooler.get()
			self.process_request_thread(request, client_address)
	
	def process_request_thread(self, request, client_address):
		try:
			self.finish_request(request, client_address)
		except socket.error, (code, msg):
			(addr, port) = client_address
			Logger.debug("HTTPServer: %s (%s, %s)" % (msg, addr, port))
		except Exception, e:
			Logger.debug("HTTPServer: unknown exception %s: %s" % (type(e), e))
		finally:
			self.close_request(request)
	
	def process_request(self, request, client_address):
		self.spooler.put((request, client_address))
	
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
		
		for t in self.threads:
			if t.isAlive():
				t._Thread__stop()
		time.sleep(3)
		for t in self.threads:
			if t.isAlive():
				t._Thread__delete()

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
