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
from xml.dom import minidom
from ovd.Logger import Logger
import random
import libvirt

from Config import Config

class VirtualMachine:
	
	"""
	This class represent Virtual Machine capabilities
	It provides :
		- connection to a domain
		- creation of a virtual machine (set_libvirt_description + create)
		- disconnection from a domain
		- many actionson virtuals machines 
			* run
			* suspend
			* resume
			* shutdown
			* destroy
	"""
	def __init__(self, name, instance, virt_co):
		
		self.domain = None
		self.name = name
		self.mac = None
		self.virt_co = virt_co
		self.instance = instance
	
	
	"""
	Connect to the domain when this one is already created
	"""
	def connect_domain(self):
		
		if self.domain:
			Logger.info("Already connected to domain. Ignoring")
			return
		try:
			self.domain = self.virt_co.lookupByName(self.name)
		except:
			Logger.error("Can't connect to domain with name %s " %self.name)
	
	
	"""
	Create the VM
	- define it
	- run it
	"""
	def create(self, ram, vcpu):
		
		self.domain = self.virt_co.defineXML(self.set_libvirt_description(self.instance.get_file_name(), ram, vcpu))
		
		if self.domain == None:
			return "ERROR"
		
		self.run()
		
		return "OK"
	
	
	"""
	Build the XML definition of the domain
	"""
	def set_libvirt_description(self, instance_file_name, ram, vcpu):
		
		doc = Document()
		rootNode = doc.createElement("domain")
		rootNode.setAttribute("type","kvm")
		doc.appendChild(rootNode)

		node = doc.createElement("name")
		node.appendChild(doc.createTextNode(self.name))
		rootNode.appendChild(node)
		
		node = doc.createElement("memory")
		node.appendChild(doc.createTextNode(ram))
		rootNode.appendChild(node)
		
		node = doc.createElement("currentMemory")
		node.appendChild(doc.createTextNode(ram))
		rootNode.appendChild(node)
		
		node = doc.createElement("vcpu")
		node.appendChild(doc.createTextNode(vcpu))
		rootNode.appendChild(node)
		
		node_os = doc.createElement("os")
		node_type_os = doc.createElement("type")
		node_type_os.setAttribute("arch","i686")
		node_type_os.setAttribute("machine","pc")
		node_type_os.appendChild(doc.createTextNode("hvm"))
		node_boot_os_hd = doc.createElement("boot")
		node_boot_os_hd.setAttribute("dev","hd")
		node_boot_os_cd = doc.createElement("boot")
		node_boot_os_cd.setAttribute("dev","cdrom")
		node_os.appendChild(node_type_os)
		node_os.appendChild(node_boot_os_hd)
		node_os.appendChild(node_boot_os_cd)
		rootNode.appendChild(node_os)
		
		node_clock = doc.createElement("clock")
		node_clock.setAttribute("offset","utc")
		rootNode.appendChild(node_clock)
		
		node_poweroff = doc.createElement("on_poweroff")
		node_poweroff.appendChild(doc.createTextNode("destroy"))
		node_reboot = doc.createElement("on_reboot")
		node_reboot.appendChild(doc.createTextNode("restart"))
		node_crash = doc.createElement("on_crash")
		node_crash.appendChild(doc.createTextNode("destroy"))
		
		rootNode.appendChild(node_poweroff)
		rootNode.appendChild(node_reboot)
		rootNode.appendChild(node_crash)
		
		node_feature = doc.createElement("features")
		node_feature.appendChild(doc.createElement("pae"))
		node_feature.appendChild(doc.createElement("acpi"))
		node_feature.appendChild(doc.createElement("apic"))
		rootNode.appendChild(node_feature)
		
		node_device = doc.createElement("devices")
		node_emulator = doc.createElement("emulator")
		node_emulator.appendChild(doc.createTextNode("/usr/bin/qemu"))
		node_disk = doc.createElement("disk")
		node_disk.setAttribute("type","file")
		node_disk.setAttribute("device","disk")
		node_disk_driver = doc.createElement("driver")
		node_disk_driver.setAttribute("name","qemu")
		node_disk_driver.setAttribute("type","qcow2")
		node_disk_source = doc.createElement("source")
		node_disk_source.setAttribute("file",Config.ulteo_pool_path + "/" + instance_file_name)
		node_disk_target = doc.createElement("target")
		node_disk_target.setAttribute("dev","hda")
		
		node_disk.appendChild(node_disk_driver)
		node_disk.appendChild(node_disk_source)
		node_disk.appendChild(node_disk_target)
		
		node_interface = doc.createElement("interface")
		node_interface.setAttribute("type","network")
		node_interface_source = doc.createElement("source")
		node_interface_source.setAttribute("network","private_routed")
		node_interface.appendChild(node_interface_source)
		
		node_input = doc.createElement("input")
		node_input.setAttribute("type","mouse")
		node_input.setAttribute("bus","ps2")
		
		node_graphics = doc.createElement("graphics")
		node_graphics.setAttribute("type","vnc")
		node_graphics.setAttribute("port","-1")
		node_graphics.setAttribute("listen","127.0.0.1")
		
		node_device.appendChild(node_emulator)
		node_device.appendChild(node_disk)
		node_device.appendChild(node_interface)
		node_device.appendChild(node_input)
		node_device.appendChild(node_graphics)
		
		rootNode.appendChild(node_device)
				
		return rootNode.toxml()
	
	
	def get_mac_address(self):
		
		xmlDesc = self.domain.XMLDesc(0)
	
		doc = minidom.parseString(xmlDesc)
		mac = doc.getElementsByTagName("mac")
	
		for node in mac:
			
			if node.nodeName == "mac":
				return node.getAttribute("address")
	
	
	"""
	Close libvirt connection
	"""
	def disconnect(self):
		
		Logger.info("%s is disconnecting..." % self.uuid)
		
		if self.libvirt_connection:
			
			self.libvirt_connection.close()
			self.libvirt_connection = None
	
	
	### libvirt controls
	
	
	"""
	Create the domain
	"""
	def run(self):	
		
		if self.domain and (self.domain.info()[0] == 1 or self.domain.info()[0] == 2 or self.domain.info()[0] == 3):
			return "ERROR"
			
		self.domain.create()
		
		Logger.info("Virtual Machine created")
		
		return "OK"
	
	
	"""
	Shutdown the domain
	"""
	def shutdown(self):	
		
		if self.domain and (self.domain.info()[0] == 4 or self.domain.info()[0] == 5  or self.domain.info()[0] == 6):
			return "ERROR"
			
		self.domain.shutdown()
		
		Logger.info("Domain shutdown")
		
		return "OK"
	
	
	"""
	Destroy the domain
	"""
	def destroy(self):
			
		if self.domain and (self.domain.info()[0] == 5 or self.domain.info()[0] == 6):
			return "ERROR"
		
		self.domain.destroy()
		
		Logger.info("Domain destroyed")
		
		return "OK"
	
	
	"""
	Reboot the domain
	"""
	def reboot(self):
		
		if self.domain:
			self.domain.reboot(0)
			Logger.info("Virtual machine rebboted")
	
	
	"""
	Suspend the domain
	"""
	def suspend(self):
			
		if self.domain and (self.domain.info()[0] == 3 or self.domain.info()[0] == 5 or self.domain.info()[0] == 6):
			return "ERROR"
		
		self.domain.suspend()
		
		Logger.info("Virtual machine paused")
		
		return "OK"
	
	
	"""
	Resume (unpause) the domain
	"""
	def resume(self):
		
		if self.domain and (self.domain.info()[0] == 4 or self.domain.info()[0] == 5 or self.domain.info()[0] == 6):
			return "ERROR"
		
		self.domain.resume()
		
		Logger.info("Virtual machine unpaused")
		
		return "OK"
	
	
	"""
	Return informations about the domain
	"""
	def info(self):
	
		if self.domain:
			dominfo = self.domain.info()
			try:
				autostart = self.domain.autostart()
			except:
				autostart = 0
		
		return {
			"state" : dominfo[0],
			"maxMem" :dominfo[1],
			"memory" :dominfo[2],
			"nrVirtCpu" :dominfo[3],
			"cpuTime" :dominfo[4],
			"autostart" :str(autostart)
		}
		
		
	def free(self):
		
		if self.domain and (self.domain.info()[0] == 1 or self.domain.info()[0] == 2 or self.domain.info()[0] == 3):
			self.destroy()
		if self.domain:
			self.domain.undefine()
			return "OK"
		else :
			return "ERROR"
	
	
	### Configuration
	
	def config(self, vcpus, memory):
		
		if self.domain is not None and self.getState() == "STOP":
			return False
			
		self.set_memory(memory)
		self.set_vcpus(vcpus)
	
	
	def getVcpus(self) :
		
		if self.domain is not None :
			info = self.domain.info()
			
			return info[3]
	
	
	def getCurrentMemory(self) :
		
		if self.domain is not None :
			info = self.domain.info()
			
			return info[2]
	
	
	"""
	Set memory value for running domain
	"""
	def set_memory(self, value):
		
		value = long(value)
		
		if self.domain is not None : 
			self.domain.setMemoryFlags(value, libvirt.VIR_DOMAIN_AFFECT_CURRENT)
	
	
	"""
	Set the number of CPU for the domain
	"""
	def set_vcpus(self, value) :
		
		if self.domain:
			if value > self.domain.maxVcpus():
				raise Exception("Maximum vCPU is %d" % self.domain.maxVcpus())
			
			self.domain.setVcpusFlags(value, libvirt.VIR_DOMAIN_AFFECT_CONFIG)
	
	
	def getState(self) :
		
		if self.domain is not None :
			
			state, state2 = self.domain.state(0)
			
			if state == 1 :
				return "RUNNING"
				
			elif state == 3 :
				return "SUSPEND"
				
			elif state == 5 :
				return "STOP"
				
			else :
				return "UNKNWOWN"
			
	"""
	Generate random mac address
	"""
	def generate_mac_adress(self) :
		
		digits = ["de","ad"]
		for i in xrange(4):
			digits.append("%02X"%(random.randint(0x00, 0xff)))
		
		return ":".join(digits)
