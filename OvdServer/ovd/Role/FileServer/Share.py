# -*- coding: utf-8 -*-

# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011, 2012
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

import os
import stat

from ovd.Logger import Logger
from ovd.Platform.System import System

from Config import Config
from HTGroup import HTGroup
from HTAccess import HTAccess


class Share:
	STATUS_NOT_EXISTS = 1
	STATUS_ACTIVE = 2
	STATUS_INACTIVE = 3
	
	def __init__(self, name, directory):
		self.name = name
		self.directory = directory + "/" + name
		self.group = "ovd_share_"+self.name
		self.ro_users = []
		self.rw_users = []
		self.htaccess = HTAccess(self.directory)
		
		self.active = False
	
	
	def exists(self):
		return os.path.isdir(self.directory)
	
	
	def isActive(self):
		return self.active
	
	
	def status(self):
		if not self.exists():
			return self.STATUS_NOT_EXISTS
		
		if self.active:
			return self.STATUS_ACTIVE
		
		return self.STATUS_INACTIVE

	
	def create(self):
		try:
			os.mkdir(self.directory, 0700)
			os.chown(self.directory, -1, Config.gid)
			os.chmod(self.directory, stat.S_IRWXU | stat.S_IRWXG | stat.S_ISGID)
			
			# TODO Find a better way
			if self.name.startswith("p"):
				## Creating minimal skeleton
				path = os.path.join(self.directory, "Data")
				os.mkdir(path, 0700)
				os.chown(path, Config.uid, Config.gid)
				os.chmod(path, stat.S_IRWXU | stat.S_IRWXG | stat.S_ISGID)
			
		except:
			Logger.warn("FS: unable to create profile '%s'"%(self.name))
			return False
		
		return True
	
	
	def delete(self):
		cmd = "rm -rf %s"%(self.directory)
		p = System.execute(cmd)
		if p.returncode is not 0:
			Logger.error("FS: unable to del share")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			return False
		
		return True
	
	
	def enable(self, mode):
		for i in ['ro', 'rw']:
			cmd = "groupadd  %s_%s"%(self.group, i)
			p = System.execute(cmd)
			if p.returncode is not 0:
				Logger.error("FS: unable to create group")
				Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
				return False
		
		cmd = 'net usershare add %s "%s" %s %s_ro:r,%s_rw:f,Everyone:d'%(self.name, self.directory, self.name, self.group, self.group)
		p = System.execute(cmd)
		if p.returncode is not 0:
			Logger.error("FS: unable to add share")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			return False
		
		self.do_right_normalization()
		
		self.htaccess.addGroup(self.group)
		self.htaccess.save()
		
		self.active = True
		return True
	
	
	def disable(self):
		path = os.path.join(self.directory, ".htaccess")
		if not os.path.exists(path):
			ret = False
			Logger.error("FS: no .htaccess")
			Logger.debug("FS: no .htaccess '%s'"%(path))
		else:
			try:
				os.remove(path)
			except Exception, err:
				ret = False
				Logger.error("FS: unable to remove .htaccess")
				Logger.debug("FS: unable to remove .htaccess '%s' return: %s"%(path, str(err)))
		
		
		cmd = "net usershare delete %s"%(self.name)
		p = System.execute(cmd)
		ret = True
		if p.returncode is not 0:
			ret = False
			Logger.error("FS: unable to del share")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
		
		for i in ['rw', 'ro']:
			cmd = "groupdel  %s_%s"%(self.group, i)
			p = System.execute(cmd)
			if p.returncode is not 0:
				ret = False
				Logger.error("FS: unable to del group")
				Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
		
		self.do_right_normalization()
		
		self.htaccess.delGroup(self.group)
		self.htaccess.save()
		
		self.active = False
		return ret
	
	
	def do_right_normalization(self):
		cmd = 'chown -R %s:%s "%s"'%(Config.uid, Config.gid, self.directory)
		p = System.execute(cmd)
		if p.returncode is not 0:
			Logger.debug("FS: following command '%s' returned %d => %s"%(cmd, p.returncode, p.stdout.read()))
		
		cmd = 'chmod -R u=rwX,g=rwX,o-rwx "%s"'%(self.directory)
		p = System.execute(cmd)
		if p.returncode is not 0:
			Logger.debug("FS: following command '%s' returned %d => %s"%(cmd, p.returncode, p.stdout.read()))
	
	
	def add_user(self, user, mode):
		if not self.active:
			self.enable(mode)
		
		cmd = "adduser %s %s_%s"%(user, self.group, mode)
		p = System.execute(cmd)
		if p.returncode is not 0:
			Logger.error("FS: unable to add user in group")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			return False
		
		htgroup = HTGroup(Config.dav_group_file)
		
		if mode == 'rw':
			self.rw_users.append(user)
			htgroup.add(user, self.group+"_rw")
		else:
			self.ro_users.append(user)
			htgroup.add(user, self.group+"_ro")

		return True
	
	
	def del_user(self, user):
		if user not in self.ro_users and user not in self.rw_users:
			return True
		
		ret = True
		if user in self.ro_users:
			cmd = "deluser %s %s_ro"%(user, self.group)
		else:
			cmd = "deluser %s %s_rw"%(user, self.group)
		
		p = System.execute(cmd)
		if p.returncode is not 0:
			ret = False
			Logger.error("FS: unable to del user in group")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
		
		htgroup = HTGroup(Config.dav_group_file)
		
		if user in self.ro_users:
			self.ro_users.remove(user)
			htgroup.delete(user, self.group+"_ro")
		if user in self.rw_users:
			self.rw_users.remove(user)
			htgroup.delete(user, self.group+"_rw")
		
		if (len(self.ro_users) + len(self.rw_users)) == 0:
			return self.disable()
		
		return True
	
	
	def has_user(self, user):
		return (user in self.ro_users) or (user in self.rw_users)
