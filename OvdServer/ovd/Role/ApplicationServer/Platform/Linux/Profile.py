# -*- coding: UTF-8 -*-

# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011, 2012, 2013
# Author David LECHEVALIER <david@ulteo.com> 2012, 2013
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

import errno
import hashlib
import locale
import os
import pwd
import stat
import time
import urllib
import urlparse

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Config import Config
from ovd.Role.ApplicationServer.Profile import Profile as AbstractProfile
from ovd.Platform.System import System

class Profile(AbstractProfile):
	MOUNT_POINT = "/mnt/ulteo/ovd"
	
	def init(self):
		self.profileMounted = False
		self.folderRedirection = []
		
		self.cifs_dst = os.path.join(self.MOUNT_POINT, self.session.id)
		self.profile_mount_point = os.path.join(self.cifs_dst, "profile")
		self.homeDir = None
	
	
	@staticmethod
	def cleanup():
		pass
	
	
	def mount_cifs(self, share, uri, dest):
		mount_env = {}
		if share.has_key("login") and share.has_key("password"):
			cmd = "mount -t cifs -o 'uid=%s,gid=0,umask=077' //%s%s %s"%(self.session.user.name, uri.netloc, uri.path, dest)
			mount_env["USER"] = share["login"]
			mount_env["PASSWD"] = share["password"]
		else:
			cmd = "mount -t cifs -o 'guest,uid=%s,gid=0,umask=077' //%s%s %s"%(self.session.user.name, uri.netloc, uri.path, dest)
		
		cmd = self.transformToLocaleEncoding(cmd)
		Logger.debug("Profile, share mount command: '%s'"%(cmd))
		p = System.execute(cmd, env=mount_env)
		if p.returncode != 0:
			Logger.debug("CIFS mount failed (status: %d) => %s"%(p.returncode, p.stdout.read()))
			return False
		
		return True
	
	
	def mount_webdav(self, share, uri, dest):
		davfs_conf   = os.path.join(self.cifs_dst, "davfs.conf")
		davfs_secret = os.path.join(self.cifs_dst, "davfs.secret")
		if uri.scheme == "webdav":
			mount_uri = urlparse.urlunparse(("http", uri.netloc, uri.path, uri.params, uri.query, uri.fragment))
		else:
			mount_uri = urlparse.urlunparse(("https", uri.netloc, uri.path, uri.params, uri.query, uri.fragment))
		
		if not System.mount_point_exist(davfs_conf):
			f = open(davfs_conf, "w")
			f.write("ask_auth 0\n")
			f.write("use_locks 0\n")
			f.write("secrets %s\n"%(davfs_secret))
			f.close()
		
		if not System.mount_point_exist(davfs_secret):
			f = open(davfs_secret, "w")
			f.close()
			os.chmod(davfs_secret, stat.S_IRUSR | stat.S_IWUSR)
		
		if share.has_key("login") and share.has_key("password"):
			f = open(davfs_secret, "a")
			f.write("%s %s %s\n"%(mount_uri, share["login"], share["password"]))
			f.close()
		
		cmd = "mount -t davfs -o 'conf=%s,uid=%s,gid=0,dir_mode=700,file_mode=600' '%s' %s"%(davfs_conf, self.session.user.name, mount_uri, dest)
		cmd = self.transformToLocaleEncoding(cmd)
		Logger.debug("Profile, sharedFolder mount command: '%s'"%(cmd))
		p = System.execute(cmd)
		if p.returncode != 0:
			Logger.debug("WebDAV mount failed (status: %d) => %s"%(p.returncode, p.stdout.read()))
			return False
		
		return True
	
	
	def mount(self):
		os.makedirs(self.cifs_dst)
		self.homeDir = pwd.getpwnam(self.transformToLocaleEncoding(self.session.user.name))[5]
		
		if self.profile is not None:
			os.makedirs(self.profile_mount_point)
			
			u = urlparse.urlparse(self.profile["uri"])
			if u.scheme == "cifs":
				ret = self.mount_cifs(self.profile, u, self.profile_mount_point)
			
			elif u.scheme in ("webdav", "webdavs"):
				ret = self.mount_webdav(self.profile, u, self.profile_mount_point)
			else:
				Logger.warn("Profile: unknown protocol in share uri '%s'"%(self.profile["uri"]))
				ret = False
			
			if ret is False:
				Logger.error("Profile mount failed")
				os.rmdir(self.profile_mount_point)
				return False
			else:
				self.profileMounted = True
		
		if self.profile is not None and self.profileMounted:
			for d in [self.DesktopDir, self.DocumentsDir]:
				src = os.path.join(self.profile_mount_point, "Data", d)
				
				while not System.mount_point_exist(src):
					try:
						os.makedirs(src)
					except OSError, err:
						if self.isNetworkError(err[0]):
							Logger.warn("Unable to access profile: %s"%(str(err)))
							return False
						
						Logger.debug2("Profile mkdir failed (concurrent access because of more than one ApS) => %s"%(str(err)))
						continue
				
			if not System.mount_point_exist(self.homeDir):
				os.makedirs(self.homeDir)
			
			cmd = "RegularUnionFS \"%s\" \"%s\" -o user=%s"%(self.profile_mount_point, self.homeDir, self.session.user.name)
			cmd = self.transformToLocaleEncoding(cmd)
			Logger.debug("Profile bind dir command '%s'"%(cmd))
			p = System.execute(cmd)
			if p.returncode != 0:
				Logger.error("Profile bind dir failed")
				Logger.error("Profile bind dir failed (status: %d) %s"%(p.returncode, p.stdout.read()))
				return False
			else:
				self.folderRedirection.append(self.homeDir)
			
			
			self.copySessionStart()
		
		for sharedFolder in self.sharedFolders:
			dest = os.path.join(self.MOUNT_POINT, self.session.id, "sharedFolder_"+ hashlib.md5(sharedFolder["uri"]).hexdigest())
			i = 0
			while System.mount_point_exist(dest):
				dest = os.path.join(self.MOUNT_POINT, self.session.id, "sharedFolder_"+ hashlib.md5(sharedFolder["server"]+ sharedFolder["dir"]).hexdigest()  + str(random.random()))
				i += 1
 			
			os.makedirs(dest)
			
			u = urlparse.urlparse(sharedFolder["uri"])
			if u.scheme == "cifs":
				ret = self.mount_cifs(sharedFolder, u, dest)
			
			elif u.scheme in ("webdav", "webdavs"):
				ret = self.mount_webdav(sharedFolder, u, dest)
			else:
				Logger.warn("Profile: unknown protocol in share uri '%s'"%(self.profile["uri"]))
				ret = False
			
			if ret is False:
				Logger.error("SharedFolder mount failed")
				os.rmdir(dest)
				return False
			else:
				sharedFolder["mountdest"] = dest
				home = self.homeDir
				
				dst = os.path.join(home, sharedFolder["name"])
				i = 0
				while System.mount_point_exist(dst) and self.ismount(dst):
					dst = os.path.join(home, sharedFolder["name"]+"_%d"%(i))
					i += 1
				
				if not System.mount_point_exist(dst):
					os.makedirs(dst)
				
				cmd = "mount -o bind \"%s\" \"%s\""%(dest, dst)
				cmd = self.transformToLocaleEncoding(cmd)
				Logger.debug("Profile bind dir command '%s'"%(cmd))
				p = System.execute(cmd)
				if p.returncode != 0:
					Logger.error("Profile bind dir failed")
					Logger.error("Profile bind dir failed (status: %d) %s"%(p.returncode, p.stdout.read()))
					return False
				else:
					sharedFolder["local_path"] = dst
					self.folderRedirection.append(dst)
					self.addGTKBookmark(dst)
		
		
		return True
	
	
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
			p = System.execute(cmd)
			if p.returncode != 0:
				Logger.error("Profile bind dir failed")
				Logger.error("Profile bind dir failed (status: %d) %s"%(p.returncode, p.stdout.read()))
		
		for sharedFolder in self.sharedFolders:
			if sharedFolder.has_key("mountdest"):
				cmd = """umount "%s" """%(sharedFolder["mountdest"])
				cmd = self.transformToLocaleEncoding(cmd)
				Logger.debug("Profile sharedFolder umount dir command: '%s'"%(cmd))
				p = System.execute(cmd)
				if p.returncode != 0:
					Logger.error("Profile sharedFolder umount dir failed")
					Logger.error("Profile sharedFolder umount dir failed (status: %d) %s"%(p.returncode, p.stdout.read()))
					continue
				
				try:
					os.rmdir(sharedFolder["mountdest"])
				except OSError, e:
					Logger.error("Unable to delete mount point (%s): %s"%(sharedFolder["mountdest"], str(e)))
		
		for fname in ("davfs.conf", "davfs.secret"):
			path = os.path.join(self.cifs_dst, fname)
			if not System.mount_point_exist(path):
				continue
			try:
				os.remove(path)
			except OSError, e:
				Logger.error("Unable to delete file (%s): %s"%(path, str(e)))
		
		if self.profile is not None and self.profileMounted:
			cmd = "umount %s"%(self.profile_mount_point)
			cmd = self.transformToLocaleEncoding(cmd)
			Logger.debug("Profile umount command: '%s'"%(cmd))
			
			for _ in xrange(5):
				p = System.execute(cmd)
				if p.returncode != 0:
					time.sleep(1)
				else:
					break
			
			if p.returncode != 0:
				Logger.error("Profile umount failed")
				Logger.debug("Profile umount failed (status: %d) => %s"%(p.returncode, p.stdout.read()))
			
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
		if not System.mount_point_exist(d):
			return
		
	
	def copySessionStop(self):
		if self.homeDir is None or not os.path.isdir(self.homeDir):
			return
		
		d = os.path.join(self.profile_mount_point, "conf.Linux")
		while not System.mount_point_exist(d):
			try:
				os.makedirs(d)
			except OSError, err:
				if self.isNetworkError(err[0]):
					Logger.warn("Unable to access profile: %s"%(str(err)))
					return
				
				Logger.debug2("conf.Linux mkdir failed (concurrent access because of more than one ApS) => %s"%(str(err)))
				continue
		
	
	@staticmethod
	def getRsyncMethod(src, dst, owner=False):
		args = ["-rltD", "--safe-links"]
		if owner:
			args.append("-o")
		
		if os.path.exists(Config.linux_profile_filters_filename):
			args.append("--include-from=%s"%Config.linux_profile_filters_filename)
		else:
			Logger.warn("Rsync filters file '%s' is missing, Using hardcoded rules"%Config.linux_profile_filters_filename)
			for p in Profile.rsyncBlacklist():
				args.append('--exclude="%s"'%p)
			
			args.append('--include="/.**"')
			args.append('--exclude="/**"')
		
		return 'rsync %s "%s/" "%s/"'%(" ".join(args), src, dst)
	
	
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
			path = os.path.join(dest_dir, self.profile["rid"])
			f = file(path, "w")
			f.write(self.homeDir)
			f.close()
		
		for sharedFolder in self.sharedFolders:
			if not sharedFolder.has_key("local_path"):
				continue
			
			path = os.path.join(dest_dir, sharedFolder["rid"])
			f = file(path, "w")
			f.write(self.transformToLocaleEncoding(sharedFolder["local_path"]))
			f.close()
