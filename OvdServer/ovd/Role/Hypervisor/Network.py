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

class Network():
	
	def __init__(self, name, virt_co):
		
		self.virt_co = virt_co
		self.name = name
	
		try:
			self.network = self.virt_co.networkLookupByName(self.name)
		except libvirt.libvirtError, err:
			self.network = None
	
	
	"""
	- Build the xml definition of a pool
	- Define it
	- Start it
	"""
	def create(self):
		
		doc = Document()
		
		rootNode = doc.createElement("network")
		doc.appendChild(rootNode)
	
		node_name = doc.createElement("name")
		node_name.appendChild(doc.createTextNode(self.name))
		
		node_forward = doc.createElement("forward")
		node_forward.setAttribute("mode","route")
		
		node_bridge = doc.createElement("bridge")
		node_bridge.setAttribute("name","virbr2")
		node_bridge.setAttribute("stp","on")
		node_bridge.setAttribute("delay","0")
	
		node_ip = doc.createElement("ip")
		node_ip.setAttribute("address","192.168.45.1")
		node_ip.setAttribute("netmask","255.255.255.0")
		
		node_dhcp = doc.createElement("dhcp")
		
		node_dhcp_range = doc.createElement("range")
		node_dhcp_range.setAttribute("start","192.168.45.2")
		node_dhcp_range.setAttribute("end","192.168.45.254")
		
		node_dhcp.appendChild(node_dhcp_range)
		node_ip.appendChild(node_dhcp)
		
		rootNode.appendChild(node_name)
		rootNode.appendChild(node_forward)
		rootNode.appendChild(node_ip)
				
		self.virt_co.networkDefineXML(rootNode.toxml())
		
		self.network = self.virt_co.networkLookupByName(self.name)
		self.network.create()
		self.network.setAutostart(1)
	
	
	def exist(self):
		return self.network is not None
