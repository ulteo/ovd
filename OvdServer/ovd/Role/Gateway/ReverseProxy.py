# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
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
from multiprocessing import Process, Pipe, current_process
import signal
import socket
import threading

from ConnectionPoolProcess import ConnectionPoolProcess
from ControlProcess import ControlChildProcess
from ovd.Config import Config
from ovd.Logger import Logger

from passfd import sendfd


def connection_pool_process(child_pipes, father_pipes, s_unix, ssl_ctx):
	Logger.info("New Gateway process started")

	# close inherited father pipes
	father_pipes[0].close()
	father_pipes[1].close()

	# do not inherit father's binded socket
	asyncore.socket_map = {}

	p = ConnectionPoolProcess(child_pipes, s_unix, ssl_ctx)

	def clean_process():
		if p.is_sleeping():
			if p.was_lazy:
				p.f_control.send(('stop_pid', current_process().pid))
				return
			else:
				p.was_lazy = True
		else:
			p.was_lazy = False
		global timer
		timer = threading.Timer(Config.gateway_process_timeout, clean_process)
		timer.start()
	global timer
	timer = threading.Timer(Config.gateway_process_timeout, clean_process)
	timer.start()

	def stop_process(signum, frame):
		global timer
		timer.cancel()
		signal.signal(signal.SIGINT, signal.SIG_IGN)
		if signum is signal.SIGTERM:
			signal.signal(signal.SIGTERM, signal.SIG_IGN)
			p.stop_asyncore()
	signal.signal(signal.SIGINT, stop_process)
	signal.signal(signal.SIGTERM, stop_process)

	p.loop()
	Logger.info("Gateway child process stopped")



class ReverseProxy(asyncore.dispatcher):

	def __init__(self, ssl_ctx, gateway, sm, rdp_port):
		asyncore.dispatcher.__init__(self)

		self.sm = sm
		self.ssl_ctx = ssl_ctx
		self.rdp_port = rdp_port

		self.processes = {}

		sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
		self.set_socket(sock)
		self.set_reuse_addr()

		try:
			self.bind(gateway)
		except:
			Logger.error('Local Bind Error, Server at port %d is not ready' % gateway[1])
			exit()

		self.listen(5)
		Logger.info('Gateway:: running on port %d' % gateway[1])


	def handle_accept(self):
		conn, peer = self.accept()

		s_unix = None
		for pid, proc in self.processes.items():
			proc = proc[0]
			nb_conn = proc[1].send("nb_conn")
			self.processes[pid][1] = nb_conn
			if nb_conn < Config.gateway_max_connection:
				s_unix = proc[2]
				break
		if s_unix is None:
			if len(self.processes) < Config.gateway_max_process:
				pid = self.create_process()
				s_unix = self.processes[pid][0][2]
			else:
				best_proc = min(self.processes, key=lambda pid:self.processes[pid][1])
				Logger.warn("Gateway service has reached the open connections limit")
				s_unix = self.processes[best_proc][0][2]

		sendfd(s_unix, conn.fileno())
		conn.close()


	def create_process(self):
		(p00, p10) = Pipe() # for the father
		(p01, p11) = Pipe() # for the child
		father_pipes = (p10, p11)
		child_pipes = (p01, p00)
		(s0, s1) = socket.socketpair(socket.AF_UNIX, socket.SOCK_STREAM)

		process = Process(target=connection_pool_process, args=(child_pipes, father_pipes, s1, self.ssl_ctx))
		process.start()
		child_pipes[0].close()
		child_pipes[1].close()

		control = ControlChildProcess(self, father_pipes)

		self.processes[process.pid] = [(process, control, s0), 0]
		return process.pid


	def kill_process(self, pid):
		p = self.processes.pop(pid)[0]
		p[0].terminate()
		p[1].stop()
		if p[1].thread is not threading.current_thread():
			p[1].thread.join()
		p[0].join()


	def close(self):
		asyncore.dispatcher.close(self)
		for pid in list(self.processes):
			self.kill_process(pid)
