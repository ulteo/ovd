# -*- coding: UTF-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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
import random
import win32api
import win32con
import win32security
import _winreg

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Profile import Profile as AbstractProfile

class Profile(AbstractProfile):	
	def init(self):
		pass
	
	def mount(self):
		registryFile = os.path.join(self.session.windowsProfileDir, "NTUSER.DAT")
		
		# Get some privileges to load the hive
		priv_flags = win32security.TOKEN_ADJUST_PRIVILEGES | win32security.TOKEN_QUERY
		hToken = win32security.OpenProcessToken (win32api.GetCurrentProcess (), priv_flags)
		backup_privilege_id = win32security.LookupPrivilegeValue (None, "SeBackupPrivilege")
		restore_privilege_id = win32security.LookupPrivilegeValue (None, "SeRestorePrivilege")
		win32security.AdjustTokenPrivileges (
			hToken, 0, [
			(backup_privilege_id, win32security.SE_PRIVILEGE_ENABLED),
			(restore_privilege_id, win32security.SE_PRIVILEGE_ENABLED)
			]
		)
		
		hiveName = "OVD_%d"%(random.randrange(10000, 50000))
		
		# Load the hive
		_winreg.LoadKey(win32con.HKEY_USERS, hiveName, registryFile)
	
		key = win32api.RegOpenKey(win32con.HKEY_USERS, hiveName+r"\Software", 0, win32con.KEY_SET_VALUE)
		win32api.RegCreateKey(key, r"Ulteo")
		win32api.RegCloseKey(key)
		
		key = win32api.RegOpenKey(win32con.HKEY_USERS, hiveName+r"\Software\Ulteo", 0, win32con.KEY_SET_VALUE)
		win32api.RegCreateKey(key, r"ovd")
		win32api.RegCloseKey(key)
		
		key = win32api.RegOpenKey(win32con.HKEY_USERS, hiveName+r"\Software\Ulteo\ovd", 0, win32con.KEY_SET_VALUE)
		win32api.RegSetValueEx(key, "profile_host", 0, win32con.REG_SZ, self.host)
		win32api.RegSetValueEx(key, "profile_directory", 0, win32con.REG_SZ, self.directory)
		win32api.RegSetValueEx(key, "profile_login", 0, win32con.REG_SZ, self.login)
		win32api.RegSetValueEx(key, "profile_password", 0, win32con.REG_SZ, self.password)
		win32api.RegCloseKey(key)
		
		
		# Rediect the Shell Folders to the remote profile
		path = hiveName+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders"
		
		key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		win32api.RegSetValueEx(key, "Desktop", 0, win32con.REG_SZ, r"U:\Desktop")
		win32api.RegSetValueEx(key, "Personal", 0, win32con.REG_SZ, r"U:\My Documents")
		win32api.RegCloseKey(key)
		
		# Unload the hive
		win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName)
	
	
	def umount(self):
		pass

