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

import ctypes

from ovd.Logger import Logger

class Msi:
	SUCCESS = 0
	INSTALLSTATE_LOCAL = 3
	
	def __init__(self):
		self.dll = ctypes.windll.LoadLibrary("msi.dll")

	def getTargetFromShortcut(self, path):
		szShortcutTarget = ctypes.c_wchar_p(path)
		szProductCode = ctypes.create_unicode_buffer(4096)
		szFeatureId = ctypes.create_unicode_buffer(4096)
		szComponentCode = ctypes.create_unicode_buffer(4096)
		
		status = self.dll.MsiGetShortcutTargetW(szShortcutTarget, ctypes.byref(szProductCode), ctypes.byref(szFeatureId), ctypes.byref(szComponentCode))
		if status != self.SUCCESS:
			Logger.debug3("MsiGetShortcutTargetW return %d on '%s'"%(status, path))
			return None
		
		path_len = ctypes.c_uint(4096)
		path_buffer = ctypes.create_unicode_buffer(4096)
		
		status = self.dll.MsiGetComponentPathW(szProductCode.value, szComponentCode.value, ctypes.byref(path_buffer), ctypes.byref(path_len))
		if status != self.INSTALLSTATE_LOCAL:
			Logger.debug2("MsiGetComponentPathW return %d on '%s'"%(status, path))
			return None
		
		return path_buffer.value


if __name__=='__main__':
	import sys
	
	if len(sys.argv) <2:
		print "Usage: %s file"%(sys.argv[0])
		sys.exit(1)
	
	Logger.initialize("testMsi", Logger.INFO | Logger.WARN | Logger.ERROR | Logger.DEBUG, None, True, False)
	try:
		msi = Msi()
	except WindowsError,e:
		print "Unable to init Msi"
		sys.exit(1)
	
	for path in sys.argv[1:]:
		p = msi.getTargetFromShortcut(path)
		print "  * path of %s is %s"%(path, str(p))
