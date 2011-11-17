# -*- coding: utf-8 -*-

# Copyright (C) 2009-2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
# Author David LECHEVALIER <david@ulteo.com> 2011 
# Author Samuel BOVEE <samuel@ulteo> 2011
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
import statvfs
import time
from xml.dom.minidom import Document

from ovd.Logger import Logger
from ovd.Platform import Platform
from ovd.Role.Role import Role as AbstractRole

from Config import Config
from Dialog import Dialog
from DirectoryWatcher import DirectoryWatcher
from Share import Share
from User import User


class Role(AbstractRole):
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		self.dialog = Dialog(self)
		self.has_run = False
		self.shares = {}
		self.wm = None
	
	def init(self):
		Logger.info("FileServer init")
		
		if not Config.init_role():
			Logger.info("FileServer never initialized, creating repository on '%s'"%(Config.spool))
			os.makedirs(Config.spool)
		
		if not self.cleanup_samba():
			Logger.error("FileServer: unable to cleanup samba")
			return False
		
		if not self.purgeGroup():
			Logger.error("FileServer: unable to cleanup users")
			return False
		
		if not self.cleanup_repository():
			Logger.error("FileServer: unable to cleanup users")
			return False
		
		self.wm = DirectoryWatcher()
		
		self.shares = self.get_existing_shares()
		
		return True

	
	@staticmethod
	def getName():
		return "FileServer"
	
	
	def stop(self):
		self.cleanup_samba()
		self.purgeGroup()
		self.wm.stop()
	
	
	def run(self):
		self.has_run = True
		self.status = Role.STATUS_RUNNING
		self.wm.start()
		while 1:
			time.sleep(30)
			Logger.debug("FileServer run loop")
		self.status = Role.STATUS_STOP
	
	
	
	def cleanup_samba(self):
		# check samba conf
		ret = True
		
		for share in self.shares.values():
			if share.isActive():
				if not share.disable():
					ret = False
		
		for usershare in self.get_enabled_usershares():
			Logger.debug("FileServer:: Removing samba usershare '%s'"%(usershare))
			s, o = commands.getstatusoutput("net usershare delete %s"%(usershare))
			if s is not 0:
				Logger.error("FS: unable to 'net usershare delete': %d => %s"%(s, o))
				ret = False
		
		return ret
	
	
	def cleanup_repository(self):
		cmd = 'chown -R %s:%s "%s"'%(Config.uid, Config.gid, Config.spool)
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.debug("FS: following command '%s' returned %d => %s"%(cmd, s, o))
			return False
		
		cmd = 'chmod -R u=rwX,g=rwX,o-rwx "%s"'%(Config.spool)
		s, o = commands.getstatusoutput(cmd)
		if s is not 0:
			Logger.debug("FS: following command '%s' returned %d => %s"%(cmd, s, o))
			return False
		
		return True
	
	
	def purgeGroup(self):
		users = Platform.System.groupMember(Config.group)
		if users is None:
			return False
		
		ret = True
		for user in users:
			if user == Config.dav_user:
				continue
			
			Logger.debug("FileServer:: deleting user '%s'"%(user))
			
			u = User(user)
			u.clean()
			if u.existSomeWhere():
				Logger.error("FS: unable to del user %s"%(user))
				ret =  False
		
		return ret
	
	
	@staticmethod
	def get_existing_shares():
		shares = {}
		
		for f in glob.glob(Config.spool+"/*"):
			name = os.path.basename(f)
			
			share = Share(name, Config.spool)
			shares[name] = share
			
		return shares
	
	
	
	def get_enabled_usershares(self):
		s, o = commands.getstatusoutput("net usershare list")
		if s is not 0:
			Logger.error("FS: unable to 'net usershare list': %d => %s"%(s, o))
			return []
		
		names = [s.strip() for s in o.splitlines()]
		
		return names
	
	
	def get_disk_size_infos(self):
		stats = os.statvfs(Config.spool)
		free_bytes = stats[statvfs.F_BSIZE] * stats[statvfs.F_BFREE] 
		#avail_bytes = stats[statvfs.F_BSIZE] * stats[statvfs.F_BAVAIL]
		total_bytes = stats[statvfs.F_BSIZE] * stats[statvfs.F_BLOCKS]
		
		return (total_bytes/1024, free_bytes/1024)
	
	
	def getReporting(self, node):
		doc = Document()
		
		infos  = self.get_disk_size_infos()
		sizeNode = doc.createElement('size')
		sizeNode.setAttribute("total", str(infos[0]))
		sizeNode.setAttribute("free", str(infos[1]))
		node.appendChild(sizeNode)
		
		for (share_id, share) in self.shares.items():
			shareNode = doc.createElement("share")
			shareNode.setAttribute("id", share_id)
			shareNode.setAttribute("status", str(share.status()))
			
			for user in share.users:
				userNode = doc.createElement("user")
				userNode.setAttribute("login", user)
				shareNode.appendChild(userNode)

			node.appendChild(shareNode)
