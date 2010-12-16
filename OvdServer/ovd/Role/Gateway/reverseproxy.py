# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
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

from ovd.Logger import Logger
import socket, asyncore, datetime, datetime, re, hashlib, string
from random import Random
from OpenSSL import SSL
import xml.etree.ElementTree as parser
import threading
from receiver import *
from receiverxmlrewriter import *
from sender import *

class ReverseProxy(asyncore.dispatcher):
	def __init__(self, fpem, GATEWAY_PORT, REMOTE_SM_FQDN, HTTPS_PORT, RDP_PORT, backlog = 5):
		asyncore.dispatcher.__init__(self)
		self.protocol="HTTP"
		self.flagRDP = False
		self.LOCAL_PORT = GATEWAY_PORT
		self.REMOTE_SM_FQDN = REMOTE_SM_FQDN
		self.REMOTE_SM_PORT = HTTPS_PORT
		self.HTTPS_PORT = HTTPS_PORT
		self.RDP_PORT = RDP_PORT
		self.lock = threading.Lock()
		self.database = {}

		self.ssl_ctx = SSL.Context(SSL.SSLv23_METHOD)
		self.ssl_ctx.use_privatekey_file(fpem)
		self.ssl_ctx.use_certificate_file(fpem)

		try:
			sock = SSL.Connection(self.ssl_ctx, socket.socket(socket.AF_INET, socket.SOCK_STREAM))
			self.set_socket(sock)
			self.socket.setblocking(0)
			self.connected = True

			try:
				self.addr = sock.getpeername()
			except socket.error:
				Logger.debug('Getpeername() failed')

		except:
			Logger.error('Socket Creation Error, exiting...')
			self.exit()

		self.set_reuse_addr()

		try:
			self.bind(("0.0.0.0", self.LOCAL_PORT))
		except:
			Logger.error('Local Bind Error, Server at port :' + str(self.LOCAL_PORT) + ' is not ready!')
			exit()

		try:
			self.listen(backlog)
			Logger.info('Listening Started...')
		except:
			Logger.error('Socket, Listen Error')
			self.exit()

	def insertToken(self, fqdn):
		try:
			self.lock.acquire()

			while True:
				token = hashlib.new('ripemd160')
				m = datetime.datetime.now().strftime("%H:%M").join(Random().sample(string.letters + string.digits,12))
				token.update(m[0:3])
				gen = token.hexdigest()[:15]
				
				if not self.database.has_key(gen):
					break

			Logger.debug('A new token is required : '+gen)
			self.database[gen] = fqdn
		except:
			gen = self.insertToken(self, fqdn)
		finally:
			self.lock.release()
		return gen

	def useToken(self, token):
		fqdn=""

		try:
			self.lock.acquire()

			if self.database.has_key(token):
				fqdn = self.database[token]
				Logger.debug("Access Granted token: " + token + " for fqdn: " + fqdn)
				del self.database[token]
			else :
				Logger.warn("Access denied token: " + token)
				fqdn =  None

		except:
			fqdn =  None
		finally:
			self.lock.release()
		return fqdn

	def exit(self):
		Logger.warn('Exiting...')
		exit()

	def handle_accept(self):
		try:
			conn, addr = self.accept()
			r = conn.recv(4096)
		except:
			Logger.error('Accepting connexion failed, client could not connect')
			return
		
		requestline=r.splitlines()[0]
		
		if requestline[-2:] == '\r\n':
			requestline = requestline[:-2]
		elif requestline[-1:] == '\n':
			requestline = requestline[:-1]

		command = None
		request_version = version = "HTTP/0.9"
		words = requestline.split()
		
		if len(words) == 2:
			[command, path] = words
			rdp_cmd=tuple(command)

			if len(rdp_cmd) > 8 :
				rdp_cookie = rdp_cmd[-7:]

				if rdp_cmd[0] == "\x03" and rdp_cmd[1] == "\x00" and rdp_cmd[-7:] == tuple("Cookie:"):
					token = self.checkCookieRDP(path)

					if len(token) > 0 :
						fqdn = self.useToken(token)

						if fqdn:
							try:
								sender(fqdn, self.RDP_PORT, receiver(conn, r))
							except:
								self.close()
						else:
							Logger.warn('No token found, authorization failed for: ' + token)

					else:
						Logger.warn('Token was not found, closing connexion for: ' + token)
						self.close()
						
					return
					
		if len(words) == 3:
			[command, path, version] = words
		elif len(words) == 2:
			[command, path] = words
			
			if command != 'POST':
				Logger.warn('Bad HTTP syntax in request:' + requestline)
				self.close()

		elif not words:
			self.close()
		else:
			self.close()
			
		if path.startswith("/ovd/client/start.php"):
			senderHTTP(self.REMOTE_SM_FQDN, self.REMOTE_SM_PORT, receiverXMLRewriter(conn, r, self), self.ssl_ctx)
		elif path.startswith("/ovd/"):
			senderHTTP(self.REMOTE_SM_FQDN, self.REMOTE_SM_PORT, receiver(conn, r), self.ssl_ctx)
		else:
			self.close()

	def checkCookieRDP(self, r):
		token = ""
		
		try:
			token = re.search('token=([\w]+);', r).group(1)
		except:
			Logger.warn('No token found in Cookie !')
		return token
