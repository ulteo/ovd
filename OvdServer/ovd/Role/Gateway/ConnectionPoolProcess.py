# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
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

import asyncore
import socket
import threading

from ControlProcess import ControlFatherProcess
from ovd.Logger import Logger
from ProtocolDetectDispatcher import ProtocolDetectDispatcher

from OpenSSL import SSL
from passfd import recvfd


class ConnectionPoolProcess():

	def __init__(self, pipes, s_unix, ssl_ctx):
		self.s_unix = s_unix
		self.t_asyncore = None
		self.was_lazy = False

		self.f_control = ControlFatherProcess(self, pipes)
		self.sm = (self.f_control.send("get_sm"), ssl_ctx)
		self.rdp_port = self.f_control.send("get_rdp_port")


	def loop(self):
		self.f_control.start()

		while True:
			try:
				fd = recvfd(self.s_unix)[0]
			except OSError, e:
				if e.errno == 4:
					# the child process receive a signal
					# but it doesn't stop himself by this way
					continue
				else:
					break
			except RuntimeError:
				break
			sock = socket.fromfd(fd, socket.AF_INET, socket.SOCK_STREAM)
			Logger.debug("New gateway connection => %s" % str(sock.getpeername()))

			ssl_conn = SSL.Connection(self.sm[1], sock)
			ssl_conn.set_accept_state()
			ProtocolDetectDispatcher(ssl_conn, self.f_control, self.sm, self.rdp_port)
			self.reload_asyncore()


	def is_sleeping(self):
		return not self.t_asyncore.is_alive()


	def reload_asyncore(self):
		def asyncore_loop():
			asyncore.loop(timeout=0.01)
			# timeout needed for more SSL layer reactivity
		if self.t_asyncore is None or not self.t_asyncore.is_alive():
			self.t_asyncore = threading.Thread(target=asyncore_loop)
			self.t_asyncore.start()


	def stop_asyncore(self):
		Logger.debug("Stopping gateway child process")
		if self.t_asyncore.is_alive():
			asyncore.close_all()
			self.t_asyncore.join()
		self.s_unix.shutdown(socket.SHUT_RD)
		self.s_unix.close()
		self.f_control.stop()
