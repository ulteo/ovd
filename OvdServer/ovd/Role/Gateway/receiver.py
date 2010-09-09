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

class receiver(asyncore.dispatcher):
	def __init__(self, conn, req):
		try:
			asyncore.dispatcher.__init__(self,conn)
			self.to_remote_buffer = ''
			self.from_remote_buffer = req
		except:
			Logger.error('receiver:: Error In Core Receiver Module...')
			self.close()

	def handle_read(self):
		try:
			read = self.recv(8192)
			self.from_remote_buffer += read
		except:
			self.close()

	def writable(self):
		return (len(self.to_remote_buffer) > 0)

	def handle_write(self):
		try:
			sent = self.send(self.to_remote_buffer)
			self.to_remote_buffer = self.to_remote_buffer[sent:]
		except:
			Logger.warn("Client's connexion seems to be busy !")

	def handle_close(self):
		self.close()
