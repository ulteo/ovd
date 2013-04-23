# -*- coding: utf-8 -*-

# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2013
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
	dav_group_file = "/var/spool/ulteo/ovd/fs.dav.group"
	FSBackendConf = "/etc/ulteo/rufs/FSBackend.conf"
	
	@classmethod
	def init(cls, infos):
		if infos.has_key("user"):
			cls.user = infos["user"]
		
		if infos.has_key("FSBackendConf"):
			cls.FSBackendConf = info["FSBackendConf"]
		
		return True
	
	
	@classmethod
	def init_role(cls):
		try:
			infos = pwd.getpwnam(cls.user)
		except KeyError, err:
			Logger.info("FileServer Config failed: no such user '%s'"%(cls.user))
			return False
		
		cls.uid = infos[2]
		cls.gid = infos[3]
		cls.spool = infos[5]
		
		if not os.path.isdir(cls.spool):
			Logger.info("FileServer Config failed: no such directory '%s'"%(cls.spool))
			return False
		
		try:
			infos = grp.getgrgid(cls.gid)
		except KeyError, err:
			Logger.info("FileServer Config failed: no such group '%d'"%(cls.gid))
			return False
		
		cls.group = infos[0]
		
		try:
			infos = pwd.getpwnam(cls.dav_user)
		except KeyError, err:
			Logger.info("FileServer Config failed: no such user '%s'"%(cls.dav_user))
			return False
		
		cls.dav_uid = infos[2]
		
		if not os.path.isdir(os.path.dirname(cls.dav_passwd_file)):
			Logger.info("FileServer Config failed: no such directory '%s'"%(os.path.dirname(cls.dav_passwd_file)))
			return False
		
		try:
			f = file(cls.dav_passwd_file, "w")
			f.close()
			os.chown(cls.dav_passwd_file, cls.dav_uid, -1)
		except Exception, err:
			Logger.info("FileServer unable to write in '%s': %s"%(cls.dav_passwd_file, str(err)))
			return False
		
		return True
