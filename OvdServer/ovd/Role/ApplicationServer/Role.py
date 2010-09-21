# -*- coding: utf-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
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

from ovd.Role.Role import Role as AbstractRole
from ovd.Config import Config
from ovd.Logger import Logger
from ovd.Platform import Platform

from Apt import Apt
from Dialog import Dialog
from Session import Session
from SessionManagement import SessionManagement
from Platform import Platform as RolePlatform


class Role(AbstractRole):
	ts_group_name = RolePlatform.TS.getUsersGroup()
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
		Logger.debug("ApplicationServer init")
		
		if not self.init_config():
			return False
		
		try:
			RolePlatform.TS.getList()
		except Exception, err:
			Logger.error("RDP server dialog failed ... exiting")
			Logger.debug("RDP server dialog: "+str(err))
			return
		
		if not Platform.System.groupExist(self.ts_group_name):
			Logger.error("The group '%s' doesn't exist"%(self.ts_group_name))
			return False
		
		if not Platform.System.groupExist(self.ovd_group_name):
			if not Platform.System.groupCreate(self.ovd_group_name):
				return False
		
		
		if not self.purgeGroup():
			Logger.error("Unable to purge group")
			return False
		
		for _ in xrange(1):
			self.threads.append(SessionManagement(self, self.sessions_spooler))
		
		if self.canManageApplications():
			self.apt = Apt()
			self.apt.init()
			self.threads.append(self.apt)
		
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
		Logger.debug2("ApplicationServer: send_session_status: %s"%(response))
	
	
	def get_session_from_login(self, login_):
		for session in self.sessions.values():
			if session["login"] == login_:
				return session
		
		return None
		
	def session_switch_status(self, session, status):
		session.switch_status(status)
		Logger.info("Session %s switch status %s"%(session.id, session.status))
		self.send_session_status(session)
	
	
	def run(self):
		self.updateApplications()
		self.has_run = True
		
		for thread in self.threads:
			thread.start()
		
		t0_update_app = time.time()
		
		self.status = Role.STATUS_RUNNING
		
		while 1:
			for session in self.sessions.values():
				try:
					ts_id = RolePlatform.TS.getSessionID(session.user.name)
				except Exception, err:
					Logger.error("RDP server dialog failed ... exiting")
					Logger.debug("RDP server dialog: "+str(err))
					return
				
				if ts_id is None:
					if session.status in [Session.SESSION_STATUS_ACTIVE, Session.SESSION_STATUS_INACTIVE]:
						Logger.error("Weird, running session %s no longer exist"%(session.id))
						
						if session.status not in [Session.SESSION_STATUS_WAIT_DESTROY, Session.SESSION_STATUS_DESTROYED]:
							self.session_switch_status(session, Session.SESSION_STATUS_WAIT_DESTROY)
							self.sessions_spooler.put(("destroy", session))
					continue
				
				try:
					ts_status = RolePlatform.TS.getState(ts_id)
				except Exception,err:
					Logger.error("RDP server dialog failed ... exiting")
					return
				
				if session.status == Session.SESSION_STATUS_INITED:
					if ts_status is RolePlatform.TS.STATUS_LOGGED:
						self.session_switch_status(session, Session.SESSION_STATUS_ACTIVE)
						continue
						
					if ts_status is RolePlatform.TS.STATUS_DISCONNECTED:
						self.session_switch_status(session, Session.SESSION_STATUS_INACTIVE)
						continue
					
				if session.status == Session.SESSION_STATUS_ACTIVE and ts_status is RolePlatform.TS.STATUS_DISCONNECTED:
					self.session_switch_status(session, Session.SESSION_STATUS_INACTIVE)
					continue
				
				if session.status == Session.SESSION_STATUS_INACTIVE and ts_status is RolePlatform.TS.STATUS_LOGGED:
					self.session_switch_status(session, Session.SESSION_STATUS_ACTIVE)
					continue
			
			
			t1 = time.time()
			if t1-t0_update_app > 30:
				self.updateApplications()
				
				t0_update_app = time.time()
			else:
				time.sleep(1)
			
			#Logger.debug("ApplicationServer run loop")
		self.status = Role.STATUS_STOP
	
	
	def purgeGroup(self):
		while True:
			users = Platform.System.groupMember(self.ovd_group_name)
			
			if users is None:
				return False
			
			if users == []:
				return True
			
			for user in users:
				# todo : check if the users is connected, if yes logoff his session
				if not Platform.System.userRemove(user):
					return False
			
		return False
	
	
	@staticmethod
	def isMemberGroupOVD(login_):
		members = Platform.System.groupMember(ApplicationServer.ovd_group_name)
		if members is None:
			return False
		
		return login_ in members
	
	def getApplications(self):
		i = 0
		while self.applicationsXML is None:
			if i > 10:
				break
			i+= 1
			time.sleep(0.2)
			
		return self.applicationsXML
	
	def canManageApplications(self):
		return self.main_instance.ulteo_system
	
	
	def updateApplications(self):
		appsdetect = RolePlatform.ApplicationsDetection()
		self.applications = appsdetect.get()
		
		
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
	
	
	def getReporting(self, node):
		doc = Document()
		
		for (sid, session) in self.sessions.items():
			sessionNode = doc.createElement("session")
			sessionNode.setAttribute("id", sid)
			sessionNode.setAttribute("status", session.status)
			sessionNode.setAttribute("user", session.user.name)
			sessionNode.setAttribute("mode", session.mode)
			
			
			for instance, app_id in session.getUsedApplication().items():
				appNode = doc.createElement("instance")
				appNode.setAttribute("id", instance)
				appNode.setAttribute("application", app_id)
				sessionNode.appendChild(appNode)
			
			node.appendChild(sessionNode)
