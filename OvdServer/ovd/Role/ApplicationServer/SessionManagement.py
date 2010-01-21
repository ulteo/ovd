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
			(request, session) = self.queue.get()
			
			if request == "create":
				self.create_session(session)
			elif request == "destroy":
				self.destroy_session(session)
			elif request == "logoff":
				self.destroy_user(session)
	
	
	def create_session(self, session):
		Logger.info("SessionManagement::create %s"%(session["id"]))
		
		if Platform.getInstance().userExist(session["login"]):
			Logger.error("unable to create session: user already exist")
			self.aps_instance.session_switch_status(session, "error")
			return
		
		
		rr = Platform.getInstance().userAdd(session["login"], session["displayName"], session["password"], [self.aps_instance.ts_group_name, self.aps_instance.ovd_group_name])
		if rr is False:
			Logger.error("unable to create session")
			self.aps_instance.session_switch_status(session, "error")
			return
		
		s = Session(session)
		s.install_client()
		
		self.aps_instance.session_switch_status(session, "ready")
	
	
	def destroy_session(self, session):
		Logger.info("SessionManagement::destroy %s"%(session["id"]))
		
		sessid = TS.getSessionID(session["login"])
		if sessid is not None:
			session["tsid"] = sessid
		self.destroy_user(session)
		del(self.aps_instance.sessions[session["id"]])
	
	
	def destroy_user(self, session):
		Logger.info("SessionManagement::logoff_user %s"%(session["login"]))
		
		if session.has_key("tsid"):
			sessid = session["tsid"]
			
			status = TS.getState(sessid)
			if status in [TS.STATUS_LOGGED, TS.STATUS_DISCONNECTED]:
				Logger.info("must log off ts session %s user %s"%(sessid, session["login"]))
				
				TS.logoff(sessid)
		
		session["applications"] = []
		session["parameters"] = {}
		s = Session(session)
		s.uninstall_client()
		
		
		if Platform.getInstance().userExist(session["login"]):
			Logger.info("remove user %s "%(session["login"]))
		
			rr = Platform.getInstance().userRemove(session["login"])
			if rr is False:
				Logger.error("unable to remove session")
		
		session["status"] = "unknown"
