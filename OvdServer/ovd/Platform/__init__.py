# -*- coding: utf-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
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


import platform


def get_platform():
	platform_ = platform.system().lower()
	for key in ["windows","microsoft","microsoft windows"]:
		if key in platform_:
			return "windows"
		
	return platform_
	



class Platform:
	_instance = None
	
	@staticmethod
	def getInstance():
		if Platform._instance is None:
			Platform.initInstance()
		
		return Platform._instance
		
		
	@staticmethod
	def initInstance():
		if get_platform() == "linux":
			from Linux.Platform import Platform as ThisPlatform
		elif get_platform() == "windows":
			from Windows.Platform import Platform as ThisPlatform
		else:
			raise Exception("Not supported platform")
		
		Platform._instance = ThisPlatform()



if get_platform() == "linux":
	from Linux.Session import Session
	from Linux.TS import TS
elif get_platform() == "windows":
	from Windows.Session import Session
	from Windows.TS import TS
else:
	raise Exception("Not supported platform")

