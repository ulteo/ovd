# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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
import socket

from Communicator import ClientCommunicator, SSLCommunicator, \
	OvdServerCommunicator, HttpServerCommunicator
from ovd.Logger import Logger


HTTP_RESPONSES = {
	httplib.FORBIDDEN : "This page is forbidden by the administrator",
	httplib.NOT_FOUND : "This page does not exists",
	httplib.INTERNAL_SERVER_ERROR : "the server crashed for an unknown reason, try again..."
}



class HTTPException(Exception):

	def __init__(self, code, path):
		if not HTTP_RESPONSES.has_key(code):
			self.code = httplib.INTERNAL_SERVER_ERROR
		else:
			self.code = code
		self.path = path


	def __str__(self):
		return "%s (%s)" % (httplib.responses[self.code], self.path)



class ProtocolDetectDispatcher(SSLCommunicator):
	
	rdp_ptn = re.compile('\x03\x00.*Cookie: .*token=([\-\w]+);.*')
	http_ptn = re.compile('((?:HEAD)|(?:GET)|(?:POST)) (.*) HTTP/(.\..)')
	
	def __init__(self, conn, f_ctrl, sm, wc, rdp_port):
		SSLCommunicator.__init__(self, conn)
		self.f_ctrl = f_ctrl
		self.sm = sm
		self.wc = wc
		self.rdp_port = rdp_port
		
		self.admin_redirection = False
	
	
	def writable(self):
		# This class doesn't have to write anything,
		# It's just use to detect the protocol
		return False
	
	
	def handle_read(self):
		if SSLCommunicator.handle_read(self) is -1:
			return
		try:
			client = ClientCommunicator(self.socket)
		except socket.error, e:
			if e[0] == socket.EBADF:
				# Connection closed before requesting anything
				# Chrome tests url by opening ssl connection without requesting
				self.handle_close()
				return
			raise e
			
		client._buffer = self._buffer
		
		request = client._buffer.split('\n', 1)[0]
		request = request.rstrip('\n\r').decode("utf-8", "replace")
		
		# find protocol
		rdp  = ProtocolDetectDispatcher.rdp_ptn.match(request)
		http = ProtocolDetectDispatcher.http_ptn.match(request)

		try:
			# RDP case
			if rdp:
				token = rdp.group(1)
				fqdn = self.f_ctrl.send(("digest_token", token))
				Logger.debug("ProtocolDetectDispatcher:: request: RDP (%s -> %s)" % (fqdn, token))
				if not fqdn:
					raise Exception('token authorization failed for: ' + token)
				server = OvdServerCommunicator((fqdn, self.rdp_port), communicator=client)
			
			# HTTP case
			elif http:
				Logger.debug("ProtocolDetectDispatcher:: request: http %s" % request)
				path = http.group(2)
				
				# Session Manager
				if path.startswith("/ovd/client/"):
					if path == "/ovd/client/start.php":
						client.set_rewrite_xml(self.f_ctrl)
					server = HttpServerCommunicator(self.sm, communicator=client)

				# Administration
				elif path == "/ovd/admin" or path.startswith("/ovd/admin/"):
					if not self.admin_redirection:
						raise HTTPException(httplib.FORBIDDEN, path)
					server = HttpServerCommunicator(self.sm, communicator=client)

				# Web Client
				elif path == '/ovd' or path.startswith("/ovd/"):
					if not self.wc:
						raise HTTPException(httplib.FORBIDDEN, path)
					server = HttpServerCommunicator(self.wc, communicator=client)

				# Unknown URL
				else:
					raise HTTPException(httplib.NOT_FOUND, path)

			# protocol error
			else:
				raise Exception('bad first request line: ' + request)
			
			client.set_communicator(server)
		
		except HTTPException, e:
			Logger.debug("Gateway:: ProtocolDetectDispatcher:: HTTP: %s" % e)
			self.send(("HTTP/1.1 %d %s\r\n\r\n<head></head><body>%s</body>") % \
				(e.code, httplib.responses[e.code], HTTP_RESPONSES[e.code]))
			self.socket.sock_shutdown(socket.SHUT_WR)
			self.handle_close()

		except Exception, err:
			Logger.error("ProtocolDetectDispatcher::handle_read error %s %s" % (type(err), err))
			self.handle_close()
	
	
	def set_admin_redirection(self, _bool):
		if isinstance(_bool, bool):
			self.admin_redirection = _bool
