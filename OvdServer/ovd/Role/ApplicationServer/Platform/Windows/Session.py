# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2014 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011, 2012, 2013, 2014
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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
import shutil
import win32api
from win32com.shell import shell, shellcon
import win32con
import win32file
import win32profile
import win32security

from ovd.Logger import Logger
from ovd.Platform.System import System
from ovd.Role.ApplicationServer.Session import Session as AbstractSession

import Langs
import LnkFile
import Reg

class Session(AbstractSession):
	SPOOL_USER = System.get_default_config_dir()
	
	def init(self):
		self.succefully_initialized = False
	
	
	def install_client(self):
		logon = win32security.LogonUser(self.user.name, None, self.user.infos["password"], win32security.LOGON32_LOGON_INTERACTIVE, win32security.LOGON32_PROVIDER_DEFAULT)
		
		data = {}
		data["UserName"] = self.user.name
		hkey = win32profile.LoadUserProfile(logon, data)
		self.windowsProfileDir = win32profile.GetUserProfileDirectory(logon)
		sessionDir = os.path.join(self.SPOOL_USER, self.user.name)
		
		self.windowsProgramsDir = shell.SHGetFolderPath(0, shellcon.CSIDL_PROGRAMS, logon, 0)
		Logger.debug("startmenu: %s"%(self.windowsProgramsDir))
		# remove default startmenu
		if os.path.exists(self.windowsProgramsDir):
			System.DeleteDirectory(self.windowsProgramsDir)
		os.makedirs(self.windowsProgramsDir)
		
		self.appDataDir = shell.SHGetFolderPath(0, shellcon.CSIDL_APPDATA, logon, 0)
		self.localAppDataDir = shell.SHGetFolderPath(0, shellcon.CSIDL_LOCAL_APPDATA, logon, 0)
		Logger.debug("localAppdata: '%s'"%(self.localAppDataDir))
		Logger.debug("appdata: '%s'"%(self.appDataDir))
		
		win32profile.UnloadUserProfile(logon, hkey)
		win32api.CloseHandle(logon)
		
		self.init_user_session_dir(sessionDir)
		
		if self.profile is not None and self.profile.hasProfile():
			if not self.profile.mount():
				if self.parameters.has_key("need_valid_profile") and self.parameters["need_valid_profile"] == "1":
					return False
			else:
				self.profile.copySessionStart()
				self.profile.installLogoffGPO()
		
		if self.profile is not None and self.profile.mountPoint is not None:
			d = os.path.join(self.profile.mountPoint, self.profile.DesktopDir)
			self.cleanupShortcut(d)
		
		self.install_desktop_shortcuts()
		
		self.overwriteDefaultRegistry(self.windowsProfileDir)
		
		if self.profile is not None and self.profile.hasProfile():
			self.profile.umount()
		
		self.succefully_initialized = True
		return True
	
	
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
				
				if not self.profile.umount():
					Logger.error("Unable to umount profile at uninstall_client of session "+self.id)
		
		try:
			shutil.rmtree(self.user_session_dir)
		except Exception:
			Logger.exception("Failed to remove spool directory '%s'"%self.user_session_dir)
		
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
			win32api.RegSetValueEx(hkey, "OVD_SESSION_DIR", 0, win32con.REG_SZ, self.user_session_dir)
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
		
		
		# Hide local drives
		value = 0
		
		drives = win32api.GetLogicalDriveStrings()
		drives = drives.split('\000')[:-1]
		for drive in drives:
			t = win32file.GetDriveType(drive)
			if t not in [win32con.DRIVE_CDROM, win32con.DRIVE_REMOVABLE, win32con.DRIVE_FIXED]:
				continue
			
			# Transform the drive letter into a bit value according to
			# http://technet.microsoft.com/en-us/library/cc959437.aspx
			value += 2 << (ord(drive.lower()[0]) - 98)
		
		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Policies\Explorer"%(hiveName)
		try:
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		except:
			key = None
		if key is None:
			Logger.error("Unable to open key '%s'"%(path))
		else:
			win32api.RegSetValueEx(key, "NoDrives", 0, win32con.REG_DWORD, value)
			# win32api.RegSetValueEx(key, "NoViewOnDrive", 0, win32con.REG_DWORD, value)
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
			
		except Exception:
			Logger.exception("Unable to reset ActiveSetup")
		
		if self.profile is not None:
			self.profile.overrideRegistry(hiveName, self.user.name)
		
		self.domain.doCustomizeRegistry(hiveName)
		
		# Timezone override
		if self.parameters.has_key("timezone"):
			tz_name = Langs.getWinTimezone(self.parameters["timezone"])
			
			ret = Reg.setTimezone(hiveName, tz_name)
			if ret is False:
				Logger.warn("Unable to set TimeZone (%s, %s)"%(self.parameters["timezone"], tz_name))
		
		
		# Hack for Windows 2012R2 relative to StartScreen integration.
		path = r"%s\Software\Microsoft\Windows\CurrentVersion\Explorer\StartPage"%(hiveName)
		try:
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
		except:
			key = None
		if key is None:
			Logger.error("Unable to open key '%s'"%(path))
		else:
			win32api.RegSetValueEx(key, "MakeAllAppsDefault", 0, win32con.REG_DWORD, 1)
			win32api.RegCloseKey(key)
		
		# Unload the hive
		win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName)
