# -*- coding: utf-8 -*-

# Copyright (C) 2010-2014 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
# Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
# Author David LECHEVALIER <david@ulteo.com> 2012
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2014
# Author Julien LANGLOIS <julien@ulteo.com> 2012
# Author Alexandre CONFIANT-LATOUR <a.confiant@ulteo.com> 2013
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

import asyncore
import httplib
import socket
import re
import xml.etree.ElementTree as parser

from ProtocolDetectDispatcher import ProtocolException
from premium.Licensing import Licensing
from HttpMessage import HttpMessage, Protocol, page_error
from Config import Config
from ovd.Logger import Logger
from OpenSSL import SSL

__all__ = ['SSLCommunicator', 'RdpClientCommunicator', 'RdpServerCommunicator',
	'HttpClientCommunicator', 'HttpServerCommunicator', 'HttpsServerCommunicator']

"""
Common communicators
"""

class Communicator(asyncore.dispatcher):
	
	def __init__(self, sock=None, communicator=None):
		self.communicator = communicator
		self._buffer = ''
		self.closed = False
		asyncore.dispatcher.__init__(self, sock=sock)
	
	
	def handle_read(self):
		self._buffer += self.recv(8192)
	
	
	def readable(self):
		if self.communicator is not None:
			return not self.communicator.closed
		return True
	
	
	def writable(self):
		if self.communicator is None:
			return False
		
		_writable = len(self.communicator._buffer) > 0
		if _writable is False and self.communicator.closed is True:
			self.close()
		
		return _writable
	
	
	def handle_write(self):
		sent = self.send(self.communicator._buffer)
		self.communicator._buffer = self.communicator._buffer[sent:]
	
	
	def shutdown_connection(self):
		try:
			self.socket.shutdown(socket.SHUT_WR)
		except socket.error:
			pass
	
	
	def handle_close(self):
		self.shutdown_connection()
		self.close()
		self.closed = True



class SSLCommunicator(Communicator):

	def readable(self):
		if Communicator.readable(self) is False:
			return False
		# hack to support SSL layer
		while self.socket.pending() > 0:
			self.handle_read_event()
		return True


	def handle_read(self):
		try:
			Communicator.handle_read(self)
		except SSL.SysCallError:
			self.handle_close()
			return -1
		except SSL.ZeroReturnError:
			self.handle_close()
			return -1
		except SSL.WantReadError:
			return -1
		except SSL.Error, e:
			# hack for prevent incomprehensible 'SSL_UNDEFINED_CONST_FUNCTION' error,
			# treated as same as an 'SSL.WantReadError' error
			if e.args[0][0][1] == 'SSL_UNDEFINED_CONST_FUNCTION':
				return -1
			else:
				raise


	def handle_write(self):
		try:
			Communicator.handle_write(self)
		except SSL.WantWriteError:
			pass
		except SSL.Error, e:
			# hack for prevent incomprehensible 'SSL_UNDEFINED_CONST_FUNCTION' error,
			# treated as same as an 'SSL.WantWriteError' error
			if e.args[0][0][1] == 'SSL_UNDEFINED_CONST_FUNCTION':
				pass
			else:
				raise

	
	
	def shutdown_connection(self):
		try:
			self.socket.sock_shutdown(socket.SHUT_WR)
		except socket.error:
			pass


class ServerCommunicator(Communicator):

	def __init__(self, remote=None, communicator=None):
		Communicator.__init__(self, communicator=communicator)
		
		self.set_socket(self.make_socket())
		
		if remote is not None:
			try:
				self.connect(remote)
			except socket.error:
				Logger.exception("%s:: socket connection failed"%self.__class__.__name__)
	
	
	def make_socket(self):
		return socket.socket(socket.AF_INET, socket.SOCK_STREAM)



class SecureServerCommunicator(SSLCommunicator, ServerCommunicator):

	def __init__(self, remote=None, communicator=None):
		ServerCommunicator.__init__(self, remote=remote, communicator=communicator)

	def make_socket(self):
		return SSL.Connection(self.ssl_ctx, ServerCommunicator.make_socket(self))



"""
RDP Communicators
"""

class RdpClientCommunicator(SSLCommunicator):

	def __init__(self, socket=None, communicator=None):
		SSLCommunicator.__init__(self, socket, communicator)

		if Licensing.check_license() is not True:
			raise ProtocolException("No valid license")


class RdpServerCommunicator(ServerCommunicator):

	def __init__(self, remote=None, communicator=None):
		ServerCommunicator.__init__(self, remote, communicator)

		if Licensing.check_license() is not True:
			raise ProtocolException("No valid license")


"""
HTTP Communicators
"""

