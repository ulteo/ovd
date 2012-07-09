# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011, 2012
# Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
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
from ovd.Platform import Platform
from ovd.SingletonSynchronizer import SingletonSynchronizer

from Platform import Platform as RolePlatform
from Session import Session

from multiprocessing import Process
import Queue
import signal

class SessionManagement(Process):
	def __init__(self, aps_instance, queue, queue2, queue_sync, logging_queue):
		Process.__init__(self)
		
		self.aps_instance = aps_instance
		self.queue = queue
		self.queue2 = queue2
		self.queue_sync = queue_sync
		self.logging_queue = logging_queue

		self.synchronizer = SingletonSynchronizer()
		self.synchronizer.backup()

	def run(self):
		self.synchronizer.restore()
		Logger._instance.setQueue(self.logging_queue, False)
		loop = True

		Platform.System.prepareForSessionActions()
		
		# Prevent the process to be stop by a keyboard interruption
		def quit(machin, truc):
			global loop
			loop = False
		signal.signal(signal.SIGINT, signal.SIG_IGN)
		signal.signal(signal.SIGTERM, quit)
		
		Logger.debug("Starting SessionManager process")
		while loop:
			try:
				(request, obj) = self.queue2.get_nowait()
			except Queue.Empty:
				try:
					(request, obj) = self.queue.get(True, 4)
				except Queue.Empty, e:
					continue
				except IOError, e:
					if e.errno == 4:
						break
					else:
						raise e
			
			if request == "create":
				session = obj
				self.create_session(session)
				session.locked = False
				self.queue_sync.put(session)
			elif request == "destroy":
				session = obj
				self.destroy_session(session)
				session.locked = False
				self.queue_sync.put(session)
			elif request == "logoff":
				user = obj
				self.destroy_user(user)
			elif request == "manage_new":
				session = obj
				self.manage_new_session(session)
				session.locked = False
				self.queue_sync.put(session)
	
	
	def create_session(self, session):
		Logger.info("SessionManagement::create %s for user %s"%(session.id, session.user.name))
		
		if session.domain.manage_user():
			if Platform.System.userExist(session.user.name):
				Logger.error("unable to create session: user %s already exists"%(session.user.name))
				session.end_status = Session.SESSION_END_STATUS_ERROR
				session.switch_status(RolePlatform.Session.SESSION_STATUS_ERROR)
				return self.destroy_session(session)
			
			session.user.infos["groups"] = [self.aps_instance.ts_group_name, self.aps_instance.ovd_group_name]
			
			if session.mode == "desktop":
				session.user.infos["shell"] = "OvdDesktop"
			else:
				session.user.infos["shell"] = "OvdRemoteApps"
			
			rr = session.user.create()
			if rr is False:
				Logger.error("unable to create session for user %s"%(session.user.name))
				session.end_status = Session.SESSION_END_STATUS_ERROR
				session.switch_status(RolePlatform.Session.SESSION_STATUS_ERROR)
				return self.destroy_session(session)
			
			session.user.created = True
			
			try:
				rr = session.install_client()
			except Exception,err:
				Logger.debug("Unable to initialize session %s: %s"%(session.id, str(err)))
				rr = False
			
			if rr is False:
				Logger.error("unable to initialize session %s"%(session.id))
				session.end_status = Session.SESSION_END_STATUS_ERROR
				session.switch_status(RolePlatform.Session.SESSION_STATUS_ERROR)
				return self.destroy_session(session)
		
		else:
			# will be customize by a lock system when the users will connect in RDP
			pass
		
		session.post_install()
		
		session.switch_status(RolePlatform.Session.SESSION_STATUS_INITED)
	
	
	def destroy_session(self, session):
		Logger.info("SessionManagement::destroy %s"%(session.id))
		
		if session.user.created or not session.domain.manage_user():
			# Doesn't have to destroy the session if the user was never created
			
			try:
				sessid = RolePlatform.TS.getSessionID(session.user.name)
			except Exception,err:
				Logger.error("RDP server dialog failed ... ")
				Logger.debug("SessionManagement::destroy_session: %s"%(str(err)))
				return
			
			if sessid is not None:
				session.user.infos["tsid"] = sessid
			
			self.logoff_user(session.user)

		session.uninstall_client()
		
		if session.domain.manage_user():
			self.destroy_user(session.user)
		
		session.switch_status(RolePlatform.Session.SESSION_STATUS_DESTROYED)
	
	
	def logoff_user(self, user):
		Logger.info("SessionManagement::logoff_user %s"%(user.name))

		if user.infos.has_key("tsid"):
			sessid = user.infos["tsid"]

			try:
				status = RolePlatform.TS.getState(sessid)
			except Exception,err:
				Logger.error("RDP server dialog failed ... ")
				Logger.debug("SessionManagement::logoff_user: %s"%(str(err)))
				return

			if status in [RolePlatform.TS.STATUS_LOGGED, RolePlatform.TS.STATUS_DISCONNECTED]:
				Logger.info("must log off ts session %s user %s"%(sessid, user.name))

				try:
					RolePlatform.TS.logoff(sessid)
				except Exception,err:
					Logger.error("RDP server dialog failed ... ")
					Logger.debug("SessionManagement::logoff_user: %s"%(str(err)))
					return
				
				del(user.infos["tsid"])
	
	
	def destroy_user(self, user):
		Logger.info("SessionManagement::destroy_user %s"%(user.name))
		
		if user.infos.has_key("tsid"):
			self.logoff_user(user)
		
		user.destroy()
	
	
	def manage_new_session(self, session):
		Logger.info("SessionManagement::manage_new_session %s for user %s"%(session.id, session.user.name))
		
		session.domain.onSessionStarts()
