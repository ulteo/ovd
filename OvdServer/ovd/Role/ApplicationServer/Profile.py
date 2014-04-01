# -*- coding: UTF-8 -*-

# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author David LECHEVALIER <david@ulteo.com> 2013
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
from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Config import Config


class Profile:
	DesktopDir = "Desktop"
	DocumentsDir = "Documents"
	
	def __init__(self, session):
		self.session = session
		
		self.profile = None
		self.sharedFolders = []
		
		self.session.profile = self
		self.init()
	
	@staticmethod
	def cleanup():
		raise NotImplementedError()
	
	def init(self):
		raise NotImplementedError()
	
	
	@staticmethod
        def rsyncBlacklist():
		raise NotImplementedError()
	
	
	@staticmethod
	def getRsyncMethod(src, dst, filter_filename, owner=False):
		args = ["-rltD", "--safe-links"]
		if owner:
			args.append("-o")
		
		if filter_filename is not None:
			args.append("--include-from=%s"%filter_filename)
		else:
			Logger.warn("Rsync filters file '%s' is missing, Using hardcoded rules"%filter_filename)
			for p in Profile.rsyncBlacklist():
				args.append('--exclude="%s"'%p)
			
			args.append('--include="/.**"')
			args.append('--exclude="/**"')
		
		return 'rsync %s "%s/" "%s/"'%(" ".join(args), src, dst)
	
	
	def setProfile(self, profile):
		self.profile = profile
	
	def addSharedFolder(self, folder):
		self.sharedFolders.append(folder)
	
	def mount(self):
		raise NotImplementedError()
	
	def umount(self):
		raise NotImplementedError()
