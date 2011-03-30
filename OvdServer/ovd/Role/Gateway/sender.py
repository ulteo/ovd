# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
# Author Laurent CLOUET <laurent@ulteo.com> 2011
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
import socket

from ovd.Logger import Logger


class sender(asyncore.dispatcher):

	def __init__(self, remote, receiver):
		asyncore.dispatcher.__init__(self)
		self.receiver = receiver
		receiver.sender = self

		self.set_socket(self.make_socket())
		try:
			self.connect(remote)
		except Exception:
			Logger.error('%s:: socket connection failed' % self.__class__.__name__)


	def make_socket(self):
		return socket.socket(socket.AF_INET, socket.SOCK_STREAM)


	def handle_read(self):
		try:
			read = self.recv(8192)
			self.receiver.to_remote_buffer += read
		except SSL.SysCallError:
			self.handle_close()
		except SSL.ZeroReturnError:
			self.close()
		except SSL.WantReadError:
			pass


	def writable(self):
		return len(self.receiver.from_remote_buffer) > 0


	def handle_write(self):
		try:
			sent = self.send(self.receiver.from_remote_buffer)
			self.receiver.from_remote_buffer = self.receiver.from_remote_buffer[sent:]
		except SSL.WantWriteError:
			pass


	def handle_close(self):
		self.close()
		if self.receiver:
			self.receiver.close()



class senderHTTP(sender):

	def __init__(self, remote, receiver, ssl_ctx):
		self.ssl_ctx = ssl_ctx
		sender.__init__(self, remote, receiver)

	def make_socket(self):
		return SSL.Connection(self.ssl_ctx, sender.make_socket(self))
