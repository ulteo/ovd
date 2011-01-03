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
import time
import win32api
import win32con

import Reg
from Waiter import Waiter

from ovd.Role.ApplicationServer.DomainNovell import DomainNovell as AbstractDomainNovell

class DomainNovell(AbstractDomainNovell):
	def onSessionStarts(self):
		if not self.zenworks:
			return True
		
		mylock = Waiter(self.session)
		
		t0 = time.time()
		while mylock.init() is False:
			d = time.time() - t0
			if d>20:
				return False
			
			time.sleep(0.5)
		
		self.session.set_user_profile_directories(mylock.userprofile, mylock.appdata)
		
		self.session.init_user_session_dir(os.path.join(mylock.appdata, "ulteo", "ovd"))
		
		return mylock.unlock()
	
	
	def doCustomizeRegistry(self, hive):
		if self.zenworks:
			return True
		
		path = hive+r"\Software\Ulteo\ovd\novell"
		Reg.CreateKeyR(win32con.HKEY_USERS, path)
		
		hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		for item in self.account.keys():
			win32api.RegSetValueEx(hkey, item, 0, win32con.REG_SZ, self.account[item])
		win32api.RegCloseKey(hkey)
		
		
		# http://www.novell.com/support/viewContent.do?externalId=3576402&sliceId=1
		# Problem when autogenerate users with same login but different SID
		if self.is_nici_well_configured():
			return True
		
		return self.configure_nici()

	
	@staticmethod
	def is_nici_well_configured():
		hkey = None
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, r"Software\Novell\NICI", 0, win32con.KEY_READ | win32con.KEY_QUERY_VALUE)
			(value_, type_) = win32api.RegQueryValueEx(hkey, "EnableUserProfileDirectory")
		except:
			return False
		finally:
			if hkey is not None:
				win32api.RegCloseKey(hkey)
		
		if type_ is not win32con.REG_DWORD:
			return False
		
		if value_ != 1:
			return False
		
		return True
	
	
	@staticmethod
	def configure_nici():
		path = r"Software\Novell\NICI"
		hkey = None
		try:
			Reg.CreateKeyR(win32con.HKEY_LOCAL_MACHINE, path)
			
			hkey = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, path, 0, win32con.KEY_SET_VALUE)
			
			win32api.RegSetValueEx(hkey, "EnableUserProfileDirectory", 0, win32con.REG_DWORD, 1)
		except:
			return False
		finally:
			  if hkey is not None:
				win32api.RegCloseKey(hkey)
		
		return True
