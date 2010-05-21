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

import os
import pythoncom
import random
import time
import win32api
from win32com.shell import shell, shellcon
import win32con
import win32file
import win32net
import win32profile
import win32security
import _winreg

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Session import Session as AbstractSession

import Langs
import LnkFile
from Msi import Msi
from Platform import Platform
import Reg

class Session(AbstractSession):
	def install_client(self):
		(logon, profileDir) = self.init()
		
		lnk_files = []
		
		buf = shell.SHGetSpecialFolderPath(logon, shellcon.CSIDL_APPDATA)
		print "appdata: ",buf
		buf = os.path.join(buf, "ovd")
		if not os.path.isdir(buf):
			os.makedirs(buf)
		os.mkdir(os.path.join(buf, "shortcuts"))
		os.mkdir(os.path.join(buf, "matching"))
		
		for (app_id, app_target) in self.applications:
			cmd = LnkFile.getTarget(app_target)
			f = file(os.path.join(buf, "matching", app_id), "w")
			f.write(cmd)
			f.close()
			
			final_file = os.path.join(buf, "shortcuts", os.path.basename(app_target))
			print "install_client %s %s %s"%(str(app_target), str(final_file), str(app_id))
			LnkFile.clone(app_target, final_file, "startovdapp", app_id)
			lnk_files.append(final_file)
		
		instances_dir = os.path.join(buf, "instances")
		os.mkdir(instances_dir)
		
		programsDir = shell.SHGetSpecialFolderPath(logon, shellcon.CSIDL_PROGRAMS)
		print "startmenu: ",programsDir
		# remove default startmenu
		if os.path.exists(programsDir):
			Platform.DeleteDirectory(programsDir)
		os.makedirs(programsDir)
		
		
		#desktopDir = shell.SHGetSpecialFolderPath(logon, shellcon.CSIDL_DESKTOPDIRECTORY)
		# bug: this return the Administrator desktop dir path ...
		desktopDir = os.path.join(profileDir, "Desktop")
		print "desktop dir",desktopDir
		if self.parameters.has_key("desktop_icons") and not os.path.exists(desktopDir):
			os.makedirs(desktopDir)
		
		# close our logon instance
		win32api.CloseHandle(logon)
		
		for srcFile in lnk_files:
			dstFile = os.path.join(programsDir, os.path.basename(srcFile))
			if os.path.exists(dstFile):
				os.remove(dstFile)
			win32file.CopyFile(srcFile, dstFile, True)
			
			
			if self.parameters.has_key("desktop_icons"):
				dstFile = os.path.join(desktopDir, os.path.basename(srcFile))
				if os.path.exists(dstFile):
					os.remove(dstFile)
				win32file.CopyFile(srcFile, dstFile, True)
	
	def uninstall_client(self):
		self.user.destroy()
		
		return True
	
	
	def init(self):
		"""Init profile repo"""
	
		logon = win32security.LogonUser(self.user.name, None, self.user.infos["password"], win32security.LOGON32_LOGON_INTERACTIVE, win32security.LOGON32_PROVIDER_DEFAULT)
		
		data = {}
		data["UserName"] = self.user.name
		
		hkey = win32profile.LoadUserProfile(logon, data)
		#self.init_ulteo_registry(sid)
		#self.init_redirection_shellfolders(sid)
		win32profile.UnloadUserProfile(logon, hkey)
		
		profileDir = win32profile.GetUserProfileDirectory(logon)
		
		print "profiledir:",profileDir
		self.overwriteDefaultRegistry(profileDir)
		
		return (logon, profileDir)
		
	
	
	def unload(self, sid):
		try:
			# Unload user reg
			win32api.RegUnLoadKey(win32con.HKEY_USERS, sid)
			win32api.RegUnLoadKey(win32con.HKEY_USERS, sid+'_Classes')
		except Exception, e:
			print "Unable to unload user reg: ",str(e)
			return False
		
		return True
	

	
	
	def overwriteDefaultRegistry(self, directory):
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


		hiveName = "OVD_%d"%(random.randrange(10000, 50000))
		
		# Load the hive
		_winreg.LoadKey(win32con.HKEY_USERS, hiveName, registryFile)
		
		# Set the language
		if self.parameters.has_key("locale"):
			path = r"%s\Control Panel\Desktop"%(hiveName)
			key = win32api.RegOpenKey(_winreg.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "MUILanguagePending", 0, win32con.REG_DWORD, Langs.getLCID(self.parameters["locale"]))
			win32api.RegSetValueEx(key, "MultiUILanguageId", 0, win32con.REG_DWORD, Langs.getLCID(self.parameters["locale"]))
			win32api.RegCloseKey(key)
		
		# Policies update
		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Policies\Explorer"%(hiveName)
		restrictions = ["DisableFavoritesDirChange",
				"DisableLocalMachineRun",
				"DisableLocalMachineRunOnce",
				"DisableMachineRunOnce",
				"DisableMyMusicDirChange",
				"DisableMyPicturesDirChange",
				"DisablePersonalDirChange",
				"EnforceShellExtensionSecurity",
				#"ForceStartMenuLogOff",
				"Intellimenus",
				"NoChangeStartMenu",
				"NoClose",
				"NoCommonGroups",
				"NoControlPanel",
				"NoDFSTab",
				"NoFind",
				"NoFolderOptions",
				"NoHardwareTab",
				"NoInstrumentation",
				"NoIntellimenus",
				"NoInternetIcon", # remove the IE icon
				"NoManageMyComputerVerb",
				"NonEnum",
				"NoNetworkConnections",
				"NoResolveSearch",
				"NoRun",
				"NoSetFolders",
				"NoSetTaskbar",
				"NoStartMenuSubFolders", # should remove the folders from startmenu but doesn't work
				"NoSMBalloonTip",
				"NoStartMenuEjectPC",
				"NoStartMenuNetworkPlaces",
				"NoTrayContextMenu",
				"NoWindowsUpdate",
				#"NoViewContextMenu", # Mouse right clic
				#"StartMenuLogOff",
				]
		
		key = _winreg.OpenKey(_winreg.HKEY_USERS, path, 0, _winreg.KEY_SET_VALUE)
		for item in restrictions:
			_winreg.SetValueEx(key, item, 0, _winreg.REG_DWORD, 1)
		_winreg.CloseKey(key)
		
		
		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Policies"%(hiveName)
		key = _winreg.OpenKey( _winreg.HKEY_USERS, path, 0, _winreg.KEY_SET_VALUE)
		_winreg.CreateKey(key, "System")
		_winreg.CloseKey(key)
		
		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Policies\System"%(hiveName)
		restrictions = ["DisableRegistryTools",
				"DisableTaskMgr",
				"NoDispCPL",
				]
		
		key = _winreg.OpenKey(_winreg.HKEY_USERS, path, 0, _winreg.KEY_SET_VALUE)
		for item in restrictions:
			_winreg.SetValueEx(key, item, 0, _winreg.REG_DWORD, 1)
		_winreg.CloseKey(key)
		
		
		
		
		# Desktop customization
		path = r"%s\Control Panel\Desktop"%(hiveName)
		items = ["ScreenSaveActive", "ScreenSaverIsSecure"]
		
		key = _winreg.OpenKey(_winreg.HKEY_USERS, path, 0, _winreg.KEY_SET_VALUE)
		for item in items:
			_winreg.SetValueEx(key, item, 0, _winreg.REG_DWORD, 0)
		_winreg.CloseKey(key)
		
		
		# Rediect the Shell Folders to the remote profile
		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders"%(hiveName)
		data = [
			"Desktop",
		]
		key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		
		for item in data:
			dst = os.path.join(directory, item)
			win32api.RegSetValueEx(key, item, 0, win32con.REG_SZ, dst)
		win32api.RegCloseKey(key)
		
		
		# Overwrite Active Setup: works partially
		hkey_src = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, "Software\Microsoft\Active Setup", 0, win32con.KEY_ALL_ACCESS)
		hkey_dst = win32api.RegOpenKey(win32con.HKEY_USERS, r"%s\Software\Microsoft\Active Setup"%(hiveName), 0, win32con.KEY_ALL_ACCESS)
		
		Reg.CopyTree(hkey_src, "Installed Components", hkey_dst)
		Reg.UpdateActiveSetup(hkey_dst, self.user.name)
		win32api.RegCloseKey(hkey_src)
		win32api.RegCloseKey(hkey_dst)
		
		
		# Unload the hive
		win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName)
	
	
	
	def init_ulteo_registry(self, sid):
		#		# Set settings to OVDShell be able to mount remote profile
		path = sid+r"\Software"
		subkey = r"Ulteo Session"
		key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		win32api.RegCreateKey(key, subkey)
		win32api.RegCloseKey(key)
		
		profile = r"\\10.42.1.254\julien\profile"
		
		path+= r"\%s"%(subkey)
		data = {}
		data["shell"] = "explorer" # seamlessrdpshell
		data["profile"] = r"%s\common"%(profile)
		data["profile_local"] = r"Z:"
		data["profile_username"] = "julien"
		data["profile_password"] = "test"
	
		key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		
		for (k,v) in data.items():
			win32api.RegSetValueEx(key, k, 0, win32con.REG_SZ, v)
		
		win32api.RegCloseKey(key)
		
	def init_redirection_shellfolders(self, sid):
		# Rediect the Shell Folders to the remote profile
		path = sid+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders"
		data = [
			"Desktop",
			"AppData",
			"Start Menu",
			"Personal",
			"Recent",
		]
		key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		
		for item in data:
			win32api.RegSetValueEx(key, item, 0, win32con.REG_SZ, r"Z:\%s"%(item))
		
		win32api.RegCloseKey(key)
