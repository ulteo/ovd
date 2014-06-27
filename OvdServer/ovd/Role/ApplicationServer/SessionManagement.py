# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2014 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011, 2014
# Author David LECHEVALIER <david@ulteo.com> 2011-2013
# Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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

from ovd.Logger import Logger
from ovd.Platform.System import System
from ovd.SingletonSynchronizer import SingletonSynchronizer

from Platform.Session import Session
from Platform.TS import TS

from multiprocessing import Process
import multiprocessing
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
		self.looping = True
		
		self.current_session_id = multiprocessing.Array("c", 30)
	
	
	def run(self):
		try:
			self.main()
		except Exception, err:
			from ovd.Config import Config
			import os
			import time
			import traceback
			try:
				f = open("%s-crash-%d.txt"%(Config.log_file, os.getpid()), "a")
				f.write("%s - Unhandled exception: \n"%(time.asctime()))
				f.write(traceback.format_exc())
				f.close()
			except IOError, err:
				pass
			
			raise err # Should we raise or continue at this point ??
	
	
	def main(self):
		self.synchronizer.restore()
		Logger._instance.setQueue(self.logging_queue, False)
		
		System.prepareForSessionActions()
		
		# Prevent the process to be stop by a keyboard interruption
		signal.signal(signal.SIGINT, signal.SIG_IGN)
		signal.signal(signal.SIGTERM, signal.SIG_IGN)
		
		Logger.debug("Starting SessionManager process")
		while True:
			self.current_session_id.value = ""
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
				self.current_session_id.value = session.id
				
				Logger.registerHook(session.log)
				self.create_session(session)
				Logger.unregisterHook(session.log)
				session.locked = False
				Logger.debug("Before put session %s in spool"%(session.id))
				self.queue_sync.put(session)
				Logger.debug("After put session %s in spool"%(session.id))
			elif request == "destroy":
				session = obj
				self.current_session_id.value = session.id
				Logger.registerHook(session.log)
				self.destroy_session(session)
				session.locked = False
				Logger.unregisterHook(session.log)
				self.queue_sync.put(session)
			elif request == "disconnect":
				session = obj
				self.disconnect_session(session)
				session.locked = False
				self.queue_sync.put(session)
			elif request == "logoff":
				session = obj
				self.destroy_user(session.user)
			elif request == "manage_new":
				session = obj
				self.current_session_id.value = session.id
				Logger.registerHook(session.log)
				self.manage_new_session(session)
				session.locked = False
				Logger.unregisterHook(session.log)
				self.queue_sync.put(session)
			elif request == "exit":
				break
		
		Logger.debug("SessionManager process stopped")


	def terminate(self):
		self.queue.put(("exit", None))
	
	
	def create_session(self, session):
		Logger.info("SessionManagement::create %s for user %s"%(session.id, session.user.name))
		
		if session.domain.manage_user():
			if System.userExist(session.user.name):
				Logger.error("unable to create session: user %s already exists"%(session.user.name))
				session.end_status = Session.SESSION_END_STATUS_ERROR
				session.switch_status(Session.SESSION_STATUS_ERROR)
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
				session.switch_status(Session.SESSION_STATUS_ERROR)
				return self.destroy_session(session)
			
			session.user.created = True
			
			try:
				rr = session.install_client()
			except Exception:
				Logger.exception("Unable to initialize session %s"%session.id)
				rr = False
			
			if rr is False:
				Logger.error("unable to initialize session %s"%(session.id))
				session.end_status = Session.SESSION_END_STATUS_ERROR
				session.switch_status(Session.SESSION_STATUS_ERROR)
				return self.destroy_session(session)
		
		else:
			# will be customize by a lock system when the users will connect in RDP
			if not session.domain.onSessionCreate():
				session.end_status = Session.SESSION_END_STATUS_ERROR
				session.switch_status(Session.SESSION_STATUS_ERROR)
				return False
		
		session.post_install()
		
		session.switch_status(Session.SESSION_STATUS_INITED)
	
	
	def destroy_session(self, session):
		Logger.info("SessionManagement::destroy %s"%(session.id))
		
		if session.user.created or not session.domain.manage_user():
			# Doesn't have to destroy the session if the user was never created
			
			try:
				sessid = TS.getSessionID(session.user.name)
			except Exception:
				Logger.exception("RDP server dialog failed ... ")
				return
			
			if sessid is not None:
				session.user.infos["tsid"] = sessid
			
			self.logoff_user(session.user)
		
		session.uninstall_client()
		
		if session.domain.manage_user():
			self.destroy_user(session.user)
		
		session.domain.onSessionEnd()
		session.switch_status(Session.SESSION_STATUS_DESTROYED)
	
	
	def disconnect_session(self, session):
		Logger.info("SessionManagement::disconnect %s"%(session.id))
		
		if session.user.created or not session.domain.manage_user():
			# Doesn't have to disconnect the session if the user was never created
			
			try:
				sessid = TS.getSessionID(session.user.name)
			except Exception:
				Logger.exception("RDP server dialog failed ... ")
				return False
			
			try:
				status = TS.getState(sessid)
			except Exception:
				Logger.exception("RDP server dialog failed ... ")
				return
			
			if status in [TS.STATUS_LOGGED]:
				Logger.info("must disconnect ts session %s user %s"%(sessid, session.user.name))
				
				try:
					TS.disconnect(sessid)
				except Exception:
					Logger.exception("RDP server dialog failed ... ")
					return
		
		session.switch_status(Session.SESSION_STATUS_INACTIVE)
	
	
	def logoff_user(self, user):
		Logger.info("SessionManagement::logoff_user %s"%(user.name))
		
		if user.infos.has_key("tsid"):
			sessid = user.infos["tsid"]
			
			try:
				status = TS.getState(sessid)
			except Exception:
				Logger.exception("RDP server dialog failed ... ")
				return
			
			if status in [TS.STATUS_LOGGED, TS.STATUS_DISCONNECTED]:
				Logger.info("must log off ts session %s user %s"%(sessid, user.name))
				
				try:
					TS.logoff(sessid)
				except Exception:
					Logger.exception("RDP server dialog failed ... ")
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
