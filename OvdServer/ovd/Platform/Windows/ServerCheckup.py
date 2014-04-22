# -*- coding: utf-8 -*-

# Copyright (C) 2014 Ulteo SAS
# http://www.ulteo.com
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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

import win32api
import win32con

from Base.ServerCheckup import ServerCheckup as AbstractServerCheckup


class ServerCheckup(AbstractServerCheckup):
	@staticmethod
	def check():
		path = r"SYSTEM\CurrentControlSet\Control\Terminal Server"
		key = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, path, 0, win32con.KEY_READ | win32con.KEY_WOW64_64KEY)
		value, vtype = win32api.RegQueryValueEx(key, r"fDenyTSConnections")
		win32api.RegCloseKey(key)
		if value == 1:
			raise Exception("Terminal Server connections disabled")
		
		path = r"SYSTEM\CurrentControlSet\Control\Terminal Server\WinStations\RDP-Tcp"
		key = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, path, 0, win32con.KEY_READ | win32con.KEY_WOW64_64KEY)
		value, vtype = win32api.RegQueryValueEx(key, r"UserAuthentication")
		win32api.RegCloseKey(key)
		if value == 1:
			raise Exception("User authentication too restrictive")
