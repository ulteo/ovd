# -*- coding: utf-8 -*-

# Copyright (C) 2011-2013 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011, 2013
# Author David LECHEVALIER <david@ulteo.com> 2011, 2012
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

from ovd.Logger import Logger
from ovd.Platform.System import System
import os

class Config:
	general = None
	thread_count = 1
	checkShell = False
	clean_dump_archive = True
	linux_icon_theme = "CrystalGnome"
	linux_skel_directory = "/dev/null"
	linux_fuse_group = "fuse"
	profile_filters_filename = os.path.join(System.get_default_config_dir(), "profiles_filter.conf")
	
	OVERRIDE_PASSWORD_METHOD_UNIX = 0x01
	OVERRIDE_PASSWORD_METHOD_CUSTOM = 0x02
	override_password_method = 0x01
	
	@classmethod
	def init(cls, infos):
		if infos.has_key("thread_count"):
				value = infos["thread_count"]
				if value.lower() == "auto":
					cls.thread_count = None
				else:
					try:
						nbThread = int(value)
					except ValueError:
						Logger.error("Invalid int number for thread_count")
						nbThread = 1

					if nbThread <= 0:
						Logger.error("thread_count must be greater than 0")
					else:
						cls.thread_count = nbThread
		
		if infos.has_key("checkshell"):
			cls.checkShell = (infos["checkshell"].lower() == "true")
		
		if infos.has_key("clean_dump_archive"):
			cls.clean_dump_archive = (infos["clean_dump_archive"].lower() == "true")
			
		if infos.has_key("linux_icon_theme"):
			cls.linux_icon_theme = infos["linux_icon_theme"]
		
		if infos.has_key("linux_skel_directory"):
			cls.linux_skel_directory = infos["linux_skel_directory"]
		
		if infos.has_key("linux_fuse_group"):
			cls.linux_fuse_group = infos["linux_fuse_group"]
		
		if infos.has_key("linux_profile_filters_filename"):
			cls.linux_profile_filters_filename = infos["linux_profile_filters_filename"]
		
		if infos.has_key("override_password_method"):
			v = infos["override_password_method"].lower()
			if v == "unix":
				Config.override_password_method = Config.OVERRIDE_PASSWORD_METHOD_UNIX
			elif v == "custom":
				Config.override_password_method = Config.OVERRIDE_PASSWORD_METHOD_CUSTOM
			else:
				Logger.error("Unknown override_password_method value '%s'"%v)
		
		return True
