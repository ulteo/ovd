# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011
# Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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
from Session import Session

from multiprocessing import Process

import Util
import Queue
import signal
import threading

class SessionManagement(threading.Thread):
	
	def __init__(self, aps_instance, queue, queue_sync):
		threading.Thread.__init__(self)
		
		self.aps_instance = aps_instance
		self.queue = queue
		self.queue_sync = queue_sync
		
		self.looping = True
	
	
	def run(self):
		
		Logger.debug("Starting SessionManager process")
		while self.looping:
			try:
				(request, obj) = self.queue.get(True, 4)
			except Queue.Empty, e:
				continue
	
			if request == "create":
				session = obj
				self.create_session(session)
				self.queue_sync.put(session)
		
		Logger.debug("SessionManager process stopped")
	
	
	def create_session(self, session):
		Logger.info("SessionManagement::create %s for user %s"%(session.id, session.user.name))
		
		
		if System.userExist(session.user.name):
			Logger.error("unable to create session: user %s already exists"%(session.user.name))
			session.end_status = Session.SESSION_END_STATUS_ERROR
			self.aps_instance.session_switch_status(session, Session.SESSION_STATUS_ERROR)
			return self.destroy_session(session)
					
		rr = session.user.create()
		
		if rr is False:
			Logger.error("unable to create session for user %s"%(session.user.name))
			session.end_status = Session.SESSION_END_STATUS_ERROR
			self.aps_instance.session_switch_status(session, Session.SESSION_STATUS_ERROR)
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
			self.aps_instance.session_switch_status(session, Session.SESSION_STATUS_ERROR)
			return self.destroy_session(session)
		
		session.post_install()
		
		self.aps_instance.session_switch_status(session, Session.SESSION_STATUS_INITED)
		Logger.info("Role::SESSION READY")
