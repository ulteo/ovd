# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
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
import re
import struct
import win32api
import win32con
import win32security

from ovd.Logger import Logger


def testMe():
	import os
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
	win32api.RegLoadKey(win32con.HKEY_USERS, hiveName, registryFile)
	
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
	
	if hkey_src is not None:
		win32api.RegCloseKey(hkey_src)
	if hkey_dst is not None:
		win32api.RegCloseKey(hkey_dst)


def CopyTree(KeySrc, SubKey, KeyDest, blacklist = []):
	hkey_src = None
	hkey_dst = None
	try:
		win32api.RegCreateKey(KeyDest, SubKey)
	
		hkey_src = win32api.RegOpenKey(KeySrc, SubKey, 0, win32con.KEY_ALL_ACCESS)
		hkey_dst = win32api.RegOpenKey(KeyDest, SubKey, 0, win32con.KEY_ALL_ACCESS)
	except Exception, err:
		if err[0] == 5:     #Access denied
			Logger.debug("Unable to open key in order to proceed CopyTree of %s: %s"%(SubKey, str(err)))
		else:
			Logger.warn("Unable to open key in order to proceed CopyTree of %s: %s"%(SubKey, str(err)))
		if hkey_src is not None:
			win32api.RegCloseKey(hkey_src)
		if hkey_dst is not None:
			win32api.RegCloseKey(hkey_dst)
		return
	
	index = 0
	while True:
		try:
			(string, object, type) = win32api.RegEnumValue(hkey_src, index)
#			print "CopyValue",string
			win32api.RegSetValueEx(hkey_dst, string, 0, type, object)
		except Exception, err:
			if err[0] == 259:   #No more data available
				break;
			if err[0] == 5:     #Access denied
				Logger.debug("Unable to copy value (%s)"%(str(err)))
			else:
				Logger.warn("Unable to copy value (%s)"%(str(err)))
		index+= 1

	index = 0
	while True:
		try:
			buf = win32api.RegEnumKey(hkey_src, index)
			
			buf_is_blacklisted = False
			context_blacklist = []
			for b_item in blacklist:
				parts = b_item.split("\\", 1)
				if parts[0] != buf:
					continue
				
				if len(parts) == 1 or len(parts[1]) == 0:
					buf_is_blacklisted = True
					#print "blacklisted",buf
					break
				else:
					context_blacklist.append(parts[1])
			
			if not buf_is_blacklisted:
				# print "CopyKey",buf
				CopyTree(hkey_src, buf, hkey_dst, context_blacklist)
		except Exception, err:
			if err[0] == 259:   #No more data available
				break;
			if err[0] == 5:     #Access denied
				Logger.debug("Unable to copy key (%s)"%(str(err)))
			else:
				Logger.warn("Unable to copy key (%s)"%(str(err)))
		index+= 1

	try:
		win32api.RegCloseKey(hkey_src)
		win32api.RegCloseKey(hkey_dst)
	except Exception, err:
		Logger.warn("Unable to close key in order to proceed CopyTree")
		Logger.error("Unable to close key in order to proceed CopyTree: %s"%(str(err)))


def CreateKeyR(hkey, path):
	if path.endswith("\\"):
		path = path[:-2]
	
	if "\\" in path:
		(parents, name) = path.rsplit("\\", 1)
		
		try:
			hkey2 = win32api.RegOpenKey(hkey, parents, 0, win32con.KEY_SET_VALUE)
		except Exception, err:
			CreateKeyR(hkey, parents)
			hkey2 = win32api.RegOpenKey(hkey, parents, 0, win32con.KEY_SET_VALUE)
	else:
		name = path
		hkey2 = hkey
	
	win32api.RegCreateKey(hkey2, name)
	win32api.RegCloseKey(hkey2)


def ProcessActiveSetupEntry(BaseKey, Entry, Username, LocaleValue):
	hkey = win32api.RegOpenKey(BaseKey, Entry, 0, win32con.KEY_ALL_ACCESS)
	
	version = False
	try:
		win32api.RegQueryValueEx(hkey, "Version")
		version=True
		win32api.RegQueryValueEx(hkey, "Locale")
	except:
		if version :
			win32api.RegSetValueEx(hkey, 'Locale', 0, win32con.REG_SZ, LocaleValue)

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

