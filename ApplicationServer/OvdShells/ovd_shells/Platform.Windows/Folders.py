# -*- coding: utf-8 -*-

# Copyright (C) 2010-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011, 2012
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

import sys
import urlparse
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
		for (name, share) in self.parseRegistry().items():
			if not self.isShareValid(share):
				print >>sys.stderr, "Unable to add share: %s (missing items)"%(str(share))
				continue
			
			self.shares[name] = share
		
		if "profile" in self.shares.keys():
			profile = self.shares["profile"]
			mount_uri = self.getMountURI(profile["uri"])
			
			if mount_uri is not None:
				if profile.has_key("login"):
					login = profile["login"]
				else:
					login = None
				
				if profile.has_key("password"):
					password = profile["password"]
				else:
					password = None
				
				try:
					win32wnet.WNetAddConnection2(win32netcon.RESOURCETYPE_DISK, "U:", mount_uri, None, login, password)
					profile["local_path"] = "U:\\"
				  
				except Exception, err:
					cmd = "net use U: %s"%(mount_uri)
					if password is not None:
						cmd+= " "+password
					
					if login is not None:
						cmd+= " /user:"+login
					
					print "Unable to mount share with default URI: ",err
					print "Try with this command: ",cmd
		
		for name in self.shares.keys():
			if name == "profile":
				continue
			
			share = self.shares[name]
			letter = self.getFreeLetter()+":"
			mount_uri = self.getMountURI(share["uri"])
			if mount_uri is None:
				continue
			
			if share.has_key("login"):
				login = share["login"]
			else:
				login = None
			
			if share.has_key("password"):
				password = share["password"]
			else:
				password = None
			
			try:
				win32wnet.WNetAddConnection2(win32netcon.RESOURCETYPE_DISK, letter, mount_uri, None, login, password)
				share["local_path"] = letter+"\\"
			
			except Exception, err:
				cmd = "net use %s %s"%(letter, mount_uri)
				if password is not None:
					cmd+= " XXXXXX"
				
				if login is not None:
					cmd+= " /user:"+login
				
				print "Unable to mount share with default URI: ",err
				print "Try with this command: ",cmd
	
	@staticmethod
	def getMountURI(uri_):
		u = urlparse.urlparse(uri_)
		if u.scheme == "cifs":
			return r"\\%s\%s"%(u.netloc, u.path[1:])
		
		if u.scheme == "webdav":
			return uri_
		
		if u.scheme == "webdavs":
			return uri_
		
		print >>sys.stderr, "Shouldn't appear: unknown protocol in share uri '%s'"%(uri_)
		return None
	
	def getPathFromID(self, id_):
		for share in self.shares.values():
			if not share.has_key("local_path"):
				continue
			
			if id_ != share["rid"]:
				continue
			
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
				
				index = 0
				while True:
					try:
						(key, data, type_) = win32api.RegEnumValue(hkey, index)
					except Exception, err:
						if err[0] == 259:   #No more data available
							break
						else:
							print >>sys.stderr, "Unable to RegEnumValue on item %d: %s"%(index, err)
							continue
					finally:
						index+= 1
					
					if type_ is not win32con.REG_SZ:
						continue
					
					share[key] = data
			
			except Exception, err:
				print "Registry content error for shares: ",err
				continue
			
			finally:
				if hkey is not None:
					win32api.RegCloseKey(hkey)
			
			shares[name] =  share
		
		return shares
	
	@staticmethod
	def isShareValid(share_):
		for item in ("rid", "uri"):
			if not share_.has_key(item):
				return False
		
		return True
