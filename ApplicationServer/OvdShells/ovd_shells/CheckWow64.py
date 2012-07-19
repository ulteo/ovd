# -*- coding: utf-8 -*-

# Copyright (C) 2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2012
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
if sys.platform == "win32":
	import ctypes
	import win32process

from Module import Module

class CheckWow64(Module):
	def beforeStartApp(self):
		if sys.platform != "win32":
			return
		
		if not win32process.IsWow64Process():
			return
		
		k32 = ctypes.windll.kernel32
		wow64 = ctypes.c_long(0)
		k32.Wow64DisableWow64FsRedirection(ctypes.byref(wow64))
		
		# The win32file package is supposed to have a Wow64DisableWow64FsRedirection function according 
		# to the documentation http://docs.activestate.com/activepython/2.7/pywin32/win32file__Wow64DisableWow64FsRedirection_meth.html
		# But when loading the package, the function does not exists
