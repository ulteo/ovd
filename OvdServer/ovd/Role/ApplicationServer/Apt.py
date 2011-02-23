# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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
import glob
import os
import ConfigParser
from Queue import Queue
from xml.dom.minidom import Document

from ovd.Logger import Logger
from ovd.Thread import Thread

class Apt(Thread):
	def __init__(self):
		Thread.__init__(self)
		
		self.directory = "/var/spool/ulteo/ovd/apt"
		self.queue = Queue()
		self.requests = {}
		
		self.i = 0
	
	
	def init(self):
		if not os.path.exists(self.directory):
			os.makedirs(self.directory)
		else:
			s,o = commands.getstatusoutput("rm -rf %s/*"%(self.directory))
	
	
	def add(self, request):
		rid = str(self.i)
		self.i+= 1
		
		request.init(os.path.join(self.directory, rid))
		self.requests[rid] = request
		self.queue.put(request)
		
		return rid
	
	
	def get(self, rid):
		try:
			return self.requests[rid]
		except KeyError, err:
			pass
		return None
	
	
	def run(self):
		while self.thread_continue():
			request = self.queue.get()
			print "perform request: ",request
			request.status = "in progress"
			ret = request.perform()
			if not ret:
				request.status = "error"
				Logger.error("Apt error on request: "+str(request))
			else:
				request.status = "success"
				Logger.debug("Apt finish request succefully")
	
	
	def terminate(self):
		self.order_stop()
	
	
class Request:
	def __init__(self):
		self.directory = None
		self.status = "created"
	
	def getStatus(self):
		return self.status
	
	def init(self, directory_):
		self.directory = directory_
	
	def perform(self):
		raise NotImplementedError();
	
	def getLog(self, log):
		raise NotImplementedError();
	


class Request_Packages(Request):
	def __init__(self, order_, packages_):
		Request.__init__(self)
		self.order = order_
		self.packages = packages_
	
	
	def getLog(self, log):
		f = self.directory+"/"+log
		if not os.path.exists(f):
			return None
		
		f = file(f, "r")
		buf = f.read()
		f.close()
		
		return buf
	
	def perform(self):
		os.mkdir(self.directory)
		if self.order == "upgrade":
			command = "dist-upgrade"
		elif self.order == "install":
			command = "install "+" ".join(self.packages)
		elif self.order == "remove":
			command = "autoremove --purge "+" ".join(self.packages)
		
		cmd = "apt-get update >>%s/stdout 2>>%s/stderr"%(self.directory, self.directory)
		ret,o = commands.getstatusoutput(cmd)
		if ret != 0:
			return False
		
		cmd = "DEBIAN_FRONTEND=noninteractive DEBIAN_PRIORITY=critical DEBCONF_NONINTERACTIVE_SEEN=true apt-get --yes --force-yes --option DPkg::Options::=--force-confold %s >>%s/stdout 2>>%s/stderr"%(command, self.directory, self.directory)
		ret, o = commands.getstatusoutput(cmd)
		if ret != 0:
			return False
		
		return True

		
class Request_Available(Request):
	def __init__(self):
		Request.__init__(self)
		self.applications = None
	
	
	def perform(self):
		ban_categories = [' ', 'GTK', 'GNOME', 'Qt', 'KDE', 'X-KDE-More', 'TextEditor', 'Core', 'X-KDE-Utilities-PIM', 'X-KDE-settings-sound', 'X-Ximian-Main', 'X-Novell-Main', 'X-Red-Hat-Base', 'Gtk', 'X-GGZ', 'X-KDE-settings-components', 'X-KDE-settings-hardware', 'X-Fedora', 'X-Red-Hat-Extra', 'X-GNOME-SystemSettings', 'X-GNOME-NetworkSettings', 'X-KDE-Utilities-Desktop', 'X-KDE-systemsettings-network', 'X-KDE-settings-security', 'X-KDE-settings-webbrowsing', 'X-KDE-information', 'X-KDE-settings-system', 'X-KDE-settings-accessibility', 'X-KDE-settings-peripherals', 'X-KDE-KDevelopIDE', 'X-KDE-systemsettings-lookandfeel-appearance', 'X-KDE-Edu-Language', 'X-KDE-settings-desktop', 'X-KDE-settings-looknfeel', 'X-KDE-Edu-Misc', 'X-KDE-settings-power', 'X-GNOME-PersonalSettings', 'X-SuSE-Sequencer', 'QT', 'X-Red-Hat-ServerConfig', 'X-Debian-Games-Arcade', 'X-SuSE-Core-System', 'X-KDE-systemsettings-advancedadministration', 'X-SuSE-Core-Game']
		applications = {}
		desktop_files = glob.glob("/usr/share/app-install/desktop/*.desktop")
		
		for a_desktop_file in desktop_files:
			parser = ConfigParser.ConfigParser()
			try:
				parser.read(a_desktop_file)
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
			
			if not parser.has_option('Desktop Entry', "Categories"):
				continue
			
			if not parser.has_option('Desktop Entry', "X-AppInstall-Package"):
				continue
	
			name = parser.get('Desktop Entry', "Name")
			categories = parser.get('Desktop Entry', "Categories")
			package = parser.get('Desktop Entry', "X-AppInstall-Package")
			
			cats = categories.split(";")
			cat = "Others"
			for c in cats:
				if c not in ban_categories:
					cat = c
					break
			
			if not applications.has_key(cat):
				applications[cat] = {}
			
			applications[cat][name]= package
		
		self.applications = applications
		return True
	
	
	def getLog(self, log):
		if self.applications is None:
			return ""
		
		doc = Document()
		categories_node = doc.createElement('categories')
				
		for category in self.applications.keys():
			category_node = doc.createElement("category")
			category_node.setAttribute("name", category)
			for (name, package) in self.applications[category].items():
				application_node = doc.createElement("application")
				application_node.setAttribute("name", name)
				application_node.setAttribute("package", package)
				category_node.appendChild(application_node)
			
			categories_node.appendChild(category_node)
		
		return categories_node.toxml()
