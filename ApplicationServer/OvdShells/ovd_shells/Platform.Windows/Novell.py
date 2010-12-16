# -*- coding: utf-8 -*-

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

import win32api
import win32con
import win32file
import win32process

class Novell:
	def __init__(self):
		self.infos = None
	
	
	def perform(self):
		self.infos = self.parseRegistry()
		if self.infos is None:
			print "No Novell information in registry"
			return False
		
		#cmd = 'loginw32.exe %{server}s %{tree}s "%{login}s" /PWD "%{password}s" /CONT'%(self.infos)
		cmd = 'loginw32.exe %(login)s /PWD %(password)s /CONT'%(self.infos)
		#print "Novell cmd: '%s'"%(cmd)
		
		(hProcess, hThread, dwProcessId, dwThreadId) = win32process.CreateProcess(None, cmd, None , None, False, 0 , None, None, win32process.STARTUPINFO())
		win32file.CloseHandle(hThread)
		win32file.CloseHandle(hProcess)
	
	
	@staticmethod
	def parseRegistry():
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_CURRENT_USER, r"Software\ulteo\ovd\novell", 0, win32con.KEY_READ | win32con.KEY_QUERY_VALUE | win32con.KEY_SET_VALUE)
		except:
			return None
		
		infos = {}
		error = False
		for item in ["login", "password", "tree", "server"]:
			try:
				(infos[item], type_) = win32api.RegQueryValueEx(hkey, item)
				if type_ is not win32con.REG_SZ:
					raise Exception("item %s in not type REG_SZ"%(item))
			except Exception, err:
				print "Registry content error for shares: ",err
				error = True
		
		win32api.RegSetValueEx(hkey, "password", 0, win32con.REG_SZ, "*****")
		win32api.RegCloseKey(hkey)
		
		if error:
			return None
		
		return infos
