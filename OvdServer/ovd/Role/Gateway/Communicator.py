# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
# Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
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
import socket

from ovd.Logger import Logger
import XML

from OpenSSL import SSL


class Communicator(asyncore.dispatcher):

	def __init__(self, sock=None):
		asyncore.dispatcher.__init__(self, sock=sock)
		self.communicator = None
		self._buffer = ''


	def handle_read(self):
		self._buffer += self.recv(8192)


	def writable(self):
		return len(self.communicator._buffer) > 0


	def handle_write(self):
		sent = self.send(self.communicator._buffer)
		self.communicator._buffer = self.communicator._buffer[sent:]


	def handle_close(self):
		self.close()
		if self.communicator:
			self.communicator.close()



class SSLCommunicator(Communicator):

	def readable(self):
		# hack to support SSL layer
		while self.socket.pending() > 0:
			self.handle_read_event()
		return True


	def handle_read(self):
		try:
			Communicator.handle_read(self)
		except SSL.SysCallError:
			self.handle_close()
		except SSL.ZeroReturnError:
			self.close()
		except SSL.WantReadError:
			return -1


	def handle_write(self):
		try:
			Communicator.handle_write(self)
		except SSL.WantWriteError:
			pass



class ServerCommunicator(Communicator):

	def __init__(self, remote, comm):
		Communicator.__init__(self)
		self.communicator = comm
		comm.communicator = self

		self.set_socket(self.make_socket())
		try:
			self.connect(remote)
		except socket.error, e:
			Logger.error("%s:: socket connection failed: %s" % (self.__class__.__name__, e))


	def make_socket(self):
		return socket.socket(socket.AF_INET, socket.SOCK_STREAM)



class OvdServerCommunicator(ServerCommunicator):
	pass



class SessionManagerCommunicator(SSLCommunicator, ServerCommunicator):

	def __init__(self, remote, communicator):
		(sm, self.ssl_ctx) = remote
		ServerCommunicator.__init__(self, sm, communicator)


	def make_socket(self):
		return SSL.Connection(self.ssl_ctx, ServerCommunicator.make_socket(self))



class ClientCommunicator(SSLCommunicator):

	def __init__(self, conn, req):
		SSLCommunicator.__init__(self, conn)
		self._buffer = req



class ClientCommunicatorRewriter(ClientCommunicator):

	def __init__(self, conn, req, f_ctrl):
		ClientCommunicator.__init__(self, conn, req)
		self.hasRewrited = False
		self.f_ctrl = f_ctrl


	def writable(self):
		if len(self.communicator._buffer) == 0:
			return False

		if self.hasRewrited:
			return True

		if XML.response_ptn.search(self.communicator._buffer):
			self.hasRewrited = True
			return True

		xml = XML.session_ptn.search(self.communicator._buffer)
		if xml:
			self.communicator._buffer = XML.rewrite(self.communicator._buffer, xml, self.f_ctrl)
			self.hasRewrited = True
		return bool(xml)
