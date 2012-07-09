# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
# Author David LECHEVALIER <david@ulteo.com> 2011, 2012
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

class Config:
	general = None
	thread_count = 1
	checkShell = False
	clean_dump_archive = True
	
	
	@staticmethod
	def init(infos):
		if infos.has_key("thread_count"):
				value = infos["thread_count"]
				if value.lower() == "auto":
					Config.thread_count = None
				else:
					try:
						nbThread = int(value)
					except ValueError:
						Logger.error("Invalid int number for thread_count")
						nbThread = 1

					if nbThread <= 0:
						Logger.error("thread_count must be greater than 0")
					else:
						Config.thread_count = nbThread
		
		if infos.has_key("checkshell"):
			Config.checkShell = (infos["checkshell"].lower() == "true")
		
		if infos.has_key("clean_dump_archive"):
			Config.clean_dump_archive = (infos["clean_dump_archive"].lower() == True)
		
		return True
