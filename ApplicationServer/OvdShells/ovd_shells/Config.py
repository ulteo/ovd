# -*- coding: utf-8 -*-

# Copyright (C) 2012-2014 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2012
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

import os
import sys
from xml.dom import minidom
from xml.parsers.expat import ExpatError


class Config:
	sm = None
	external_apps_token = None
	no_desktop_process = False
	use_known_drives = False
	profile_mode = None
	use_local_ime = False
	desktop_icons = False
	
	application_to_start = []
	scripts_to_start = []
	
	
	def load(self, d):
		filename = os.path.join(d, "shell.conf")
		if not os.path.isfile(filename):
			print >>sys.stderr, "No such file '%s'"%(filename)
			return False
		
		try:
			document = minidom.parse(filename)
		
		except ExpatError, err:
			print >>sys.stderr, "Unable to load XML file '%s'"%(filename)
			return False
		
		shellNode = document.documentElement
		if shellNode.nodeName != "shell":
			print >>sys.stderr, "Unrecognized document element"
			return False
		
		for node in shellNode.getElementsByTagName("setting"):
			if not node.hasAttribute("name") or not node.hasAttribute("value"):
				continue
			
			key = node.getAttribute("name")
			if key == "external_apps_token":
				if not shellNode.hasAttribute("sm"):
					print >>sys.stderr, "Receive a 'external_apps_token' but no sm"
					continue
				
				self.external_apps_token = node.getAttribute("value")
				self.sm = shellNode.getAttribute("sm")
			
			elif key == "no_desktop_process" and node.getAttribute("value") == "1":
				self.no_desktop_process = True
			
			elif key == "use_known_drives" and node.getAttribute("value").lower() == "true":
				self.use_known_drives = True
			
			elif key == "profile_mode":
				self.profile_mode = node.getAttribute("value").lower()
			
			elif key == "use_local_ime" and node.getAttribute("value") == "1":
				self.use_local_ime = True
			
			elif key == "desktop_icons" and node.getAttribute("value") == "1":
				self.desktop_icons = True
		
		
		nodes = shellNode.getElementsByTagName("start")
		if len(nodes) > 0:
			for node in nodes[0].getElementsByTagName("application"):
				if not node.hasAttribute("app_id"):
					print >>sys.stderr, "Unable to parse application to start: app_id parameter is missing"
					continue
				
				application = {}
				
				try:
					application["id"] = int(node.getAttribute("app_id"))
				except ValueError, err:
					print >>sys.stderr, "Invalid application id '%s'"%(node.getAttribute("app_id"))
					continue
				
				if node.hasAttribute("arg"):
					application["arg"] = node.getAttribute("arg")
				
				fileNodes = node.getElementsByTagName("file")
				if len(fileNodes) > 0:
					fileNode = fileNodes[0]
					if not fileNode.hasAttribute("type") or not fileNode.hasAttribute("location") or not fileNode.hasAttribute("path"):
						print >>sys.stderr, "Unable to parse application file argument: parameters are missing"
						continue
					
					application["file"] = {}
					application["file"]["type"] = fileNode.getAttribute("type")
					application["file"]["location"] = fileNode.getAttribute("location")
					application["file"]["path"] = fileNode.getAttribute("path")
				
				self.application_to_start.append(application)

		nodes = shellNode.getElementsByTagName("scripts")
		if len(nodes) > 0:
			for node in nodes[0].getElementsByTagName("script"):
				if not node.hasAttribute("id"):
					print >>sys.stderr, "Unable to parse script to start: script_id parameter is missing"
					continue
				
				script = {}
				
				try:
					script["id"] = int(node.getAttribute("id"))
				except ValueError, err:
					print >>sys.stderr, "Invalid script id '%s'"%(node.getAttribute("id"))
					continue
				
				if node.hasAttribute("name"):
					script["name"] = node.getAttribute("name")
				
				self.scripts_to_start.append(script)
		
		return True
