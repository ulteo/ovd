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
from ovd.Platform import Platform
from ovd.Platform import Session
from ovd.Platform import TS
from ovd.Platform import User
from ovd import util

from ovd.Communication.Dialog import Dialog as AbstractDialog

class Dialog(AbstractDialog):
	def __init__(self, role_instance):
		self.role_instance = role_instance
	
	@staticmethod
	def getName():
		return "aps"
	
	
	def process(self, request):
		path = request["path"]
		
		if request["method"] == "GET":
			Logger.debug("do_GET "+path)
			
			if path == "/applications":
				return self.req_applications(request)
			
			elif path.startswith("/application/icon/"):
				app_id = path[len("/application/icon/"):]
				return self.req_icon(app_id)
			
			elif path.startswith("/session/status/"):
				buf = path[len("/session/status/"):]
				return self.req_session_status(buf)
			
			elif path.startswith("/session/destroy/"):
				buf = path[len("/session/destroy/"):]
				return self.req_session_destroy(buf)
			
			return None
		
		elif request["method"] == "POST":
			if path == "/session/create":
				return self.req_session_create(request)
			
			elif path == "/user/loggedin":
				return self.req_user_loggedin(request)
			
			elif path == "/user/logout":
				return self.req_user_logout(request)
			
			return None
		
		return None

	@staticmethod
	def session2xmlstatus(session):
		doc = Document()
		rootNode = doc.createElement('session')
		rootNode.setAttribute("id", session.id)
		rootNode.setAttribute("status", session.status)
		doc.appendChild(rootNode)
		
		return doc
	
	
	def req_applications(self, request):
		doc = self.role_instance.getApplications()
		if doc is None:
			return None
		
		return self.req_answer(doc)

	
	def req_icon(self, app_id):
		if self.role_instance.applications is None:
			print "test1"
			return None
		
		if not self.role_instance.applications.has_key(app_id):
			print "test2"
			return None
		
		app =  self.role_instance.applications[app_id]
		
		data = Platform.getInstance().getApplicationIcon(app["filename"])
		if data is None:
			print "test3"
			return None
		
		response = {}
		response["code"] = httplib.OK
		response["Content-Type"] = "image/png"
		response["data"] = data
		return response
	
	def req_session_create(self, request):
		try:
			document = minidom.parseString(request["data"])
			sessionNode = document.documentElement
			
			if sessionNode.nodeName != "session":
				raise Exception("invalid root node")
			
			if not sessionNode.hasAttribute("id"):
				raise Exception("invalid root node")
			
			session = {}
			session["id"] = sessionNode.getAttribute("id")
			session["status"] = "wait_init"
			if len(session["id"])==0:
				raise Exception("Missing attribute id")
	
			userNode = sessionNode.getElementsByTagName("user")[0]
			
			for attr in ["login", "password", "displayName"]:
				if not userNode.hasAttribute(attr):
					raise Exception("invalid child node: missing attribute "+attr)
				
				session[attr] = userNode.getAttribute(attr)
			
			session["applications"] = []
			applicationNodes = sessionNode.getElementsByTagName("application")
			for node in applicationNodes:
				app_id = node.getAttribute("id")
				app_target = node.getAttribute("desktopfile")
				
				session["applications"].append((app_id, app_target))
			
			session["parameters"] = {}
			for node in sessionNode.getElementsByTagName("parameter"):
				session["parameters"][node.getAttribute("name")] = node.getAttribute("value")
		
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		user = User(session["login"], {"displayName": session["displayName"], "password": session["password"]})
		session = Session(session["id"], user, session["parameters"], session["applications"])
		
		self.role_instance.sessions[session.id] = session
		self.role_instance.sessions_spooler.put(("create", session))
		
		return self.req_answer(self.session2xmlstatus(session))
	
	
	def req_session_status(self, session_id):
		if self.role_instance.sessions.has_key(session_id):
			session = self.role_instance.sessions[session_id]
		else:
			session = Session(session_id, None, None, None)
			session.status = "unknown"
		
		return self.req_answer(self.session2xmlstatus(session))
	
	
	def req_session_destroy(self, session_id):
		if self.role_instance.sessions.has_key(session_id):
			session = self.role_instance.sessions[session_id]
			session.status = "wait_destroy"
			self.role_instance.sessions_spooler.put(("destroy", session))
		else:
			session = Session(session_id, None, None, None)
			session.status = "unknown"
		
		return self.req_answer(self.session2xmlstatus(session))
	
	

	def req_user_loggedin(self, request):
		try:
			document = minidom.parseString(request["data"])
			rootNode = document.documentElement
			
			if rootNode.nodeName != "user":
				raise Exception("invalid root node")
			
			if not rootNode.hasAttribute("login"):
				raise Exception("invalid root node")
			
			login = rootNode.getAttribute("login")
			
		except:
			Logger.warn("Invalid xml input !!")
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		ret = TS.getSessionID(login)
		rootNode.setAttribute("loggedin", str((ret is not None)).lower())
		
		return self.req_answer(document)
	
	
	def req_user_logout(self, request):
		try:
			document = minidom.parseString(request["data"])
			rootNode = document.documentElement
			
			if rootNode.nodeName != "user":
				raise Exception("invalid root node")
			
			if not rootNode.hasAttribute("login"):
				raise Exception("invalid root node")
			
			login = rootNode.getAttribute("login")
			
		except:
			Logger.warn("Invalid xml input !!")
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		ret = TS.getSessionID(login)
		if ret is None:
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "unknown user")
			doc.appendChild(rootNode)
			
			return self.req_answer(doc)
		
		user = User(login, {"tsid": ret})
		self.role_instance.sessions_spooler.put(("logoff", user))
		
		return self.req_answer(document)
	
	
	
	def webservices_logoff(self, arg):
		try:
			_, login, domain = arg.split("/", 3)
		except:
			Logger.warn("webservices_loggedin: usage error not enough argument")
			return self.webservices_answer(self.error2xml("usage"))
		
		if len(login) == 0:
			Logger.warn("webservices_loggedin: usage error empty login")
			return self.webservices_answer(self.error2xml("usage"))
		
		if len(domain) == 0:
			domain = "local"
			
	
		Logger.info("webservices_logoff: login '%s'"%(login))
		found = None
		sessions = win32ts.WTSEnumerateSessions(None)
		for session in sessions:
			if not 0 < session["SessionId"] < 65536:
				continue
			
			l_ = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
			if login != l_:
				continue
	
			d_ = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSDomainName)
			computerName = win32api.GetComputerName()
			if d_.lower() == computerName.lower():
				if domain == "ad":
					continue
			else:
				if domain == "local":
					continue
			
			found = session["SessionId"]
			break;
		
		if found is None:
			return self.webservices_answer(self.error2xml("not found"))
		
		doc = Document()
		rootNode = doc.createElement("session")
		rootNode.setAttribute("id", str(found))
		
		self.server.daemon.log.debug("webservices_logoffADUser: start thread logoff")
		th = threading.Thread(target=self.perform_logoff, args=[session["SessionId"]])
		th.start()
		rootNode.setAttribute("status", "logged off")

		doc.appendChild(rootNode)
		return self.webservices_answer(doc)
	
	
	def perform_logoff(self, session_id):
		try:
			Logger.debug("perform_logoff: start logoff %d"%(session_id))
			ret = win32ts.WTSLogoffSession(None, session_id, True)
			Logger.debug("perform_logoff: finish logoff %d ret: %s"%(session_id, str(ret)))
		except Exception, e:
			Logger.warn("perform_logoff: exception %s"%(e))

	
	def webservices_domain(self):
		Logger.info("webservices_domain")
		doc = Document()
		domain = None
		
		try:
			domain = win32api.GetComputerNameEx(win32con.ComputerNameDnsDomain)
		except Excpetion, e:
			Logger.warn("webservices_domain: exception '%s'"%(str(e)))
			rootNode = doc.createElement("error")
			rootNode.setAttribute("id", "internal")
			
			textNode = doc.createTextNode(str(e))
			rootNode.appendChild(textNode)
		
		if domain is not None:
			if domain == u"":
				Logger.info("webservices_domain: no domain")
				rootNode = doc.createElement("error")
				rootNode.setAttribute("id", "no_domain")
			else:
				rootNode = doc.createElement("domain")
				rootNode.setAttribute("name", domain)
				
				Logger.info("webservices_domain: '%s'"%(domain))
		
		doc.appendChild(rootNode)
		self.send_response(httplib.OK)
		self.send_header('Content-Type', 'text/xml')
		self.end_headers()
		self.wfile.write(doc.toxml())
		return


