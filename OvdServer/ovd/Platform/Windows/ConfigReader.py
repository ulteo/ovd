# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import os
import win32api
import win32con
import pywintypes

from ovd import Config as ConfigModule

from Base.ConfigReader import ConfigReader as AbstractConfigReader


class ConfigReader(AbstractConfigReader):
	CONFIG_LOCATION = "Software\\ulteo\\OVD\\SlaveServer"
	
	@classmethod
	def process(cls, filename = None):
		if filename is not None:
			return cls.read_ini(filename)
		
		return cls.read_registry()
	
	
	@classmethod
	def read_registry(cls):
		data = {}
		
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, cls.CONFIG_LOCATION, 0, win32con.KEY_ENUMERATE_SUB_KEYS|win32con.KEY_QUERY_VALUE|win32con.KEY_WOW64_64KEY)
		except pywintypes.error, err:
			ConfigModule.report_error("Unable to open '%s' from registry"%(cls.CONFIG_LOCATION))
			return data
		
		data["main"] = cls.reg_enum_values(hkey)
		subkeys = cls.reg_enum_keys(hkey)
		
		win32api.RegCloseKey(hkey)
		
		for subkey in subkeys:
			location = os.path.join(cls.CONFIG_LOCATION, subkey)
			
			try:
				hkey = win32api.RegOpenKey(win32con.HKEY_LOCAL_MACHINE, location, 0, win32con.KEY_QUERY_VALUE|win32con.KEY_WOW64_64KEY)
			except pywintypes.error, err:
				ConfigModule.report_error("Unable to open '%s' from registry"%(location))
				return data
			
			data[subkey] = cls.reg_enum_values(hkey)
			
			win32api.RegCloseKey(hkey)
		
		return data
	
	
	@classmethod
	def reg_enum_keys(cls, hkey):
		data = []
		
		index = 0
		flag_continue = True
		while flag_continue:
			try:
				subKey = win32api.RegEnumKey(hkey, index)
				index+= 1
				
				data.append(subKey)
			except Exception, err:
				flag_continue = False
		
		return data
	
	
	@classmethod
	def reg_enum_values(cls, hkey):
		data = {}
		
		index = 0
		flag_continue = True
		while flag_continue:
			try:
				(k, _, _) = win32api.RegEnumValue(hkey, index)
				(v, _) = win32api.RegQueryValueEx(hkey, k)
				
				index+= 1
				
				data[k.lower()] = str(v)
			except Exception, err:
				flag_continue = False
		
		return data



if __name__=='__main__':
	data = ConfigReader.process()
	print data
