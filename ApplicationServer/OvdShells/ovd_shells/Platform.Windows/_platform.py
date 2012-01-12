# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
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
import os
import time

import pythoncom
import win32api
import win32com.client
from win32com.shell import shell, shellcon
import win32con
import win32event
import win32file
import win32process


def findProcessWithEnviron(pattern):
	return None

def existProcess(pid):
	hProcess = win32api.OpenProcess(win32con.PROCESS_QUERY_INFORMATION, False, pid)
	if hProcess is None:
		return False
	
	win32file.CloseHandle(hProcess);
	return True

def launch(cmd, wait=False):
	(hProcess, hThread, dwProcessId, dwThreadId) = win32process.CreateProcess(None, cmd, None , None, False, 0 , None, None, win32process.STARTUPINFO())
	
	if wait:
		win32event.WaitForSingleObject(hProcess, win32event.INFINITE)
	
	win32file.CloseHandle(hProcess)
	win32file.CloseHandle(hThread)
	
	return dwProcessId

def kill(pid):
	hProcess = win32api.OpenProcess(win32con.PROCESS_TERMINATE, False, pid)
	if hProcess is None:
		 print "doesn't exist pid"
		 return False
	
	ret = win32process.TerminateProcess(hProcess, 0)
	
	win32file.CloseHandle(hProcess);
	return ret

def getUserSessionDir():
	try:
		encoding = locale.getpreferredencoding()
	except locale.Error:
		encoding = "UTF-8"
	
	d = shell.SHGetSpecialFolderPath(None, shellcon.CSIDL_APPDATA)
	d = d.encode(encoding)
	
	return os.path.join(d, "ulteo", "ovd")


def launchIntegratedClient(configuration_file_):
	if os.path.exists(configuration_file_) == False:                                                                            
		return False
	
	java_cmd = detectJavaw()
	if java_cmd is None:
		dirs = os.environ["PATH"].split(";")
		dirs.insert(0, os.path.abspath(os.path.curdir))
		
		for d in dirs:
			path = os.path.join(d, r"jre\bin\java.exe")
			if os.path.exists(path):
				print "Found java in '%s'"%(path)
				java_cmd = '"'+path+'" -jar "%1" %*'
				break
		
		if java_cmd is None:
			print "No JRE available from registry nor in $PATH"
			return False
	
	
	jar_location = r"OVDExternalAppsClient.jar"
	folder = None
	
	dirs = os.environ["PATH"].split(";")
	dirs.insert(0, os.path.abspath(os.path.curdir))
	
	for d in dirs:
		path = os.path.join(d, jar_location)
		if os.path.exists(path):
			folder = d
			break
	
	if folder is None:
		print "No OVD integrated client installed on the system"
		return None
	
	java_cmd = java_cmd.replace("%1", jar_location)
	java_cmd = java_cmd.replace("%*", '-c "%s" -o "%s"'%(configuration_file_, os.path.join(getUserSessionDir(), "dump-externalapps.txt")))
	
	(hProcess, hThread, dwProcessId, dwThreadId) = win32process.CreateProcess(None, java_cmd, None , None, False, win32process.CREATE_NO_WINDOW , None, folder, win32process.STARTUPINFO())
	win32file.CloseHandle(hProcess)
	win32file.CloseHandle(hThread)
	  
	return True 


def detectJavaw():
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

def startDesktop():
	explorer_path = r"%s\explorer.exe"%(os.environ["windir"])
	launch(explorer_path, True)

def startSeamless():
	launch("seamlessrdpshell")
	
def transformCommand(cmd_, args_):
		args = args_
		if len(args)>0:
			if "%1" in cmd_:
				cmd_ = cmd_.replace("%1", args.pop(0))
			if "%*" in cmd_:
				cmd_ = cmd_.replace("%*", " ".join(['"'+a+'"' for a in args]))
				args = []
		
		if len(args)>0:
			cmd_+= " "+" ".join(['"'+a+'"' for a in args])
		
		return cmd_

def getSubProcess(ppid):
	pythoncom.CoInitialize()
	WMI = win32com.client.GetObject('winmgmts:')
	processes = WMI.InstancesOf('Win32_Process')
	
	pids = []
	
	for process in processes:
		pid = process.Properties_('ProcessID').Value
		parent = process.Properties_('ParentProcessId').Value
		
		if parent == ppid:
			pids.append(pid)
	
	return pids


def lock(t):
	pushLock()
	
	t0 = time.time()
	
	while isLocked():
		if time.time() - t0 > t:
			return False
		
		time.sleep(0.5)
	
	return True


def isLocked():
	appdata = os.getenv("APPDATA")
	lockFile = os.path.join(appdata, "ulteo", "ulock")
	
	return os.path.exists(lockFile)


def pushLock():
	appdata = os.getenv("APPDATA")
	lockFile = os.path.join(appdata, "ulteo", "ulock")
	
	try:
		handle = open(lockFile, 'w')
		handle.close()
	except Exception, e:
		print "Unable to create lock: "+str(e)


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
