# -*- coding: utf-8 -*-

# Copyright (C) 2011-2014 Ulteo SAS
# http://www.ulteo.com
# Author Gaëtan COLLET <gaetan@ulteo.com> 2011
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

from ovd.Communication.Dialog import Dialog as AbstractDialog
from ovd.Logger import Logger
from Config import Config

from xml.dom.minidom import Document
from xml.dom import minidom

import httplib
import urllib2

class DialogHypVM(AbstractDialog):
	
	def __init__(self, role_instance):
		self.role_instance = role_instance
		Logger.info("DialogHypVM::init")
	
	def process(self, request):
		
		path = request["path"]
		
		if request["method"] == "POST":
			Logger.debug("do_POST "+path)
			
			if path == "/vm/info":
				return self.req_sendip(request)
				
			if path == "/vm/name":
				return self.req_name(request)
	
		return None
	
	
	"""
	Send ip of the new virtual machine to the session manager
	"""
	def req_sendip(self, request):
		
		try:
			document = minidom.parseString(request["data"])
			rootNode = document.documentElement
		
			if rootNode.nodeName != "vm":
				raise Exception("invalid root node")
			
		except:
			Logger.error("Invalid input XML")
			doc = Document()
			rootNode = doc.createElement("error")
			rootNode.setAttribute("reason", "Invalid input XML")
			doc.appendChild(rootNode)		
			
		response = self.send_packet("/vm/info", document)
				
		if response is False:
			
			Logger.warn("DialogHypVM::send_ip unable to send request to the session manager")
			return False
		
		return self.req_answer(document)
	
	
	"""
	Send the name of the new virtual machine to her
	"""
	def req_name(self, request):
		
		
		try:
			document = minidom.parseString(request["data"])
			rootNode = document.documentElement
		
			if rootNode.nodeName != "mac":
				raise Exception("invalid root node")
			
		except:
			Logger.warn("Invalid xml input !!")
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "name")
			doc.appendChild(rootNode)
			
			return self.req_answer(doc)
		
		mac = rootNode.getAttribute("address")	
		name = self.role_instance.get_vm_by_mac(mac)
		rootNode.setAttribute("name",name)
	
		return self.req_answer(document)
		
		
	def send_packet(self, path, document):
		
		req = urllib2.Request("http://"+Config.session_manager+":1111" + path)
		
		req.add_header("Host", "%s:%s"%(Config.session_manager, "1111"))
		
		req.add_header("Content-type", "text/xml; charset=UTF-8")
		
		req.add_data(document.toxml())
					
		try:
			stream = urllib2.urlopen(req)
		except IOError:
			Logger.exception("Guest::send_packet path: "+path)
			return False
		except httplib.BadStatusLine:
			Logger.exception("Guest::send_packet path: "+path+" not receive HTTP response")
			return False
		
		return stream
