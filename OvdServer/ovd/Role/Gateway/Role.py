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

from multiprocessing import Pipe
import os
import socket
from SocketServer import TCPServer
from sys import version_info as version
import threading

from Config import Config
from ControlProcess import ControlChildProcess
from ConnectionPoolProcess import ConnectionPoolProcess
from Dialog import Dialog
from TCPHandler import GatewayTCPHandler
from ovd.Logger import Logger
from ovd.Role.Role import Role as AbstractRole

from OpenSSL import SSL


class Role(AbstractRole):
	
	@staticmethod
	def getName():
		return "Gateway"
	
	
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		self.dialog = Dialog(self)
		self.has_run = False
		
		self.server = None
		self.ssl_ctx = None
		self.processes = {}
		
		self.kill_mutex = threading.Lock()
	
	
	def init(self):
		Logger.info("Gateway init")

		if version[0] != 2 or version[1] < 6 or (version[1] == 7 and version[2] in [1, 2]):
			Logger.error("Gateway:: incompatibility with current Python machine '%d.%d.%d'" % version[:3])
			return False
		
		fpem = os.path.join(Config.general.conf_dir, "gateway.pem")
		if os.path.exists(fpem):
			self.ssl_ctx = SSL.Context(SSL.SSLv23_METHOD)
			self.ssl_ctx.use_privatekey_file(fpem)
			self.ssl_ctx.use_certificate_file(fpem)
			self.ssl_ctx.load_verify_locations(fpem)
		else:
			Logger.error("Gateway role need a certificate (%s)" % fpem)
			return False
		
		addr = (Config.address, Config.port)
		try:
			GatewayTCPHandler.role = self
			self.server = TCPServer(addr, GatewayTCPHandler, bind_and_activate=False)
			self.server.allow_reuse_address = Config.general.server_allow_reuse_address
			
			self.server.server_bind()
			self.server.server_activate()
		except socket.error, e:
			Logger.error("Gateway:: socket init: %s" % e)
			return False
		
		Logger.info('Gateway:: running on (%s, %d)' % addr)
		return True
	
	
	def stop(self):
		self.server.shutdown()
		for pid in list(self.processes):
			self.kill_process(pid)
	
	
	def run(self):
		self.has_run = True
		self.status = Role.STATUS_RUNNING
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
