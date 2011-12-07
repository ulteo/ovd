# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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
import win32api
import win32con
import win32process
import win32file

from ovd_shells.ExternalAppsClient import ExternalAppsClient as AbstractExternalAppsClient


class ExternalAppsClient(AbstractExternalAppsClient):
	def launch(self):
		java_cmd = self.detectJavaw()
		if java_cmd is None:
			java_cmd = self.detectEmbededJava()
			
			if java_cmd is None:
				print "No JRE available from registry nor in $PATH"
				return False
		
		
		jar_location = r"OVDExternalAppsClient.jar"
		folder = self.detectJarFolder(jar_location)
		if folder is None:
			print "No OVD integrated client installed on the system"
			return None
		
		java_cmd = java_cmd.replace("%1", jar_location)
		java_cmd = java_cmd.replace("%*", '-c "%s" -o "%s"'%(self.configuration_file, self.log_file))
		
		(hProcess, hThread, dwProcessId, dwThreadId) = win32process.CreateProcess(None, java_cmd, None , None, False, win32process.CREATE_NO_WINDOW , None, folder, win32process.STARTUPINFO())
		win32file.CloseHandle(hProcess)
		win32file.CloseHandle(hThread)
		  
		return True 
	
	
	@classmethod
	def detectJavaw(cls):
		key = None
		
		try:
			key = win32api.RegOpenKey(win32con.HKEY_CLASSES_ROOT, r"Applications\javaw.exe\shell\open\command", 0, win32con.KEY_READ)
			data = win32api.RegQueryValue(key, None)
		except Exception, err:
			return None
		
		finally:
			if key is not None:
				win32api.RegCloseKey(key)
		
		if data is not None:
			return data.replace("javaw", "java")
		
		return None
	
	
	@classmethod
	def detectEmbededJava(cls):
		dirs = os.environ["PATH"].split(";")
		dirs.insert(0, os.path.abspath(os.path.curdir))
		
		for d in dirs:
			path = os.path.join(d, r"jre\bin\java.exe")
			if os.path.exists(path):
				print "Found java in '%s'"%(path)
				return '"'+path+'" -jar "%1" %*'
		
		return None
	
	
	@classmethod
	def detectJarFolder(cls, jar):
		dirs = os.environ["PATH"].split(";")
		dirs.insert(0, os.path.abspath(os.path.curdir))
		
		for d in dirs:
			path = os.path.join(d, jar)
			if os.path.exists(path):
				return d
		
		return None