class HttpClientCommunicator(SSLCommunicator):
	
	def __init__(self, sock, ctrl=None, ssl_ctx=None):
		self.f_ctrl = ctrl
		self.ssl_ctx = ssl_ctx
		self.http = HttpMessage(self)
		self.http_history = []
		self.last_service = None
		SSLCommunicator.__init__(self, sock)


	def handle_read(self, data=None):
		if data is None:
			# Read data from socket
			SSLCommunicator.handle_read(self)
		else:
			# Data has already been read by ProtocolDetectDispatcher
			self._buffer = data

		if not self.http.is_ready():
			# Parse it until ready
			if self.make_http_message():
				# Now it is ready
				self.http_history.append(self.http) # Push http object in history
				self._buffer = self.process() # Stream it
		else:
			# Data is streamed "on-the-fly"
			self._buffer = self._buffer # Stream it

		if self.http.is_complete() :
			self.http = HttpMessage(self)
	
	
	def make_http_message(self):
		# Do we have headers ?
		if not self.http.have_headers():
			if self.http.put_headers() is None:
				return False

		# Now we have valid headers

		# Push data
		self.http.put_body()

		return self.http.is_ready()

	
	def process(self):
		# Rewrite GET on /ovd/guacamole/ovdlogin
		if self.http.path.startswith("/ovd/guacamole/ovdlogin"):
			match = re.search("(?P<separator>[?&])token=(?P<token>[^&]*)", self.http.path)

			if Licensing.check_license() is not True:
				raise ProtocolException("No valid license")

			if match is not None:
				token = match.group("token")
				address = self.f_ctrl.send(("digest_token", token))

				if not address or type(address) != tuple or len(address)<2:
					raise Exception('token authorization failed for: ' + token)

				host, port = address
				path = self.http.path[0:match.start("separator")]
				path+= match.group("separator")+"server="+host+"&port="+str(port)
				path+= self.http.path[match.end("token"):]

				match = HttpMessage.http_req_ptn.search(self.http.headers)

				if match is not None:
					headers = self.http.headers[0:match.start("url")]
					headers+= path
					headers+= self.http.headers[match.end("url"):]

					self.http.path = path
					self.http.headers = headers

		# Check last service. If different, a new serverCommunicator must be created
		reconnect = False
		if self.last_service is not None and self.last_service != self.http.service :
			names = ['SESSION_MANAGER', 'ADMINISTRATION', 'WEB_CLIENT', 'ROOT']
			Logger.debug("Gateway:: Client service type switched from "+names[self.last_service]+" to "+names[self.http.service])
			reconnect = True

		# test path permission
		http_code = self.http.auth()
		if http_code is not httplib.OK:
			host = self.http.get_header("Host")
			if host is None:
				host = "%s:%d" % (self.socket.getsockname())
			
			self.send(page_error(http_code, addr=host))
			self.socket.sock_shutdown(socket.SHUT_WR)
			self.handle_close()
			return ''

		# path redirection
		if self.communicator is None or reconnect is True :
			addr = None
		else:
			addr = self.communicator.getpeername()[0]

		redirection = self.http.redirect(addr)

		if redirection is not None:
			(protocol, addr) = redirection

			# Update service
			self.last_service = self.http.service

			if self.communicator is not None:
				self.communicator.close()

			if protocol is Protocol.HTTP:
				self.communicator = HttpServerCommunicator(
					addr, self.f_ctrl, communicator=self)
			elif protocol is Protocol.HTTPS:
				self.communicator = HttpsServerCommunicator(
					(addr, self.ssl_ctx), self.f_ctrl, communicator=self)
		
		# gateway header's tag
		self.http.set_header('OVD-Gateway', 'on')
		
		# keep alive header handle
		if not Config.http_keep_alive:
			self.http.set_header('Connection', 'close')
		
		return self.http.show()


