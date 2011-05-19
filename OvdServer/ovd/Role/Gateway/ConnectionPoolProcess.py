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
import signal
import socket
import threading

from Config import Config
from ControlProcess import ControlFatherProcess
from ovd.Logger import Logger
from ProtocolDetectDispatcher import ProtocolDetectDispatcher

from OpenSSL import SSL
from passfd import recvfd


class ConnectionPoolProcess(Process):
	def __init__(self, child_pipes, father_pipes, s_unix, ssl_ctx):
		Process.__init__(self)
		
		self.father_pipes = father_pipes
		self.s_unix = s_unix
		self.ssl_ctx = ssl_ctx
		
		self.f_control = ControlFatherProcess(self, child_pipes)
		self.t_asyncore = None
		
		# use for process cleaning
		self.was_lazy = False
		self.clean_timer = threading.Timer(Config.process_timeout, self.clean)
	
	
	def run(self):
		Logger.info("Gateway:: new process started")
		
		# close inherited father pipes
		self.father_pipes[0].close()
		self.father_pipes[1].close()
		
		self.f_control.start()
		self.clean_timer.start()
		
		signal.signal(signal.SIGINT, self.stop)
		signal.signal(signal.SIGTERM, self.stop)
		
		self.sm = (self.f_control.send("get_sm"), self.ssl_ctx)
		self.rdp_port = self.f_control.send("get_rdp_port")
		
		while True:
			try:
				fd = recvfd(self.s_unix)[0]
			except OSError, e:
				if e.errno == 4:
					# the child process receive a signal
					# but it should not stop itself by this way
					continue
				else:
					break
			except RuntimeError:
				break
			sock = socket.fromfd(fd, socket.AF_INET, socket.SOCK_STREAM)
			Logger.debug("Gateway:: new connection => %s" % str(sock.getpeername()))
			
			ssl_conn = SSL.Connection(self.sm[1], sock)
			ssl_conn.set_accept_state()
			ProtocolDetectDispatcher(ssl_conn, self.f_control, self.sm, self.rdp_port)
			
			# reload asyncore if stopped
			if self.t_asyncore is None or not self.t_asyncore.is_alive():
				# timeout needed for more SSL layer reactivity
				self.t_asyncore = threading.Thread(target=lambda:asyncore.loop(timeout=0.01))
				self.t_asyncore.start()
		
		Logger.info("Gateway:: child process stopped")
	
	
	def stop(self, signum, frame):
		self.clean_timer.cancel()
		signal.signal(signal.SIGINT, signal.SIG_IGN)
		if signum is signal.SIGTERM:
			Logger.debug("Gateway:: Stopping child process")
			signal.signal(signal.SIGTERM, signal.SIG_IGN)
			if self.t_asyncore.is_alive():
				asyncore.close_all()
				self.t_asyncore.join()
			self.s_unix.shutdown(socket.SHUT_RD)
			self.s_unix.close()
			self.f_control.stop()
	
	
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
		return not self.t_asyncore.is_alive()
