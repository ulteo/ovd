# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
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

import errno
from multiprocessing import Pipe
import os
import socket
import threading

from Config import Config
from ControlProcess import ControlChildProcess
from ConnectionPoolProcess import ConnectionPoolProcess
from ovd.Logger import Logger
from ovd.Role.Role import Role as AbstractRole

from OpenSSL import SSL
from passfd import sendfd


class Role(AbstractRole):

	HTTPS_PORT = 443
	RDP_PORT = 3389


	@staticmethod
	def getName():
		return "Gateway"


	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)

		self.sm = (Config.general.session_manager, self.HTTPS_PORT)
		self.rdp_port = self.RDP_PORT

		self.sock = None
		self.ssl_ctx = None
		self.processes = {}


	def init(self):
		Logger.info("Gateway init")

		fpem = os.path.join(Config.general.conf_dir, "gateway.pem")
		if os.path.exists(fpem):
			self.ssl_ctx = SSL.Context(SSL.SSLv23_METHOD)
			self.ssl_ctx.use_privatekey_file(fpem)
			self.ssl_ctx.use_certificate_file(fpem)
		else:
			Logger.error("Gateway role need a certificate (%s)" % fpem)
			return False

		try:
			self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
			self.sock.bind((Config.address, Config.port))
			self.sock.listen(5)
		except socket.error, e:
			Logger.error("Gateway:: socket init: %s" % e)
			return False

		Logger.info('Gateway:: running on (%s, %d)' % (Config.address, Config.port))
		return True


	def stop(self):
		if self.sock:
			self.sock.shutdown(socket.SHUT_RD)
			self.sock.close()
		for pid in list(self.processes):
			self.kill_process(pid)


	def run(self):
		if not self.sock:
			return

		self.has_run = True
		self.status = Role.STATUS_RUNNING

		while True:
			try:
				conn = self.sock.accept()[0]
			except socket.error, e:
				# common stop
				if e.errno is errno.EINVAL:
					break
				else:
					Logger.error("Gateway:: socket accept: %s" % e)
					raise e

			s_unix = None
			for pid, proc in self.processes.items():
				proc = proc[0]
				nb_conn = proc[1].send("nb_conn")
				self.processes[pid][1] = nb_conn
				if nb_conn < Config.max_connection:
					s_unix = proc[2]
					break
			if s_unix is None:
				if len(self.processes) < Config.max_process:
					pid = self.create_process()
					s_unix = self.processes[pid][0][2]
				else:
					best_proc = min(self.processes, key=lambda pid:self.processes[pid][1])
					Logger.warn("Gateway service has reached the open connections limit")
					s_unix = self.processes[best_proc][0][2]

			sendfd(s_unix, conn.fileno())
			conn.close()

		self.status = Role.STATUS_STOP


	def create_process(self):
		(p00, p10) = Pipe() # for the father
		(p01, p11) = Pipe() # for the child
		father_pipes = (p10, p11)
		child_pipes = (p01, p00)
		(s0, s1) = socket.socketpair(socket.AF_UNIX, socket.SOCK_STREAM)

		proc = ConnectionPoolProcess(child_pipes, father_pipes, s1, self.ssl_ctx)
		proc.start()
		child_pipes[0].close()
		child_pipes[1].close()

		control = ControlChildProcess(self, father_pipes)
		control.start()

		self.processes[proc.pid] = [(proc, control, s0), 0]
		return proc.pid


	def kill_process(self, pid):
		p = self.processes.pop(pid)[0]
		p[0].terminate()
		p[1].stop()
		if p[1] is not threading.current_thread():
			p[1].join()
		p[0].join()


	def getReporting(self, node):
		pass


	def finalize(self):
		pass

