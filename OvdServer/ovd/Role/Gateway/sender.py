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

class sender(asyncore.dispatcher):
	def __init__(self, receiver, remoteip, remoteport):
		try:
			asyncore.dispatcher.__init__(self)
			self.receiver = receiver
			receiver.sender = self
			self.create_socket(socket.AF_INET, socket.SOCK_STREAM)
			self.connect((remoteip, remoteport))
		except:
			Logger.error('sender:: Core sender module error...')
			exit()

	def handle_read(self):
		read = self.recv(8192)
		self.receiver.to_remote_buffer += read #appends read to the remote buffer

	def writable(self):
		return (len(self.receiver.from_remote_buffer) > 0)

	def handle_write(self):
		try:
			sent = self.send(self.receiver.from_remote_buffer)
			self.receiver.from_remote_buffer = self.receiver.from_remote_buffer[sent:]
		except:
			Logger.error('sender:: Handle_write error')
			exit()

	def handle_close(self):
		try:
			self.close()
			self.receiver.close()
		except:
			Logger.error('sender:: close connexion !!')
