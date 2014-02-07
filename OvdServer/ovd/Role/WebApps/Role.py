# -*- coding: utf-8 -*-

# Copyright (C) 2012-2014 Ulteo SAS
# http://www.ulteo.com
# Author Miguel Angel Garcia <mgarcia@pressenter.com.ar> 2012
# Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
# Author David LECHEVALIER <david@ulteo.com> 2014
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

from multiprocessing import Pipe
import os
import socket
from SocketServer import TCPServer
from sys import version_info as version
import threading

from Config import Config, setup_apps
from ControlProcess import ControlChildProcess
from ConnectionPoolProcess import ConnectionPoolProcess
from TCPHandler import WebAppsTCPHandler, WebAppsTCPServer
from ovd.Logger import Logger
from ovd.Role.Role import Role as AbstractRole
from SessionsRepository import SessionsRepository
from ApplicationsRepository import ApplicationsRepository

from OpenSSL import SSL


class Role(AbstractRole):
	
	@staticmethod
	def getName():
		return "WebApps"
	
	
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		
		self.server = None
		self.ssl_ctx = None
		self.processes = {}
		
		self.kill_mutex = threading.Lock()
		self.sessions_repo = SessionsRepository.initialize()
		self.apps_repo = ApplicationsRepository.initialize()
	
	
	def init(self):
		Logger.info("[WebApps] WebApps role init")

		if version[0] != 2 or version[1] < 6 or (version[1] == 7 and version[2] in [1, 2]):
			Logger.error("[WebApps] incompatibility with current Python machine '%d.%d.%d'" % version[:3])
			return False
		
		fpem = os.path.join(Config.general.conf_dir, "WebApps.pem")
		if os.path.exists(fpem):
			self.ssl_ctx = SSL.Context(SSL.SSLv23_METHOD)
			self.ssl_ctx.use_privatekey_file(fpem)
			self.ssl_ctx.use_certificate_file(fpem)
			self.ssl_ctx.load_verify_locations(fpem)
		else:
			Logger.error("[WebApps] WebApps role need a certificate (%s)" % fpem)
			return False
		
		addr = (Config.address, Config.port)
		try:
			WebAppsTCPHandler.role = self
			self.server = WebAppsTCPServer(addr, WebAppsTCPHandler, bind_and_activate=False)
			self.server.allow_reuse_address = Config.general.server_allow_reuse_address
			
			self.server.server_bind()
			self.server.server_activate()
		except socket.error:
			Logger.exception("[WebApps] socket init")
			return False
		
		Logger.info('[WebApps] running on (%s, %d)' % addr)
		return True
	
	
	def order_stop(self):
		AbstractRole.order_stop(self)
		self.force_stop()
	
	
	def force_stop(self):
		if self.server is not None:
			self.server.shutdown()
		self.sessions_repo.stop()
		self.apps_repo.stop()
		for pid in list(self.processes):
			self.kill_process(pid)
	
	
	def run(self):
		self.status = Role.STATUS_RUNNING
		self.sessions_repo.start()
		self.apps_repo.start()
		setup_apps()
		self.server.serve_forever()
		self.status = Role.STATUS_STOP
	
	
	def create_process(self):
		(p00, p10) = Pipe() # for the father
		(p01, p11) = Pipe() # for the child
		self.pipes = (p10, p11)
		child_pipes = (p01, p00)
		
		proc = ConnectionPoolProcess(child_pipes, self.pipes, self.ssl_ctx)
		proc.start()
		child_pipes[0].close()
		child_pipes[1].close()
		
		ctrl = ControlChildProcess(self)
		ctrl.start()
		
		self.processes[proc.pid] = [(proc, ctrl), 0]
		return proc.pid
	
	
	def kill_process(self, pid):
		self.kill_mutex.acquire()
		
		(proc, ctrl) = self.processes.pop(pid)[0]
		proc.terminate()
		ctrl.terminate()
		
		if ctrl is not threading.current_thread():
			ctrl.join()
		proc.join()
		
		self.kill_mutex.release()
	
	
	def getReporting(self, node):
		pass
	
	
	def finalize(self):
		pass
