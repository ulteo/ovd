# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
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

from OpenSSL import SSL

import asyncore
import re
import socket
import time

from ovd.Logger import Logger
from receiver import receiver, receiverXMLRewriter
from sender import sender, senderHTTP
from TokenDatabase import digestToken


class ReverseProxy(asyncore.dispatcher):

	def __init__(self, ssl_ctx, gateway, sm, rdp_port):
		asyncore.dispatcher.__init__(self)

		self.sm = sm
		self.rdp_port = rdp_port
		self.ssl_ctx = ssl_ctx

		self.rdp_ptn = re.compile('\x03\x00.*Cookie: .*token=([\-\w]+);.*')
		self.http_ptn = re.compile('((?:HEAD)|(?:GET)|(?:POST)) (.*) HTTP/(.\..)')

		sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
		self.set_socket(SSL.Connection(self.ssl_ctx, sock))
		#self.set_reuse_addr()

		try:
			self.bind(gateway)
		except:
			Logger.error('Local Bind Error, Server at port %d is not ready' % gateway[1])
			exit()

		self.listen(5)
		Logger.info('Gateway:: listening started')


	def handle_accept(self):
		conn, peer = self.accept()
		Logger.debug3("ReverseProxy: New connection => %s"%(str(peer)))
		
		ProtocolDetectDispatcher(conn, peer, self)


class ProtocolDetectDispatcher(asyncore.dispatcher):
	def __init__(self, conn, peer, proxyInstance):
		asyncore.dispatcher.__init__(self, conn)
		self.peer = peer
		self.proxyInstance = proxyInstance
	
	def writable(self):
		return False
		# This class doesn't have to write anything,
		# It's just use to detect the protocol
	
	def handle_read(self):
		Logger.debug3("ProtocolDetectDispatcher::handle_read on %s"%(repr(self)))
		try:
			r = self.recv(4096)
			while self.socket.pending() > 0:
				r+= self.recv(4096)
		
		except SSL.SysCallError:
			Logger.debug3("ProtocolDetectDispatcher::handle_read SSL.SysCallError")
			self.handle_close()
		except SSL.ZeroReturnError:
			Logger.debug3("ProtocolDetectDispatcher::handle_read SSL.ZeroReturnError")
			self.close()
		except SSL.WantReadError:
			return
		
		request = r.split('\n', 1)[0]
		utf8_request = request.rstrip('\n\r').decode("utf-8", "replace")

		# find protocol
		rdp  = self.proxyInstance.rdp_ptn.match(request)
		http = self.proxyInstance.http_ptn.match(request)

		try:
			# RDP case
			if rdp:
				token = rdp.group(1)
				fqdn = digestToken(token)
				if not fqdn:
					raise Exception('token authorization failed for: ' + token)
				sender((fqdn, self.proxyInstance.rdp_port), receiver(self.socket, r))
				

			# HTTP case
			elif http:
				Logger.debug("ProtocolDetectDispatcher:: request: http %s %s" % (utf8_request, str(self.peer)))
				path = http.group(2)

				if not (path == '/ovd' or path.startswith("/ovd/")):
					raise Exception('wrong HTTP path: ' + path)

				if path == "/ovd/client/start.php":
					rec = receiverXMLRewriter(self.socket, r)
				else:
					rec = receiver(self.socket, r)
				senderHTTP(self.proxyInstance.sm, rec, self.proxyInstance.ssl_ctx)

			# protocol error
			else:
				raise Exception('bad first request line: ' + request)

		except Exception, err:
			Logger.debug("ProtocolDetectDispatcher::handle_read error %s %s"%(type(err), err))
			self.handle_close()
