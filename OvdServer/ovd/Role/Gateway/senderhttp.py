# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
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
import socket
import asyncore
from OpenSSL import SSL as SSL
from threading import Thread
import xml.etree.ElementTree as parser

class senderHTTP(asyncore.dispatcher):
	def __init__(self, fpem_location, receiver, remoteip, remoteport):
		self.remoteip = remoteip
		self.remoteport = remoteport
		self.fpem = fpem_location
		
		try:
			asyncore.dispatcher.__init__(self)
			self.receiver = receiver
			receiver.sender = self
		except:
			Logger.error('senderHTTP:: Core sender module error...')
			exit()

		try:
			ctx = SSL.Context(SSL.SSLv23_METHOD)
			ctx.use_privatekey_file(self.fpem)
			ctx.use_certificate_file(self.fpem)
			sock = SSL.Connection(ctx, socket.socket(socket.AF_INET, socket.SOCK_STREAM))
			self.set_socket(sock)
			self.socket.connect((self.remoteip, self.remoteport))
		except:
			Logger.error('senderHTTP:: Socket Creation Error')
			exit()

	def handle_read(self):
		try :
			read = self.recv(8192)
			self.receiver.to_remote_buffer += read
		except:
			self.close()

	def writable(self):
		return (len(self.receiver.from_remote_buffer) > 0)

	def handle_write(self):
		try:
			sent = self.send(self.receiver.from_remote_buffer)
			self.receiver.from_remote_buffer = self.receiver.from_remote_buffer[sent:]
		except:
			Logger.error('senderHTTP:: Handle_write error')
			self.close()

	def handle_close(self):
		try:
			self.close()
			self.receiver.close()
		except:
			Logger.error('senderHTTP::failed to close connexion !')
