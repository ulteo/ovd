# -*- coding: UTF-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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
import random
import win32api
import win32con
import win32file
import win32netcon
import win32security
import win32wnet
import _winreg

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Profile import Profile as AbstractProfile

import Reg
import Util

class Profile(AbstractProfile):	
	def init(self):
		self.mountPoint = None
	
	
	def hasProfile(self):
		return (self.profile is not None)
	
	
	def mount(self):
		buf = self.getFreeLetter()
		if buf is None:
			Logger.warn("No drive letter available: unable to init profile")
			return
		
		self.mountPoint = "%s:"%(buf)
		
		try:
			win32wnet.WNetAddConnection2(win32netcon.RESOURCETYPE_DISK, self.mountPoint, r"\\%s\%s"%(self.profile["server"], self.profile["dir"]), None, self.profile["login"], self.profile["password"])
		
		except Exception, err:
			Logger.error("Unable to mount drive")
			Logger.debug("WNetAddConnection2 return %s"%(err))
			Logger.debug("Unable to mount drive, '%s', try the net use command equivalent: '%s'"%(str(err), "net use %s \\\\%s\\%s %s /user:%s"%(self.mountPoint, self.profile["server"], self.profile["dir"], self.profile["password"], self.profile["login"])))
			
			self.mountPoint = None
			return False
		
		return True
	
	
	def umount(self):
		if self.mountPoint is None:
			return
		
		try:
			win32wnet.WNetCancelConnection2(self.mountPoint, 0, True)
		
		except Exception, err:
			Logger.error("Unable to umount drive")
			Logger.debug("WNetCancelConnection2 return %s"%(err))
			Logger.debug("Unable to umount drive, net use command equivalent: '%s'"%("net use %s: /delete"%(self.mountPoint)))
	
	
	def copySessionStart(self):
		for f in [self.DesktopDir, self.DocumentsDir]:
			d = os.path.join(self.mountPoint, f)

			while not os.path.exists(d):
				try:
					os.makedirs(d)
				except OSError, err:
					Logger.debug2("Profile mkdir failed (concurrent access because of more than one ApS) => %s"%(str(err)))
					continue
		
		
		d = os.path.join(self.mountPoint, "conf.Windows")
		if os.path.exists(d):
			# Copy user registry
			self.session.obainPrivileges()
			
			src = os.path.join(d, "NTUSER.DAT")
			if os.path.exists(src):
				dst = os.path.join(self.session.windowsProfileDir, "NTUSER.DAT")
				
				rand = random.randrange(10000, 50000)
				
				hiveName_src = "OVD_%s_%d"%(str(self.session.id), rand)
				_winreg.LoadKey(win32con.HKEY_USERS, hiveName_src, src)
				
				hiveName_dst = "OVD_%s_%d"%(str(self.session.id), rand+1)
				_winreg.LoadKey(win32con.HKEY_USERS, hiveName_dst, dst)
				
				hkey_src = win32api.RegOpenKey(win32con.HKEY_USERS, r"%s"%(hiveName_src), 0, win32con.KEY_ALL_ACCESS)
				hkey_dst = win32api.RegOpenKey(win32con.HKEY_USERS, r"%s"%(hiveName_dst), 0, win32con.KEY_ALL_ACCESS)
				
				Reg.CopyTree(hkey_src, "Software", hkey_dst)
				
				win32api.RegCloseKey(hkey_src)
				win32api.RegCloseKey(hkey_dst)
				
				win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName_src)
				win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName_dst)
				
			
			# Copy AppData
			src = os.path.join(d, "AppData")
			if os.path.exists(src):
				dst = self.session.appDataDir
				
				try:
					Util.copyDirOverride(src, dst)
				except Exception, err:
					Logger.error("Unable to copy appData from profile")
					Logger.debug("Unable to copy appData from profile: %s"%(str(err)))
	
	
	def copySessionStop(self):
		# etre sur que le type est logoff !
		
		
		d = os.path.join(self.mountPoint, "conf.Windows")
		while not os.path.exists(d):
			try:
				os.makedirs(d)
			except OSError, err:
				Logger.debug2("conf.Windows mkdir failed (concurrent access because of more than one ApS) => %s"%(str(err)))
				continue
		
		self.session.obainPrivileges()
		
		# Copy user registry
		src = os.path.join(self.session.windowsProfileDir, "NTUSER.DAT")
		dst = os.path.join(d, "NTUSER.DAT")
		
		if os.path.exists(src):
			try:
				win32file.CopyFile(src, dst, False)
			except:
				Logger.error("Unable to copy registry to profile")
		else:
			Logger.warn("Weird: no NTUSER.DAT in user home dir ...")
		
		# Copy AppData
		src = self.session.appDataDir
		dst = os.path.join(d, "AppData")
		try:
			Util.copyDirOverride(src, dst)
		except Exception, err:
			Logger.error("Unable to copy appData to profile")
			Logger.debug("Unable to copy appData to profile: %s"%(str(err)))
	
	
	def overrideRegistry(self, hiveName):
		if self.profile is not None or len(self.sharedFolders)>0:
			Reg.CreateKeyR(win32con.HKEY_USERS, hiveName+r"\Software\Ulteo")
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, hiveName+r"\Software\Ulteo", 0, win32con.KEY_ALL_ACCESS)
			Reg.DeleteTree(key, r"ovd", False)
			win32api.RegCloseKey(key)
		
		
		if self.profile is not None:
			path = hiveName+r"\Software\Ulteo\ovd\profile"
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "host", 0, win32con.REG_SZ, self.profile["server"])
			win32api.RegSetValueEx(key, "directory", 0, win32con.REG_SZ, self.profile["dir"])
			win32api.RegSetValueEx(key, "login", 0, win32con.REG_SZ, self.profile["login"])
			win32api.RegSetValueEx(key, "password", 0, win32con.REG_SZ, self.profile["password"])
			win32api.RegCloseKey(key)
			
			# Set the name
			path = hiveName+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\MountPoints2\##%s#%s"%(self.profile["server"], self.profile["dir"])
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "_LabelFromReg", 0, win32con.REG_SZ, "Personal User Profile")
			win32api.RegCloseKey(key)
		
		shareNum = 0
		for share in self.sharedFolders:
			path = hiveName+r"\Software\Ulteo\ovd\share_%d"%(shareNum)
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "host", 0, win32con.REG_SZ, share["server"])
			win32api.RegSetValueEx(key, "directory", 0, win32con.REG_SZ, share["dir"])
			win32api.RegSetValueEx(key, "login", 0, win32con.REG_SZ, share["login"])
			win32api.RegSetValueEx(key, "password", 0, win32con.REG_SZ, share["password"])
			win32api.RegCloseKey(key)
			
			# Set the name
			path = hiveName+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\MountPoints2\##%s#%s"%(share["server"], share["dir"])
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "_LabelFromReg", 0, win32con.REG_SZ, share["name"])
			win32api.RegCloseKey(key)
			
			shareNum+= 1
		
		if self.profile is not None:
			# Redirect the Shell Folders to the remote profile
			path = hiveName+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders"
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "Desktop",  0, win32con.REG_SZ, os.path.join(r"\\%s"%(self.profile["server"]), self.profile["dir"], self.DesktopDir))
			win32api.RegSetValueEx(key, "Personal", 0, win32con.REG_SZ, os.path.join(r"\\%s"%(self.profile["server"]), self.profile["dir"], self.DocumentsDir))
			win32api.RegCloseKey(key)
	
	
	def getFreeLetter(self):
		# ToDo: manage a global LOCK system to avoid two threads get the same result
		
		drives = win32api.GetLogicalDriveStrings().split('\x00')[:-1]
	
		for i in "ZYXWVUTSRQPONMLKJIHGFEDCBA":
			letter = "%s:\\"%(i.upper())
			#print letter
			if letter not in drives:
				return i
		
		return None
