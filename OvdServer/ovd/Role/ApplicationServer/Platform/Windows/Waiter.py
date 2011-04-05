# -*- coding: UTF-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author David LECHEVALIER <david@ulteo.com> 2011
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

import locale
import time
import win32api
import win32con
import win32security

import os
import Reg

from ovd.Logger import Logger


class Waiter():
	def __init__(self, session):
		self.session = session
		self.sid = None
		self.userprofile = None
		self.appdata = None
		self.lockFile = None
		self.userDir = {}
	
	
	def init(self):
		try:
			sid, _, _ = win32security.LookupAccountName(None, self.session.user.name)
			self.sid = win32security.ConvertSidToStringSid(sid)
		except Exception, err:
			return False
		if not self._get_userprofile_directory():
			return False
		
		if not self._get_usershellfolders():
			return False
		
		if not self._get_lockfile():
			return False
		
		if not self.is_locked():
			return False
		return True
	
	
	def is_locked(self):
		if os.path.exists(self.lockFile):
			return True
		return False	


	def _get_lockfile(self):
		self.lockFile = os.path.join(self.userDir["AppData"], "ulteo", "ulock")
		return True
	
	
	def unlock(self):
		try:
			os.remove(self.lockFile)
		except Exception, e:
			Logger.warn("Unable to remove lock "+str(e))
		return True
	
	
	def _get_userprofile_directory(self):
		path = r"Software\Microsoft\Windows NT\CurrentVersion\ProfileList\%s"%(self.sid)
		
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, path, 0, win32con.KEY_QUERY_VALUE)
		except:
			return False
		
		try:
			value_,type_ = win32api.RegQueryValueEx(hkey, "ProfileImagePath")
		except:
			return False
		finally:
			win32api.RegCloseKey(hkey)
		
		value_ = win32api.ExpandEnvironmentStrings(value_)
		self.userprofile = value_
		
		return True
	
	
	def _get_usershellfolders(self):
		path = self.sid+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders"
		
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_QUERY_VALUE)
		except:
			return False
		
		(_, encoding) = locale.getdefaultlocale()
		if encoding is None:
			encoding = "UTF8"
		
		error = False
		for value in ["AppData", "Desktop", "Programs"]:
			try:
				value_,type_ = win32api.RegQueryValueEx(hkey, value)
			except:
				error = True
				break
			
			if self.userprofile is not None:
				value_ = value_.replace("%USERPROFILE%", self.userprofile)
			value_ = win32api.ExpandEnvironmentStrings(value_)
			
			if type(value_) is not unicode:
				value_ = unicode(value_, encoding)			

			self.userDir[value] = value_
		
		win32api.RegCloseKey(hkey)
		if error:
			return False
		
		return True
