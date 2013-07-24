# -*- coding: UTF-8 -*-
# Copyright (C) 2009-2011 Ulteo SAS
# Author Laurent CLOUET <laurent@ulteo.com> 2009-2011
# Author Julien LANGLOIS <julien@ulteo.com> 2009-2011
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

import hashlib
import locale
import os
import pythoncom
import re
import tempfile
import pywintypes
import win32api
from win32com.shell import shell, shellcon
import win32event
import win32file
import win32process

from ovd.Logger import Logger
import LnkFile
from MimeTypeInfos import MimeTypeInfos
from Msi import Msi
import Util


class ApplicationsDetection:
	shortcut_ext = ".lnk"
	
	def __init__(self):
		(_, encoding) = locale.getdefaultlocale()
		if encoding is None:
			encoding = "utf8"
		
		try:
			self.msi = Msi()
		except WindowsError,e:
			Logger.warn("getApplicationsXML_nocache: Unable to init Msi")
			self.msi = None
		
		self.mimetypes = MimeTypeInfos()
		self.mimetypes.load()
		
		pythoncom.CoInitialize()

		try:
			self.path = shell.SHGetFolderPath(None, shellcon.CSIDL_COMMON_STARTMENU, 0, 0)
		except:
			Logger.error("getApplicationsXML_nocache : no  ALLUSERSPROFILE key in environnement")
			self.path = os.path.join('c:\\', 'Documents and Settings', 'All Users', 'Start Menu')

	def find_lnk(self):
		ret = []
		for root, dirs, files in os.walk(self.path):
			for name in files:
				l = os.path.join(root,name)
				if os.path.isfile(l) and l[-3:] == "lnk":
					ret.append(l)
					
		return ret
	
	@staticmethod
	def isBan(name_):
		name = name_.lower()
		for ban in ['uninstall', 'update']:
			if ban in name:
				return True
		return False

	@staticmethod
	def _compare_commands(cm1, cm2):
		if cm1.lower().find(cm2.lower()) != -1:
			return True
		return False

	def get(self):
		applications = {}
		
		(_, encoding) = locale.getdefaultlocale()
		if encoding is None:
			encoding = "utf8"
		
		files = self.find_lnk()
		
		for filename in files:
			shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
			shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(filename)
			
			name = os.path.basename(filename)[:-4]
			if self.isBan(name):
				continue
			
			try:
				local_id = hashlib.md5(filename.encode(encoding)).hexdigest()
			except UnicodeEncodeError, err:
				local_id = hashlib.md5(filename.encode('utf-8')).hexdigest()
			
			application = {}
			application["local_id"] = local_id
			application["name"] = name
			application["command"] = unicode(shortcut.GetPath(0)[0], encoding)
			application["filename"] = filename
			
			if len(shortcut.GetIconLocation()[0])>0:
				application["icon"]  = unicode(shortcut.GetIconLocation()[0], encoding)
			
			
			if self.msi is not None:
				buf = self.msi.getTargetFromShortcut(filename)
				if buf is not None:
					application["command"] = buf
			
			if not application["command"].lower().endswith(".exe") and ".exe " not in application["command"].lower():
				continue
			
			if win32process.IsWow64Process() and not os.path.exists(application["command"]):
				application["command"] = Util.clean_wow64_path(application["command"])
			
			if " " in application["command"]:
				application["command"] = '"%s"'%(application["command"])
			
			if len(shortcut.GetArguments())>0:
				application["command"]+= " "+unicode(shortcut.GetArguments(), encoding)
			
			try:
				if len(shortcut.GetDescription())>0:
					application["description"] = unicode(shortcut.GetDescription(), encoding)
			except pywintypes.com_error:
				Logger.debug("ApplicationsDetection::get unable to get description for %s"%(application["filename"]))
			
			application["mimetypes"] = self.mimetypes.get_mime_types_from_command(application["command"])
			
			applications[application["local_id"]] = application
			
		
		return applications
	
	@staticmethod
	def getExec(filename):
		Logger.debug("ApplicationsDetection::getExec %s"%(filename))
		cmd = LnkFile.getTarget(filename)
		if cmd is None:
			Logger.error("Unable to get command from shortcut '%s'"%(filename))
			return None
		
		return cmd
	
	def getIcon(self, filename):
		Logger.debug("ApplicationsDetection::getIcon %s"%(filename))
		if not os.path.exists(filename):
			Logger.warn("ApplicationsDetection::getIcon no such file")
			return None
		
		
		pythoncom.CoInitialize()
		
		if win32process.IsWow64Process():
			wow64_value = Util.Wow64DisableWow64FsRedirection()
		
		shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
		shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(filename)
		
		(iconLocation_src, iconId) = shortcut.GetIconLocation()
		iconLocation = win32api.ExpandEnvironmentStrings(iconLocation_src)
		
		if not os.path.exists(iconLocation) and win32process.IsWow64Process():
			iconLocation = Util.clean_wow64_path(iconLocation)
		
		if len(iconLocation) == 0 or not os.path.exists(iconLocation):
			Logger.debug("ApplicationsDetection::getIcon No IconLocation, use shortcut target")
			iconLocation = win32api.ExpandEnvironmentStrings(shortcut.GetPath(shell.SLGP_RAWPATH)[0])
			
			if not os.path.exists(iconLocation) and win32process.IsWow64Process():
				iconLocation = Util.clean_wow64_path(iconLocation)
			
			if len(iconLocation) == 0 or not os.path.exists(iconLocation):
				Logger.warn("ApplicationsDetection::getIcon Neither IconLocation nor shortcut target on %s (%s)"%(filename, iconLocation))
				if win32process.IsWow64Process():
					Util.Revert64DisableWow64FsRedirection(wow64_value)
				
				return None
		
		if win32process.IsWow64Process():
			Util.Revert64DisableWow64FsRedirection(wow64_value)
		
		path_tmp = tempfile.mktemp()
		
		path_bmp = path_tmp + ".bmp"
		path_png = path_tmp + ".png"
		
		
		cmd = """"%s" "%s,%d" "%s" """%("exeIcon2png.exe", iconLocation, iconId, path_png)
		status = self.execute(cmd, True)
		if status != 0:
			Logger.warn("Unable to extract icon, use alternative method")
			Logger.debug("ApplicationsDetection::getIcon following command returned %d: %s"%(status, cmd))
			if os.path.exists(path_png):
				os.remove(path_png)
			
			cmd = """"%s" "%s" "%s" """%("extract_icon.exe", iconLocation, path_bmp)
			
			status = self.execute(cmd, True)
			if status != 0:
				Logger.warn("Unable to extract icon with the alternative method")
				Logger.debug("ApplicationsDetection::getIcon following command returned %d: %s"%(status, cmd))
				if os.path.exists(path_bmp):
					os.remove(path_bmp)
				
				return None
			
			if not os.path.exists(path_bmp):
				Logger.debug("ApplicationsDetection::getIcon No bmp file returned")
				return None
			
			
			cmd = """"%s" -Q -O "%s" "%s" """%("bmp2png.exe", path_png, path_bmp)
			status = self.execute(cmd, True)
			if status != 0:
				Logger.warn("Unable to extract icon with the alternative method")
				Logger.debug("ApplicationsDetection::getIcon following command returned %d: %s"%(status, cmd))
				os.remove(path_bmp)
				if os.path.exists(path_png):
					os.remove(path_png)
				
				return None
			
			os.remove(path_bmp)
		
		if not os.path.exists(path_png):
			Logger.debug("ApplicationsDetection::getIcon No png file returned")
			return None
		
		f = open(path_png, 'rb')
		buffer = f.read()
		f.close()
		
		os.remove(path_png)
		return buffer
		
	
	@staticmethod	
	def execute(cmd, wait=False):
		try:
			(hProcess, hThread, dwProcessId, dwThreadId) = win32process.CreateProcess(None, cmd, None , None, False, 0 , None, None, win32process.STARTUPINFO())
		except Exception, err:
			Logger.error("Unable to exec '%s': %s"%(cmd, str(err)))
			return 255
		
		if wait:
			win32event.WaitForSingleObject(hProcess, win32event.INFINITE)
			retValue = win32process.GetExitCodeProcess(hProcess)
		else:
			retValue = dwProcessId
		
		win32file.CloseHandle(hProcess)
		win32file.CloseHandle(hThread)
		
		return retValue
	
	@staticmethod
	def ArrayUnique(ar):
		r = []
		for i in ar:
			if i not in r:
				r.append(i)
		
		return r
