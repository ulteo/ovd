# -*- coding: utf-8 -*-

# Copyright (C) 2008-2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2008-2010
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

import commands
import ConfigParser
import hashlib
import os
import re
import tempfile

from ovd.Logger import Logger

class ApplicationsDetection():
	shortcut_ext = ".desktop"
	
	def __init__(self):
		self.path = "/usr/share/applications"
	
		self.desktop_keys_required = ['Name', 'Exec']
		self.desktop_keys = {
				'Name' : ['Name[en]',
				'name'], # Non standard but used by IBM Lotus Symphony
				'Icon' : ['Icon'],
				'Categories' : ['Categories'],
				'Comment' : ['Comment','GenericName[en]', 'GenericName'],
				}
	
	
	def find_files(self):
		ret = []
		for root, _, files in os.walk(self.path):
			for name in files:
				l = os.path.join(root,name)
				if not os.path.isfile(l):
					continue
	
				if not os.path.splitext(l)[1] == ".desktop":
					continue
				
				ret.append(l)
				
		return ret
	
	
	@staticmethod
	def isBan(parser):
		if parser.has_option('Desktop Entry', 'Categories'):
			categ = parser.get('Desktop Entry','Categories')
			
			for elem in ["information", "Peripherals", "settings", "Screensaver", "System"]:
				if elem in categ:
					return True
		
		return False
	
	
	def get(self):
		applications = {}
		files = self.find_files()
		
		for filename in files:
			parser = ConfigParser.ConfigParser()
			
			try:
				parser.read(filename)
			except ConfigParser.MissingSectionHeaderError:
				continue
		
			if not parser.has_section('Desktop Entry'):
				continue
		
			if not parser.has_option('Desktop Entry', 'Type'):
				continue
			if not parser.get('Desktop Entry','Type') == "Application":
				# the spec define three type: Application, Link and
				# Directory
				continue
		
			if not parser.has_option('Desktop Entry', "Name"):
				continue
			
			if not parser.has_option('Desktop Entry', "Exec"):
				continue
			
			if self.isBan(parser):
				continue
			
			
			application = {}
			application["local_id"] = hashlib.md5(filename).hexdigest()
			application["name"] = parser.get('Desktop Entry', "Name")
			application["command"] = parser.get('Desktop Entry', "Exec")
			application["filename"] = filename
			application["mimetypes"] = []
			if parser.has_option('Desktop Entry', "MimeType"):
				mimes = parser.get('Desktop Entry', "MimeType").strip()
				if len(mimes)>0:
					if mimes.endswith(";"):
						mimes = mimes[:-1]
					
					application["mimetypes"] = mimes.split(";")
			
			
			if parser.has_option('Desktop Entry', "Icon"):
				application["icon"] = parser.get('Desktop Entry', "Icon")
				
			if parser.has_option('Desktop Entry', "Comment"):
				application["description"] = parser.get('Desktop Entry', "Comment")
			elif parser.has_option('Desktop Entry', "GenericName"):
				application["description"] = parser.get('Desktop Entry', "GenericName")
			
			applications[application["local_id"]] = application
			
		return applications
	
	@staticmethod
	def getExec(filename):
		Logger.debug("ApplicationsDetection::getExec %s"%(filename))
		parser = ConfigParser.ConfigParser()
		
		try:
			parser.read(filename)
		except ConfigParser.MissingSectionHeaderError:
			Logger.warn("ApplicationsDetection::getExec invalid desktop file syntax")
			return None
	
		if not parser.has_section('Desktop Entry'):
			Logger.warn("ApplicationsDetection::getExec invalid desktop file syntax")
			return None	
		
		if not parser.has_option('Desktop Entry', "Exec"):
			Logger.warn("ApplicationsDetection::getExec invalid desktop file syntax")
			return None
		
		return parser.get('Desktop Entry', "Exec")
	
	
	def getIcon(self, filename):
		Logger.debug("ApplicationsDetection::getIcon %s"%(filename))
		parser = ConfigParser.ConfigParser()
		
		try:
			parser.read(filename)
		except ConfigParser.MissingSectionHeaderError:
			Logger.warn("ApplicationsDetection::getIcon invalid desktop file syntax")
			return None
	
		if not parser.has_section('Desktop Entry'):
			Logger.warn("ApplicationsDetection::getIcon invalid desktop file syntax")
			return None	
		
		if not parser.has_option('Desktop Entry', "Icon"):
			# icon field is not required for type=Application
			return None
		
		iconName = parser.get('Desktop Entry', "Icon")
		if not os.path.isabs(iconName):
			iconName = self.chooseBestIcon(iconName)
			if iconName is None:
				return None
		
		bufFile = tempfile.mktemp(".png")		
		cmd = 'convert -resize 32x32 "%s" "%s"'%(iconName, bufFile)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.debug("getIcon cmd '%s' returned (%d): %s"%(cmd, s, o))
			Logger.error("getIcon: imagemagick error")
			if os.path.exists(bufFile):
				os.remove(bufFile)
			
			return None
		
		try:
			f = file(bufFile, "r")
		except IOError, err:
			Logger.error("ApplicationsDetection::getIcon finale icon file '%s' does not exists"%(bufFile))
			return None
		
		buffer = f.read()
		f.close()
		os.remove(bufFile)
		
		return buffer
	
	def chooseBestIcon(self, pattern):
		cmd = 'find /usr/share/pixmaps /usr/share/icons -xtype f -iname "*%s*"'%(pattern)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.debug("chooseBestIcon cmd '%s' returned (%d): %s"%(cmd, s, o))
			Logger.error("chooseBestIcon: unable to get icon")
			return None
		
		files = o.splitlines()
		if len(files)==0:
			return None
		
		list1 = {};
		list2 = {};
		
		for image in files:
			cmd = 'identify "%s"'%(image)
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.debug("chooseBestIcon cmd '%s' returned (%d): %s"%(cmd, s, o))
				continue
			
			if not o.startswith(image+" "):
				Logger.debug("chooseBestIcon identify weird out")
				continue
			
			
			buf = o[len(image+" "):]
			buf = buf.split(" ")
			if len(buf)<2:
				Logger.debug("chooseBestIcon identify weird out")
				continue
			
			res = buf[1]
			match = re.match("(\d+)x(\d+).*", res)
			if match is None:
				Logger.debug("chooseBestIcon identify weird out")
				continue
			
			height =  int(match.group(2))
			
			if height >= 32:
				list1[height] = image
			else:
				list2[height] = image
		
		
		if len(list1) > 0:
			return list1.values()[0]
		
		if len(list2) > 0:
			return list2.values()[-1]
		
		return None
	
	
	def getDebianPackage(self, applications):
		for key in applications.keys():
			application = applications[key]
			
			cmd = 'dpkg -S "%s"'%(application["filename"])
			
			status,out = commands.getstatusoutput(cmd)
			if status != 0:
				continue
			
			if not ":" in out:
				continue
			
			(package, _) = out.split(":", 1)
			application["package"] = package