def GetLocaleValue(hkey_src):
	index = 0
	flag_continue = True
	while flag_continue:
		try:
			entry = win32api.RegEnumKey(hkey_src, index)
			hkey = win32api.RegOpenKey(hkey_src, entry, 0, win32con.KEY_ALL_ACCESS)
			try:
				(string, type) = win32api.RegQueryValueEx(hkey, "Locale")
				if not (string == "*" ):
					return string
			except:
				pass
			finally:
				if hkey is not None:
					win32api.RegCloseKey(hkey)
			index+= 1
		except Exception, err:
			flag_continue = False

	return "EN"

def UpdateActiveSetup(Username, hiveName, active_setup_path):
	# Overwrite Active Setup: works partially
	hkey_src = None
	hkey_dst = None
	
	try:
		hkey_src = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, active_setup_path, 0, win32con.KEY_ALL_ACCESS)
		CreateKeyR(win32con.HKEY_USERS, r"%s\%s"%(hiveName,active_setup_path))
		hkey_dst = win32api.RegOpenKey(win32con.HKEY_USERS, r"%s\%s"%(hiveName,active_setup_path), 0, win32con.KEY_ALL_ACCESS)
		CopyTree(hkey_src, "Installed Components", hkey_dst)
		
	except Exception, err:
		Logger.warn("Unable to copy tree")
		Logger.debug("Unable to copy tree: "+str(err))
		return
	finally:
		if hkey_dst is not None:
			win32api.RegCloseKey(hkey_dst)
		if hkey_src is not None:
			win32api.RegCloseKey(hkey_src)
	
	components_path = r"%s\%s\%s"%(hiveName,active_setup_path, "Installed Components")
	hkey_src = win32api.RegOpenKey(win32con.HKEY_USERS, components_path, 0, win32con.KEY_ALL_ACCESS)
	keyToRemove = []
	
	localeValue = GetLocaleValue(hkey_src)

	index = 0
	flag_continue = True
	while flag_continue:
		try:
			buf = win32api.RegEnumKey(hkey_src, index)
			if ProcessActiveSetupEntry(hkey_src, buf, Username, localeValue) == False:
				keyToRemove.append(buf)

			index+= 1
		except Exception, err:
			flag_continue = False

	for key in keyToRemove:
		DeleteTree(hkey_src, key)
	win32api.RegCloseKey(hkey_src)

def DeleteTree(key, subkey, deleteRoot = True):
	try:
		hkey = win32api.RegOpenKey(key, subkey, 0, win32con.KEY_ALL_ACCESS)
	except:
		# subkey already doesn't exist
		return
	
	index = 0
	flag_continue = True
	while flag_continue:
		try:
			subsubKey = win32api.RegEnumKey(hkey, index)
			# don't increment because it's a enum and we delete the key so the index is always 0
			#index+= 1
#			print "delete key: ",subsubKey
			DeleteTree(hkey, subsubKey)
		except Exception, err:
#			print "enum key except",err
			flag_continue = False
	
	index = 0
	flag_continue = True
	while flag_continue:
		try:
			(value, _, _) = win32api.RegEnumValue(hkey, index)
			# don't increment because it's a enum and we delete the key so the index is always 0
			# index+= 1
#			print "delete value: ",value
			win32api.RegDeleteValue(hkey, value)
		except Exception, err:
#			print "enum value except",err
			flag_continue = False
		
	win32api.RegCloseKey(hkey)
	
	
	if deleteRoot:
		win32api.RegDeleteKey(key, subkey)


def TreeSearchExpression(hive, subpath, motif):
	path = hive+"\\"+subpath
	hkey = None
	res = None
	index = 0
	flag_continue = True

	try:
		hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_ALL_ACCESS)
	except Exception, err:
		pass
	if hkey is None:
		return None

	while flag_continue:
		try:			
			subsubKey = win32api.RegEnumKey(hkey, index)
			index+= 1
			res = TreeSearchExpression(hive, subpath+"\\"+subsubKey, motif)
			if res is not None:
				return res
		except Exception, err:
			flag_continue = False
	index = 0
	flag_continue = True
	while flag_continue:
		try:
			(value, obj, objType) = win32api.RegEnumValue(hkey, index)
			if objType is not win32con.REG_DWORD:
				res = None
				res = re.search(motif, obj)
				if res is not None:
					res = res.group(0)
					break

			index+= 1
			
		except Exception, err:
			if err[0] != 259:  #no more data available
				Logger.error("TreeSearchExpression: %s"%(str(err)))
			flag_continue = False
		
	win32api.RegCloseKey(hkey)

	return res


