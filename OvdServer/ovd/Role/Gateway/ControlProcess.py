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
from multiprocessing.reduction import rebuild_socket
import pickle
from threading import Thread

from TokenDatabase import digestToken, insertToken


class ControlClassProcess(Thread):
	
	def __init__(self, _class):
		Thread.__init__(self)
		self._class = _class
		(self._pipe_s, self._pipe_m) = self._class.pipes
	
	
	def run(self):
		while True:
			try:
				cmd = self._pipe_s.recv()
				if type(cmd) is type(""):
					args = ()
				elif type(cmd) is type(()):
					args = cmd[1:]
					cmd = cmd[0]
				cmd = '_'+cmd
				if hasattr(self, cmd):
					attr = getattr(self, cmd)
					self._pipe_s.send(attr(*args))
				else:
					self._pipe_s.send(None)
			except (EOFError, IOError):
				break
	
	
	def terminate(self):
		self._pipe_s.close()
		self._pipe_m.close()
	
	
	def send(self, cmd):
		self._pipe_m.send(cmd)
		return self._pipe_m.recv()



class ControlFatherProcess(ControlClassProcess):
	
	def _nb_conn(self):
		return len(asyncore.socket_map) / 2
	
	def _socket(self, picklable):
		args = pickle.loads(picklable)
		sock = rebuild_socket(*args)
		self._class.socks.put(sock)
	
	def _stop(self):
		self._class.stop()



class ControlChildProcess(ControlClassProcess):
	
	def _digest_token(self, token):
		return digestToken(token)
	
	def _insert_token(self, fqdn):
		return insertToken(fqdn)
	
	def _stop_pid(self, pid):
		self._class.kill_process(pid)
