# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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
import string
import threading
import uuid
import xml.etree.ElementTree as parser

from ovd.Logger import Logger
from receiver import receiver, receiverXMLRewriter
from sender import sender, senderHTTP


class ReverseProxy(asyncore.dispatcher):

	def __init__(self, FPEM, GATEWAY_PORT, REMOTE_SM_FQDN, HTTPS_PORT, RDP_PORT):
		asyncore.dispatcher.__init__(self)

		self.REMOTE_SM_FQDN = REMOTE_SM_FQDN
		self.REMOTE_SM_PORT = HTTPS_PORT
		self.HTTPS_PORT = HTTPS_PORT
		self.RDP_PORT = RDP_PORT

		self.lock = threading.Lock()
		self.database = {}

		self.ssl_ctx = SSL.Context(SSL.SSLv23_METHOD)
		self.ssl_ctx.use_privatekey_file(FPEM)
		self.ssl_ctx.use_certificate_file(FPEM)

		sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
		self.set_socket(SSL.Connection(self.ssl_ctx, sock))
		#self.set_reuse_addr()

		try:
			self.bind(("0.0.0.0", GATEWAY_PORT))
		except:
			Logger.error('Local Bind Error, Server at port %d is not ready' % GATEWAY_PORT)
			exit()

		self.listen(5)
		Logger.info('Gateway:: listening started')


	def insertToken(self, fqdn):
		token = str(uuid.uuid4())
		try:
			self.lock.acquire()
			self.database[token] = fqdn
		finally:
			self.lock.release()
		Logger.debug('token %s inserted' % token)
		return token


	def handle_accept(self):
		conn, peer = self.accept()
		addr, port = peer
		try:
			r = conn.recv(4096)
		except SSL.ZeroReturnError:
			pass
		except Exception, err:
			Logger.debug('ReverseProxy::handle_accept error %s %s'%(type(err), err))
			conn.close()
			return

		request = r.split('\n', 1)[0]
		utf8_request = request.rstrip('\n\r').decode("utf-8", "replace")
		Logger.debug("Gateway:: request: %s (%s,%d)" % (utf8_request, addr, port))

		# find protocol
		rdp = re.match('\x03\x00(.*)Cookie: mstshash=(\w+);token=([\-\w]+);', request)
		http = re.match('((?:HEAD)|(?:GET)|(?:POST)) (.*) HTTP/(.\..)', request)

		try:
			# RDP case
			if rdp:
				token = rdp.group(3)

				# get FQDN
				self.lock.acquire()
				if self.database.has_key(token):
					fqdn = self.database[token]
					del self.database[token]
					self.lock.release()
					Logger.debug("Access Granted token: %s for fqdn: %s" % (token, fqdn))
				else:
					raise Exception('token authorization failed for: ' + token)

				sender(fqdn, self.RDP_PORT, receiver(conn, r))

			# HTTP case
			elif http:
				path = http.group(2)

				if not path.startswith("/ovd/"):
					raise Exception('wrong HTTP path: ' + path)

				if path == "/ovd/client/start.php":
					rec = receiverXMLRewriter(conn, r, self)
				else:
					rec = receiver(conn, r)
				senderHTTP(self.REMOTE_SM_FQDN, self.REMOTE_SM_PORT, rec, self.ssl_ctx)

			# protocol error
			else:
				raise Exception('bad first request line: ' + request)

		except Exception, err:
			self.lock.release()
			Logger.debug("ReverseProxy::handle_accept error %s %s"%(type(err), err))
			self.close()
