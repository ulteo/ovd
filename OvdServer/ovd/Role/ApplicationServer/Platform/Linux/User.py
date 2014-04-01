# -*- coding: utf-8 -*-

# Copyright (C) 2010-2014 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2012, 2013
# Author David LECHEVALIER <david@ulteo.com> 2011, 2012, 2013
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

import fcntl
import grp
import os
import pwd
import subprocess
import time
import xrdp

from ovd.Logger import Logger
from ovd.Platform.System import System
from ovd.Platform.MountPoint import MountPoint
from ovd.Role.ApplicationServer.User import User as AbstractUser
from ovd.Role.ApplicationServer.Config import Config


class FileLock():
	def __init__(self, path):
		self.path = path
	
	def acquire(self):
		try:
			self.fp = open(self.path, 'w')
			self.lock = fcntl.flock(self.fp.fileno(),fcntl.LOCK_EX)
		except Exception, err:
			Logger.exception("Error while acquiring user lock")
	
	def release(self):
		try:
			self.lock = fcntl.flock(self.fp.fileno(),fcntl.LOCK_UN)
			self.fp.close()
		except Exception:
			Logger.exception("Error while releasing user lock")



class User(AbstractUser):
	CUSTOM_PASSWD_FILE = "/etc/ulteo/ovd/users.passwd"
	
	def create(self):
		lock = FileLock("/tmp/user.lock")
		
		# TODO get the default home in /etc/default/useradd
		default_home_dir = os.path.join(u"/home", self.name)
		home_dir = default_home_dir
		i = 0
		while os.path.exists(home_dir) and i < 100:
			home_dir = default_home_dir+"_%d"%(i)
			i+= 1

		if i > 0:
			Logger.warn("Unable to create home directory %s, the home is now %s"%(default_home_dir, home_dir))

		if os.path.exists(home_dir):
			Logger.error("Unable to find a valid home directory")
			return False
		
		cmd = u"useradd -m -d '%s' -k '%s'"%(home_dir, Config.linux_skel_directory)
		if self.infos.has_key("displayName"):
			cmd+= u""" --comment "%s,,," """%(self.infos["displayName"].replace('"', ""))
		
		groups = ["video", "audio", "pulse", "pulse-access", Config.linux_fuse_group]
		if self.infos.has_key("groups"):
			groups+= self.infos["groups"]
		cmd+= u" --groups %s"%(",".join(groups))
		
		cmd+= u" "+self.name
		
		retry = 5
		while retry !=0:
			if retry < 0:
				  Logger.error("ERROR: unable to add a new user")
			lock.acquire()
			p = System.execute(System.local_encode(cmd))
			lock.release()
			if p.returncode == 0:
				break
			
			Logger.debug("Add user :retry %i"%(6-retry))
			if p.returncode == 9: # user already exist
				Logger.error("User %s already exist"%(self.name))
				break;
			if p.returncode == 1: # an other process is creating a user
				Logger.error("An other process is creating a user")
				retry -=1
				time.sleep(0.2)
				continue
			if p.returncode != 0:
				Logger.error("UserAdd return %d (%s)"%(p.returncode, p.stdout.read()))
				return False
		
		
		if self.infos.has_key("password"):
			if not self.set_password():
				return False
		
		return self.post_create()
	
	def set_password(self):
		lock = FileLock("/tmp/user.lock")
		
		cmd = 'passwd -r files "%s"'%(self.name)
		password = "%s\n"%(self.infos["password"])
		retry = 5
		while retry !=0:
			if retry < 0:
				Logger.error("ERROR: unable to add a new user")
				return False
			
			lock.acquire()
			p = self.exec_command(cmd, False)
			p.stdin.write(password)
			p.stdin.write(password)
			p.wait()
			lock.release()
			if p.returncode == 0:
				break
			
			Logger.debug("Passwd of %s:retry %i"%(self.name, 6-retry))
			if p.returncode == 5: # an other process is creating a user
				Logger.debug("An other process is creating a user")
				retry -=1
				time.sleep(0.2)
				continue
			if p.returncode != 0:
				Logger.error("chpasswd return %d (%s)"%(p.returncode, p.stdout.read()))
				return False
		return True
	
	
	def disable_password(self):
		lock = FileLock("/tmp/user.lock")
		
		cmd = 'passwd -r files -d "%s"'%(self.name)
		retry = 5
		while retry !=0:
			if retry < 0:
				Logger.error("ERROR: unable to disable user")
				return False
			lock.acquire()
			p = self.exec_command(cmd)
			lock.release()
			if p.returncode == 0:
				break
			
			Logger.debug("Passwd of %s:retry %i"%(self.name, 6-retry))
			if p.returncode == 5: # an other process is creating a user
				Logger.debug("An other process is creating a user")
				retry -=1
				time.sleep(0.2)
				continue
			if p.returncode != 0:
				Logger.error("passwd return %d (%s)"%(p.returncode, p.stdout.read()))
				return False
		return True
	
	
	def set_custom_password(self):
		if not os.path.exists(self.CUSTOM_PASSWD_FILE):
			Logger.error("Custom password file '%s' doesn't not exists. Please create it and update your pam auth rules to use it before retry use custom password system"%self.CUSTOM_PASSWD_FILE)
			return False
		
		cmd = 'htpasswd -b "%s" "%s" "%s"'%(self.CUSTOM_PASSWD_FILE, self.name, self.infos["password"])
		
		lock = FileLock("/tmp/user.lock")
		lock.acquire()
		p = self.exec_command(cmd)
		lock.release()
		
		if p.returncode != 0:
			Logger.error("htpasswd return %d (%s)"%(p.returncode, p.stdout.read()))
			return False
		
		return True
	
	
	def disable_custom_password(self):
		if not os.path.exists(self.CUSTOM_PASSWD_FILE):
			return True
		
		cmd = 'htpasswd -D "%s" "%s"'%(self.CUSTOM_PASSWD_FILE, self.name)
		
		lock = FileLock("/tmp/user.lock")
		lock.acquire()
		p = self.exec_command(cmd)
		lock.release()
		
		if p.returncode != 0:
			Logger.error("passwd return %d (%s)"%(p.returncode, p.stdout.read()))
			return False
		
		return True
	
	
	def post_create(self):
		name = System.local_encode(self.name)
		
		if self.infos.has_key("shell"):
			xrdp.UserSetShell(name, self.infos["shell"])
			xrdp.UserAllowUserShellOverride(name, True)
		
		
		try:		
			self.home = pwd.getpwnam(name)[5]
		except KeyError:
			return False
		return True
	
	
	def exists(self):
		try:
			pwd.getpwnam(System.local_encode(self.name))
		except KeyError:
			return False
		
		return True
	
	
	def destroy(self):
		lock = FileLock("/tmp/user.lock")
		
		arg_remove = ""
		if self.check_remaining_mount_points():
			arg_remove = "--remove"
		
		cmd = "userdel --force %s %s"%(arg_remove, System.local_encode(self.name))
		
		retry = 5
		while retry !=0:
			lock.acquire()
			p = System.execute(cmd)
			lock.release()
			if p.returncode == 0:
				return True
			if p.returncode == 12:
				Logger.debug("mail dir error: '%s' return %d => %s"%(str(cmd), p.returncode, p.stdout.read()))
				return True
			
			Logger.debug("User delete of %s: retry %i"%(self.name, 6-retry))
			if p.returncode == 1 or p.returncode == 10: # an other process is creating a user
				Logger.debug("An other process is creating a user")
				retry -=1
				time.sleep(0.2)
				continue
			if p.returncode != 0:
				Logger.error("userdel return %d (%s)"%(p.returncode, p.stdout.read()))
				return False
		
		return True
	
	
	def check_remaining_mount_points(self):
		try:
			user = pwd.getpwnam(System.local_encode(self.name))
		except KeyError:
			return False
		
		mount_points = MountPoint.get_list(user.pw_dir)
		if mount_points is None:
			return False
		
		success = True
		for d in mount_points:
			path = System.local_encode(d)
			Logger.warn("Remaining mount point '%s'"%(path))
			cmd = 'umount "%s"'%(path)
			
			p = System.execute(cmd)
			if p.returncode == 0:
				continue
			
			Logger.warn("Unable to unmount remaining mount point %s: force the unmount"%(path))
			Logger.debug('umount command "%s" return: %s'%(cmd,  p.stdout.read()))
			cmd = 'umount -l "%s"'%(path)
			p = System.execute(cmd)
			if p.returncode != 0:
				Logger.error("Unable to force the unmount remaining mount point %s"%(path))
				Logger.debug('umount command "%s" return: %s'%(cmd,  p.stdout.read()))
			
			success = False
		
		if success == False:
			Logger.error("Unable to unmount remaining mount point, home dir %s won't be purged"%(user.pw_dir))
		
		return success
	
	
	@classmethod
	def exec_command(cls, cmd, wait=True):
			subprocess_args = {}
			subprocess_args["stdin"] = subprocess.PIPE
			subprocess_args["stdout"] = subprocess.PIPE
			subprocess_args["stderr"] = subprocess.STDOUT
			subprocess_args["shell"] = True
			subprocess_args["preexec_fn"] = os.setpgrp
			
			p = subprocess.Popen(System.local_encode(cmd), **subprocess_args)
			if wait:
					p.wait()
			
			return p
