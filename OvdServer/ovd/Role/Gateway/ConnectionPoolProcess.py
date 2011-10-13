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
from multiprocessing import Process, current_process
import Queue
import signal
import threading

from Config import Config
from ControlProcess import ControlFatherProcess
from ovd.Logger import Logger
from ProtocolDetectDispatcher import ProtocolDetectDispatcher

from OpenSSL import SSL


class ConnectionPoolProcess(Process):
	
	def __init__(self, child_pipes, father_pipes, ssl_ctx):
		Process.__init__(self)
		
		self.pipes = child_pipes
		self.father_pipes = father_pipes
		self.ssl_ctx = ssl_ctx
		
		self.f_control = None
		self.t_asyncore = None
		self.socks = None
		
		# use for process cleaning
		self.was_lazy = False
		self.clean_timer = None
	
	
	def run(self):
		Logger.info("Gateway:: new process started")
		
		signal.signal(signal.SIGINT, signal.SIG_IGN)
		signal.signal(signal.SIGTERM, signal.SIG_IGN)
		
		# close inherited father pipes
		self.father_pipes[0].close()
		self.father_pipes[1].close()
		
		self.socks = Queue.Queue()

		self.clean_timer = threading.Timer(Config.process_timeout, self.clean)
		self.clean_timer.start()
		
		self.f_control = ControlFatherProcess(self)
		self.f_control.start()
		
		while self.f_control.is_alive() or not self.socks.empty():
			try:
				sock = self.socks.get(timeout=0.01)
				ssl_conn = SSL.Connection(self.ssl_ctx, sock)
				Logger.debug("Gateway:: new connection => %s" % str(ssl_conn.getpeername()))
				ssl_conn.set_accept_state()
				ProtocolDetectDispatcher(ssl_conn, self.f_control, self.ssl_ctx)
			except (IOError, Queue.Empty):
				continue

			# reload asyncore if stopped
			if self.t_asyncore is None or not self.t_asyncore.is_alive():
				# timeout needed for more SSL layer reactivity
				self.t_asyncore = threading.Thread(target=lambda:asyncore.loop(timeout=0.01))
				self.t_asyncore.start()
		
		if self.t_asyncore is not None and self.t_asyncore.is_alive():
			asyncore.close_all()
			self.t_asyncore.join()
		
		Logger.info("Gateway:: child process stopped")
	
	
	def stop(self):
		Logger.debug("Gateway:: stopping child process")
		self.clean_timer.cancel()
		self.f_control.terminate()
	
	
	def terminate(self):
		self.father_pipes[0].send('stop')
	
	
	def clean(self):
		if self.is_sleeping():
			if self.was_lazy:
				self.f_control.send(('stop_pid', current_process().pid))
				return
			else:
				self.was_lazy = True
		else:
			self.was_lazy = False
		self.clean_timer = threading.Timer(Config.process_timeout, self.clean)
		self.clean_timer.start()
	
	
	def is_sleeping(self):
		return self.t_asyncore is None or not self.t_asyncore.is_alive()
