# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

from xml.dom import minidom
from xml.dom.minidom import Document
import os

from ovd.Communication.Dialog import Dialog as AbstractDialog
from ovd.Logger import Logger
from ovd.Config import Config
from User import User
from Session import Session
import Util

class Dialog(AbstractDialog):
	
	def __init__(self, role_instance):
		self.role_instance = role_instance
		
		
	@staticmethod
	def getName():
		return "guest"
	
	
	def process(self, request):
		path = request["path"]
		
		Logger.info("guest role Dialog::process(%s)"%(str(request)))
		
		if request["method"] == "GET":
			Logger.debug("do_GET "+ path)
				
			if path.startswith("/session/status"):
				return self.req_session_status(request)
				
			return None
			
		if request["method"] == "POST":
			Logger.debug("do_POST "+ path)
			
			if path == "/session/create":
				return self.req_session_create(request)
				
			return None
				
		return None
		
		
	@staticmethod
	def session2xmlstatus(session):
		doc = Document()
		rootNode = doc.createElement('session')
		rootNode.setAttribute("id", session.id)
		rootNode.setAttribute("status", session.status)
		
		if session.status == Session.SESSION_STATUS_DESTROYED and session.end_status is not None:
			rootNode.setAttribute("reason", session.end_status)
		
		doc.appendChild(rootNode)
	
		return doc
		
		
	def req_session_status(self, request):
		
		if self.role_instance.session is not None :
			return self.req_answer(self.session2xmlstatus(self.role_instance.session))
		
		
	def req_session_create(self, request):
				
		try:
			document = minidom.parseString(request["data"])
			sessionNode = document.documentElement
			
			if sessionNode.nodeName != "session":
				raise Exception("invalid root node")
			
			
			if not sessionNode.hasAttribute("id"):
				raise Exception("invalid root node")
			
			
			session_info = {}
			session_info["id"] = sessionNode.getAttribute("id")
			userNode = sessionNode.getElementsByTagName("user")[0]
			
			for attr in ["login","password","displayName"]:
				
				if not userNode.getAttribute(attr) :
					raise Exception("invalid child node : missing attribute "+attr)
				
				session_info[attr] = userNode.getAttribute(attr)
			
		except:
			Logger.warn("Guest::Req_session_create, Invalid XML input !!")
			doc = Document()
			guest_name = self.role_instance.get_guest_name_from_file()
			guest_name = guest_name[10:]
			rootNode = doc.createElement("error")
			rootNode.setAttribute("id", guest_name)
			rootNode.setAttribute("reason", "invalid xml")
			doc.appendChild(rootNode)
			
			return self.req_answer(doc)
			
		user = User(session_info["login"], {"displayName": session_info["displayName"], "password": session_info["password"]})
		session = Session(session_info["id"], user)
			
		self.role_instance.session = session
				
		if user.exists() :
			self.role_instance.manager.session_switch_status(session, Session.SESSION_STATUS_INITED)
		else :
			self.role_instance.session_spooler.put(("create", self.role_instance.session))
			self.role_instance.session.status = Session.SESSION_STATUS_INITED
		
		return self.req_answer(self.session2xmlstatus(self.role_instance.session))
