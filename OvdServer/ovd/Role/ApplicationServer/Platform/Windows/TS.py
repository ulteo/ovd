# -*- coding: utf-8 -*-

# Copyright (C) 2009-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2010-2011
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

import os
import pywintypes
import time
import win32api
import win32security
import win32ts

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Config import Config
from ovd.Role.ApplicationServer.TS import TS as AbstractTS


class TS(AbstractTS):
	@staticmethod
	def getUsersGroup():
		try:
			sid = win32security.ConvertStringSidToSid("S-1-5-32-555")
			name, _, _ = win32security.LookupAccountSid(None, sid)
		except Exception, err:
			Logger.error("TS::getUsersGroup unable to found remote users group replacing by default name")
			return "Remote Desktop Users"
		
		return name
	
	@staticmethod
	def getList():
		l = []
		
		sessions = win32ts.WTSEnumerateSessions(None)
		for session in sessions:
			if not 0 < session["SessionId"] < 65536:
				continue
			
			l.append(session["SessionId"])
		
		return l
	
	
	@staticmethod
	def getSessionID(username_):
		domain_ = None
		if "@" in username_:
			(username_, domain_) = username_.split("@", 1)
		
		localdomain = win32api.GetComputerName()
		
		sessions = win32ts.WTSEnumerateSessions(None)
		session_closing = []
		
		for session in sessions:
			if not 0 < session["SessionId"] < 65536:
				continue
			
			try:
				login = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
				if login.lower() != username_.lower():
					continue
				
				domain = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSDomainName)
				if domain_ is not None and domain.lower() == localdomain.lower():
					Logger.debug("Ts session %d is not from the domain user %s but from a local user"%(session["SessionId"], username_))
					continue
				
				elif domain_ is None and domain.lower() != localdomain.lower():
					Logger.debug("Ts session %d is not from the local user %s but from a domain user"%(session["SessionId"], username_))
					continue
				
				if Config.checkShell:
					shell = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSInitialProgram)
					if not os.path.basename(shell).lower().startswith("ovd"):
						Logger.debug("Ts session %d is not relative to OVD"%(session["SessionId"]))
						continue
				
			except pywintypes.error, err:
				if err[0] == 7007: # A close operation is pending on the session.
					session_closing.append(session)
				if err[0] == 7022: # Session not found.
					continue
				else:
					Logger.warn("Unable to list session %d"%(session["SessionId"]))
					Logger.debug("WTSQuerySessionInformation returned %s"%(err))
				continue
			
			return session["SessionId"]
		
		t0 = time.time()
		len1 = len(session_closing)
		len2 = 0
		while len1>0 and time.time()-t0 < 10:
			if len2 == len1:
				time.sleep(0.2)
			len2 = len1
			
			for i in xrange(len(session_closing)):
				session = session_closing.pop(0)
				
				try:
					login = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
					if login != username_:
						continue
					
					domain = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSDomainName)
					if domain.lower() != domain_.lower():
						Logger.debug("Ts session %d is not from the user %s but from a AD user"%(session["SessionId"], username_))
						continue

					if Config.checkShell:
						shell = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSInitialProgram)
						if not os.path.basename(shell).lower().startswith("ovd"):
							Logger.debug("Ts session %d is not relative to OVD"%(session["SessionId"]))
							continue
					
				except pywintypes.error, err:
					if err[0] == 7007: # A close operation is pending on the session.
						Logger.debug("TS session %d close operation pending"%(session["SessionId"]))
						session_closing.append(session)
					continue
				
				return session["SessionId"]
			
			len1 = len(session_closing)
		
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
			return False
		return True
