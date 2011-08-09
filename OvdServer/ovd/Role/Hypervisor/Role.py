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

from ovd.Logger import Logger
from ovd.Role.Role import Role as AbstractRole
import Queue
import socket
import libvirt

from Virtual_Machine import VirtualMachine
from Pool import Pool
from Config import Config
from HttpServer import HttpServer2
from Network import Network

class Role(AbstractRole):
  
	def __init__(self, main_instance):
		
		AbstractRole.__init__(self, main_instance)
		self.virt_co = libvirt.open(Config.libvirt_uri)
		self.has_run = False
		self.queue = Queue.Queue()
		self.pool = Pool(Config.ulteo_pool_name,self.virt_co)
		self.virtual_machine = {}
		self.network = Network(Config.network_name, self.virt_co)
		self.webserver = None
	
	
	"""
	Check if the storage pool exist, if not create it, if yes, reload it 
	Check if the virtual machine network exist, if not create it
	Reload virtual machine in the list of VM
	Init the HTTP server use for communication between hypervisor and virtuals machines
	"""
	def init(self):
	  
		if not self.pool.exist():
			self.pool.create(Config.ulteo_pool_path)
		else:
			self.pool.reload()
			
		if not self.network.exist():
			self.network.create()
			
		self.reload_vm()
		
		self.webserver = HttpServer2((Config.lan, Config.port), self)
			
		return True
	
	
	@staticmethod
	def getName():
		return "Hypervisor"
	
	
	"""
	When role is stopped, all running virtuals machines are stopped
	and the http server is closed
	"""
	def stop(self):
		Logger.info("Hypervisor:: stopping")
		
		for vm in self.virtual_machine :
			vm = self.virtual_machine[vm]
			
			if vm.getStatus() == "RUNNING" :
				vm.shutdown()
				
		self.webserver.server_close()
	
	
	def finalize(self):
		Logger.info("Hypervisor:: stopping")
	
	
	def run(self):
		
		self.has_run = True
		
		self.status = Role.STATUS_RUNNING
		
		self.webserver.serve_forever()
		
		while True:
			
			try:
				(request, obj) = self.queue.get(True, 4)
			except Queue.Empty, err:
				continue
			# This error is ue to the sigterm sended by the init script
			except TypeError:
				return
			except (EOFError, socket.error):
				return
			
			if request == "create":
				self.create_vm(obj[0])
		
		self.status = Role.STATUS_STOP
	
	
	"""
	- Re-create all the VirtualMachine objects
	- Add them in the virtual_machine list
	"""
	def reload_vm(self):
		
		list_volumes = self.pool.instances
		
		for vol in list_volumes:
			
			instance = list_volumes[vol]
			tmp = instance.get_file_name().split(".")
			vm_name = "ulteo_ovd_"+tmp[0]
			
			vm = VirtualMachine(vm_name, instance, self.virt_co)
			vm.connect_domain()
			
			self.virtual_machine[vm_name] = vm
			
			#vm.run()
	
	
	def getReporting(self, node):
		pass
	
	
	"""
	Function to change domains settings
	We can change :
	- number of vcpus
	- allocated memory
	"""
	def config(self, vname, vcpus, memory):
		
		vm = self.virtual_machine[vname]
		vm.config(long(vcpus),long(memory))
		
		return vm
	
	
	"""
	This function allow you to delete a virtual machine
	It delete the instance from the ulteo-ovd pool and the virtual machine object from vm list too
	"""
	def delete(self,vname):
		
		vm = self.virtual_machine[vname]
		ret = vm.free()
		
		instance = self.pool.instances[vm.instance.name]
		instance.delete()
				
		del self.virtual_machine[vname]
		
		return ret
	
	
	"""
	Return the name of the current hypervisor
	"""
	def get_hyp_name(self):
		return self.virt_co.getType()
	
	
	"""
	Return the URI used by libvirt
	"""
	def get_uri(self):
		return self.virt_co.getURI()
	
	
	"""
	Return the name of the VM which use the mac address "mac"
	"""
	def get_vm_by_mac(self, mac):
		
		for vm in self.virtual_machine:
			
			vm = self.virtual_machine[vm]
			mac = mac.replace("-",":")
			
			if vm.get_mac_address().lower() == mac.lower():
				return vm.name
				
		return "UNKONWN"
	
	
	"""
	Create a VM
	- create the instance (define it)
	- add it to the list of instances in the pool
	- create the VirtualMachine object and define de VM
	- add the new VM in the virtual_machine list
	"""
	def create_vm(self, instance, ram, vcpu):
		
		instance.create()
		
		self.pool.instances[instance.name] = instance
		
		name_instance = instance.get_file_name()
		tab = name_instance.split(".")
		
		vm = VirtualMachine("ulteo_ovd"+"_"+tab[0], instance,self.virt_co)
		ret = vm.create(ram, vcpu)
		
		if ret == "ERROR":
			return "ERROR"
			
		self.virtual_machine["ulteo_ovd"+"_"+tab[0]] = vm
		
		return "OK"
	
	
	"""
	Function used to manage virtual machine
	"""
	def manage(self, vname, action):
		
		if action == "run":
			return self.virtual_machine[vname].run()
			
		elif action == "suspend":
			return self.virtual_machine[vname].suspend()
			
		elif action == "destroy":
			return self.virtual_machine[vname].destroy()
			
		elif action == "resume":
			return self.virtual_machine[vname].resume()
			
		elif action == "shutdown":
			return self.virtual_machine[vname].shutdown()
			
		elif action == "info":
			return str(self.virtual_machine[vname].info())
