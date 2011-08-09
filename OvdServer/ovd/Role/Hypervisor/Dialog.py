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

from ovd.Communication.Dialog import Dialog as AbstractDialog
from ovd.Logger import Logger
from ovd.Platform.System import System
import b36
from xml.dom.minidom import Document
from xml.dom import minidom

from Instance import Instance
from Config import Config

class Dialog(AbstractDialog):
	
	def __init__(self, role_instance):
		self.role_instance = role_instance
	
	
	@staticmethod
	def getName():
		return "hypervisor"
	
	
	def process(self, request):
		path = request["path"]
		
		if request["method"] == "GET":
			
			Logger.debug("do_GET "+path)
			
			if path == "/status" :
				return self.req_status()
		
		
		elif request["method"] == "POST":
			
			Logger.debug("do_POST "+path)
			
			if path == "/vm/destroy":
				return self.req_delete(request)
	
			if path == "/vm/create":
				return self.req_create(request)
	
			if path == "/vm/manage":
				return self.req_manage(request)
				
			if path == "/vm/name":
				return self.req_name(request)
				
			if path == "/vm/configure":
				return self.req_configure(request)
		
		return None
	
	
	"""
	This function allow the creation of a new virtual machine
	Param request contain settings for the virtual machine under xml format
	Return name and status of the new instance
	"""
	def req_create(self,request):
		
		try:
			document = minidom.parseString(request["data"])
			rootNode = document.documentElement
		
			if rootNode.nodeName != "vm":
				raise Exception("invalid root node")
			
		except:
			Logger.warn("Invalid xml input !!")
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "name")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		ram = rootNode.getAttribute("ram")
		vcpu = rootNode.getAttribute("vcpu")
		master = rootNode.getAttribute("master")
	
		if not self.role_instance.pool.masters.has_key(master):
			return False
		
		obj_master = self.role_instance.pool.masters[master]
		
		new_id = self.role_instance.pool.get_last_instance(master)
		
		id_tmp = b36.b362int(new_id) + 1
		
		name = b36.int2b36(id_tmp, 4)
		
		instance = Instance(obj_master, name, self.role_instance.pool, self.role_instance.virt_co)
		
		ret = self.role_instance.create_vm(instance, ram, vcpu)
		
		doc = Document()
		rootNode = doc.createElement("vm")
		rootNode.setAttribute("name",instance.get_name())
		rootNode.setAttribute("status",ret)
		doc.appendChild(rootNode)
				
		return self.req_answer(doc)
	
	
	"""
	This function allow to delete a virtual machine
	She takes a param named request. This one contains the id of
	the virtual machine which have to be delete, under xml format.
	Return OK or ERROR
	"""
	def req_delete(self, request):
		
		try:
			document = minidom.parseString(request["data"])
			rootNode = document.documentElement
		
			if rootNode.nodeName != "vm":
				raise Exception("invalid root node")
			
		except:
			Logger.warn("Invalid xml input !!")
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "name")
			doc.appendChild(rootNode)
			
			return self.req_answer(doc)
		
		vname = rootNode.getAttribute("id")
				
		ret = self.role_instance.delete("ulteo_ovd_"+vname)
		
		rootNode.setAttribute("status",ret)
		
		return self.req_answer(document)
		
	
	"""
	This function set the new configuration of a virtual machine
	She takes a param named request, which contains settings for the new instance (ram and cpu)
	"""
	def req_configure(self, request):
		
		try:
			document = minidom.parseString(request["data"])
			rootNode = document.documentElement
			
			if rootNode != "vm" :
				raise Exception("invalid root node")
			
		except:
			Logger.warn("Invalid input XML")
			doc = Document()
			rootNode = doc.createElement("error")
			rootNode.setAttribute("id", "name")
			doc.appendChild(rootNode)
			
			return self.req_answer(doc)
			
		vname = rootnode.getAttribute("id")
		ram = rootNode.getAttribute("ram")
		vcpus = rootNode.getAttribute("cpu")
		
		self.role_instance.config(vname, vcpus, ram)
		
		rootNode.setAttribute("status", "OK")
		
		return self.req_answer(document)
	
	
	"""
	This function allow to execute some actions on the virtual machines
	Input XML have to contains the id of the virtual machine to manage and an action
	Actions could be : run/suspend/destroy/resume/shutdown
	"""
	def req_manage(self, request):
		
		try:
			document = minidom.parseString(request["data"])
			rootNode = document.documentElement
		
			if rootNode.nodeName != "vm":
				raise Exception("invalid root node")
			
		except:
			Logger.warn("Invalid xml input !!")
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "name")
			doc.appendChild(rootNode)
			
			return self.req_answer(doc)
		
		vname = rootNode.getAttribute("id")	
		action = rootNode.getAttribute("action")
		ret = self.role_instance.manage("ulteo_ovd_"+vname,action)
		
		doc = Document()
		rootNode = doc.createElement("vm")
		rootNode.setAttribute("status",ret)
		doc.appendChild(rootNode)
		
		return self.req_answer(doc)
	
	
	"""
	This function return many informations about the hypervisor :
	- list of available masters
	- list of user instance
	- list of the vm and give their states
	"""
	def req_status(self):
		
		doc = Document()
		rootNode = doc.createElement("hypervisor")
		doc.appendChild(rootNode)
		
		cpuInfo = System.getCPUInfos()
		
		list_masters = self.role_instance.pool.masters
		
		for master in list_masters:
			
			node_master = doc.createElement("master")
			
			master = list_masters[master]
			
			node_master.setAttribute("capacity", str(master.get_capacity()))
			node_master.setAttribute("allocation", str(master.get_allocation()))
			node_master.setAttribute("id", str(master.name))
			node_master.setAttribute("name",str(master.get_name()))
			node_master.setAttribute("vcpu", "1")
			node_master.setAttribute("ram", "2000")
			node_master.setAttribute("cpu_model", str(cpuInfo[0]))
			
			rootNode.appendChild(node_master)
			
		list_instances = self.role_instance.pool.instances
		
		for instance in list_instances:
			
			node_instance = doc.createElement("instance")
			
			instance = list_instances[instance]
			
			node_instance.setAttribute("id", str(instance.name))
			node_instance.setAttribute("master" , instance.master.get_file_name())
			
			rootNode.appendChild(node_instance)
			
		
		list_vm = self.role_instance.virtual_machine
		
		for vm in list_vm:
			
			node_vm = doc.createElement("vm")
			
			vm = list_vm[vm]
			
			node_vm.setAttribute("status",vm.getState())
			node_vm.setAttribute("id",vm.name[10:])
			node_vm.setAttribute("master" , vm.instance.master.name)
			node_vm.setAttribute("vcpu",str(vm.getVcpus()))
			node_vm.setAttribute("cpu_model", str(cpuInfo[0]))
			node_vm.setAttribute("ram",str(vm.getCurrentMemory()))
			node_vm_name = doc.createElement("name")
			node_vm_name.appendChild(doc.createTextNode(vm.name[10:]))
			
			node_vm.appendChild(node_vm_name)
			rootNode.appendChild(node_vm)
		
		return self.req_answer(doc)
