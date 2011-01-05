# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
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
import os
import stat

from ovd.Logger import Logger

from Config import Config


class Share:
	STATUS_NOT_EXISTS = 1
	STATUS_ACTIVE = 2
	STATUS_INACTIVE = 3
	
	def __init__(self, name, directory):
		self.name = name
		self.directory = directory + "/" + name
		self.group = "ovd_share_"+self.name
		self.users = []
		
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
		except:
			Logger.warn("FS: unable to create profile '%s'"%(self.name))
			return False
		
		return True
	
	
	def delete(self):
		cmd = "rm -rf %s"%(self.directory)
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.error("FS: unable to del share")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
		
		return s == 0
	
	
	
	def enable(self):
		cmd = "groupadd  %s"%(self.group)
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.error("FS: unable to create group")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			return False
		
		cmd = 'net usershare add %s "%s" %s %s:f,Everyone:d'%(self.name, self.directory, self.name, self.group)
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.error("FS: unable to add share")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			return False
		
		path = os.path.join(self.directory, ".htaccess")
		try:
			f = file(path, "w")
		except IOError, err:
			Logger.error("FS: unable to write .htaccess")
			Logger.debug("FS: unable to write .htaccess '%s' return: %s"%(path, str(err)))
			return False
		for user in self.users:
			f.write("Require user %s\n"%(user))
		f.close()
		
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
		s, o = commands.getstatusoutput(cmd)
		ret = True
		if s is not 0:
			ret = False
			Logger.error("FS: unable to del share")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
		
		
		cmd = "groupdel  %s"%(self.group)
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			ret = False
			Logger.error("FS: unable to del group")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))

		self.active = False
		return ret
	
	
	def add_user(self, user):
		if not self.active:
			self.enable()
		
		cmd = "adduser %s %s"%(user, self.group)
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.error("FS: unable to add user in group")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			return False
		
		self.users.append(user)
		return True
	
	
	def del_user(self, user):
		if user not in self.users:
			return True
		
		ret = True
		cmd = "deluser %s %s"%(user, self.group)
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			ret = False
			Logger.error("FS: unable to del user in group")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
		
		self.users.remove(user)
		if len(self.users) == 0:
			return self.disable()
		
		return True
	
	
	def has_user(self, user):
		return (user in self.users)
