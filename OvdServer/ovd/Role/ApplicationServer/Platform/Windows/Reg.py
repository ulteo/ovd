# -*- coding: UTF-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009-2010
# Author David LECHEVALIER <david@ulteo.com> 2010
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
import win32security
import _winreg

from ovd.Logger import Logger


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
		Logger.error("disableActiveSetup: %s => %s"%(exception_string, trace_exc))
	
	win32api.RegCloseKey(hkey_src)
	win32api.RegCloseKey(hkey_dst)


def CopyTree(KeySrc, SubKey, KeyDest):
	win32api.RegCreateKey(KeyDest, SubKey)
	hkey_src = win32api.RegOpenKey(KeySrc, SubKey, 0, win32con.KEY_ALL_ACCESS)
	hkey_dst = win32api.RegOpenKey(KeyDest, SubKey, 0, win32con.KEY_ALL_ACCESS)
	
	index = 0
	while True:
		try:
			(string, object, type) = win32api.RegEnumValue(hkey_src, index)
			index+= 1
			
			win32api.RegSetValueEx(hkey_dst, string, 0, type, object)
		except:
			break
	
	
	index = 0
	while True:
		try:
			buf = win32api.RegEnumKey(hkey_src, index)
			index+= 1
			
			CopyTree(hkey_src, buf, hkey_dst)
		except:
			break
	win32api.RegCloseKey(hkey_src)
	win32api.RegCloseKey(hkey_dst)

def OpenKeyCreateIfDoesntExist(root, path):
	try:
		key = _winreg.OpenKey(root, path,0, _winreg.KEY_SET_VALUE)
	except Exception, err:
		key = None
	
	if key is not None:
		return key
	else:
		keyName = os.path.basename(path)
		parentPath = os.path.dirname(path)
		if len(parentPath) == 0:
			parentPath = None
		
		need_to_create_son = False
		if parentPath is not None:
			key = OpenKeyCreateIfDoesntExist(root, parentPath)
			if key is not None:
				need_to_create_son = True
			else:
				return None
		
		if need_to_create_son:
			try:
				_winreg.CreateKey(key, keyName)
			except Exception, err:
				return None
		
		return key

def ProcessActiveSetupEntry(BaseKey, Entry, Username):
	hkey = win32api.RegOpenKey(BaseKey, Entry, 0, win32con.KEY_ALL_ACCESS)
	
	try:
		win32api.RegQueryValueEx(hkey, "StubPath")
	except:
		win32api.RegCloseKey(hkey)
		return False

	try:
		(string, type) = win32api.RegQueryValueEx(hkey, "IsInstalled")
		if not (string == 1 or string == '\x01\x00\x00\x00'):
			win32api.RegCloseKey(hkey)
			return False
	except:
		pass
	try:
		(string, type) = win32api.RegQueryValueEx(hkey, "CloneUser")
		if string == 1 or string == '\x01\x00\x00\x00' :
			win32api.RegSetValueEx(hkey, 'Username', 0, win32con.REG_SZ, Username)
	except :
		pass

	win32api.RegCloseKey(hkey)
	return True

def UpdateActiveSetup(KeySrc, Username):
	hkey_src = win32api.RegOpenKey(KeySrc, "Installed Components", 0, win32con.KEY_ALL_ACCESS)
	keyToRemove = []
	
	index = 0
	while True:
		try:
			buf = win32api.RegEnumKey(hkey_src, index)
			ProcessActiveSetupEntry(hkey_src, buf, Username)
			if ProcessActiveSetupEntry(hkey_src, buf, Username) == False:
				keyToRemove.append(buf)

			index+= 1
		except:
			win32api.RegCloseKey(hkey_src)
			break

	for key in keyToRemove:
		DeleteTree(hkey_src, key)
	win32api.RegCloseKey(hkey_src)

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
			Logger.error("getActiveSetupKeys: %s"%(str(e)))
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
