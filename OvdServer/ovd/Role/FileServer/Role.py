# -*- coding: utf-8 -*-

# Copyright (C) 2009-2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
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
import glob
import os
import time
from xml.dom.minidom import Document

from ovd.Config import Config
from ovd.Logger import Logger
from ovd.Platform import Platform
from ovd.Role.Role import Role as AbstractRole

from Dialog import Dialog
from Share import Share


class Role(AbstractRole):
	group_name = "ovd-fs"
	
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		self.dialog = Dialog(self)
		self.has_run = False
		self.shares = {}
		self.spool = os.path.join(Platform.System.get_default_data_dir(), "fs")
	
	def init(self):
		Logger.info("FileServer init")
		
		if not os.path.isdir(self.spool):
			Logger.info("FileServer never initialized, creating repository on '%s'"%(self.spool))
			os.makedirs(self.spool)
		
		if not Platform.System.groupExist(self.group_name):
			Logger.error("FileServer: group '%s' doesn't exists"%(self.group_name))
			return False
		
		if not self.cleanup_samba():
			Logger.error("FileServer: unable to cleanup samba users")
			return False
		
		return True

	
	@staticmethod
	def getName():
		return "FileServer"
	
	
	def stop(self):
		Logger.info("FileServer:: stopping")
		self.cleanup_samba()
	
	
	def run(self):
		self.has_run = True
		while 1:
			time.sleep(30)
			Logger.debug("FileServer run loop")
	
	
	
	def cleanup_samba(self):
		# check samba conf
		ret = True
		
		for share in self.get_enabled_usershares():
			Logging.debug("FileServer:: Removing share '%s'"%(share.name))
			s, o = commands.getstatusoutput("net usershare delete %s"%(share.name))
			if s is not 0:
				Logger.error("FS: unable to 'net usershare delete': %d => %s"%(s, o))
				ret = False
		
		ret2 = self.purgeGroup()
		
		return ret and ret2
	
	
	def purgeGroup(self):
		users = Platform.System.groupMember(self.group_name)
		if users is None:
			return False
		
		ret = True
		for user in users:
			Logger.debug("FileServer:: deleting user '%s'"%(user))
			cmd = 'smbpasswd -x %s'%(user)
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("FS: unable to del smb password")
				Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
			
			
			cmd = "userdel %s"%(user)
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("FS: unable to create user")
				Logger.debug("FS: command '%s' return %d: %s"%(cmd, s, o.decode("UTF-8")))
				ret =  False
		
		return ret
	
	
	def get_existing_shares(self):
		shares = []
		
		for f in glob.glob(self.spool+"/*"):
			name = os.path.basename(f)
			
			if name in self.shares.keys():
				continue
			
			share = Share(name, self.spool)
			shares.append(share)
			
		return shares + self.shares.values()
	
	
	def get_enabled_usershares(self):
		exisings = self.get_existing_shares()
		
		s, o = commands.getstatusoutput("net usershare list")
		if s is not 0:
			Logger.error("FS: unable to 'net usershare list': %d => %s"%(s, o))
			return []
		
		names = [s.strip() for s in o.splitlines()]
		
		
		shares = []
		for share in exisings:
			if share.name in names:
				shares.append(share)
		
		return shares
