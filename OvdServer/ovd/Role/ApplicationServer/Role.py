# -*- coding: utf-8 -*-

# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2011
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011, 2012
# Author Laurent CLOUET <laurent@ulteo.com> 2010
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

import glob
import multiprocessing
import Queue
import os
import socket
import time
import threading
from xml.dom.minidom import Document

from ovd.Role.Role import Role as AbstractRole
from ovd.Logger import Logger
from ovd.Platform import Platform

from Apt import Apt
from Config import Config
from Dialog import Dialog
from Session import Session
from SessionManagement import SessionManagement
from Manager import Manager
from Platform import Platform as RolePlatform
import MPQueue


class Role(AbstractRole):
	sessions = {}
	sessions_spooler = None
	
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		self.dialog = Dialog(self)
		Logger._instance.close()
		self.sessions = {}
		self.locked_sessions = []
		self.sessions_spooler = MPQueue.Queue()
		self.sessions_spooler2 = MPQueue.Queue()
		self.sessions_sync = MPQueue.Queue()
		self.logging_queue = MPQueue.Queue()

		self.manager = Manager(self.main_instance.smRequestManager)
		self.threads = []
		
		self.applications = {}
		self.applications_id_SM = {}
		self.applications_mutex = threading.Lock()
		
		self.has_run = False
		
		self.static_apps = RolePlatform.ApplicationsStatic(self.main_instance.smRequestManager)
		self.static_apps_must_synced = False
		self.static_apps_lock = threading.Lock()
	
	
	def init(self):
		Logger.debug("ApplicationServer init")
		
		try:
			RolePlatform.TS.getList()
		except Exception, err:
			Logger.error("RDP server dialog failed ... exiting")
			Logger.debug("RDP server dialog: "+str(err))
			return
		
		if not Platform.System.groupExist(self.manager.ts_group_name):
			Logger.error("The group '%s' doesn't exist"%(self.manager.ts_group_name))
			return False
		
		if not Platform.System.groupExist(self.manager.ovd_group_name):
			if not Platform.System.groupCreate(self.manager.ovd_group_name):
				return False
		
		
		if not self.manager.purgeGroup():
			Logger.error("Unable to purge group")
			return False
		
		if Config.clean_dump_archive:
			self.purgeArchives()
		
		if Config.thread_count is None:
			cpuInfos = Platform.System.getCPUInfos()
			vcpu = cpuInfos[0]
			ram_total = Platform.System.getRAMTotal()
			ram = int(round(ram_total / 1024.0 / 1024.0))
			
			nb_thread = int(round(1 + (ram + vcpu * 2)/3))
		else:
			nb_thread = Config.thread_count

		Logger._instance.setQueue(self.logging_queue, True)
		Logger._instance.close()
		for _ in xrange(nb_thread):
			self.threads.append(SessionManagement(self.manager, self.sessions_spooler, self.sessions_spooler2, self.sessions_sync, self.logging_queue))
		
		if self.canManageApplications():
			self.apt = Apt()
			self.apt.init()
			self.threads.append(self.apt)
		
		return True
	
	@staticmethod
	def getName():
		return "ApplicationServer"
	
	
	def switch_to_production(self):
		self.setStaticAppsMustBeSync(True)
	
	
	def stop(self):
		for thread in self.threads:
			thread.terminate()
		
		for thread in self.threads:
			thread.join()
		
		self.update_locked_sessions()
		
		for session in self.sessions.values():
			self.manager.session_switch_status(session, Session.SESSION_STATUS_WAIT_DESTROY)
		
		Platform.System.prepareForSessionActions()
		
		cleaner = SessionManagement(self.manager, None, None, None, None)
		for session in self.sessions.values():
			session.end_status = Session.SESSION_END_STATUS_SHUTDOWN
			cleaner.destroy_session(session)
		
		self.manager.purgeGroup()
	
	
	
	def get_session_from_login(self, login_):
		for session in self.sessions.values():
			if session["login"] == login_:
				return session
		
		return None
	
	
	def run(self):
		self.updateApplications()
		self.has_run = True
		
		Logger._instance.lock.acquire()
		Logger._instance.close()
		for thread in self.threads:
			thread.start()
		Logger._instance.lock.release()
		
		t0_update_app = time.time()
		
		self.status = Role.STATUS_RUNNING
		
		while self.thread.thread_continue():
			while True:
				try:
					session = self.sessions_sync.get_nowait()
				except Queue.Empty, e:
					break
				except (EOFError, socket.error):
					Logger.debug("Role stopping")
					return
				
				if not self.sessions.has_key(session.id):
					Logger.warn("Session %s do not exist, session information are ignored"%(session.id))
					continue
				
				if session.status != self.sessions[session.id].status:
					self.manager.session_switch_status(session, session.status)
				
				if session.status == RolePlatform.Session.SESSION_STATUS_DESTROYED:
					del(self.sessions[session.id])
				else:
					self.sessions[session.id] = session

			self.update_locked_sessions()
			
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
						
						if session.status == Session.SESSION_STATUS_ACTIVE:
							# User has logged off
							session.end_status = Session.SESSION_END_STATUS_NORMAL
						
						if session.status not in [Session.SESSION_STATUS_WAIT_DESTROY, Session.SESSION_STATUS_DESTROYED, Session.SESSION_STATUS_ERROR]:
							self.manager.session_switch_status(session, Session.SESSION_STATUS_WAIT_DESTROY)
							self.spool_action("destroy", session.id)
					continue
				
				try:
					ts_status = RolePlatform.TS.getState(ts_id)
				except Exception,err:
					Logger.error("RDP server dialog failed ... exiting")
					Logger.debug("RDP server dialog: "+str(err))
					return
				
				if session.status == Session.SESSION_STATUS_INITED:
					if ts_status is RolePlatform.TS.STATUS_LOGGED:
						self.manager.session_switch_status(session, Session.SESSION_STATUS_ACTIVE)
						if not session.domain.manage_user():
							self.spool_action("manage_new", session.id)
						
						continue
						
					if ts_status is RolePlatform.TS.STATUS_DISCONNECTED:
						self.manager.session_switch_status(session, Session.SESSION_STATUS_INACTIVE)
						continue
					
				if session.status == Session.SESSION_STATUS_ACTIVE and ts_status is RolePlatform.TS.STATUS_DISCONNECTED:
					self.manager.session_switch_status(session, Session.SESSION_STATUS_INACTIVE)
					continue
				
				if session.status == Session.SESSION_STATUS_INACTIVE and ts_status is RolePlatform.TS.STATUS_LOGGED:
					self.manager.session_switch_status(session, Session.SESSION_STATUS_ACTIVE)
					if not session.domain.manage_user():
						self.spool_action("manage_new", session.id)
					continue
			
			
			if self.isStaticAppsMustBeSync():
				self.static_apps.synchronize()
				self.setStaticAppsMustBeSync(False)
			
			
			t1 = time.time()
			if t1-t0_update_app > 30:
				self.updateApplications()
				
				t0_update_app = time.time()
			else:
				time.sleep(1)
			
			#Logger.debug("ApplicationServer run loop")
		self.status = Role.STATUS_STOP
	
	
	def purgeArchives(self):
		for path in glob.glob(os.path.join(Config.general.spool_dir, "sessions dump archive", "*")):
			try:
				os.remove(path)
			except OSError, err:
				pass
	
	
	def setStaticAppsMustBeSync(self, value):
		try:
			self.static_apps_lock.acquire()
			
			self.static_apps_must_synced = (value is True)
		except:
			Logger.warn("Unable to lock mutex static apps")
		finally:
			self.static_apps_lock.release()
	
	
	def isStaticAppsMustBeSync(self):
		try:
			self.static_apps_lock.acquire()
			
			return self.static_apps_must_synced
		except:
			Logger.warn("Unable to lock mutex static apps")
			return False
		finally:
			self.static_apps_lock.release()
	
	
	def canManageApplications(self):
		return self.main_instance.ulteo_system
	
	
	def updateApplications(self):
		try:
			appsdetect = RolePlatform.ApplicationsDetection()
		except:
			Logger.warn("Bug #3: Unable to access to registry in the same time a user access to a session")
			appsdetect = None
			for i in xrange(3):
				try:
				  	appsdetect = RolePlatform.ApplicationsDetection()
				  	break
				except:
					pass
			
			if appsdetect is None:
				Logger.info("Unsuccefully build ApplicationDetection object in 4 times")
				return
		
		applications = appsdetect.get()
		if self.main_instance.ulteo_system:
			appsdetect.getDebianPackage(applications)
		
		known_ids = []
		
		self.applications_mutex.acquire()
		
		for id_, application in applications.items():
			known_ids.append(id_)
			
			if self.applications.has_key(id_):
				if self.applications[id_] == application:
					continue
			
			self.applications[id_] = application
		
		for id_ in self.applications.keys():
			if id_ in known_ids:
				continue
			
			del(self.applications[id_])
		self.applications_mutex.release()
	
	
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
	
	
	def update_locked_sessions(self):
		actions_to_delete = []
		for action in self.locked_sessions:
			(action_name, session_id) = action
			if not self.sessions.has_key(session_id):
				Logger.error("Session %s is not existing anymore !"%(session_id))
				actions_to_delete.append(action)
				continue
			
			session = self.sessions[session_id]
			if session.locked:
				continue
			
			session.locked = True
			self.sessions_spooler2.put((action_name, session))
			
			actions_to_delete.append(action)
		
		for action in actions_to_delete:
			self.locked_sessions.remove(action)		
	
	
	def spool_action(self, action, session_id):
		if not self.sessions.has_key(session_id):
			Logger.warn("Unable to spool %s on session %s, the session do not exist"%(action, session_id))
			return
		
		session = self.sessions[session_id]
		if session.locked:
			self.locked_sessions.append((action, session.id))
		else:
			session.locked = True
			self.sessions_spooler.put((action, session))

