# -*- coding: utf-8 -*-

# Copyright (C) 2009-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
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

from HttpFile import HttpFile


class InstancesManager:
	DIR_TYPE_NATIVE        = 0
	DIR_TYPE_SHARED_FOLDER = 1
	DIR_TYPE_RDP_DRIVE     = 2
	DIR_TYPE_KNOWN_DRIVES  = 3
	DIR_TYPE_HTTP_URL      = 4
	
	
	def __init__(self):
		self.known_drives = None
		self.shared_folders = None
		
		self.instances = []
	
	
	def setDrivesDB(self, known_drives):
		self.known_drives = known_drives
	
	
	def setSharedFolderDB(self, shared_folders):
		self.shared_folders = shared_folders
	
	
	def getInstanceByToken(self, token):
		for instance in self.instances:
			if instance[1] == token:
				return instance
		
		return None
	
	
	def start_app_empty(self, token, app_id):
		cmd = "startovdapp %d"%(app_id)
		extra = None
		
		instance = self.launch(cmd)
		if token is None:
			token = instance
		
		self.instances.append((instance, token, extra))
		
		return True
	
	
	def start_app_with_arg(self, token, app_id, dir_type, f_path, f_share):
		extra = None
		
		if dir_type == self.DIR_TYPE_NATIVE:
			arg = f_path
		
		elif dir_type == self.DIR_TYPE_SHARED_FOLDER:
			if self.shared_folders is None:
				print "No Shared folders registered"
				return False
			
			local_path = self.shared_folders.getPathFromID(f_share)
			if local_path is None:
				print "Unknown shared folder '%s'"%(f_share)
				return False
			
			arg = os.path.join(local_path, f_path.replace("/", os.path.sep))
		
		elif dir_type == self.DIR_TYPE_RDP_DRIVE:
			local_path = self.shareName2path(f_share)
			arg = os.path.join(local_path, f_path.replace("/", os.path.sep))
		
		elif dir_type == self.DIR_TYPE_KNOWN_DRIVES:
			if self.known_drives is None:
				print "No Known drives registered"
				return False
			
			local_path = self.known_drives.getPath(f_share)
			if local_path is None:
				  print "Unknown drive ID %s"%(f_share)
				  return False
			
			arg = os.path.join(local_path, f_path.replace("/", os.path.sep))
		
		elif dir_type == self.DIR_TYPE_HTTP_URL:
			http = HttpFile(f_share, f_path)
			if not http.recv():
				print "Unable to get file by HTTP"
				return False
			
			local_path = os.path.dirname(http.path)
			arg = os.path.join(local_path, f_path.replace("/", os.path.sep))
			extra = http
		
		else:
			print "Unknown type"
			return False
		
		
		cmd = 'startovdapp %d "%s"'%(app_id, arg)
		
		instance = self.launch(cmd)
		if token is None:
			token = instance
		
		# ToDo: sleep 0.5s and check if the process exist
		# with startovdapp return status, get the error
		
		self.instances.append((instance, token, extra))
		return True
	
	
	def stop_app(self, token):
		instance = self.getInstanceByToken(token)
		if instance is None:
			print "Not existing token",token
			return False
		
		self.kill(instance[0])
		
		self.instances.remove(instance)
		
		return True
	
	
	def has_running_instances(self):
		return (len(self.instances) != 0)
	
	
	def kill_all_apps(self):
		for instance in self.instances:
			self.kill(instance[0])
		
		return True
	
	
	def get_exited_instances(self):
		instances = self.wait()
		
		if len(instances) == 0:
			return []
		
		tokens = []
		
		for instance in instances:
			self.instances.remove(instance)
			tokens.append(instance[1])
			
			if instance[2] is not None:
				# Backup file
				instance[2].send()
		
		return tokens
	
	
	def wait(self):
		"""
		wait for all self.instances
		"""
		raise NotImplementedError("must be redeclared")
	
	def launch(self, cmd):
		raise NotImplementedError("must be redeclared")
	
	def kill(self, pid):
		raise NotImplementedError("must be redeclared")
	
	@staticmethod
	def shareName2path(share):
		raise NotImplementedError("must be redeclared")
