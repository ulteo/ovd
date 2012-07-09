# -*- coding: UTF-8 -*-

# Copyright (C) 2010-2012 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2010, 2012
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

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Profile import Profile as AbstractProfile

import Reg
import Util

class Profile(AbstractProfile):	
	registry_copy_blacklist = [r"Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders"]
	
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
			return True
		
		try:
			win32wnet.WNetCancelConnection2(self.mountPoint, 0, True)
		
		except Exception, err:
			Logger.error("Unable to umount drive")
			Logger.debug("WNetCancelConnection2 return %s"%(err))
			Logger.debug("Unable to umount drive, net use command equivalent: '%s'"%("net use %s: /delete"%(self.mountPoint)))
			return False
		return True
	
	
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

			# clean temporary file used by windows to load registry
			dirs = None
			try:
				dirs = os.listdir(d)
			except Exception, err:
				Logger.warn("Unable to list content of the directory %s (%s)"%(directory, str(err)))
				return

			for content in dirs:
				if content.startswith(r"NTUSER.DAT.LOG") or content.startswith(r"NTUSER.DAT{"):
					try :
						path = os.path.join(d, content)
						os.remove(path)
					except Exception, err:
						Logger.warn("Unable to delete %s (%s)"%(path, str(err)))
			
			# Copy user registry
			
			src = os.path.join(d, "NTUSER.DAT")
			if os.path.exists(src):
				dst = os.path.join(self.session.windowsProfileDir, "NTUSER.DAT")
				
				rand = random.randrange(10000, 50000)
				
				hiveName_src = "OVD_%s_%d"%(str(self.session.id), rand)
				win32api.RegLoadKey(win32con.HKEY_USERS, hiveName_src, src)
				
				hiveName_dst = "OVD_%s_%d"%(str(self.session.id), rand+1)
				win32api.RegLoadKey(win32con.HKEY_USERS, hiveName_dst, dst)
				
				hkey_src = win32api.RegOpenKey(win32con.HKEY_USERS, r"%s"%(hiveName_src), 0, win32con.KEY_ALL_ACCESS)
				hkey_dst = win32api.RegOpenKey(win32con.HKEY_USERS, r"%s"%(hiveName_dst), 0, win32con.KEY_ALL_ACCESS)
				
				Reg.CopyTree(hkey_src, "Software", hkey_dst, self.registry_copy_blacklist)
				
				win32api.RegCloseKey(hkey_src)
				win32api.RegCloseKey(hkey_dst)
				
				win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName_src)
				win32api.RegUnLoadKey(win32con.HKEY_USERS, hiveName_dst)
				
			
			# Copy AppData
			src = os.path.join(d, "AppData")
			if os.path.exists(src):
				dst = self.session.appDataDir
				
				try:
					Util.copyDirOverride(src, dst, ["Protect", "Start Menu", "Crypto", "ulteo", "Identities"])
				except Exception, err:
					Logger.error("Unable to copy appData from profile")
					Logger.debug("Unable to copy appData from profile: %s"%(str(err)))

			# Copy LocalAppData
			src = os.path.join(d, "LocalAppData")
			if os.path.exists(src):
				dst = self.session.localAppDataDir
				
				try:
					Util.copyDirOverride(src, dst, ["Temp", "Cache", "Caches", "Temporary Internet Files", "History", "Credentials"])
				except Exception, err:
					Logger.error("Unable to copy LocalAppData from profile")
					Logger.debug("Unable to copy LocalAppData from profile: %s"%(str(err)))
	
	
	def copySessionStop(self):
		# etre sur que le type est logoff !
		
		
		d = os.path.join(self.mountPoint, "conf.Windows")
		while not os.path.exists(d):
			try:
				os.makedirs(d)
			except OSError, err:
				Logger.debug2("conf.Windows mkdir failed (concurrent access because of more than one ApS) => %s"%(str(err)))
				continue
		
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
			Util.copyDirOverride(src, dst, ["Protect", "Start Menu", "Crypto", "ulteo", "Identities"])
		except Exception, err:
			Logger.error("Unable to copy appData to profile "+src+" "+dst)
			Logger.debug("Unable to copy appData to profile: %s"%(str(err)))
		
		# Copy localAppData
		src = self.session.localAppDataDir
		dst = os.path.join(d, "LocalAppData")
		try:
			Util.copyDirOverride(src, dst, ["Temp", "Cache", "Temporary Internet Files", "History", "Credentials"])
		except Exception, err:
			Logger.error("Unable to copy localAppData to profile from "+src+" to "+dst)
			Logger.debug("Unable to copy localAppData to profile: %s"%(str(err)))	
	
	
	def overrideRegistry(self, hiveName, username):
		username_motif = r"u([a-zA-Z0-9\x00]{31}_\x00A\x00P\x00S\x00)"
		subpath = "Software\Microsoft\Windows NT\CurrentVersion\Windows Messaging Subsystem\Profiles\Outlook"
		
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
			
			lastUsername = Reg.TreeSearchExpression(hiveName, subpath, username_motif)
			if lastUsername is not None:
				uni_username = username.encode("UTF-16LE")
				Reg.TreeReplace(hiveName, subpath, lastUsername, uni_username)

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
			win32api.RegSetValueEx(key, "Desktop",  0, win32con.REG_SZ, r"U:\%s"%(self.DesktopDir))
			win32api.RegSetValueEx(key, "Personal", 0, win32con.REG_SZ, r"U:\%s"%(self.DocumentsDir))
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
