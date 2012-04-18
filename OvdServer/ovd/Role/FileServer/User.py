# -*- coding: utf-8 -*-

# Copyright (C) 2010-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2012
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

import pwd

from ovd import commands
from ovd.Logger import Logger

from Config import Config


class User:
	def __init__(self, login):
		self.login = login
	
	
	def create(self, password):
		cmd = "useradd -d /dev/null -s /bin/false -G %s %s"%(Config.group, self.login)
		p = commands.execute(cmd)
		if p.returncode == 2304:
			Logger.warn("FS: unable to create user: already exists")
			return False
		elif p.returncode != 0:
			Logger.error("FS: unable to create user")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			return False
		
		cmd = 'smbpasswd -s -a %s'%(self.login)
		p = commands.execute(cmd, wait = False)
		p.stdin.write("%s\n%s\n"%(password, password))
		p.wait()
		
		if p.returncode != 0:
			Logger.error("FS: unable to set samba password")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			return False
		
		cmd = 'htpasswd -b %s "%s" "%s"'%(Config.dav_passwd_file, self.login, password)
		p = commands.execute(cmd)
		if p.returncode != 0:
			Logger.error("FS: unable to update apache auth file")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			return False
		
		return True
	
	
	def destroy(self):
		ret = True
		
		cmd = 'htpasswd -D %s "%s"'%(Config.dav_passwd_file, self.login)
		p = commands.execute(cmd)
		if p.returncode != 0:
			Logger.error("FS: unable to update apache auth file")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			return False
		
		cmd = 'smbpasswd -x %s'%(self.login)
		p = commands.execute(cmd)
		if p.returncode != 0:
			Logger.error("FS: unable to del smb password")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			ret = False
		
		cmd = "userdel -f %s"%(self.login)
		p = commands.execute(cmd)
		if p.returncode != 0:
			Logger.error("FS: unable to del user")
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
			ret = False
		
		return ret
	
	
	def clean(self):
		cmd = 'htpasswd -D %s "%s"'%(Config.dav_passwd_file, self.login)
		p = commands.execute(cmd)
		if p.returncode != 0:
			Logger.warn("FS: unable to remove user %s in 'clean' process"%(self.login))
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
		
		cmd = 'smbpasswd -x %s'%(self.login)
		p = commands.execute(cmd)
		if p.returncode != 0:
			Logger.warn("FS: unable to remove user %s in 'clean' process"%(self.login))
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
		
		cmd = "userdel -f %s"%(self.login)
		p = commands.execute(cmd)
		if p.returncode != 0:
			Logger.warn("FS: unable to remove user %s in 'clean' process"%(self.login))
			Logger.debug("FS: command '%s' return %d: %s"%(cmd, p.returncode, p.stdout.read().decode("UTF-8")))
	
	
	def existSomeWhere(self):
		try:
			pwd.getpwnam(self.login)
			return True
		except:
			pass
		
		cmd = "smbpasswd -e %s"%(self.login)
		p = commands.execute(cmd)
		if p.returncode == 0:
			return True
		
		accessOK = False
		try:
			f = file(Config.dav_passwd_file, "r")
			accessOK = True
		except:
			pass
		if accessOK:
			lines = f.readlines()
			f.close
			
			for line in lines:
				if line.startswith(self.login+":"):
					return True
		
		return False
