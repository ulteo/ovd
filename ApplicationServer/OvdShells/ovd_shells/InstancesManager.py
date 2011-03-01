# -*- coding: utf-8 -*-

# Copyright (C) 2009,2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
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

import os
import struct
import threading
import time

from HttpFile import HttpFile
from OvdAppChannel import OvdAppChannel

class InstancesManager(threading.Thread):
	def __init__(self, vchannel, folders, drives):
		threading.Thread.__init__(self)
		
		self.vchannel = vchannel
		self.folders = folders
		self.drives = drives
		self.jobs = []
		self.instances = []


	def pushJob(self, job):
		# todo mutex lock
		self.jobs.append(job)
		# todo mutex unlock
	
	
	def popJob(self):
		# todo mutex lock
		if len(self.jobs) == 0:
			return None
		# todo mutex unlock
		return self.jobs.pop()
	
	def getInstanceByToken(self, token):
		for instance in self.instances:
			if instance[1] == token:
				return instance
		
		return None
	
	def run(self):
		self.drives.rebuild()
		self.vchannel.Write(OvdAppChannel.getDrivesMessage(self.drives.getListUID()))
		
		t_init = 0
		while True:
			t0 = time.time()
			job = self.popJob()
			if job is not None:
				print "IM got job",job
				
				order = job[0]
				if order == OvdAppChannel.ORDER_START:
					(token, app) = job[1:3]
					cmd = "startovdapp %d"%(app)
					extra = None
					
					if len(job)>3:
						dir_type = job[3]
						local_path = None
						
						if dir_type == OvdAppChannel.DIR_TYPE_RDP_DRIVE:
							local_path = self.shareName2path(job[4])
						
						elif dir_type == OvdAppChannel.DIR_TYPE_KNOWN_DRIVES:
							local_path = self.drives.getPath(job[4])
							if local_path is None:
								 print "Unknown drive ID %s"%(job[4])
								 continue
						
						elif dir_type == OvdAppChannel.DIR_TYPE_HTTP_URL:
							http = HttpFile(job[4], job[5])
							if not http.recv():
								print "Unable to get file by HTTP"
								continue
							
							local_path = os.path.dirname(http.path)
							extra = http
						
						else:
							local_path = self.folders.getPathFromID(job[4])
						
						if local_path is not None:
							arg = os.path.join(local_path, job[5].replace("/", os.path.sep))
							cmd+=' "%s"'%(arg)
					instance = self.launch(cmd)
					
					# ToDo: sleep 0.5s and check if the process exist
					# with startovdapp return status, get the error
					
					buf = struct.pack("<B", OvdAppChannel.ORDER_STARTED)
					buf+= struct.pack("<I", token)
					self.vchannel.Write(buf)
					
					self.instances.append((instance, token, extra))
				
				elif order == OvdAppChannel.ORDER_STOP:
					token = job[1]
					instance = self.getInstanceByToken(token)
					
					if instance is None:
						print "Not existing token",token
						continue
					
					self.kill(instance[0])
					self.onInstanceExited(instance)
			
			ret = self.wait()
			
			if job is None and ret is False:
				time.sleep(0.1)
			
					
			t1 = time.time()
			t_init+= (t1 - t0)
			if t_init > 5:
				# We send channel init time to time to manage the reconnection
				self.vchannel.Write(OvdAppChannel.getInitPacket())
				
				if self.drives.rebuild():
					self.vchannel.Write(OvdAppChannel.getDrivesMessage(self.drives.getListUID()))
				
				t_init = 0
	
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
		"""
		wait for all self.instances
		"""
		raise NotImplementedError("must be redeclared")
	
	def launch(self, cmd):
		raise NotImplementedError("must be redeclared")
	
	@staticmethod
	def shareName2path(share):
		raise NotImplementedError("must be redeclared")
	
	def onInstanceNotAvailable(self, token):
		buf = struct.pack("<B", OvdAppChannel.ORDER_CANT_START)
		buf+= struct.pack("<I", token)
		self.vchannel.Write(buf)
	
	def onInstanceExited(self, instance):
		self.instances.remove(instance)
		
		buf = struct.pack("<B", OvdAppChannel.ORDER_STOPPED)
		buf+= struct.pack("<I", instance[1])
		self.vchannel.Write(buf)
		
		if instance[2] is not None:
			# Backup file
			instance[2].send()
