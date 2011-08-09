# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Gaëtan COLLET <gaetan@ulteo.com> 2011
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

from xml.dom.minidom import Document
import libvirt
import os

from Master import Master
from Instance import Instance

class Pool():
	
	def __init__(self, name, virt_co):
		
		self.name = name
		self.virt_co = virt_co
		self.masters = {}
		self.instances = {}
	
		try:
			self.pool = self.virt_co.storagePoolLookupByName(self.name)
		except libvirt.libvirtError, err:
			self.pool = None
	
	
	"""
	This function re-create Master and Instance objects
	"""
	def reload(self):
	
		list_volumes = self.pool.listVolumes()
		
		for item in list_volumes:
			
			name = Master.extract_name(item)
	
			if name is not None:
				self.masters[name] = Master(name, self, self.virt_co)
		
		for item in list_volumes:
			
			name = Instance.extract_name(item)
	
			if name is None:
				continue
			
			master = self.masters[name[0]]
		
			self.instances[name[1]] = Instance(master, name[1], self, self.virt_co)
	
	
	"""
	This function return the name of the last instance created from a master
	"""
	def get_last_instance(self, name_master):
		
		master = self.masters[name_master]
		max_id = "0000"
		for instance in self.instances.values():
			if instance.master != master:
				continue
			if instance.name < max_id:
				continue
			      
			max_id = instance.name
			
		return max_id
	
	
	"""
	- Build the xml definition of a pool
	- Define it
	- Start it
	"""
	def create(self, path):
		
		doc = Document()
		
		rootNode = doc.createElement("pool")
		rootNode.setAttribute("type", "dir")
		doc.appendChild(rootNode)
	
		node_name = doc.createElement("name")
		node_name.appendChild(doc.createTextNode(self.name))
	
		node_source = doc.createElement("source")
		node_target = doc.createElement("target")
		node_path = doc.createElement("path")
	
		node_target.appendChild(node_path)
	
		node_path.appendChild(doc.createTextNode(path))
		
		
		rootNode.appendChild(node_name)
		rootNode.appendChild(node_source)
		rootNode.appendChild(node_target)
		
		if not os.path.isdir(path):
		    os.makedirs(path)
	
		self.virt_co.storagePoolDefineXML(rootNode.toxml(),0)
	
		self.pool = self.virt_co.storagePoolLookupByName(self.name)
	
		self.pool.create(0)
	
	
	def exist(self):
		return self.pool is not None
