# -*- coding: UTF-8 -*-

# Copyright (C) 2010-2014 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011, 2012, 2013
# Author David LECHEVALIER <david@ulteo.com> 2010, 2012, 2013, 2014
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2012, 2014
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
import time
import urlparse
import win32api
import win32con
import win32file
import win32netcon
import win32security
import win32wnet
from win32com.shell import shell, shellcon

from ovd.Logger import Logger
from ovd.Platform.System import System
from ovd.Role.ApplicationServer.Config import Config
from ovd.Role.ApplicationServer.Profile import Profile as AbstractProfile

import Reg
import Util
from GPO import GPO


class Profile(AbstractProfile):
	registry_copy_blacklist = [r"Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders"]
	vfsCommand = u"VFS.exe"
	vfsParameter = u"/s U:"
	
	def init(self):
		self.mountPoint = None
	
	@staticmethod
	def cleanup():
		gpo = GPO()
		if not gpo.parse():
			Logger.error("Failed to parse GPO")
			return False
		
		absCommand = Util.get_from_PATH(Profile.vfsCommand)
		if absCommand is None:
			Logger.warn("VFS.exe is not installed")
			return False
		
		if not gpo.contain(GPO.LOGOFF, absCommand, Profile.vfsParameter):
			gpo.remove(GPO.LOGOFF, absCommand, Profile.vfsParameter)
                        return gpo.save()
		
		return True
	
	
	def hasProfile(self):
		return (self.profile is not None)
	
	
	def mount(self):
		buf = self.getFreeLetter()
		if buf is None:
			Logger.warn("No drive letter available: unable to init profile")
			return
		
		self.mountPoint = "%s:"%(buf)
		mount_uri = self.getMountURI(self.profile["uri"])
		if mount_uri is None:
			return False
		
		if self.profile.has_key("login"):
			login = self.profile["login"]
		else:
			login = None
		
		if self.profile.has_key("password"):
			password = self.profile["password"]
		else:
			password = None
		
		try:
			win32wnet.WNetAddConnection2(win32netcon.RESOURCETYPE_DISK, self.mountPoint, mount_uri, None, login, password)
		
		except Exception, err:
			cmd = "net use %s %s"%(self.mountPoint, mount_uri)
			if password is not None:
				cmd+= " XXXXXXXX"
			
			if login is not None:
				cmd+= " /user:"+login
			
			Logger.exception("Unable to mount drive")
			Logger.debug("Unable to mount drive, try the net use command equivalent: '%s'"%cmd)
			
			self.mountPoint = None
			return False
		
		return True
	
	
	def installLogoffGPO(self):
		gpo = GPO()
		if gpo.parse() is False:
			Logger.error("Failed to parse GPO file")
			return False
		
		
		absCommand = Util.get_from_PATH(Profile.vfsCommand)
		if absCommand is None:
			Logger.warn("VFS.exe is not installed")
			return False
		
		if not gpo.contain(GPO.LOGOFF, absCommand, Profile.vfsParameter):
			gpo.add(GPO.LOGOFF, absCommand, Profile.vfsParameter)
			return gpo.save()
		
		return True
	
	
	def umount(self):
		if self.mountPoint is None:
			return True
		
		try:
			win32wnet.WNetCancelConnection2(self.mountPoint, 0, True)
		
		except Exception:
			Logger.exception("Unable to umount drive")
			Logger.debug("Unable to umount drive, net use command equivalent: '%s'"%("net use %s: /delete"%(self.mountPoint)))
			return False
		return True
	
	
	@staticmethod
	def getMountURI(uri_):
		u = urlparse.urlparse(uri_)
		if u.scheme == "cifs":
			return r"\\%s\%s"%(u.netloc, u.path[1:])
		
		if u.scheme == "webdav":
			return urlparse.urlunparse(("http", u.netloc, u.path, u.params, u.query, u.fragment))
		
		if u.scheme == "webdavs":
			return urlparse.urlunparse(("https", u.netloc, u.path, u.params, u.query, u.fragment))
		
		Logger.warn("Shouldn't appear: unknown protocol in share uri '%s'"%(uri_))
		return None
	
	
	@staticmethod
	def toCygPath(path):
		res = path.replace(":", ":\\")
		
		if os.path.isabs(res):
                	letter = path[0].lower()
			res = path[2:]
			res = "/cygdrive/"+letter+"/"+path[2:];

		res = res.replace("\\", "/")
		res = res.replace("//", "/")
		return res
	
	
	@staticmethod
        def rsyncBlacklist():
		return []
	
	
	def copySessionStart(self):
		d = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
		profile_tmp_dir = os.path.join(d, "ulteo", "profile", self.session.user.name)
		profile_tmp_dir = System.local_encode(profile_tmp_dir)
		profile_filter = System.local_encode(Config.profile_filters_filename)
		
		for f in [self.DesktopDir, self.DocumentsDir]:
			d = os.path.join(self.mountPoint, "Data", f)
			
			trial = 5
			while not os.path.exists(d):
				try:
					os.makedirs(d)
				except OSError:
					trial -= 1
					if trial == 0:
						Logger.exception("Failed to create directory %s"%d)
						return False
					
					time.sleep(random.randint(1,10)/100.0)
					Logger.debug2("Profile mkdir failed (concurrent access because of more than one ApS)")
					continue
		
		
		d = os.path.join(self.mountPoint, "conf.Windows.%s"%System.getWindowsVersionName())
		if os.path.exists(d):
			
			# clean temporary file used by windows to load registry
			dirs = None
			try:
				dirs = os.listdir(d)
			except Exception:
				Logger.exception("Unable to list content of the directory %s"%d)
				return
			
			for content in dirs:
				if content.startswith(r"NTUSER.DAT.LOG") or content.startswith(r"NTUSER.DAT{"):
					try :
						path = os.path.join(d, content)
						os.remove(path)
					except Exception:
						Logger.exception("Unable to delete %s"%path)
			
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
			
			# Copy configuration File
			if self.profile['profile_mode'] == 'standard':
				cmd = self.getRsyncMethod(Profile.toCygPath(d), Profile.toCygPath(profile_tmp_dir), Profile.toCygPath(profile_filter))
        	        	Logger.debug("rsync cmd '%s'"%(cmd))
				
		                p = System.execute(cmd)
        		        if p.returncode is not 0:
                		        Logger.error("Unable to copy conf from profile")
                        		Logger.debug("Unable to copy conf from profile, cmd '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read()))
			
		if os.path.exists(profile_tmp_dir):
			System.rchown(profile_tmp_dir, self.session.user.name)

	
	
	def copySessionStop(self):
		# etre sur que le type est logoff !
		
		
		d = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
		profile_tmp_dir = os.path.join(d, "ulteo", "profile", self.session.user.name)
		profile_tmp_dir = System.local_encode(profile_tmp_dir)
		profile_filter = System.local_encode(Config.profile_filters_filename)

		d = os.path.join(self.mountPoint, "conf.Windows.%s"%System.getWindowsVersionName())
		trial = 5
		while not os.path.exists(d):
			try:
				os.makedirs(d)
			except OSError:
				trial -= 1
				if trial == 0:
					Logger.exception("Failed to create directory %s"%d)
					return False
				
				time.sleep(random.randint(1,10)/100.0)	
				Logger.debug2("conf.Windows mkdir failed (concurrent access because of more than one ApS)")
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
		
		
		# Copy configuration File
		if self.profile['profile_mode'] == 'standard':
			cmd = self.getRsyncMethod(Profile.toCygPath(profile_tmp_dir), Profile.toCygPath(d), Profile.toCygPath(profile_filter))
			Logger.debug("rsync cmd '%s'"%(cmd))
		
			p = System.execute(cmd)
			if p.returncode is not 0:
				Logger.error("Unable to copy conf to profile")
				Logger.debug("Unable to copy conf to profile, cmd '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read()))
		
		if os.path.exists(profile_tmp_dir):
			System.DeleteDirectory(profile_tmp_dir)
	
	
	def overrideRegistry(self, hiveName, username):
		fileservers = []
		username_motif = r"u([a-zA-Z0-9\x00]{31}_\x00A\x00P\x00S\x00)"
		subpath = "Software\Microsoft\Windows NT\CurrentVersion\Windows Messaging Subsystem\Profiles\Outlook"
		
		if (self.profile is not None and self.mountPoint is not None) or len(self.sharedFolders)>0:
			Reg.CreateKeyR(win32con.HKEY_USERS, hiveName+r"\Software\Ulteo")
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, hiveName+r"\Software\Ulteo", 0, win32con.KEY_ALL_ACCESS)
			Reg.DeleteTree(key, r"ovd", False)
			win32api.RegCloseKey(key)
		
		
		if self.profile is not None and self.mountPoint is not None:
			path = hiveName+r"\Software\Ulteo\ovd\profile"
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "rid", 0, win32con.REG_SZ, self.profile["rid"])
			win32api.RegSetValueEx(key, "uri", 0, win32con.REG_SZ, self.profile["uri"])
			if self.profile.has_key("login"):
				win32api.RegSetValueEx(key, "login", 0, win32con.REG_SZ, self.profile["login"])
			
			if self.profile.has_key("password"):
				win32api.RegSetValueEx(key, "password", 0, win32con.REG_SZ, self.profile["password"])
			
			win32api.RegCloseKey(key)
			
			# Set the name
			u = urlparse.urlparse(self.profile["uri"])
			path = hiveName+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\MountPoints2\##%s#%s"%(u.netloc, u.path[1:].replace("/", "#"))
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			fileservers.append(u.netloc)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "_LabelFromReg", 0, win32con.REG_SZ, "Personal User Profile")
			win32api.RegCloseKey(key)
			
			lastUsername = Reg.TreeSearchExpression(hiveName, subpath, username_motif)
			if lastUsername is not None:
				uni_username = username.encode("UTF-16LE")
				Reg.TreeReplace(hiveName, subpath, lastUsername, uni_username)
		
		shareNum = 0
		for share in self.sharedFolders:
			path = hiveName+r"\Software\Ulteo\ovd\%s"%(share["rid"])
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "rid", 0, win32con.REG_SZ, share["rid"])
			win32api.RegSetValueEx(key, "uri", 0, win32con.REG_SZ, share["uri"])
			if share.has_key("login"):
				win32api.RegSetValueEx(key, "login", 0, win32con.REG_SZ, share["login"])
			
			if share.has_key("password"):
				win32api.RegSetValueEx(key, "password", 0, win32con.REG_SZ, share["password"])
			
			win32api.RegCloseKey(key)
			
			# Set the name
			u = urlparse.urlparse(share["uri"])
			# No need to authorize shortcut in sharedFolder ??
			#if u.netloc not in fileservers:
			#	fileservers.append(u.netloc)
			#
			path = hiveName+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\MountPoints2\##%s#%s"%(u.netloc, u.path[1:].replace("/", "#"))
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "_LabelFromReg", 0, win32con.REG_SZ, share["name"])
			win32api.RegCloseKey(key)
			
			shareNum+= 1
		
		if self.profile is not None:
			# Redirect the Shell Folders to the remote profile
			d = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
			path = hiveName+r"\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders"
			base = os.path.join(d, r"ulteo\profile", self.session.user.name)
			
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "Desktop",  0, win32con.REG_SZ, r"U:\Data\%s"%(self.DesktopDir))
			win32api.RegSetValueEx(key, "Personal", 0, win32con.REG_SZ, r"U:\Data\%s"%(self.DocumentsDir))
			win32api.RegSetValueEx(key, "AppData", 0, win32con.REG_SZ, os.path.join(base, "CSIDL_APPDATA"))
			win32api.RegSetValueEx(key, "Local AppData", 0, win32con.REG_SZ, os.path.join(base, "CSIDL_LOCAL_APPDATA"))
			win32api.RegCloseKey(key)
			
		
		# Register file server
		rangeNumber = 1
		for address in fileservers:
			path = hiveName+r"\Software\Microsoft\Windows\CurrentVersion\Internet Settings\ZoneMap\Ranges\Range%i"%(rangeNumber)
			Reg.CreateKeyR(win32con.HKEY_USERS, path)
			key = win32api.RegOpenKey(win32con.HKEY_USERS, path, 0, win32con.KEY_SET_VALUE)
			win32api.RegSetValueEx(key, "file",  0, win32con.REG_DWORD, 1)
			win32api.RegSetValueEx(key, ":Range",  0, win32con.REG_SZ, address)
			rangeNumber += 1
	
	
	def getFreeLetter(self):
		# ToDo: manage a global LOCK system to avoid two threads get the same result
		
		drives = win32api.GetLogicalDriveStrings().split('\x00')[:-1]
		
		for i in "ZYXWVUTSRQPONMLKJIHGFEDCBA":
			letter = "%s:\\"%(i.upper())
			#print letter
			if letter not in drives:
				return i
		
		return None
