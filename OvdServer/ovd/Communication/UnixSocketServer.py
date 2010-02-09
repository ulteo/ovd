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

import cgi
import httplib
import os
from Queue import Queue

import socket
import struct
from threading import Thread

from ovd.Logger import Logger
from ovd.Config import Config
from ovd.Communication import Communication as AbstractCommunication

class UnixSocketServer(AbstractCommunication):
	def __init__(self, dialogInterfaces):
		AbstractCommunication.__init__(self, dialogInterfaces)
		
		self.socket_filename = "/var/spool/ulteo/ovd.sock"
		if Config.infos.has_key("server_socket_filename"):
			self.socket_filename = Config.infos["server_socket_filename"]
		

		self.queue = Queue()
		self.threads = []
	
	def initialize(self):
		if os.path.exists(self.socket_filename):
			try:
				os.remove(self.socket_filename)
			except Exception, e:
				Logger.error("Unable to initialize Communication: %s"%(str(e)))
				return False
				
		
		self.server = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
		try:
			self.server.bind(self.socket_filename)
		except Exception, e:
			Logger.error("Unable to initialize Communication: %s"%(str(e)))
			return False
		
		os.chmod(self.socket_filename, 0777)
		# todo: authorized www-data group to access
		# todo2: use acl to only authorized www-data user
		
		return True
	
	
	def run(self):
		for i in xrange(10):
			t = RequestHandler(i, self, self.queue)
			t.start()
			self.threads.append(t)
		
		self.server.listen(5)
		
		while 1:
			(sock, _) = self.server.accept()
			Logger.debug("Communication accept job")
			self.queue.put(sock)
			Logger.debug("Communication dispatched job")
	
	def stop(self):
		self.server.close()
		for thread in self.threads:
			if thread.isAlive():
				thread._Thread__stop()
		
		try:
			os.remove(self.socket_filename)
		except Exception:
			pass


class RequestHandler(Thread):
	id = None
	queue = None
	
	def __init__(self, id_, server_instance, queue_):
		Thread.__init__(self)
		
		self.id = id_
		self.server_instance = server_instance
		self.queue = queue_
		#Logger.debug("Processor init %s"%(str(self.id)))
	
	def __str__(self):
		return "RequestHandler "+str(self.id)
	
	def run(self):
		while True:
			#Logger.debug("%s wait job"%(str(self)))
			job = self.queue.get()
			Logger.debug("%s got job"%(str(self)))
			self.work(job)
			#Logger.debug("%s finish job"%(str(self)))
	
	
	def sock2data(self, sock):
		buffer = sock.recv(4)
		try:
			packet_len = struct.unpack('>I', buffer)[0]
		except Exception:
			Logger.warn("sock2data: packet recv syntax error")
			return None
		return sock.recv(packet_len)

	
	def buffer2request(self, data):
		req = {}
		index = 0
		max_len = len(data)
		
		if index+4>max_len:
			return None
		a_len = struct.unpack('>I', data[index:index+4])[0]
		index+= 4
		if a_len == 0:
			req["method"] = "GET"
		else:
			req["method"] = "POST"
		
		if index+4>max_len:
			return None
		a_len = struct.unpack('>I', data[index:index+4])[0]
		index+= 4
		if index+a_len>max_len:
			return None
		req["client"] = data[index:index+a_len].decode('utf-8')
		index+= a_len
		
		if index+4>max_len:
			return None
		a_len = struct.unpack('>I', data[index:index+4])[0]
		index+= 4
		if index+a_len>max_len:
			return None
		req["path"] = data[index:index+a_len].decode('utf-8')
		index+= a_len
		
		return req
		
	def response2buffer(self, response):
		buffer = ""
		b = "%d %s"%(response["code"], httplib.responses[response["code"]])
		buffer+= struct.pack('>I', len(b))
		buffer+= b
		
		buffer+= struct.pack('>I', len(response["Content-Type"]))
		buffer+= response["Content-Type"]
		
		buffer+= struct.pack('>I', len(response["data"]))
		buffer+= response["data"]
		
		return buffer
	
	
	def work(self, sock):
		try:
			data = self.sock2data(sock)
			if data is None:
				sock.close()
				return False
			req = self.buffer2request(data)
			if req is None:
				sock.close()
				return False
		
			req["args"] = {}
			if "?" in req["path"]:
				req["path"], args = req["path"].split("?", 1)
				
				args = cgi.parse_qsl(args)
				for (k,v) in args:
					req["args"][k] = v
			
			response = self.server_instance.process(req)
			if response is None:
				sock.close()
				return False
			if response is False:
				# todo: change the out when unauthorized
				sock.close()
				return False
			
			data = self.response2buffer(response)
	
			
			sock.send(struct.pack('>I', len(data)))
			sock.send(data)

		
		except socket.error, err:
			Logger.warn("process_req: error while comm: %s"%(str(err)))
		finally:
			sock.close()
