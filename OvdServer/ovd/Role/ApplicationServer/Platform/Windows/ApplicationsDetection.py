# -*- coding: UTF-8 -*-
# Copyright (C) 2009 Ulteo SAS
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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

import locale
import md5
import os
import pythoncom
import re
import tempfile
from win32com.shell import shell, shellcon
import win32event
import win32file
import win32process

from ovd.Logger import Logger
import mime
from Msi import Msi


class ApplicationsDetection:
	def __init__(self):
		self.mimetypes = mime.MimeInfos()
		
		try:
			self.msi = Msi()
		except WindowsError,e:
			Logger.warn("getApplicationsXML_nocache: Unable to init Msi")
			self.msi = None
			
		pythoncom.CoInitialize()

		try:
			self.path = str(shell.SHGetSpecialFolderPath(None, shellcon.CSIDL_COMMON_STARTMENU))
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
		
		files = self.find_lnk()
		
		for filename in files:
			shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
			shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(filename)
			
			if not os.path.splitext(shortcut.GetPath(0)[0])[1].lower() == ".exe":
				continue
			
			
			name = os.path.basename(filename)[:-4]
			if self.isBan(name):
				continue
			
			application = {}
			application["id"] = md5.md5(filename).hexdigest()
			application["name"] = unicode(name, encoding)
			application["command"] = unicode(shortcut.GetPath(0)[0], encoding)
			application["filename"] = unicode(filename, encoding)
			
			if len(shortcut.GetDescription())>0:
				application["description"] = unicode(shortcut.GetDescription(), encoding)
				
			if len(shortcut.GetIconLocation()[0])>0:
				application["icon"]  = unicode(shortcut.GetIconLocation()[0], encoding)

			# Find the mime types linked to the application
			# TODO: there is probably a faster way to handle this
			
			
			mimes = []
			for extension in self.mimetypes.extensions:
				ext = self.mimetypes.ext_keys[extension]
				
				for app_path in ext["apps"]:
					if not ext["type"] in mimes and self._compare_commands(app_path, application["command"]):
						mimes.append(ext["type"])
						break
			
			application["mimetypes"] = mimes
			

			if self.msi is not None:
				application["command"] = self.msi.getTargetFromShortcut(filename)
				if application["command"] is None:
					application["command"] = shortcut.GetPath(0)[0] + " " + shortcut.GetArguments()
					
			applications[application["id"]] = application
			
		
		return applications
	
	
	def getIcon(self, filename):
		Logger.debug("ApplicationsDetection::getIcon %s"%(filename))
		if not os.path.exists(filename):
			Logger.warn("ApplicationsDetection::getIcon no such file")
			return None
		
		
		pythoncom.CoInitialize()
		
		shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
		shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(filename)
		
		(iconLocation, iconId) = shortcut.GetIconLocation()
		iconLocation = self.evalPath(iconLocation)
		
		if len(iconLocation) == 0 or not os.path.exists(iconLocation):
			Logger.debug("ApplicationsDetection::getIcon No IconLocation, use shortcut target")
			iconLocation = self.evalPath(shortcut.GetPath(shell.SLGP_RAWPATH)[0])
			
			if len(iconLocation) == 0 or not os.path.exists(iconLocation):
				Logger.warn("ApplicationsDetection::getIcon Neither IconLocation nor shortcut target on %s (%s)"%(filename, iconLocation))
				return None
		
		path_tmp = tempfile.mktemp()
		
		path_bmp = path_tmp + ".bmp"
		cmd = """"%s" "%s" "%s" """%("extract_icon.exe", iconLocation, path_bmp)
		
		status = self.execute(cmd, True)
		if status != 0:
			Logger.warn("ApplicationsDetection::getIcon following command returned %d: %s"%(status, cmd))
			if os.path.exists(path_bmp):
				os.remove(path_bmp)
			
			return None
		
		if not os.path.exists(path_bmp):
			Logger.warn("ApplicationsDetection::getIcon No bmp file returned")
			return None
		
		path_png = path_tmp + ".png"
		
		cmd = """"%s" -Q -O "%s" "%s" """%("bmp2png.exe", path_png, path_bmp)
		status = self.execute(cmd, True)
		if status != 0:
			Logger.warn("ApplicationsDetection::getIcon following command returned %d: %s"%(status, cmd))
			os.remove(path_bmp)
			if os.path.exists(path_png):
				os.remove(path_png)
			
			return None
		
		if not os.path.exists(path_png):
			Logger.warn("ApplicationsDetection::getIcon No png file returned")
			return None
		
		f = open(path_png, 'rb')
		buffer = f.read()
		f.close()
		
		os.remove(path_bmp)
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

	@staticmethod
	def evalPath(path):
		all = re.findall("%[a-zA-Z]+%", path)
		all = ApplicationsDetection.ArrayUnique(all)
		
		for item in all:
			buf = item[1:-1].upper()
			if os.environ.has_key(buf):
				path = path.replace(item, os.environ[buf])
		
		return path

