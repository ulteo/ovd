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

import hashlib
import os
import random
import threading
import time

class Drives:
	TAG_FILE = ".ulteo.id"
	
	def __init__(self):
		self.drives = {}
		self.uids = {}
		self.mutex = threading.Lock()
	
	
	def rebuild(self):
		hasChanges = False
		
		disks = self.getDrivesList()
		drives = {}
		uids = {}
		
		for path in self.drives.keys():
			if path not in disks:
				hasChanges = True
			else:
				tag = self.getTag(path)
				if tag is None:
					hasChanges = True
					tag = self.generateTag()
					self.putTag(path, tag)
				
				drives[path] = tag
				uids[tag] = path
		
		for d in disks:
			if d in drives.keys():
				continue
			
			hasChanges = True
			tag = self.getTag(d)
			if tag is None:
				tag = self.generateTag()
				self.putTag(d, tag)
			
			drives[d] = tag
			uids[tag] = d
		
		self.mutex.acquire()
		self.drives = drives
		self.uids = uids
		self.mutex.release()
		
		return hasChanges
	
	
	def getListUID(self):
		self.mutex.acquire()
		keys = self.uids.keys()
		self.mutex.release()
		return keys
	
	
	def getPath(self, uid):
		self.mutex.acquire()
		try:
			path = self.uids[uid]
		except KeyError, err:
			path = None
		self.mutex.release()
		
		return path
	
	
	@staticmethod
	def getDrivesList():
		raise NotImplementedError("must be redeclared")
	
	
	@staticmethod
	def generateTag():
		return hashlib.md5("%f%f"%(random.random(), time.time())).hexdigest()[:16]
	
	
	@staticmethod
	def putTag(path, tag):
		filename = os.path.join(path, Drives.TAG_FILE)
		
		try:
			f = file(filename, "w")
		except IOError, err:
			return None
		
		f.write(tag)
		f.close()
		
		return tag
	
	@staticmethod
	def getTag(path):
		filename = os.path.join(path, Drives.TAG_FILE)
		
		try:
			f = file(filename, "r")
		except IOError, err:
			return None
		
		content = f.read()
		f.close()
		
		if len(content)<16:
			return None
		
		return content[:16]
