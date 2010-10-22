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

import win32api
import win32con
import win32netcon
import win32wnet

from ovd_shells.Folders import Folders as AbstractFolders
import _platform as Platform

class Folders(AbstractFolders):
	def __init__(self):
		self.shares = {}
	
	
	def registerShares(self):
		self.shares = self.parseRegistry()
		
		if "profile" in self.shares.keys():
			profile = self.shares["profile"]
			try:
				win32wnet.WNetAddConnection2(win32netcon.RESOURCETYPE_DISK, "U:", r"\\%s\%s"%(profile["host"], profile["directory"]), None, profile["login"], profile["password"])
				profile["local_path"] = "U:\\"
			  
			except Exception, err:
				cmd = "net use U: \\\\%s\\%s %s /user:%s"%(profile["host"], profile["directory"], profile["password"], profile["login"])
				print "Unable to mount share: ",err
				print "Try with this command: ",cmd
		
		for name in self.shares.keys():
			if name == "profile":
				continue
			
			share = self.shares[name]
			letter = self.getFreeLetter()+":"
			
			try:
				win32wnet.WNetAddConnection2(win32netcon.RESOURCETYPE_DISK, letter, r"\\%s\%s"%(share["host"], share["directory"]), None, share["login"], share["password"])
				share["local_path"] = letter+"\\"
			
			except Exception, err:
				cmd = "net use %s \\\\%s\\%s %s /user:%s"%(letter, share["host"], share["directory"], share["password"], share["login"])
				print "Unable to mount share: ",err
				print "Try with this command: ",cmd
	
	
	def getPathFromID(self, id_):
		for share in self.shares.values():
			if share["directory"] == id_ and share.has_key("local_path"):
				return share["local_path"]
		
		return None
	
	
	@staticmethod
	def getFreeLetter():
		drives = win32api.GetLogicalDriveStrings().split('\x00')[:-1]
		
		for i in "ZYXWVUTSRQPONMLKJIHGFEDCBA":
			letter = "%s:\\"%(i.upper())
			#print letter
			if letter not in drives:
				return i
		
		return None
	
	
	@staticmethod
	def parseRegistry():
		shares = {}
		
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_CURRENT_USER, r"Software\ulteo\ovd", 0, win32con.KEY_READ)
		except:
			return shares
		
		shares_name = []
		i = 0
		while True:
			try:
				name = win32api.RegEnumKey(hkey, i)
				shares_name.append(name)
			except Exception, err:
				break
			i+= 1
		win32api.RegCloseKey(hkey)
		
		for name in shares_name:
			share = {}
			hkey = None
			try:
				hkey = win32api.RegOpenKey(win32con.HKEY_CURRENT_USER, r"Software\ulteo\ovd\%s"%(name), 0, win32con.KEY_READ | win32con.KEY_QUERY_VALUE)
				
				for item in ["host", "directory", "login", "password"]:
					(share[item], type_) = win32api.RegQueryValueEx(hkey, item)
					if type_ is not win32con.REG_SZ:
						raise Exception("item %s in not type REG_SZ"%(item))
			except Exception, err:
				print "Registry content error for shares: ",err
				continue
			
			finally:
				if hkey is not None:
					win32api.RegCloseKey(hkey)
			
			shares[name] =  share
		
		return shares
