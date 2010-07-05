# -*- coding: UTF-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
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
import pwd

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Profile import Profile as AbstractProfile

class Profile(AbstractProfile):
	MOUNT_POINT = "/mnt/ulteo/ovd"
	
	def init(self):
		self.profileMounted = False
		self.folderRedirection = []
		
		self.cifs_dst = os.path.join(self.MOUNT_POINT, self.session.id)
	
	
	def mount(self):
		os.makedirs(self.cifs_dst)
		
		cmd = "mount -t cifs -o username=%s,password=%s,uid=%s,gid=0,umask=077 //%s/%s %s"%(self.login, self.password, self.session.user.name, self.host, self.directory, self.cifs_dst)
		Logger.debug("Profile mount command: '%s'"%(cmd))
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("Profile mount failed")
			Logger.debug("Profile mount failed (status: %d) => %s"%(s, o))
		else:
			self.profileMounted = True
		
		
		home = pwd.getpwnam(self.session.user.name)[5]
		for d in ["Desktop", "My Documents"]:
			src = os.path.join(self.cifs_dst, d)
			dst = os.path.join(home, d)
			
			if not os.path.exists(src):
				os.makedirs(src)
			
			if not os.path.exists(dst):
				os.makedirs(dst)
			
			cmd = "mount -o bind \"%s\" \"%s\""%(src, dst)
			Logger.debug("Profile bind dir command '%s'"%(cmd))
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("Profile bind dir failed")
				Logger.error("Profile bind dir failed (status: %d) %s"%(s, o))
			else:
				self.folderRedirection.append(dst)
	
	
	def umount(self):
		while len(self.folderRedirection)>0:
			d = self.folderRedirection.pop()
			
			if not os.path.ismount(d):
				continue
			
			cmd = "umount \"%s\""%(d)
			Logger.debug("Profile bind dir command: '%s'"%(cmd))
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("Profile bind dir failed")
				Logger.error("Profile bind dir failed (status: %d) %s"%(s, o))
		
		if self.profileMounted:
			cmd = "umount %s"%(self.cifs_dst)
			Logger.debug("Profile umount command: '%s'"%(cmd))
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("Profile umount failed")
				Logger.debug("Profile umount failed (status: %d) => %s"%(s, o))
			
			os.rmdir(self.cifs_dst)
