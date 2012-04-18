# -*- coding: utf-8 -*-

# Copyright (C) 2008-2012 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2008, 2010, 2011, 2012
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

import hashlib
import os
import tempfile

import xdg.BaseDirectory
import xdg.DesktopEntry
import xdg.Exceptions
import xdg.IconTheme

from ovd import commands
from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Config import Config

class ApplicationsDetection():
	shortcut_ext = ".desktop"
	
	def __init__(self):
		self.path = "applications"
		
	
	def find_files(self):
		for path in xdg.BaseDirectory.xdg_data_dirs:
			for root, _, files in os.walk(os.path.join(path, self.path)):
				for name in files:
					l = os.path.join(root,name)
					if not os.path.isfile(l):
						continue
					
					if not os.path.splitext(l)[1] == self.shortcut_ext:
						continue
					
					yield l
	
	
	@staticmethod
	def isBan(entry):
		categ = entry.getCategories()
		for elem in ["information", "Peripherals", "settings", "Screensaver", "System"]:
			if elem in categ:
				return True
		
		return False
	
	
	def get(self):
		applications = {}
		files = self.find_files()
		
		for filename in files:
			entrie = xdg.DesktopEntry.DesktopEntry(filename)
			
			if entrie.getType() != "Application":
				# the spec define three type: Application, Link and
				# Directory
				continue
			
			if entrie.getName() == u'':
				continue
			
			if entrie.getExec() == u'':
				continue
			
			if self.isBan(entrie):
				continue
			
			
			application = {}
			application["local_id"] = hashlib.md5(entrie.filename).hexdigest()
			application["name"] = entrie.getName()
			application["command"] = entrie.getExec()
			application["filename"] = entrie.filename
			application["mimetypes"] = entrie.getMimeTypes()
			
			if entrie.getIcon() != u'':
				application["icon"] = entrie.getIcon()
			
			if entrie.getComment() != u'':
				application["description"] = entrie.getComment()
			elif entrie.getGenericName() != u'':
				application["description"] = entrie.getGenericName()
			
			applications[application["local_id"]] = application
		
		return applications
	
	
	@staticmethod
	def getExec(filename):
		Logger.debug("ApplicationsDetection::getExec %s"%(filename))
		
		try:
			entry = xdg.DesktopEntry.DesktopEntry(filename)
		except xdg.Exceptions.Error as detail:
			Logger.warn("ApplicationsDetection::getExec %s" % detail)
			return None

		if entry.getExec() == u'':
			Logger.warn("ApplicationsDetection::getExec no command to execute")
			return None
		
		return entry.getExec()
	
	
	def getIcon(self, filename):
		Logger.debug("ApplicationsDetection::getIcon %s"%(filename))
		
		try:
			entry = xdg.DesktopEntry.DesktopEntry(filename)
		except xdg.Exceptions.Error as detail:
			Logger.warn("ApplicationsDetection::getIcon %s" % detail)
			return None
		
		iconName = entry.getIcon()
		if entry.getIcon() == u'':
			# icon field is not required for type=Application
			return None
		
		iconPath = xdg.IconTheme.getIconPath(iconName, size = 32, theme=Config.linux_icon_theme, extensions = ["png", "xpm"])
		if iconPath == None:
			return None
		
		bufFile = tempfile.mktemp(".png")		
		cmd = 'convert -resize 32x32 "%s" "%s"'%(iconPath, bufFile)
		p = commands.execute(cmd)
		if p.returncode != 0:
			Logger.debug("getIcon cmd '%s' returned (%d): %s"%(cmd, p.returncode, p.stdout.read()))
			Logger.error("getIcon: imagemagick error")
			if os.path.exists(bufFile):
				os.remove(bufFile)
			
			return None
		
		
		f = file(bufFile, "r")
		buf = f.read()
		f.close()
		os.remove(bufFile)
		
		return buf
	
	
	def getDebianPackage(self, applications):
		for key in applications.keys():
			application = applications[key]
			
			cmd = 'dpkg -S "%s"'%(application["filename"])
			
			p = commands.execute(cmd)
			if p.returncode != 0:
				continue
			
			out = p.stdout.read()
			if not ":" in out:
				continue
			
			(package, _) = out.split(":", 1)
			application["package"] = package
