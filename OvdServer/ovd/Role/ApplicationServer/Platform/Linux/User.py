# -*- coding: utf-8 -*-

# Copyright (C) 2010-2012 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2012
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

import fcntl
import pwd
import time
import xrdp

from ovd import commands
from ovd.Logger import Logger
from ovd.Platform.System import System
from ovd.Role.ApplicationServer.User import User as AbstractUser


class FileLock():
	def __init__(self, path):
		self.path = path
	
	def acquire(self):
		try:
			self.fp = open(self.path, 'w')
			self.lock = fcntl.flock(self.fp.fileno(),fcntl.LOCK_EX)
		except Exception, err:
			Logger.error("Error while acquiring user lock(%s)"%(str(err)))
	
	def release(self):
		try:
			self.lock = fcntl.flock(self.fp.fileno(),fcntl.LOCK_UN)
			self.fp.close()
		except Exception, e:
			Logger.error("Error while releasing user lock(%s)"%(str(err)))



class User(AbstractUser):
	def create(self):
		lock = FileLock("/tmp/user.lock")
		
		cmd = u"useradd -m -k /dev/null"
		if self.infos.has_key("displayName"):
			cmd+= u" --comment '%s,,,'"%(self.infos["displayName"])
		
		groups = ["video", "audio", "pulse", "pulse-access", "fuse"]
		if self.infos.has_key("groups"):
			groups+= self.infos["groups"]
		cmd+= u" --groups %s"%(",".join(groups))
		
		cmd+= u" "+self.name
		
		retry = 5
		while retry !=0:
			if retry < 0:
				  Logger.error("ERROR: unable to add a new user")
			lock.acquire()
			p = commands.execute(System.local_encode(cmd))
			lock.release()
			if p.returncode == 0:
				break
			
			Logger.debug("Add user :retry %i"%(6-retry))
			if p.returncode == 2304: # user already exist
				Logger.error("User %s already exist"%(self.name))
				break;
			if p.returncode == 256: # an other process is creating a user
				Logger.error("An other process is creating a user")
				retry -=1
				time.sleep(0.2)
				continue
			if p.returncode != 0:
				Logger.error("UserAdd return %d (%s)"%(p.returncode, p.stdout.read()))
				return False
		
		
		if self.infos.has_key("password"):
			cmd = 'echo "%s:%s" | chpasswd'%(self.name, self.infos["password"])
			retry = 5
			while retry !=0:
				if retry < 0:
					Logger.error("ERROR: unable to add a new user")
					return False
				lock.acquire()
				p = commands.execute(cmd)
				lock.release()
				if p.returncode == 0:
					break
				
				Logger.debug("Chpasswd of %s:retry %i"%(self.name, 6-retry))
				if p.returncode == 256 or p.returncode == 2560: # an other process is creating a user
					Logger.debug("An other process is creating a user")
					retry -=1
					time.sleep(0.2)
					continue
				if p.returncode != 0:
					Logger.error("chpasswd return %d (%s)"%(p.returncode, p.stdout.read()))
					return False
		
		return self.post_create()
	
	
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
		
		cmd = "userdel --force  --remove %s"%(System.local_encode(self.name))
		retry = 5
		while retry !=0:
			lock.acquire()
			p = commands.execute(cmd)
			lock.release()
			if p.returncode == 0:
				return True
			if p.returncode == 12:
				Logger.debug("mail dir error: '%s' return %d => %s"%(str(cmd), p.returncode, p.stdout.read()))
				return True
			
			Logger.debug("User delete of %s: retry %i"%(self.name, 6-retry))
			if p.returncode == 256 or s == 2560: # an other process is creating a user
				Logger.debug("An other process is creating a user")
				retry -=1
				time.sleep(0.2)
				continue
			if p.returncode != 0:
				Logger.error("userdel return %d (%s)"%(p.returncode, p.stdout.read()))
				return False
		
		return True
