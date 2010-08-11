# -*- coding: utf-8 -*-

# Copyright (C) 2008-2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
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
import time
import threading
from xml.dom.minidom import Document

from ovd.Config import Config
from ovd.Logger import Logger

from ovd.Platform import Platform

from Dialog import Dialog

class SlaveServer:
	def __init__(self, CommunicationClass):
		Logger.debug("SlaveServer construct")
		
		self.stopped = False
		
		self.roles = []
		self.threads = []
		self.monitoring = None
		
		self.ulteo_system = False
		if os.path.exists("/etc/debian_chroot"):
			f = file("/etc/debian_chroot", 'r')
			buf = f.read()
			f.close()
			
			if "OVD" in buf:
				self.ulteo_system = True
		
		
		self.dialog = Dialog(self)
		
		for role in Config.roles:
			try:
				Role = __import__("ovd.Role.%s.Role"%(role), {}, {}, "Role")
			
			except ImportError:
				Logger.error("Unsupported role '%s'"%(role))
				sys.exit(2)
			
			self.roles.append(Role.Role(self))
		
		dialogInstances = []
		dialogInstances.append(self.dialog)
		for role in self.roles:
			dialogInstances.append(role.dialog)
		
		self.communication = CommunicationClass(dialogInstances)
	
	
	def init(self):
		Logger.debug("SlaveServer init")
		
		# Initialisation
		try:
			if not self.dialog.initialize():
				raise Exception()
		except Exception, e:
			Logger.error("SlaveServer: unable to initialize dialog class %s"%(str(e)))
			return False
		
		self.updateMonitoring()
		
		if not self.communication.initialize():
			Logger.error("SlaveServer: unable to initialize communication class")
			return False
		
		self.threads.append(threading.Thread(target=self.communication.run))
		
		
		for role in self.roles:
			try:
				if not role.init():
					raise Exception()
			except Exception, e:
				Logger.error("SlaveServer: unable to initialize role '%s' %s"%(role.getName(), str(e)))
				return False
				
			self.threads.append(threading.Thread(target=role.run))
		
		# Start 
		for thread in self.threads:
			thread.start()
		
		
		if not self.dialog.send_server_status():
			Logger.warn("SlaveServer::loop unable to send status ready")
			return False
		
		return True
	
	
	def loop_procedure(self):
		for thread in self.threads:
			if not thread.isAlive():
				Logger.warn("One thread stop")
				Logger.debug("ToDo: be more specific. Make difference between Roles and main threads")
				return False
			
			self.updateMonitoring()
	
	
	def stop(self, Signum=None, Frame=None):
		Logger.info("SlaveServer stop")
		self.stopped = True
	
		for thread in self.threads:
			if thread.isAlive():
				thread._Thread__stop()
		
		Logger.info("Waiting for thread stop")
		time.sleep(2)
		for thread in self.threads:
			if thread.isAlive():
				thread._Thread__delete()
		
		for role in self.roles:
			if role.has_run:
				Logger.info("Stopping role %s"%(role.getName()))
				role.stop()
				
		self.communication.stop()
		self.dialog.stop()
	
		return 0
	
	
	def getMonitoring(self):
		i = 0
		while self.monitoring is None:
			if i > 10:
				break
			i+= 1
			time.sleep(0.2)
			
		return self.monitoring
	
	
	def updateMonitoring(self):
		cpu_load = Platform.System.getCPULoad()
		ram_used = Platform.System.getRAMUsed()

		doc = Document()
		monitoring = doc.createElement('monitoring')
		
		cpu = doc.createElement('cpu')
		cpu.setAttribute('load', str(cpu_load))
		
		monitoring.appendChild(cpu)
		
		ram = doc.createElement('ram')
		ram.setAttribute('used', str(ram_used))
		monitoring.appendChild(ram)
		
		doc.appendChild(monitoring)
		
		self.monitoring = doc
