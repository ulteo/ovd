# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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

import multiprocessing
import os
import sys
import servicemanager
import win32event
import win32service
import win32serviceutil

from ovd.Communication.HttpServer import HttpServer as Communication
from ovd import Config as ConfigModule
from ovd.Config import Config
from ovd.Logger import Logger
from ovd.SlaveServer import SlaveServer
from ovd.Platform.System import System

class OVD(win32serviceutil.ServiceFramework):
	_svc_name_ = "OVD"
	_svc_display_name_ = "Ulteo OVD Slave Server"
	_svc_description_ = "Ulteo OVD Slave Server"
	
	def __init__(self, args):
		win32serviceutil.ServiceFramework.__init__(self, args)
		
		# Give the multiprocessing module a python interpreter to run
		if sys.argv[0].endswith("exe"):
			basedir = os.path.dirname(sys.argv[0])
			multiprocessing.set_executable(os.path.join(basedir, "ulteo-ovd-slaveserver.exe"))
		
		# Init the logger instance
		Win32Logger.initialize("OVD", Config.log_level, None)
		ConfigModule.report_error = WinReport_error
		
		self.hWaitStop = win32event.CreateEvent(None, 0, 0, None)
	
	
	def SvcDoRun(self):
		self.ReportServiceStatus(win32service.SERVICE_START_PENDING)
		
		config_file = os.path.join(System.get_default_config_dir(), "slaveserver.conf")
		if not Config.read(config_file):
			Logger.error("invalid configuration file '%s'"%(config_file))
			self.ReportServiceStatus(win32service.SERVICE_STOPPED)
			return
		
		if not Config.is_valid():
			Logger.error("invalid config")
			self.ReportServiceStatus(win32service.SERVICE_STOPPED)
			return
		
		Win32Logger.initialize("OVD", Config.log_level, Config.log_file)
		
		
		slave = SlaveServer(Communication)
		
		if not slave.load_roles():
			self.ReportServiceStatus(win32service.SERVICE_STOPPED)
			return
		
		if not slave.init():
			Logger.error("Unable to initialize SlaveServer")
			slave.stop()
			return
		
		self.ReportServiceStatus(win32service.SERVICE_RUNNING)
		
		inited = False
		rc = win32event.WAIT_TIMEOUT
		while rc == win32event.WAIT_TIMEOUT:
			if not inited:
				ret = slave.push_production()
				if ret:
					inited = True
					Logger.info("SlaveServer started")
				else:
					Logger.warn("Session Manager not connected. Sleeping for a while ...")
			
			if inited:
				slave.loop_procedure()
			
			rc = win32event.WaitForSingleObject(self.hWaitStop, 30 * 1000)
		
		if not slave.stopped:
			slave.stop()
		
		Logger.info("SlaveServer stopped")
		self.ReportServiceStatus(win32service.SERVICE_STOPPED)
	
	
	def SvcStop(self):
		Logger.info("Stopping SlaveServer")
		self.ReportServiceStatus(win32service.SERVICE_STOP_PENDING)
		
		win32event.SetEvent(self.hWaitStop)
	
	
	def SvcShutdown(self):
		# Reinit Logger because the Windows service manager logging system is already down
		Logger.initialize("OVD", Config.log_level, Config.log_file, False)
		Logger.info("Stopping SlaveServer (shutdown)")
		
		win32event.SetEvent(self.hWaitStop)



class Win32Logger(Logger):
	def __init__(self, name, loglevel, file = None):
		Logger.__init__(self, name, loglevel, file)
	
	
	def log_info(self, message):
		Logger.log_info(self, message)
		servicemanager.LogInfoMsg(message)

	
	def log_warn(self, message):
		Logger.log_warn(self, message)
		servicemanager.LogWarningMsg(message)
	
	
	def log_error(self, message):
		Logger.log_error(self, message)
		servicemanager.LogErrorMsg(message)
	
	
	# Static methods
	@staticmethod 
	def initialize(name, loglevel, file=None, threaded=False):
		instance = Win32Logger(name, loglevel, file)
		Logger._instance = instance


def WinReport_error(message):
	Logger.error(message)


if __name__=='__main__':
	# when a service is produced by py2exe, this function is never called
	win32serviceutil.HandleCommandLine(OVD)
