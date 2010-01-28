# -*- coding: utf-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
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

from Queue import Queue
import time
from xml.dom.minidom import Document

from ovd.Role import AbstractRole
from ovd.Config import Config
from ovd.Logger import Logger
from ovd.Platform import Platform
from ovd.Platform import TS

from Dialog import Dialog
from Session import Session
from SessionManagement import SessionManagement


class ApplicationServer(AbstractRole):
	ts_group_name = Platform.getInstance().get_default_ts_users_group()
	ovd_group_name = "OVDUsers"
	session_manager = None
	
	sessions = {}
	sessions_spooler = None
	
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		self.dialog = Dialog(self)
		self.sessions = {}
		self.sessions_spooler = Queue()
		self.threads = []
		
		self.applications = None
		self.applicationsXML = None
		
		self.has_run = False
	
	
	def init(self):
		Logger.info("ApplicationServer init")
		
		if not self.init_config():
			return False
		
		try:
			TS.getList()
		except Exception,err:
			Logger.error("RDP server dialog failed ... exiting")
			return
		
		if not Platform.getInstance().groupExist(self.ts_group_name):
			Logger.error("The group '%s' doesn't exist"%(self.ts_group_name))
			return False
		
		if not Platform.getInstance().groupExist(self.ovd_group_name):
			if not Platform.getInstance().groupCreate(self.ovd_group_name):
				return False
		
		
		if not self.purgeGroup():
			Logger.error("Unable to purge group")
			return False
		
		for i in xrange(5):
			self.threads.append(SessionManagement(self, self.sessions_spooler))
		
		return True
	
	@staticmethod
	def getName():
		return "ApplicationServer"
	
	
	def stop(self):
		for thread in self.threads:
			if thread.isAlive():
				thread._Thread__stop()
				
		cleaner = SessionManagement(self, self.sessions_spooler)
		for session in self.sessions.values():
			cleaner.destroy_session(session)
		
		self.purgeGroup()
	
	
	def init_config(self):
		if not Config.infos.has_key("session_manager"):
			Logger.error("Role %s need a 'session_manager' config key"%(self.getName()))
			return False
		
		self.session_manager =  Config.session_manager
		return True
	
	def send_session_status(self, session):
		doc = Document()
		rootNode = doc.createElement('session')
		rootNode.setAttribute("id", session.id)
		rootNode.setAttribute("status", session.status)
		doc.appendChild(rootNode)
		
		response = self.main_instance.dialog.send_packet("/session/status", doc)
	
	
	def get_session_from_login(self, login_):
		for session in self.sessions.values():
			if session["login"] == login_:
				return session
		
		return None
		
	def session_switch_status(self, session, status):
		session.status = status
		Logger.info("Session switch status")
		self.send_session_status(session)
	
	
	def run(self):
		self.updateApplications()
		self.has_run = True
		
		for thread in self.threads:
			thread.start()
		
		t0_update_app = time.time()
		
		while 1:
			for session in self.sessions.values():
				try:
					ts_id = TS.getSessionID(session.user.name)
				except Exception,err:
					Logger.error("RDP server dialog failed ... exiting")
					return
				
				if ts_id is None:
					if session.status in [Session.SESSION_STATUS_ACTIVE, Session.SESSION_STATUS_INACTIVE]:
						Logger.error("Weird, running session no longer exist")
						self.session_switch_status(session, Session.SESSION_STATUS_WAIT_DESTROY)
						self.sessions_spooler.put(("destroy", session))
					continue
				
				try:
					ts_status = TS.getState(ts_id)
				except Exception,err:
					Logger.error("RDP server dialog failed ... exiting")
					return
				
				if session.status == Session.SESSION_STATUS_INITED:
					if ts_status is TS.STATUS_LOGGED:
						self.session_switch_status(session, Session.SESSION_STATUS_ACTIVE)
						continue
						
					if ts_status is TS.STATUS_DISCONNECTED:
						self.session_switch_status(session, Session.SESSION_STATUS_INACTIVE)
						continue
					
				if session.status == Session.SESSION_STATUS_ACTIVE and ts_status is TS.STATUS_DISCONNECTED:
					self.session_switch_status(session, Session.SESSION_STATUS_INACTIVE)
					continue
			
			
			t1 = time.time()
			if t1-t0_update_app > 30:
				self.updateApplications()
				t0_update_app = time.time()
			else:
				time.sleep(1)
			
			#Logger.debug("ApplicationServer run loop")
	
	
	def purgeGroup(self):
		users = Platform.getInstance().groupMember(self.ovd_group_name)
		if users is None:
			return False
		
		for user in users:
			# todo : check if the users is connected, if yes logoff his session
			
			if not Platform.getInstance().userRemove(user):
				return False
		
		return True
	
	
	@staticmethod
	def isMemberGroupOVD(login_):
		members = Platform.getInstance().groupMember(ApplicationServer.ovd_group_name)
		if members is None:
			return False
		
		return login in members
	
	def getApplications(self):
		i = 0
		while self.applicationsXML is None:
			if i > 10:
				break
			i+= 1
			time.sleep(0.2)
			
		return self.applicationsXML

	def updateApplications(self):
		self.applications = Platform.getInstance().detectAvailableApplications()
		
		doc = Document()
		rootNode = doc.createElement('applications')
		
		for application in self.applications.values():
			appNode = doc.createElement("application")
			
			appNode.setAttribute("id", application["id"])
			appNode.setAttribute("name", application["name"])
			appNode.setAttribute("desktopfile", application["filename"])
			if application.has_key("description"):
				appNode.setAttribute("description", application["description"])
			
			exeNode = doc.createElement("executable")
			exeNode.setAttribute("command", application["command"])
			#if application.has_key("icon"):
			#	exeNode.setAttribute("icon", application["icon"])
			
			exeNode.setAttribute("mimetypes", ";".join(application["mimetypes"])+";")
			appNode.appendChild(exeNode)
			
			rootNode.appendChild(appNode)
			
		doc.appendChild(rootNode)
		self.applicationsXML = doc
