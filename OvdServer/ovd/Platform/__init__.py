# -*- coding: utf-8 -*-

# Copyright (C) 2009 - 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
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

import sys

if sys.platform == "linux2":
	platform_name = "Linux"
elif sys.platform == "win32":
	 platform_name = "Windows" 
else:
	raise Exception("Not supported platform")

Platform = __import__("%s.%s"%(__name__, platform_name), {}, {}, platform_name)
Platform.Base = sys.modules[__name__]

sys.modules[__name__] = Platform
sys.modules[__name__+".Base"] = Platform.Base
