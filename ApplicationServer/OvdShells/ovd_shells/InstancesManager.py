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

import threading

class InstanceManager(threading.Thread):
	def __init__(self, vchannel):
		self.vchannel = vchannel
		self.jobs = []
		self.instances = []
		
		
	def pushJob(self, app_id, token):
		# todo mutex lock
		self.jobs.append((token, app_id))
		# todo mutex unlock
	
	
	def run(self):
		# todo mutex lock
		while len(self.job) != 0:
			(token, app) self.job.pop()
			cmd = "notepad" # harcoded ...
			
			instance = self.launch(cmd)
			
			buf = struct.pack(">B", 0x02)
			buf+= struct.pack(">I", token)
			print "send1",token
			self.vchannel.Write(buf)
			
			self.instances.append((instance, token))
	
	def stop(self):
		if self.isAlive():
			self._Thread__stop()
		
		for instance in self.instances:
			self.kill(instance[0])
			
			buf = struct.pack(">B", 0x02)
			buf+= struct.pack(">I", instance[1])
			print "send2",instance[1]
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
	
	
	def onInstanceExited(self, instance):
		self.instances.remove(instance)
		
		buf = struct.pack(">B", 0x02)
		buf+= struct.pack(">I", instance[1])
		print "send3",instance[1]
		self.vchannel.Write(buf)
