# -*- coding: utf-8 -*-

# Copyright (C) 2009,2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
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

import struct
import threading
import time

from Application import Application
import OvdAppChannel

class InstancesManager(threading.Thread):
	def __init__(self, vchannel):
		threading.Thread.__init__(self)
		
		self.vchannel = vchannel
		self.jobs = []
		self.instances = []


	def pushJob(self, app_id, token):
		# todo mutex lock
		self.jobs.append((token, app_id))
		# todo mutex unlock
	
	
	def popJob(self):
		# todo mutex lock
		if len(self.jobs) == 0:
			return None
		# todo mutex unlock
		return self.jobs.pop()
	
	
	def run(self):
		while True:
			job = self.popJob()
			if job is not None:
				print "IM got job",job
				(token, app) = job
				application = Application(app, [])
				if not application.isAvailable():
					print "Application %d is not available"%(app)
					self.onInstanceNotAvailable(token)
					return
				
				cmd = application.getFinalCommand()
				if cmd is None:
					print "No available command"
					self.onInstanceNotAvailable(token)
					return
				
				instance = self.launch(cmd)
				
				buf = struct.pack("<B", OvdAppChannel.ORDER_STARTED)
				buf+= struct.pack("<I", token)
				print "send1",token
				self.vchannel.Write(buf)
				
				self.instances.append((instance, token))
			
			ret = self.wait()
			
			if job is None and ret is False:
				time.sleep(0.1)
			
	
	def stop(self):
		if self.isAlive():
			self._Thread__stop()
		
		for instance in self.instances:
			self.kill(instance[0])
			
			buf = struct.pack("<B", OvdAppChannel.ORDER_STOPPED)
			buf+= struct.pack("<I", instance[1])
			self.vchannel.Write(buf)
		
		self.instances = []
	
	def wait(self):
		"""must be redeclared
		
		wait for all self.instances
		
		"""
		pass
	
	def launch(self, cmd):
		"""must be redeclared"""
		pass
	
	def onInstanceNotAvailable(self, token):
		buf = struct.pack("<B", OvdAppChannel.ORDER_CANT_START)
		buf+= struct.pack("<I", token)
		self.vchannel.Write(buf)
	
	def onInstanceExited(self, instance):
		self.instances.remove(instance)
		
		buf = struct.pack("<B", OvdAppChannel.ORDER_STOPPED)
		buf+= struct.pack("<I", instance[1])
		self.vchannel.Write(buf)
