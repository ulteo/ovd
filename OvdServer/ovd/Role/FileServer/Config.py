# -*- coding: utf-8 -*-

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

import grp
import os
import pwd

from ovd.Logger import Logger

class Config:
	user  = "ovd-fs"
	group = None
	uid = None
	gid = None
	spool = None
	
	@staticmethod
	def init():
		try:
			infos = pwd.getpwnam(Config.user)
		except KeyError, err:
			Logger.info("FileServer Config failed: no such user '%s'"%(Config.user))
			return False
		
		Config.uid = infos[2]
		Config.gid = infos[3]
		Config.spool = infos[5]
		
		if not os.path.isdir(Config.spool):
			Logger.info("FileServer Config failed: no such directory '%s'"%(Config.spool))
			return False
		
		try:
			infos = grp.getgrgid(Config.gid)
		except KeyError, err:
			Logger.info("FileServer Config failed: no such group '%d'"%(Config.gid))
			return False
		
		Config.group = infos[0]
		
		return True
