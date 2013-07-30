# -*- coding: UTF-8 -*-

# Copyright (C) 2010-2012 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2012
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

import commands
import errno
import hashlib
import locale
import os
import pwd
import urllib

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Profile import Profile as AbstractProfile

class Profile(AbstractProfile):
	MOUNT_POINT = "/mnt/ulteo/ovd"
	
	def init(self):
		self.profileMounted = False
		self.folderRedirection = []
		
		self.cifs_dst = os.path.join(self.MOUNT_POINT, self.session.id)
		self.profile_mount_point = os.path.join(self.cifs_dst, "profile")
		self.homeDir = None
	
	
	def mount(self):
		os.makedirs(self.cifs_dst)
		self.homeDir = pwd.getpwnam(self.transformToLocaleEncoding(self.session.user.name))[5]
		
		if self.profile is not None:
			os.makedirs(self.profile_mount_point)
			
			cmd = "mount -t cifs -o username=%s,password=%s,uid=%s,gid=0,umask=077 //%s/%s %s"%(self.profile["login"], self.profile["password"], self.session.user.name, self.profile["server"], self.profile["dir"], self.profile_mount_point)
			cmd = self.transformToLocaleEncoding(cmd)
			Logger.debug("Profile mount command: '%s'"%(cmd))
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("Profile mount failed")
				Logger.debug("Profile mount failed (status: %d) => %s"%(s, o))
				os.rmdir(self.profile_mount_point)
			else:
				self.profileMounted = True
		
		for sharedFolder in self.sharedFolders:
			dest = os.path.join(self.MOUNT_POINT, self.session.id, "sharedFolder_"+ hashlib.md5(sharedFolder["server"]+ sharedFolder["dir"]).hexdigest())
			if not os.path.exists(dest):
				os.makedirs(dest)
			
			print "mount dest ",dest
			cmd = "mount -t cifs -o username=%s,password=%s,uid=%s,gid=0,umask=077 //%s/%s %s"%(sharedFolder["login"], sharedFolder["password"], self.session.user.name, sharedFolder["server"], sharedFolder["dir"], dest)
			cmd = self.transformToLocaleEncoding(cmd)
			Logger.debug("Profile, sharedFolder mount command: '%s'"%(cmd))
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("Profile sharedFolder mount failed")
				Logger.debug("Profile sharedFolder mount failed (status: %d) => %s"%(s, o))
				os.rmdir(dest)
			else:
				sharedFolder["mountdest"] = dest
				home = self.homeDir
				
				dst = os.path.join(home, sharedFolder["name"])
				i = 0
				while os.path.exists(dst):
					dst = os.path.join(home, sharedFolder["name"]+"_%d"%(i))
					i += 1
				
				if not os.path.exists(dst):
					os.makedirs(dst)
				
				cmd = "mount -o bind \"%s\" \"%s\""%(dest, dst)
				cmd = self.transformToLocaleEncoding(cmd)
				Logger.debug("Profile bind dir command '%s'"%(cmd))
				s,o = commands.getstatusoutput(cmd)
				if s != 0:
					Logger.error("Profile bind dir failed")
					Logger.error("Profile bind dir failed (status: %d) %s"%(s, o))
				else:
					sharedFolder["local_path"] = dst
					self.folderRedirection.append(dst)
					self.addGTKBookmark(dst)
		
		if self.profile is not None and self.profileMounted:
			for d in [self.DesktopDir, self.DocumentsDir]:
				src = os.path.join(self.profile_mount_point, d)
				dst = os.path.join(self.homeDir, d)
				
				while not os.path.exists(src):
					try:
						os.makedirs(src)
					except OSError, err:
						if self.isNetworkError(err[0]):
							Logger.warn("Unable to access profile: %s"%(str(err)))
							return
						
						Logger.debug2("Profile mkdir failed (concurrent access because of more than one ApS) => %s"%(str(err)))
						continue
				
				if not os.path.exists(dst):
					os.makedirs(dst)
				
				cmd = "mount -o bind \"%s\" \"%s\""%(src, dst)
				cmd = self.transformToLocaleEncoding(cmd)
				Logger.debug("Profile bind dir command '%s'"%(cmd))
				s,o = commands.getstatusoutput(cmd)
				if s != 0:
					Logger.error("Profile bind dir failed")
					Logger.error("Profile bind dir failed (status: %d) %s"%(s, o))
				else:
					self.folderRedirection.append(dst)
			
			
			self.copySessionStart()
	
	
	def umount(self):
		if self.profile is not None and self.profileMounted:
			self.copySessionStop()
		
		while len(self.folderRedirection)>0:
			d = self.folderRedirection.pop()
			
			try:
				if not self.ismount(d):
					continue
			except IOError, err:
				Logger.error("Unable to check mount point %s :%s"%(d, str(err)))
			
			cmd = "umount \"%s\""%(d)
			cmd = self.transformToLocaleEncoding(cmd)
			Logger.debug("Profile bind dir command: '%s'"%(cmd))
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("Profile bind dir failed")
				Logger.error("Profile bind dir failed (status: %d) %s"%(s, o))
		
		for sharedFolder in self.sharedFolders:
			if sharedFolder.has_key("mountdest"):
				cmd = """umount "%s" """%(sharedFolder["mountdest"])
				cmd = self.transformToLocaleEncoding(cmd)
				Logger.debug("Profile sharedFolder umount dir command: '%s'"%(cmd))
				s,o = commands.getstatusoutput(cmd)
				if s != 0:
					Logger.error("Profile sharedFolder umount dir failed")
					Logger.error("Profile sharedFolder umount dir failed (status: %d) %s"%(s, o))
					continue
				
				try:
					os.rmdir(sharedFolder["mountdest"])
				except OSError, e:
					Logger.error("Unable to delete mount point (%s): %s"%(sharedFolder["mountdest"], str(e)))
		
		if self.profile is not None and self.profileMounted:
			cmd = "umount %s"%(self.profile_mount_point)
			cmd = self.transformToLocaleEncoding(cmd)
			Logger.debug("Profile umount command: '%s'"%(cmd))
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("Profile umount failed")
				Logger.debug("Profile umount failed (status: %d) => %s"%(s, o))
			
			try:
				os.rmdir(self.profile_mount_point)
			except OSError, e:
				Logger.error("Unable to delete mount point (%s): %s"%(self.profile_mount_point, str(e)))

		try:		
			os.rmdir(self.cifs_dst)
		except OSError, e:
			Logger.error("Unable to delete profile (%s): %s"%(self.cifs_dst, str(e)))
		
	
	def copySessionStart(self):
		if self.homeDir is None or not os.path.isdir(self.homeDir):
			return
		
		d = os.path.join(self.profile_mount_point, "conf.Linux")
		if not os.path.exists(d):
			return
		
		# Copy conf files
		cmd = self.getRsyncMethod(d, self.homeDir, True)
		Logger.debug("rsync cmd '%s'"%(cmd))
		
		s,o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.error("Unable to copy conf from profile")
			Logger.debug("Unable to copy conf from profile, cmd '%s' return %d: %s"%(cmd, s, o))
	
	
	def copySessionStop(self):
		if self.homeDir is None or not os.path.isdir(self.homeDir):
			return
		
		d = os.path.join(self.profile_mount_point, "conf.Linux")
		while not os.path.exists(d):
			try:
				os.makedirs(d)
			except OSError, err:
				if self.isNetworkError(err[0]):
					Logger.warn("Unable to access profile: %s"%(str(err)))
					return
				
				Logger.debug2("conf.Linux mkdir failed (concurrent access because of more than one ApS) => %s"%(str(err)))
				continue
		
		# Copy conf files
		cmd = self.getRsyncMethod(self.homeDir, d)
		Logger.debug("rsync cmd '%s'"%(cmd))
		
		s,o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.error("Unable to copy conf to profile")
			Logger.debug("Unable to copy conf to profile, cmd '%s' return %d: %s"%(cmd, s, o))
	
	@staticmethod
	def getRsyncMethod(src, dst, owner=False):
		grep_cmd = " | ".join(['grep -v "/%s$"'%(word) for word in Profile.rsyncBlacklist()])
		find_cmd = 'find "%s" -maxdepth 1 -name ".*" | %s'%(src, grep_cmd)
		
		args = ["-rltD", "--safe-links"]
		if owner:
			args.append("-o")
		
		return 'rsync %s $(%s) "%s/"'%(" ".join(args), find_cmd, dst)
	
	@staticmethod
	def rsyncBlacklist():
		return [".gvfs", ".pulse", ".pulse-cookie", ".rdp_drive", ".Trash", ".vnc"]
	
	
	@staticmethod
	def transformToLocaleEncoding(data):
		try:
			encoding = locale.getpreferredencoding()
		except:
			encoding = "UTF-8"
		
		return data.encode(encoding)
	
	
	@staticmethod
	def ismount(path):
		# The content returned by /proc/mounts escape space using \040
		escaped_path = path.replace(" ", "\\040")
		
		for line in file('/proc/mounts'):
			components = line.split()
			if len(components) > 1 and components[1] == escaped_path:
				return True
		
		return False
	
	
	@staticmethod
	def isNetworkError(err):
		networkError = [errno.EIO, errno.ECOMM,
				errno.ENETDOWN,
				errno.ENETUNREACH,
				errno.ENETRESET,
				errno.ECONNABORTED,
				errno.ECONNRESET,
				errno.ENOTCONN,
				errno.ESHUTDOWN,
				errno.ECONNREFUSED,
				errno.EHOSTDOWN,
				errno.EHOSTUNREACH
				]
		return err in networkError
	
	
	def addGTKBookmark(self, url):
		url = self.transformToLocaleEncoding(url)
		url = urllib.pathname2url(url)
		
		path = os.path.join(self.homeDir, ".gtk-bookmarks")
		f = file(path, "a")
		f.write("file://%s\n"%(url))
		f.close()
	
	
	def register_shares(self, dest_dir):
		if self.profileMounted is True:
			path = os.path.join(dest_dir, self.profile["dir"])
			f = file(path, "w")
			f.write(self.homeDir)
			f.close()
		
		for sharedFolder in self.sharedFolders:
			if not sharedFolder.has_key("local_path"):
				continue
			
			path = os.path.join(dest_dir, sharedFolder["dir"])
			f = file(path, "w")
			f.write(self.transformToLocaleEncoding(sharedFolder["local_path"]))
			f.close()
