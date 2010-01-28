# -*- coding: UTF-8 -*-

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

from threading import Thread

from ovd.Logger import Logger
from ovd.Platform import Platform
from ovd.Platform import Session
from ovd.Platform import TS


class SessionManagement(Thread):
	def __init__(self, aps_instance, queue):
		Thread.__init__(self)
		
		self.aps_instance = aps_instance
		self.queue = queue
	
	def run(self):
		while True:
			#Logger.debug("%s wait job"%(str(self)))
			(request, obj) = self.queue.get()
			
			if request == "create":
				session = obj
				self.create_session(session)
			elif request == "destroy":
				session = obj
				self.destroy_session(session)
			elif request == "logoff":
				user = obj
				self.destroy_user(user)
	
	
	def create_session(self, session):
		Logger.info("SessionManagement::create %s"%(session.id))
		
		if Platform.getInstance().userExist(session.user.name):
			Logger.error("unable to create session: user already exist")
			self.aps_instance.session_switch_status(session, Session.SESSION_STATUS_ACTIVE)
			return
		
		
		session.user.infos["groups"] = [self.aps_instance.ts_group_name, self.aps_instance.ovd_group_name]
		
		if True: # todo is parameter["mode"] == "desktop"
			session.user.infos["shell"] = "OvdDesktop"
		else:
			session.user.infos["shell"] = "OvdRemoteApps"
		
		rr = session.user.create()
		if rr is False:
			Logger.error("unable to create session")
			self.aps_instance.session_switch_status(session, Session.SESSION_STATUS_ACTIVE)
			return
		
		session.install_client()
		
		self.aps_instance.session_switch_status(session, Session.SESSION_STATUS_INITED)
	
	
	def destroy_session(self, session):
		Logger.info("SessionManagement::destroy %s"%(session.id))
		
		try:
			sessid = TS.getSessionID(session.user.name)
		except Exception,err:
			Logger.error("RDP server dialog failed ... ")
			Logger.debug("SessionManagement::destroy_session: %s"%(str(err)))
			return
		
		if sessid is not None:
			session.user.infos["tsid"] = sessid
		self.destroy_user(session.user)
		del(self.aps_instance.sessions[session.id])
		self.aps_instance.session_switch_status(session, Session.SESSION_STATUS_DESTROYED)
	
	
	def destroy_user(self, user):
		Logger.info("SessionManagement::logoff_user %s"%(user.name))
		
		if user.infos.has_key("tsid"):
			sessid = user.infos["tsid"]
			
			try:
				status = TS.getState(sessid)
			except Exception,err:
				Logger.error("RDP server dialog failed ... ")
				Logger.debug("SessionManagement::destroy_user: %s"%(str(err)))
				return
			
			if status in [TS.STATUS_LOGGED, TS.STATUS_DISCONNECTED]:
				Logger.info("must log off ts session %s user %s"%(sessid, user.name))
				
				try:
					TS.logoff(sessid)
				except Exception,err:
					Logger.error("RDP server dialog failed ... ")
					Logger.debug("SessionManagement::destroy_user: %s"%(str(err)))
					return
				
		
		user.destroy()
