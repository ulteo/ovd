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

import platform

def get_platform():
	platform_ = platform.system().lower()
	for key in ["windows","microsoft","microsoft windows"]:
		if key in platform_:
			return "windows"
		
	return platform_
	

if get_platform() == "linux":
	from Linux._platform import *
	from Linux import InstancesManager

elif get_platform() == "windows":
	from Windows._platform import *
	from Windows import InstancesManager

else:
	raise Exception("Not supported platform")

