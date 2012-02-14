# -*- coding: utf-8 -*-

# Copyright (C) 2011-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
# Author David LECHEVALIER <david@ulteo.com> 2011
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

class Config:
	general = None
	multithread = False
	checkShell = False
	clean_dump_archive = True
	linux_icon_theme = "CrystalGnome"
	
	
	@classmethod
	def init(cls, infos):
		if infos.has_key("multithread"):
			cls.multithread = (infos["multithread"].lower() == "true")
		
		if infos.has_key("checkshell"):
			cls.checkShell = (infos["checkshell"].lower() == "true")
		
		if infos.has_key("clean_dump_archive"):
			cls.clean_dump_archive = (infos["clean_dump_archive"].lower() == "true")
			
		if infos.has_key("linux_icon_theme"):
			cls.linux_icon_theme = infos["linux_icon_theme"]
		
		return True
