#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import os
import sys

from ovd.Communication.HttpServer import HttpServer as Communication
from ovd.Config import Config
from ovd.Logger import Logger
from ovd.SlaveServer import SlaveServer
from ovd.Platform import Platform
from Win32Logger import Win32Logger


class OVD(win32serviceutil.ServiceFramework, SlaveServer):
	_svc_name_ = "OVD"
	_svc_display_name_ = "Ulteo OVD slave server"
	_svc_description_ = "Ulteo OVD slave server"
	
	def __init__(self,args):
		win32serviceutil.ServiceFramework.__init__(self, args)
		
		# Init the logger instance
		Win32Logger.initialize("OVD", Logger.INFO | Logger.WARN | Logger.ERROR, None)
		
		config_file = os.path.join(Platform.getInstance().get_default_config_dir(), "ovd-slaveserver.conf")
		if not Config.read(config_file):
			Logger.error("invalid configuration file '%s'"%(config_file))
			sys.exit(1)
	
		if not Config.is_valid():
			Logger.error("invalid config")
			sys.exit(1)
		
		
		self.log_flags = 0
		for item in Config.log_level:
			if item == "info":
				self.log_flags|= Logger.INFO
			elif item == "warn":
				self.log_flags|= Logger.WARN
			elif item == "error":
				self.log_flags|= Logger.ERROR
			elif item == "debug":
				self.log_flags|= Logger.DEBUG
		Win32Logger.initialize("OVD", self.log_flags, Config.log_file)
		
		
		SlaveServer.__init__(self, Communication)
		self.hWaitStop = win32event.CreateEvent(None, 0, 0, None)
	
	
	def SvcDoRun(self):
		self.ReportServiceStatus(win32service.SERVICE_START_PENDING)
		
		if not SlaveServer.init(self):
			Win32Logger.initialize("OVD", log_flags, Config.log_file)
		
		
		server.loop()
		
		self.ReportServiceStatus(win32service.SERVICE_RUNNING)
		timeout = 60 * 1000
		rc = win32event.WaitForSingleObject(self.hWaitStop, timeout)
		while rc == win32event.WAIT_TIMEOUT:
			for thread in self.threads:
				if not thread.isAlive():
					Logger.warn("One thread stop")
					return False
				
			self.updateMonitoring()
			
			rc = win32event.WaitForSingleObject(self.hWaitStop, timeout)
		
		
		SlaveServer.stop(self)
		self.ReportServiceStatus(win32service.SERVICE_STOPPED)
	
	def SvcStop(self):
		Logger.info("Stopping agent")
		self.ReportServiceStatus(win32service.SERVICE_STOP_PENDING)
		
		win32event.SetEvent(self.hWaitStop)
	
	def SvcShutdown(self):
		# Reinit Logger because the Windows service manager logging system is already down
		Logger.initialize("OVD", self.log_flags, Config.log_file, False)
		Logger.info("Stopping agent (shutdown)")

		win32event.SetEvent(self.hWaitStop)



if __name__=='__main__':
	win32serviceutil.HandleCommandLine(OVD)
