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

import win32api
import win32net
import win32con
import os

from Logger import Logger

class SessionManagement:
	ULTEO_GROUP = "Ulteo OVD Users"
	USERS_GROUPS_NAME = ["Ulteo OVD Users", "Remote Desktop Users"]
	
	def __init__(self, daemonInstance):
		self.daemonInstance = daemonInstance
		self.sessions = {}
	
	def initialize(self):
		Logger.debug("SessionManagement initialize")
		if not self.existGroupOVD():
			if not self.createGroupOVD():
				return False
		
		if not self.purgeGroupOVD():
			Logger.error("Unable to purge group")
			return False
		
		return True

	def exist(self, session_id):
		return self.sessions.has_key(session_id)

	def get(self, session_id):
		return self.sessions[session_id]

	def add(self, session):
		self.sessions[session.id] = session
	
	def delete(self, session):
		del(self.sessions[session.id])

	def createGroupOVD(self):
		try:
			data = {}
			data['name'] = SessionManagement.ULTEO_GROUP
			data['comment'] = 'Group included all the OVD user. Users are auto generated and auto deleted'
		
			win32net.NetLocalGroupAdd(None, 1, data)
		except win32net.error, e:
			Logger.error("SessionManagement createGroupOVD: '%s'"%(str(e)))
			return False

		return True

	@staticmethod
	def existGroupOVD():
		try:
			win32net.NetLocalGroupGetInfo(None, SessionManagement.ULTEO_GROUP, 0)
		except win32net.error, e:
			Logger.error("SessionManagement existGroupOVD: '%s'"%(str(e)))
			return False
		
		return True
	
	@staticmethod
	def purgeGroupOVD():
		try:
			(users, _, _) = win32net.NetLocalGroupGetMembers(None, SessionManagement.ULTEO_GROUP, 1)
			for user in users:
				win32net.NetUserDel(None, user['name'])
		except win32net.error, e:
			Logger.error("SessionManagement purgeGroupOVD: '%s'"%(str(e)))
			return False
		
		return True
	
	
	@staticmethod
	def isMemberGroupOVD(login_):
		try:
			members, _,_ = win32net.NetLocalGroupGetMembers(None, SessionManagement.ULTEO_GROUP, 0)
		except win32net.error, e:
			Logger.error("SessionManagement isMemberGroupOVD: '%s'"%(str(e)))
			return False
		
		for user in members:
			if user["name"] == login_:
				return True
		
		return False
	
	
	@staticmethod
	def DeleteDirectory(path):
		for file in os.listdir(path):
			filename = os.path.join(path, file)
			win32api.SetFileAttributes(filename, win32con.FILE_ATTRIBUTE_NORMAL)
			if os.path.isdir(filename):
				DeleteDirectoryR(filename) 
			else:
				os.remove(filename)
		win32api.SetFileAttributes(path, win32con.FILE_ATTRIBUTE_NORMAL)
		os.rmdir(path)
