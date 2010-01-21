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
import win32con

import win32security



def testMe():
	import os
	import _winreg
	directory = r"C:\Documents and Settings\tt"
	
	registryFile = os.path.join(directory, "NTUSER.DAT")
	
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


	hiveName = "testme"
	
	# Load the hive
	_winreg.LoadKey(win32con.HKEY_USERS, hiveName, registryFile)
	
	disableActiveSetup(hiveName)
	
	win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName)


def disableActiveSetup(rootPath):
	path = r"Software\Microsoft\Active Setup"
	hkey_src = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, path, 0, win32con.KEY_ALL_ACCESS)
	
	path = r"%s\%s"%(rootPath, path)
	hkey_dst = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_ALL_ACCESS)
	
	try:
		CopyTree(hkey_src, "Installed Components", hkey_dst)
	except Exception, err:
		import traceback
		import sys
		exception_type, exception_string, tb = sys.exc_info()
		trace_exc = "".join(traceback.format_tb(tb))
		print trace_exc
		print exception_string
	
	win32api.RegCloseKey(hkey_src)
	win32api.RegCloseKey(hkey_dst)


def CopyTree(KeySrc, SubKey, KeyDest):
	#print "delete: ",SubKey
	#DeleteTree(KeyDest, SubKey)
	
	win32api.RegCreateKey(KeyDest, SubKey)
	hkey_src = win32api.RegOpenKey(KeySrc, SubKey, 0, win32con.KEY_ALL_ACCESS)
	hkey_dst = win32api.RegOpenKey(KeyDest, SubKey, 0, win32con.KEY_ALL_ACCESS)
	
	index = 0
	while True:
		try:
			(string, object, type) = RegEnumValue(hkey_src, index)
			
			win32api.RegSetValueEx(hkey_dst, string, 0, type, object)
		except:
			break
	
	
	index = 0
	while True:
		try:
			buf = win32api.RegEnumKey(KeySrc, index)
			index+= 1
			
			CopyTree(hkey_src, buf, hkey_dst)
		except:
			break
	win32api.RegCloseKey(hkey_src)
	win32api.RegCloseKey(hkey_dst)



def DeleteTree(key, subkey, deleteRoot = True):
	hkey = win32api.RegOpenKey(key, subkey, 0, win32con.KEY_ALL_ACCESS)
	
	index = 0
	while True:
		try:
			buf = win32api.RegEnumKey(hkey, index)
			index+= 1
	
			Session.RegDeleteTree(hkey, buf)
		except:
			break
		
	win32api.RegCloseKey(hkey)
	
	
	if deleteRoot:
		win32api.RegDeleteKey(key, subkey)


def getActiveSetupKeys():
	path = r"Software\Microsoft\Active Setup\Installed Components"
	
	keys = {}

	hkey = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, path, 0, win32con.KEY_ENUMERATE_SUB_KEYS|win32con.KEY_QUERY_VALUE)
	index = 0
	while True:
		try:
			k = win32api.RegEnumKey(hkey, index)
			index+= 1
			
			keys[k] = "1.0.0.0"
		except Exception,e:
			#print "error: ",e
			break
	win32api.RegCloseKey(hkey)
	
	for k in keys.keys():
		p = r"%s\%s"%(path, k)
		hkey = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, p, 0, win32con.KEY_QUERY_VALUE)
		
		try:
			(version, _) = win32api.RegQueryValueEx(hkey, "Version")
		except Exception,e:
			print "error: ",e
			win32api.RegCloseKey(hkey)
			continue
		
		win32api.RegCloseKey(hkey)
		
		keys[k] = version
	
	return keys

def disableActiveSetup22(rootPath):
	path = r"%s\Software\Microsoft\Active Setup"%(rootPath)
	hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_ALL_ACCESS)
	
	DeleteTree(hkey, "Installed Components", False)
	win32api.RegCloseKey(hkey)

	objs = getActiveSetupKeys()
	
	for k,v in objs.items():
		version = v
	
		path = r"%s\Software\Microsoft\Active Setup\Installed Components"%(rootPath)
		hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_ALL_ACCESS)
		win32api.RegCreateKey(hkey, k)
		win32api.RegCloseKey(hkey)
		
		path = r"%s\%s"%(path, k)
		hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_ALL_ACCESS)
		win32api.RegSetValueEx(hkey, "Version", 0, win32con.REG_SZ, version)
		win32api.RegCloseKey(hkey)
