# -*- coding: utf-8 -*-

# Copyright (C) 2009-2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009-2010
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

import win32api
import win32ts

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.TS import TS as AbstractTS


class TS(AbstractTS):
	@staticmethod
	def getList():
		l = {}
		
		sessions = win32ts.WTSEnumerateSessions(None)
		for session in sessions:
			if not 0 < session["SessionId"] < 65536:
				continue
			
			login = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
			l[session["SessionId"]] = login
		
		return l
	
	
	@staticmethod
	def getSessionID(username_):
		domain_ = None
		if "@" in username_:
			(username_, domain_) = username_.split("@", 1)
		
		if domain_ is None:
			domain_ = win32api.GetComputerName()
		
		sessions = win32ts.WTSEnumerateSessions(None)
		for session in sessions:
			if not 0 < session["SessionId"] < 65536:
				continue
			
			try:
				state = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSConnectState)
			except:
				Logger.warn("Unable to list session %d"%(session["SessionId"]))
				continue
			
			
			login = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
			if login != username_:
				continue
			
			domain = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSDomainName)
			if domain.lower() != domain_.lower():
				Logger.debug("Session %s: ts session %d is not from the user %s but from a AD user"%(session["SessionId"], username_))
				continue
			
			return session["SessionId"]
		return None
	
	
	@staticmethod
	def getState(session_id):		
		state = win32ts.WTSQuerySessionInformation(None, session_id, win32ts.WTSConnectState)
		if state in [win32ts.WTSActive, win32ts.WTSConnected, win32ts.WTSInit]:
			return TS.STATUS_LOGGED

		if state == win32ts.WTSDisconnected:
			return TS.STATUS_DISCONNECTED
		
		return TS.STATUS_UNKNOWN
	
	
	@staticmethod
	def logoff(session_id):
		try:
			Logger.debug("perform_logoff: start logoff %d"%(session_id))
			ret = win32ts.WTSLogoffSession(None, session_id, True)
			Logger.debug("perform_logoff: finish logoff %d ret: %s"%(session_id, str(ret)))
		except Exception, e:
			Logger.warn("perform_logoff: exception %s"%(e))
		return True
