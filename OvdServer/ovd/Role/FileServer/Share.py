# -*- coding: utf-8 -*-

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
		self.users = []
		
		self.active = False
	
	
	def exists(self):
		return os.path.isdir(self.directory)
	
	
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
		users = ["%s:f"%(user) for user in self.users]
		
		cmd = 'net usershare add %s "%s" %s %s,Everyone:d'%(self.name, self.directory, self.name, ",".join(users))
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.error("FS: unable to add share")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			return False
		
		path = os.path.join(self.directory, ".htaccess")
		try:
			f = file(path, "w")
		except IOError, err:
			Logger.error("FS: unable to wrtite .htaccess")
			Logger.debug("FS: unable to wrtite .htaccess '%s' return: %s"%(path, str(err)))
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
		

		self.active = False
		return ret
	
	
	def add_user(self, user, password):
		cmd = "useradd -d /dev/null -s /bin/false -G %s %s"%(Config.group, user)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("FS: unable to create user")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			return False
		
		
		cmd = 'echo "%s\\n%s" | smbpasswd -s -a %s'%(password, password, user)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("FS: unable to set samba password")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			return False
		
		cmd = 'htpasswd -b %s "%s" "%s"'%(Config.dav_passwd_file, user, password)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("FS: unable to update apache auth file")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			return False
		
		
		self.users.append(user)
		return True
	
	
	def del_user(self, user):
		ret = True
		
		cmd = 'htpasswd -D %s "%s"'%(Config.dav_passwd_file, user)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("FS: unable to update apache auth file")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			return False
		
		
		cmd = 'smbpasswd -x %s'%(user)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("FS: unable to del smb password")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			ret = False
		
		cmd = "userdel %s"%(user)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("FS: unable to create user")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			ret = False
		
		if user in self.users:
			self.users.remove(user)
		
		return ret
