# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Gaëtan COLLET <gaetan@ulteo.com> 2011
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import libvirt
import re

class Master:
	
	proto_name = "master"
	proto_sep  = "_"
	proto_id   = "[0-9a-z]{4}"
	proto_ext  = "qcow2"
	
	def __init__(self, name, pool, virt_co):
		
		self.name = name
		self.pool = pool
		self.virt_co = virt_co
		self.file_name = self.get_file_name()
	
	
	def exist(self):
		
		try:
			self.pool.pool.storageVolLookupByName(self.file_name)
			
		except libvirt.libvirtError, err:
			return False
		
		return True
	
	
	
	"""
	Return the file name : master_name + .qcow2
	"""
	def get_file_name(self):
		return self.get_name()+"."+self.proto_ext
	
	
	"""
	Return name of the master
	"""
	def get_name(self):
		return "%s%s%s" %(self.proto_name, self.proto_sep, self.name)
	
	
	"""
	This function return the id of the master
	"""
	@staticmethod
	def extract_name(name):
		
		r = re.match("^%s%s(%s)\.%s$"%(Master.proto_name, Master.proto_sep, Master.proto_id, Master.proto_ext), name)
		if r is None:
			return None
			
		return r.groups()[0]
	
	
	def __repr__(self):
		return self.file_name	
	
	
	"""
	Return allocation of the master
	"""
	def get_allocation(self):
		
		vol = self.pool.pool.storageVolLookupByName(self.file_name)
		infos = vol.info()
		
		return infos[2]
	
	
	"""
	Return capacity of the master
	"""
	def get_capacity(self):
		
		vol = self.pool.pool.storageVolLookupByName(self.file_name)
		infos = vol.info()
		
		return infos[1]
