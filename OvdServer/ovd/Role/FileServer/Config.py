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
	general = None
	user  = "ovd-fs"
	group = None
	uid = None
	gid = None
	spool = None
	
	dav_user = "www-data"
	dav_uid = None
	dav_passwd_file = "/var/spool/ulteo/ovd/fs.dav.passwd"
	
	@staticmethod
	def init(infos):
		if infos.has_key("user"):
			Config.user = infos["user"]
		
		
		return True
	
	
	@staticmethod
	def init_role():
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
		
		try:
			infos = pwd.getpwnam(Config.dav_user)
		except KeyError, err:
			Logger.info("FileServer Config failed: no such user '%s'"%(Config.dav_user))
			return False
		
		Config.dav_uid = infos[2]
		
		if not os.path.isdir(os.path.dirname(Config.dav_passwd_file)):
			Logger.info("FileServer Config failed: no such directory '%s'"%(os.path.dirname(Config.dav_passwd_file)))
			return False
		
		try:
			f = file(Config.dav_passwd_file, "w")
			f.close()
			os.chown(Config.dav_passwd_file, Config.dav_uid, -1)
		except Exception, err:
			Logger.info("FileServer unable to write in '%s': %s"%(Config.dav_passwd_file, str(err)))
			return False
		
		
		return True
