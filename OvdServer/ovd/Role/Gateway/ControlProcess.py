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
import threading

from TokenDatabase import digestToken, insertToken


class ControlClassProcess():

	def __init__(self, _class, pipes):
		self._class = _class
		(self._pipe_s, self._pipe_m) = pipes
		self.running = True

		def control(pipe):
			while True:
				try:
					cmd = pipe.recv()
					if type(cmd) is type(""):
						args = ()
					elif type(cmd) is type(()):
						args = cmd[1:]
						cmd = cmd[0]
					cmd = '_'+cmd
					if hasattr(self, cmd):
						attr = getattr(self, cmd)
						pipe.send(attr(*args))
					else:
						pipe.send(None)
				except (EOFError, IOError):
					break

		self.thread = threading.Thread(target=control, args=(self._pipe_s,))
		self.thread.start()


	def send(self, cmd):
		self._pipe_m.send(cmd)
		return self._pipe_m.recv()


	def stop(self):
		self._pipe_s.close()
		self._pipe_m.close()



class ControlFatherProcess(ControlClassProcess):

	def __init__(self, _class, pipes):
		ControlClassProcess.__init__(self, _class, pipes)

	def _nb_conn(self):
		return len(asyncore.socket_map) / 2



class ControlChildProcess(ControlClassProcess):

	def __init__(self, _class, pipes):
		ControlClassProcess.__init__(self, _class, pipes)

	def _get_sm(self):
		return self._class.sm

	def _get_rdp_port(self):
		return self._class.rdp_port

	def _digest_token(self, token):
		return digestToken(token)

	def _insert_token(self, fqdn):
		return insertToken(fqdn)

	def _stop_pid(self, pid):
		self._class.kill_process(pid)
