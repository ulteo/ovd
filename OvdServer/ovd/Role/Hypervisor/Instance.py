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

from xml.dom.minidom import Document
import os
import re

from Config import Config
from Master import Master

class Instance(Master):
	proto_name = "instance"
	
	def __init__(self, master, name, pool, virt_co):
		
		self.master = master
		
		Master.__init__(self, name, pool, virt_co)
		
		
	"""
	Return name of the Instance
	"""
	def get_name(self):
		
		return "%s%s%s%s%s" %(self.master.get_name(), self.proto_sep, self.proto_name, self.proto_sep, self.name)
		
		
	"""
	Rerturn id of the instance
	"""
	@staticmethod
	def extract_name(name):
		
		r = re.match("^%s%s(%s)%s%s%s(%s)\.%s$"%(Master.proto_name, Master.proto_sep, Master.proto_id, Instance.proto_sep, Instance.proto_name, Instance.proto_sep, Instance.proto_id,  Instance.proto_ext), name)
		if r is None:
			return None
		
		return (r.groups()[0], r.groups()[1])
		
		
	"""
	- Build the XML definition of an instance
	- Create the instance
	"""
	def create(self):
				
		doc = Document()
		
		rootNode = doc.createElement("volume")
		doc.appendChild(rootNode)
		
		node_name = doc.createElement("name")
		node_name.appendChild(doc.createTextNode(self.file_name))
		
		node_capacity = doc.createElement("capacity")
		node_capacity.appendChild(doc.createTextNode(str(self.master.get_capacity())))
		
		node_allocation = doc.createElement("allocation")
		node_allocation.appendChild(doc.createTextNode(str(self.master.get_allocation())))
		
		node_source = doc.createElement("source")
		
		node_target = doc.createElement("target")
		
		node_path = doc.createElement("path")
		node_path.appendChild(doc.createTextNode(os.path.join(Config.ulteo_pool_path, self.file_name)))
		
		node_format = doc.createElement("format")
		node_format.setAttribute("type", "qcow2")
		
		node_target.appendChild(node_path)
		node_target.appendChild(node_format)
		
		node_backingStore = doc.createElement("backingStore")
		
		node_path_backStore = doc.createElement("path")
		
		node_format_backStore = doc.createElement("format")
		node_format_backStore.setAttribute("type", "qcow2")
		
		node_path_backStore.appendChild(doc.createTextNode(os.path.join(Config.ulteo_pool_path, self.master.file_name)))
		
		node_backingStore.appendChild(node_path_backStore)
		node_backingStore.appendChild(node_format_backStore)
		
		rootNode.appendChild(node_name)
		rootNode.appendChild(node_capacity)
		rootNode.appendChild(node_allocation)
		rootNode.appendChild(node_source)
		rootNode.appendChild(node_target)
		rootNode.appendChild(node_backingStore)
		
		self.pool.pool.createXML(rootNode.toxml(),0)
	
	
	"""
	Delete the instance
	"""
	def delete(self):
		
		instance = self.master.pool.pool.storageVolLookupByName(self.file_name)
		instance.delete(0)
		del self.master.pool.instances[self.name]
