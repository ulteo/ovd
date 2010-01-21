#! /usr/bin/env python
# Copyright (C) 2008-2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com>
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

import md5
import commands
import ConfigParser
import random
import sys, os

from ovd.Logger import Logger

class ApplicationsDetection():
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
		for root, dirs, files in os.walk(self.path):
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
			
			for elem in ["settings", "Peripherals", "System", "information"]:
				if elem in categ:
					return True
		
		return False
	
	
	def get(self):
		applications = {}
		files = self.find_files()
		parser = ConfigParser.ConfigParser()
		
		for filename in files:
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
			application["id"] = md5.md5(filename).hexdigest()
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
			
			applications[application["id"]] = application
			
		return applications
	
	
	def getIcon(self, filename):
		Logger.warn("youpla %s"%(filename))
		parser = ConfigParser.ConfigParser()
		
		try:
			parser.read(filename)
		except ConfigParser.MissingSectionHeaderError:
			Logger.warn("ouet -1")
			return None
	
		if not parser.has_section('Desktop Entry'):
			Logger.warn("ouet 0")
			return None	
		
		if not parser.has_option('Desktop Entry', "Icon"):
			Logger.warn("ouet 1")
			return None
		
		iconName = parser.get('Desktop Entry', "Icon")
		
		cmd = 'find /usr/share/pixmaps /usr/share/icons -iname "*%s*"'%(iconName)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("find error:%d (%s)"%(s, o))
			return None
		
		files = o.splitlines()
		if len(files)==0:
			return None
		
		f_src = files[0]
		
		bufFile = "/tmp/%f.png"%(random.random())
		cmd = 'convert -resize 32x32 "%s" "%s"'%(f_src, bufFile)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("find error:%d (%s)"%(s, o))
			
			if os.path.exists(bufFile):
				os.remove(bufFile)
		
		
			return None
		
		
		f = file(bufFile, "r")
		buffer = f.read()
		f.close()
		os.remove(bufFile)
		
		return buffer
