# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
# Author Julien LANGLOIS <julien@ulteo.com> 2009-2010
# Author David LECHEVALIER <david@ulteo.com> 2011, 2012
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
import pywintypes
import random
import win32api
from win32com.shell import shell, shellcon
import win32con
import win32file
import win32profile
import win32security

from ovd.Logger import Logger
from ovd.Platform import Platform
from ovd.Role.ApplicationServer.Session import Session as AbstractSession

import Langs
import LnkFile
import Reg

class Session(AbstractSession):
	def init(self):
		self.installedShortcut = []
		self.succefully_initialized = False
	
	
	def install_client(self):
		logon = win32security.LogonUser(self.user.name, None, self.user.infos["password"], win32security.LOGON32_LOGON_INTERACTIVE, win32security.LOGON32_PROVIDER_DEFAULT)
		
		data = {}
		data["UserName"] = self.user.name
		hkey = win32profile.LoadUserProfile(logon, data)
		self.windowsProfileDir = win32profile.GetUserProfileDirectory(logon)
		
		self.windowsProgramsDir = shell.SHGetFolderPath(0, shellcon.CSIDL_PROGRAMS, logon, 0)
		Logger.debug("startmenu: %s"%(self.windowsProgramsDir))
		# remove default startmenu
		if os.path.exists(self.windowsProgramsDir):
			Platform.System.DeleteDirectory(self.windowsProgramsDir)
		os.makedirs(self.windowsProgramsDir)
		
		self.windowsDesktopDir = shell.SHGetFolderPath(0, shellcon.CSIDL_DESKTOPDIRECTORY, logon, 0)
		
		self.appDataDir = shell.SHGetFolderPath(0, shellcon.CSIDL_APPDATA, logon, 0)
		self.localAppDataDir = shell.SHGetFolderPath(0, shellcon.CSIDL_LOCAL_APPDATA, logon, 0)
		Logger.debug("localAppdata: '%s'"%(self.localAppDataDir))
		Logger.debug("appdata: '%s'"%(self.appDataDir))
		
		win32profile.UnloadUserProfile(logon, hkey)
		win32api.CloseHandle(logon)
		
		self.set_user_profile_directories(self.windowsProfileDir, self.appDataDir)
		self.init_user_session_dir(os.path.join(self.appDataDir, "ulteo", "ovd"))
		
		if self.profile is not None and self.profile.hasProfile():
			if not self.profile.mount():
				return False
			
			self.profile.copySessionStart()
		
		if self.profile is not None and self.profile.mountPoint is not None:
			d = os.path.join(self.profile.mountPoint, self.profile.DesktopDir)
			self.cleanupShortcut(d)
		
		self.install_desktop_shortcuts()
		
		self.overwriteDefaultRegistry(self.windowsProfileDir)
		
		if self.profile is not None and self.profile.hasProfile():
			self.profile.umount()
		
		self.succefully_initialized = True
		return True
	
	
	def set_user_profile_directories(self, userprofile, appDataDir):
		self.user.home          = userprofile
		self.appDataDir         = appDataDir
		
	
	
	def install_shortcut(self, shortcut):
		if self.mode != Session.MODE_DESKTOP:
			return
		
		self.installedShortcut.append(os.path.basename(shortcut))
		
		dstFile = os.path.join(self.windowsProgramsDir, os.path.basename(shortcut))
		if os.path.exists(dstFile):
			os.remove(dstFile)
		
		try:
			win32file.CopyFile(shortcut, dstFile, True)
		except pywintypes.error, err:
			if err[0] == 5: # Access is denied
				Logger.error("Session::Windows::install_shortcut Access is denied on copy of '%s' to '%s'"%(shortcut, dstFile))
			else:
				# other error
				Logger.error("Session::Windows::install_shortcut error on copy of '%s' to '%s', wintypes error %s"%(shortcut, dstFile, err[0]))
		
		if self.parameters.has_key("desktop_icons") and self.parameters["desktop_icons"] == "1":
			if self.profile is not None and self.profile.mountPoint is not None:
				d = os.path.join(self.profile.mountPoint, self.profile.DesktopDir)
			else:
				d = self.windowsDesktopDir
				if  not os.path.exists(self.windowsDesktopDir):
					os.makedirs(self.windowsDesktopDir)
			  
			dstFile = os.path.join(d, os.path.basename(shortcut))
			if os.path.exists(dstFile):
				os.remove(dstFile)
			try:
				win32file.CopyFile(shortcut, dstFile, True)
			except pywintypes.error, err:
				if err[0] == 5: # Access is denied
					Logger.error("Session::Windows::install_shortcut Access is denied on copy of '%s' to '%s'"%(shortcut, dstFile))
					return
				# other error
				Logger.error("Session::Windows::install_shortcut error on copy of '%s' to '%s', wintypes error %s"%(shortcut, dstFile, err[0]))
				return
	
	
	def get_target_file(self, application):
		return application["name"]+".lnk"
	
	
	def clone_shortcut(self, src, dst, command, args):
		return LnkFile.clone(src, dst, command, " ".join(args))
	
	
	def uninstall_client(self):
		if not self.succefully_initialized:
			return
		
		self.archive_shell_dump()
		
		if self.profile is not None and self.profile.hasProfile():
			if not self.profile.mount():
				Logger.warn("Unable to mount profile at uninstall_client of session "+self.id)
			else:
				self.profile.copySessionStop()
				
				desktop_path = os.path.join(self.profile.mountPoint, self.profile.DesktopDir)
				self.cleanupShortcut(desktop_path)
				
				for shortcut in self.installedShortcut:
					dstFile = os.path.join(self.profile.mountPoint, self.profile.DesktopDir, shortcut)
					if os.path.exists(dstFile):
						os.remove(dstFile)
				
				if not self.profile.umount():
					Logger.error("Unable to umount profile at uninstall_client of session "+self.id)
		
		
		self.domain.onSessionEnd()
		return True
	
	
	def overwriteDefaultRegistry(self, directory):
		registryFile = os.path.join(directory, "NTUSER.DAT")
		
		hiveName = "OVD_%s_%d"%(str(self.id), random.randrange(10000, 50000))
		
		# Load the hive
		win32api.RegLoadKey(win32con.HKEY_USERS, hiveName, registryFile)
		
		# Set the OVD Environnment
		path = r"%s\Environment"%(hiveName)
		try:
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		except:
			hkey = None
		if hkey is None:
			Logger.error("Unable to open key '%s'"%(path))
		else:
			win32api.RegSetValueEx(hkey, "OVD_SESSION_DIR", 0, win32con.REG_SZ, os.path.join(self.appDataDir, "ulteo", "ovd"))
			win32api.RegCloseKey(hkey)
		
		# Set the language
		if self.parameters.has_key("locale"):
			cl = Langs.getLCID(self.parameters["locale"])
			wl = Langs.unixLocale2WindowsLocale(self.parameters["locale"])
			
			path = r"%s\Control Panel\Desktop"%(hiveName)
			try:
				Reg.CreateKeyR(win32con.HKEY_USERS, path)
				hkey = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			except:
				hkey = None
			if hkey is None:
				Logger.error("Unable to open key '%s'"%(path))
			else:
				win32api.RegSetValueEx(hkey, "MUILanguagePending", 0, win32con.REG_SZ, "%08X"%(cl))
				win32api.RegSetValueEx(hkey, "PreferredUILanguagesPending", 0, win32con.REG_MULTI_SZ, [wl])
				win32api.RegCloseKey(hkey)
				
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
				"NoSetFolders",
				"NoSetTaskbar",
				#"NoStartMenuSubFolders", # should remove the folders from startmenu but doesn't work + On 2008, start menu is empty if this key is set
				"NoSMBalloonTip",
				"NoStartMenuEjectPC",
				"NoStartMenuNetworkPlaces",
				"NoTrayContextMenu",
				"NoWindowsUpdate",
				#"NoViewContextMenu", # Mouse right clic
				#"StartMenuLogOff",
				]
		try:
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		except:
			key = None
		if key is None:
			Logger.error("Unable to open key '%s'"%(path))
		else:
			for item in restrictions:
				win32api.RegSetValueEx(key, item, 0, win32con.REG_DWORD, 1)
			win32api.RegCloseKey(key)

		# Enable to use of lnk file from share without popup
		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Policies\Associations"%(hiveName)
	
		try:
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		except:
			key = None
		if key is None:
			Logger.error("Unable to open key '%s'"%(path))
		else:
			win32api.RegSetValueEx(key, "ModRiskFileTypes", 0, win32con.REG_SZ, ".exe;.msi;.vbs")
			win32api.RegCloseKey(key)


		# start menu customization
		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Explorer\Advanced"%(hiveName)
		restrictions = ["Start_ShowRun", "StartMenuAdminTools", "Start_AdminToolsRoot"]
		try:
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		except:
			key = None
		
		if key is None:
			Logger.error("Unable to open key '%s'"%(path))
		else:
			for item in restrictions:
				win32api.RegSetValueEx(key, item, 0, win32con.REG_DWORD, 0)
			win32api.RegCloseKey(key)
		
		if self.profile is not None:
			# http://support.microsoft.com/kb/810869
			# Do not show recycle bin
			path = r"%s\Software\Microsoft\Windows\CurrentVersion\Explorer\HideDesktopIcons\NewStartPanel"%(hiveName)
			restrictions = ["{645FF040-5081-101B-9F08-00AA002F954E}"]
			try:
				Reg.CreateKeyR(win32con.HKEY_USERS, path)
				key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			except:
				key = None
		
			if key is None:
				Logger.error("Unable to open key '%s'"%(path))
			else:
				for item in restrictions:
					win32api.RegSetValueEx(key, item, 0, win32con.REG_DWORD, 1)
				win32api.RegCloseKey(key)
	

		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Policies\System"%(hiveName)
		restrictions = ["DisableRegistryTools",
				"DisableTaskMgr",
				"DisableLockWorkstation",
				"NoDispCPL",
				]
		
		try:
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		except:
			key = None
		if key is None:
			Logger.error("Unable to open key '%s'"%(path))
		else:
			for item in restrictions:
				win32api.RegSetValueEx(key, item, 0, win32con.REG_DWORD, 1)
			win32api.RegCloseKey(key)
		
		
		# Remove Windows startup sound
		keys = ["WindowsLogon", "WindowsLogoff",
			"SystemStart", # old Windows 2003, not used anymore in 2008
			]
		for k in keys:
			path = r"%s\AppEvents\Schemes\Apps\.Default\%s\.Current"%(hiveName, k)
			
			try:
				Reg.CreateKeyR(win32con.HKEY_USERS, path)
				key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			except:
				key = None
			if key is None:
				Logger.error("Unable to open key '%s'"%(path))
			else:
				win32api.RegSetValueEx(key, None, 0, win32con.REG_EXPAND_SZ, "")
				win32api.RegCloseKey(key)
		
		
		# Desktop customization
		path = r"%s\Control Panel\Desktop"%(hiveName)
		items = ["ScreenSaveActive", "ScreenSaverIsSecure"]
		
		try:
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		except:
			key = None
		if key is None:
			Logger.error("Unable to open key '%s'"%(path))
		else:
			for item in items:
				win32api.RegSetValueEx(key, item, 0, win32con.REG_DWORD, 0)
			win32api.RegCloseKey(key)
		
		# Overwrite Active Setup: works partially
		try:
			Reg.UpdateActiveSetup(self.user.name, hiveName, r"Software\Microsoft\Active Setup")
			# On 64 bits architecture, Active Setup is already present in path "Software\Wow6432Node\Microsoft\Active Setup"
			if "PROGRAMW6432" in os.environ.keys():
				Reg.UpdateActiveSetup(self.user.name, hiveName, r"Software\Wow6432Node\Microsoft\Active Setup")
			
		except Exception, err:
			Logger.warn("Unable to reset ActiveSetup")
			Logger.debug("Unable to reset ActiveSetup: "+str(err))
		
		if self.profile is not None:
			self.profile.overrideRegistry(hiveName, self.user.name)
		
		self.domain.doCustomizeRegistry(hiveName)
		
		# Timezone override
		if self.parameters.has_key("timezone"):
			tz_name = Langs.getWinTimezone(self.parameters["timezone"])
			
			ret = Reg.setTimezone(hiveName, tz_name)
			if ret is False:
				Logger.warn("Unable to set TimeZone (%s, %s)"%(self.parameters["timezone"], tz_name))
		
		
		# Unload the hive
		win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName)