def TreeReplace(hive, subpath, src, dest):
	index = 0
	hkey = None
	flag_continue = True
	path = hive+"\\"+subpath

	try:
		hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_ALL_ACCESS)
	except Exception, err:
		pass
	if hkey is None:
		return
	
	while flag_continue:
		try:
			subsubKey = win32api.RegEnumKey(hkey, index)
			index+= 1
			TreeReplace(hive, subpath+"\\"+subsubKey, src, dest)
		except Exception, err:
			flag_continue = False
	index = 0
	flag_continue = True
	while flag_continue:
		try:
			(value, obj, objType) = win32api.RegEnumValue(hkey, index)
			if objType is not win32con.REG_DWORD:
				res = None
				if src in obj:
					res = obj.replace(src, dest)
				if res is not None:
					win32api.RegSetValueEx(hkey, value, 0, objType, res)

			index+= 1
			
		except Exception, err:
			if err[0] != 259:  #no more data available
				Logger.error("TreeReplace: %s"%(str(err)))
			flag_continue = False
		
	win32api.RegCloseKey(hkey)


def LsTree(key, subkey, nb=0):
	print "%s * Node %s"%(" "*(nb*2), subkey)
	hkey = win32api.RegOpenKey(key, subkey, 0, win32con.KEY_ALL_ACCESS)
	
	index = 0
	flag_continue = True
	while flag_continue:
		try:
			subsubKey = win32api.RegEnumKey(hkey, index)
			index+= 1
			LsTree(hkey, subsubKey, nb+1)
		except Exception, err:
			flag_continue = False
	
	index = 0
	flag_continue = True
	while flag_continue:
		try:
			(value, _, _) = win32api.RegEnumValue(hkey, index)
			index+= 1
			
			print "%s * Value %s"%(" "*(nb+2)*2, value)
		except Exception, err:
			flag_continue = False
		
	win32api.RegCloseKey(hkey)


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


def setTimezone(rootPath, tz):
	# http://www.windowsitpro.com/article/registry2/jsi-tip-0398-how-to-set-the-time-zone-by-editing-the-registry-.aspx
	
	path = r"Software\Microsoft\Windows NT\CurrentVersion\Time Zones\%s"%(tz)
	hkey = None
	try:
		hkey = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, path, 0, win32con.KEY_QUERY_VALUE)
		(std, _) = win32api.RegQueryValueEx(hkey, "std")
		(dlt, _) = win32api.RegQueryValueEx(hkey, "dlt")
		(tzi, _) = win32api.RegQueryValueEx(hkey, "TZI")
	except Exception, err:
		Logger.error("setTimezone, Registry error "+str(err))
		return False
	finally:
		if hkey is not None:
			win32api.RegCloseKey(hkey)
	
	if len(tzi)<44:
		Logger.error("setTimezone, bad TZI len "+len(tzi))
		return False
	
	Bias = struct.unpack('<I', tzi[:4])[0]
	StandardBias = struct.unpack('<I', tzi[4:8])[0]
	DaylightBias = struct.unpack('<I', tzi[8:12])[0]
	StandardStart = tzi[12:28]
	DaylightStart = tzi[28:44]
	
	path = r"%s\SYSTEM\CurrentControlSet\Control\TimeZoneInformation"%(rootPath)
	CreateKeyR(win32con.HKEY_USERS, path)
	hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_ALL_ACCESS)
	if hkey is None:
		Logger.error("setTimezone, unable to open "+path)
		return False
	
	win32api.RegSetValueEx(hkey, "ActiveTimeBias", 0, win32con.REG_DWORD, Bias)
	win32api.RegSetValueEx(hkey, "Bias", 0, win32con.REG_DWORD, Bias)
	win32api.RegSetValueEx(hkey, "StandardBias", 0, win32con.REG_DWORD, StandardBias)
	win32api.RegSetValueEx(hkey, "StandardStart", 0, win32con.REG_BINARY, StandardStart)
	win32api.RegSetValueEx(hkey, "DaylightStart", 0, win32con.REG_BINARY, DaylightStart)
	
	win32api.RegSetValueEx(hkey, "StandardName", 0, win32con.REG_SZ, std)
	win32api.RegSetValueEx(hkey, "DaylightName", 0, win32con.REG_SZ, dlt)
	win32api.RegCloseKey(hkey)
	
	return True
