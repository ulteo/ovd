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

from ovd.Role import AbstractRole
from ovd.Config import Config
from ovd.Logger import Logger
from ovd.Platform import Platform

from Dialog import Dialog


class FileServer(AbstractRole):
	spool = "/var/lib/ulteo/ovd/fs"
	shares_dir = "/var/lib/samba/usershares"
	
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		self.dialog = Dialog(self)
		self.has_run = False
	
	def init(self):
		Logger.info("FileServer init")
		
		if not os.path.isdir(self.spool):
			os.makedirs(self.spool)
		
		self.cleanup_samba()
		
		return True

	
	@staticmethod
	def getName():
		return "FileServer"
	
	
	def stop(self):
		pass
	
	
	def run(self):
		self.has_run = True
		while 1:
			time.sleep(30)
			Logger.debug("FileServer run loop")
	
	
	def get_enabled_usershares(self):
		s, o = commands.getstatusoutput("net usershare list")
		if s is not 0:
			Logger.error("FS: unable to 'net usershare list': %d => %s"%(s, o))
			return []
		
		return [s.strip() for s in o.splitlines()]
	
	def cleanup_samba(self):
		# check samba conf
		ret = True
		
		for share in self.get_enabled_usershares():
			s, o = commands.getstatusoutput("net usershare delete %s"%(share))
			if s is not 0:
				Logger.error("FS: unable to 'net usershare delete': %d => %s"%(s, o))
				ret = False
		
		return ret
	
	def get_profiles(self):
		profiles = []
		
		for f in glob.glob(self.spool+"/*"):
			name = os.path.basename(f)
			if name.startswith("p_"):
				profiles.append(name[2:])
			
		return profiles
	
	def exists_profile(self, name):
		return os.path.isdir(self.spool+"/p_"+name)
	
	def enable_profile(self, name, user, passwd):
		s, o = commands.getstatusoutput('net usershare add %s "%s"'%("p_"+name, self.spool+"/p_"+name))
		if s is not 0:
			Logger.error("FS: unable to 'net usershare add': %d => %s"%(s, o))
		
		return s==0
		
	def disable_profile(self, name):
		s, o = commands.getstatusoutput("net usershare delete p_%s"%(name))
		if s is not 0:
			Logger.error("FS: unable to 'net usershare delete': %d => %s"%(s, o))
		
		return s==0
		
	
	def create_profile(self, name):
		try:
			os.mkdir(self.spool+"/p_"+name)
		except:
			Logger.warn("FS: unable to create profile '%s'"%(name))
			return False
		
		return True
	
	def get_shares(self):
		shares = []
		
		for f in glob.glob(self.spool+"/*"):
			name = os.path.basename(f)
			if name.startswith("s_"):
				shares.append(name[2:])
			
		return shares
	
	def exists_share(self, name):
		return os.path.isdir(self.spool+"/s_"+name)
	

