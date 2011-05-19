# -*- coding: utf-8 -*-

# Copyright (C) 2008-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2008-2011
# Author David LECHEVALIER <david@ulteo.com> 2011
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

import os
import time
import sys
from xml.dom.minidom import Document

from ovd.Config import Config
from ovd.Exceptions import InterruptedException
from ovd.Logger import Logger
from ovd.Platform.System import System

from Dialog import Dialog
from SMRequestManager import SMRequestManager


class SlaveServer:
	def __init__(self, CommunicationClass):
		Logger.debug("SlaveServer construct")
		
		self.stopped = False
		
		self.role_dialogs = []
		self.monitoring = None
		self.time_last_send_monitoring = 0
		
		self.ulteo_system = False
		if os.path.exists("/etc/debian_chroot"):
			f = file("/etc/debian_chroot", 'r')
			buf = f.read()
			f.close()
			
			if "OVD" in buf:
				self.ulteo_system = True
		
		
		self.dialog = Dialog(self)
		self.smRequestManager = SMRequestManager()
		
		self.communication = CommunicationClass()
		self.communication.dialogInterfaces.append(self.dialog)
	
	
	def load_roles(self):
		for role in Config.roles:
			try:
				Role       = __import__("ovd.Role.%s.Role"  %(role), {}, {}, "Role")
				RoleDialog = __import__("ovd.Role.%s.Dialog"%(role), {}, {}, "Dialog")
				RoleConfig = __import__("ovd.Role.%s.Config"%(role), {}, {}, "Config")
			
			except ImportError:
				Logger.error("Unsupported role '%s'"%(role))
				import traceback
				Logger.debug(traceback.format_exc())
				return False
			
			if not RoleConfig.Config.init(Config.get_role_dict(role)):
				Logger.error("Unable to init configuration for role '%s'"%(role))
				return False
			
			RoleConfig.Config.general = Config
			
			role_instance = Role.Role(self)
			dialog_instance = RoleDialog.Dialog(role_instance)
			
			self.communication.dialogInterfaces.append(dialog_instance)
			self.role_dialogs.append((role_instance, dialog_instance))
		
		return True
	
	
	def init(self):
		Logger.debug("SlaveServer init")
		
		if not self.communication.initialize():
			return False
		
		self.communication.thread.start()
		
		for (role, dialog) in self.role_dialogs:
			try:
				if not role.init():
					raise Exception()
			except InterruptedException:
				return False
			except Exception, e:
				Logger.error("SlaveServer: unable to initialize role '%s' %s"%(role.getName(), str(e)))
				return False
			
			role.thread.start()
		
		# Check each thread has started correctly (communication + roles)
		t0 = time.time()
		while self.communication.getStatus() is not self.communication.STATUS_RUNNING:
			t1 = time.time()
			
			if (t1-t0 > 20) or (not self.communication.thread.isAlive()) or self.communication.getStatus() is self.communication.STATUS_ERROR:
				Logger.warn("SlaveServer::init communication thread error")
				return False
			
			Logger.info("Waiting for communication status running")
			time.sleep(1)
		for (role, dialog) in self.role_dialogs:
			while role.getStatus() is not role.STATUS_RUNNING:
				t1 = time.time()
				
				if (t1-t0 > 20) or (not role.thread.isAlive()) or role.getStatus() is role.STATUS_ERROR:
					Logger.warn("SlaveServer::init role %s error"%(role.getName()))
					return False
				
				Logger.info("Waiting for role %s status running"%(role.getName()))
				time.sleep(1)
		
		self.updateMonitoring()
		return True
	
	
	def push_production(self):
		# Initialisation
		try:
			if not self.smRequestManager.initialize():
				raise Exception()
		except Exception, e:
			Logger.debug("smRequestManager initialize returned %s"%(str(e)))
			
			return False
		
		if not self.smRequestManager.switch_status(self.smRequestManager.STATUS_READY):
			Logger.warn("SlaveServer::loop unable to send status ready")
			return False
		
		for (role, dialog) in self.role_dialogs:
			role.switch_to_production()
		
		return True
	
	
	def loop_procedure(self):
		for role_dialog in list(self.role_dialogs):
			role = role_dialog[0]
			if not role.thread.isAlive():
				Logger.warn("Thread '%s' stopped" % role.thread.getName())
				self.role_dialogs.remove(role_dialog)
		
		self.updateMonitoring()
		
		t1 = time.time()
		if t1-self.time_last_send_monitoring > 30:
			self.time_last_send_monitoring = t1
			
			doc = self.getMonitoring()
			self.smRequestManager.send_server_monitoring(doc)
			
			self.time_last_send_monitoring = time.time()
	
	
	def stop(self, Signum=None, Frame=None):
		Logger.info("SlaveServer stop")
		self.stopped = True
		self.smRequestManager.switch_status(self.smRequestManager.STATUS_PENDING)
		
		for (role, dialog) in self.role_dialogs:
			if role.getStatus() is role.STATUS_RUNNING:
				Logger.debug("Stopping role %s" % role.getName())
				role.stop()
		
		for (role, dialog) in self.role_dialogs:
			if role.thread.isAlive():
				Logger.debug("Waiting %s will stop" % role.getName())
				role.thread.join(10)
				if role.thread.isAlive():
					Logger.error("Role %s was stopped by using low force" % role.getName())
					role.thread._Thread__stop()
					role.thread.join(5)
					if role.thread.isAlive():
						Logger.error("Role %s was stopped by using force" % role.getName())
						role.thread._Thread__delete()
			role.finalize()
			
			Logger.info("Role %s stopped" % role.getName())
		
		self.communication.stop()
		if self.communication.thread.isAlive():
			self.communication.thread.join()
		
		self.smRequestManager.switch_status(self.smRequestManager.STATUS_DOWN)
	
	
	def getMonitoring(self):
		rootNode = self.monitoring.cloneNode(True)
		rootNode.setAttribute("name", self.smRequestManager.name)
		
		doc = Document()
		for (role, dialog) in self.role_dialogs:
			node = doc.createElement("role")
			
			role.getReporting(node)
			node.setAttribute("name", role.getName())
			rootNode.appendChild(node)
		  
		doc.appendChild(rootNode)
		return doc
	
	
	def updateMonitoring(self):
		cpu_load = System.getCPULoad()
		ram_used = System.getRAMUsed()
		
		doc = Document()
		monitoring = doc.createElement('server')
		
		cpu = doc.createElement('cpu')
		cpu.setAttribute('load', str(cpu_load))
		
		monitoring.appendChild(cpu)
		
		ram = doc.createElement('ram')
		ram.setAttribute('used', str(ram_used))
		monitoring.appendChild(ram)
		
		self.monitoring = monitoring