class HttpServerCommunicator(ServerCommunicator):

	def __init__(self, addr, ctrl, communicator=None):
		self.f_ctrl = ctrl
		self.http = HttpMessage(self)
		self.http_communicator = None
		ServerCommunicator.__init__(self, addr, communicator=communicator)


	def handle_read(self):
		# Read data in buffer
		ServerCommunicator.handle_read(self)

		# Fetch the Http request from parent communicator
		if (self.http_communicator is None) and (len(self.communicator.http_history) > 0):
			self.http_communicator = self.communicator.http_history.pop(0)

			if self.http_communicator.path in Config.force_buffering:
				Logger.debug("Force buffering : "+self.http_communicator.path)
				self.http.force_full_buffering = True

		if not self.http.is_ready():
			# Parse it until ready
			if self.make_http_message():
				# Now it is ready
				self._buffer = self.process() # Stream it
				self.http_communicator = None
		else:
			# Data is streamed "on-the-fly"
			self._buffer = self._buffer # Stream it

		if self.http.is_complete() :
			self.http = HttpMessage(self)
	
	
	def make_http_message(self):
		# Do we have headers ?
		if not self.http.have_headers():
			if self.http.put_headers() is None:
				return False
		
		# Now we have valid headers

		# Push data
		self.http.put_body()

		return self.http.is_ready()


	def process(self):
		# in any case of redirection with HTTP protocol use
		if self.http.have_redirection():
			location = self.http.get_header("Location")
			if location is not None and location.startswith("http://"):
				location = location.replace("http", "https", 1)
				self.http.set_header("Location", location)
		
		# XML rewriting on start request
		if (self.http_communicator is not None) and (self.http_communicator.path == "/ovd/client/start"):
			body = self.http.get_body()
			xml = self.rewrite_xml(body)
			self.http.set_body(xml)

		return self.http.show()
	
	
	def rewrite_xml(self, body):
		try:
			session = parser.XML(body)
			if session.tag.lower() != 'session':
				raise Exception("not a 'session' XML response")
		except Exception:
			Logger.exception("Gateway:: parsing XML session failed")
			return None
		
		session.set('mode_gateway', 'on')
		for server in session.findall('server'):
			port = Protocol.RDP
			
			if server.attrib.has_key("port"):
				try:
					port = int(server.attrib["port"])
				except ValueError,err:
					Logger.warn("Gateway:: Invalid protocol: server port attribute is not a digit (%s)"%(server.attrib["port"]))
			
			token = self.f_ctrl.send(('insert_token', (server.attrib['fqdn'], port)))
			server.set('token', token)
			del server.attrib['fqdn']
			if server.attrib.has_key("port"):
				del server.attrib["port"]
		
		return parser.tostring(session)



class HttpsServerCommunicator(SecureServerCommunicator):

	def __init__(self, remote, ctrl, communicator=None):
		(addr, self.ssl_ctx) = remote
		self.f_ctrl = ctrl
		self.http = HttpMessage(self)
		self.http_communicator = None
		SecureServerCommunicator.__init__(self, addr, communicator=communicator)

		self.parent_http = None
		if communicator is not None:
			self.parent_http = self.communicator.http


	def handle_read(self):
		# Read data in buffer
		SecureServerCommunicator.handle_read(self)

		# Fetch the Http request from parent communicator
		if (self.http_communicator is None) and (len(self.communicator.http_history) > 0):
			self.http_communicator = self.communicator.http_history.pop(0)

			if self.http_communicator.path in Config.force_buffering:
				Logger.debug("Force buffering : "+self.http_communicator.path)
				self.http.force_full_buffering = True

		if not self.http.is_ready():
			# Parse it until ready
			if self.make_http_message():
				# Now it is ready
				self._buffer = self.process() # Stream it
				self.http_communicator = None
		else:
			# Data is streamed "on-the-fly"
			self._buffer = self._buffer # Stream it

		if self.http.is_complete() :
			self.http = HttpMessage(self)


	def make_http_message(self):
		# Do we have headers ?
		if not self.http.have_headers():
			if self.http.put_headers() is None:
				return False

		# Now we have valid headers

		# Push data
		self.http.put_body()

		return self.http.is_ready()


	def process(self):
		# in any case of redirection with HTTP protocol use
		if self.http.have_redirection():
			location = self.http.get_header("Location")
			if location is not None and location.startswith("http://"):
				location = location.replace("http", "https", 1)
				self.http.set_header("Location", location)

		# XML rewriting on start request
		if (self.http_communicator is not None) and (self.http_communicator.path == "/ovd/client/start"):
			body = self.http.get_body()
			xml = self.rewrite_xml(body)
			self.http.set_body(xml)

		return self.http.show()


	def rewrite_xml(self, body):
		try:
			session = parser.XML(body)
			if session.tag.lower() != 'session':
				raise Exception("not a 'session' XML response")
		except Exception:
			Logger.exception("Gateway:: parsing XML session failed")
			return None

		session.set('mode_gateway', 'on')
		for server in session.findall('server'):
			port = Protocol.RDP

			if server.attrib.has_key("port"):
				try:
					port = int(server.attrib["port"])
				except ValueError,err:
					Logger.warn("Gateway:: Invalid protocol: server port attribute is not a digit (%s)"%(server.attrib["port"]))

			token = self.f_ctrl.send(('insert_token', (server.attrib['fqdn'], port)))
			server.set('token', token)
			del server.attrib['fqdn']
			if server.attrib.has_key("port"):
				del server.attrib["port"]

		return parser.tostring(session)
