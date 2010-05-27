# -*- coding: utf-8 -*-

# Copyright (C) 2008-2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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

import httplib
import urllib
import urllib2
import socket
from xml.dom import minidom
from xml.dom.minidom import Document

from ovd.Logger import Logger
from ovd import util

from ovd.Communication.Dialog import Dialog as AbstractDialog

class Dialog(AbstractDialog):
	def __init__(self, role_instance):
		self.role_instance = role_instance
	
	@staticmethod
	def getName():
		return "fs"
	
	
	def process(self, request):
		path = request["path"]
		
		if request["method"] == "GET":
			Logger.debug("do_GET "+path)
			
			if path == "/list/all":
				return self.req_list_all(request)
			
			elif path.startswith("/profile/create"):
				buf = path[len("/profile/create/"):]
				
				doc = Document()
				rootNode = doc.createElement('profile')
				rootNode.setAttribute("id", buf)
				doc.appendChild(rootNode)
				request["data"] = doc.toxml()
				
				return self.req_profile_create(request)
			
			elif path.startswith("/profile/enable"):
				buf = path[len("/profile/enable/"):]
				
				doc = Document()
				rootNode = doc.createElement('profile')
				rootNode.setAttribute("id", buf)
				rootNode.setAttribute("user", "toto")
				rootNode.setAttribute("password", "tata")
				doc.appendChild(rootNode)
				request["data"] = doc.toxml()
				
				return self.req_profile_enable(request)
			
			elif path.startswith("/profile/disable"):
				buf = path[len("/profile/disable/"):]
				
				doc = Document()
				rootNode = doc.createElement('profile')
				rootNode.setAttribute("id", buf)
				doc.appendChild(rootNode)
				request["data"] = doc.toxml()
				
				return self.req_profile_disable(request)
	
			
			
			
		
		elif request["method"] == "POST":
			if path == "/profile/create":
				return self.req_profile_create(request)
		
		return None
	
	def req_list_all(self, request):
		profiles = self.role_instance.get_profiles()
		shares = self.role_instance.get_shares()
		
		doc = Document()
		rootNode = doc.createElement('fs')
		doc.appendChild(rootNode)
		
		for profile in profiles:
			node = doc.createElement('profile')
			node.setAttribute("id", profile)
			rootNode.appendChild(node)
		for share in shares:
			node = doc.createElement('share')
			node.setAttribute("id", share)
			rootNode.appendChild(node)
		
		return self.req_answer(doc)
	
	def req_profile_create(self, request):
		try:
			document = minidom.parseString(request["data"])
			roodNode = document.documentElement
			
			if roodNode.nodeName != "profile":
				raise Exception("invalid root node")
			
			if not roodNode.hasAttribute("id"):
				raise Exception("invalid root node")
			
			profile = roodNode.getAttribute("id")
			if len(profile)==0 or "/" in profile:
				raise Exception("invalid root node")
			
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if self.role_instance.exists_profile(profile):
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "already_exists")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if not self.role_instance.create_profile(profile):
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "system_error")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		doc = Document()
		rootNode = doc.createElement('profile')
		rootNode.setAttribute("id", profile)
		doc.appendChild(rootNode)
		return self.req_answer(doc)

	def req_profile_enable(self, request):
		try:
			document = minidom.parseString(request["data"])
			roodNode = document.documentElement
			
			if roodNode.nodeName != "profile":
				raise Exception("invalid root node")
			
			if not roodNode.hasAttribute("id"):
				raise Exception("invalid root node")
			
			elements = {}
			for item in ["id", "user", "password"]:
				if not roodNode.hasAttribute(item):
					raise Exception("invalid root node")
				elements[item] = roodNode.getAttribute(item)
				if len(elements[item])==0:
					raise Exception("invalid root node")
			
			profile = elements["id"]
			
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if not self.role_instance.exists_profile(profile):
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "not_exists")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if not self.role_instance.enable_profile(profile, elements["user"], elements["password"]):
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "system_error")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		doc = Document()
		rootNode = doc.createElement('profile')
		rootNode.setAttribute("id", profile)
		rootNode.setAttribute("status", "enable")
		doc.appendChild(rootNode)
		return self.req_answer(doc)


	def req_profile_disable(self, request):
		try:
			document = minidom.parseString(request["data"])
			roodNode = document.documentElement
			
			if roodNode.nodeName != "profile":
				raise Exception("invalid root node")
			
			if not roodNode.hasAttribute("id"):
				raise Exception("invalid root node")
			
			if not roodNode.hasAttribute("id"):
				raise Exception("invalid root node")
			
			profile = roodNode.getAttribute("id")
			if len(profile)==0 or "/" in profile:
				raise Exception("invalid root node")
			
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if not self.role_instance.exists_profile(profile):
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "not_exists")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if not self.role_instance.disable_profile(profile):
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "system_error")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		doc = Document()
		rootNode = doc.createElement('profile')
		rootNode.setAttribute("id", profile)
		rootNode.setAttribute("status", "disable")
		doc.appendChild(rootNode)
		return self.req_answer(doc)
