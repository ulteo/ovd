# -*- coding: utf-8 -*-

# Copyright (C) 2014 Ulteo SAS
# http://www.ulteo.com
# Author David LECHEVALIER <david@ulteo.com> 2013
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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

from ovd.Logger import Logger


class HTGroup:
	groupFile = None
	entries = {}
	
	def __init__(self, groupFile):
		self.groupFile = groupFile
		
	
	def load(self):
		if not os.path.exists(self.groupFile):
			return True
		
		try:
			f = open(self.groupFile, 'r')
			for line in f.readlines():
				compo = line.split(":")
				if len(compo) != 2:
					Logger.warn("FS: HTTP group file %s is not properly formatted"%(self.groupFile))
					return False
				
				group = compo[0].strip()
				users = compo[1].split()
				self.entries[group] = users
			
			f.close()
		except Exception:
			Logger.exception("FS: Failed to load %s"%self.groupFile)
			return False
		
		return True
	
	
	def save(self):
		try:
			f = open(self.groupFile, 'w+')
			for entry in self.entries:
				if len(self.entries[entry]) == 0:
					continue
				
				f.write("%s:"%(entry))
				for user in self.entries[entry]:
					f.write(" %s"%(user))
				f.write("\n")
			
			f.close()
		except Exception:
			Logger.exception("FS: Failed to save group in %s"%self.groupFile)
			return False
		
		return True
	
	
	def add(self, user, group):
		if not self.load():
			return False
		
		if group not in self.entries:
			self.entries[group] = []
		
		if user not in self.entries[group]:
			self.entries[group].append(user)
		
		if not self.save():
			return False
		
		return True
	
	
	def delete(self, user, group):
		if not self.load():
			return False
		
		if user not in self.entries[group]:
			return True
		
		self.entries[group].remove(user)
		
		if not self.save():
			return False
	
	
	def purge(self):
		f = open(self.groupFile, 'w')
		f.close()
