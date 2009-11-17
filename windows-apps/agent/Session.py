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

from xml.dom import minidom
import threading
import win32ts

from Logger import Logger
from SessionManagement import SessionManagement
from User import User


class Session:
	UNKNOWN = 0
	UNINITIALIZED = 1
	INITIALIZED = 2
	LOGGED = 3
	DISCONNECTED = 4
	DESTROYING = 5
	DESTROYED = 6
	
	def __init__(self, xmlContent):
		self.thread = None
		self.user = None
		self.parseXML(xmlContent)


	def init(self):
		self.user = User(self.userParams[0], self.userParams[1], self.userParams[2], SessionManagement.USERS_GROUPS_NAME)
		Logger.info("Session %s inited (user: %s)"%(self.id, self.user.login))
		

	def getState(self):		
		if self.thread is not None:
			if self.thread.isAlive():
				return self.DESTROYING
			return self.DESTROYED
		
		
		if self.user is None:
			return self.UNINITIALIZED
		
		instance = self.getTsInstance()
		if instance is None:
			return self.INITIALIZED
		
		state = win32ts.WTSQuerySessionInformation(None, instance, win32ts.WTSConnectState)
		if state in [win32ts.WTSActive, win32ts.WTSConnected, win32ts.WTSInit]:
			return self.LOGGED

		if state == win32ts.WTSDisconnected:
			return self.DISCONNECTED
		
		return self.UNKNOWN

	def getTsInstance(self):
		sessions = win32ts.WTSEnumerateSessions(None)
		for session in sessions:
			if not 0 < session["SessionId"] < 65536:
				continue
			login = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
			if self.user.login != login:
				continue
			
			return session["SessionId"]
		return None

	def exitTsSession(self):
		instance = self.getTsInstance()
		if instance is None:
			Logger.debug("Session (%s) has no active ts instance"%(self.id))
			return True
		try:
			win32ts.WTSLogoffSession(None, instance, True)
		except Exception, e:
			Logger.warn("Session %s: excpetion at logoff (%s)"%(self.id, str(e)))
			return False
		return True
		
	def orderDestroy(self):
		self.thread = threading.Thread(target=self.destroy)
		self.thread.start()

	def destroy(self):
		try:
			if self.user is not None:
				self.exitTsSession()
				self.user.destroy()
		except Exception,e:
			Logger.warn("Session %s destroy: error (%s)"%(self.id, str(e)))
			return
			
		Logger.info("Session (%s) destroyed"%(self.id))
			
	def parseXML(self, xmlContent):
		document = minidom.parseString(xmlContent)
		sessionNode = document.documentElement
		
		if sessionNode.nodeName != "session":
			raise Exception("invalid root node")
		
		if not sessionNode.hasAttribute("id"):
			raise Exception("invalid root node")
				
		self.id = sessionNode.getAttribute("id")
		if len(self.id)==0:
			raise Exception("Missing attribute id")

		userNode = sessionNode.firstChild
		#print "3"
		if userNode.nodeName != "user":
			raise Exception("invalid child node")
		#print "4"
		self.userParams = []
		for attr in ["login", "password", "displayName"]:
			if not userNode.hasAttribute(attr):
				raise Exception("invalid child node: missing attribute "+attr)
			self.userParams.append(userNode.getAttribute(attr))


