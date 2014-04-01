# -*- coding: utf-8 -*-

# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author Vincent Roullier <vincent.roullier@ulteo.com> 2013
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

import base64
import glob
import os
from xml.dom.minidom import Document

from ovd.Config import Config
from ovd.Logger import Logger


class Scripts:
	supportedExtention = {"sh":"bash", "vbs": "vbs", "py":"python", "bat":"batch", "ps1":"powershell", "unknow":"unknow"}
	
	def __init__(self, communicationInstance):
		self.supportedExtention = {"sh":"bash", "vbs": "vbs", "py":"python", "bat":"batch", "unknow":"unknow"}
		self.communicationInstance = communicationInstance
		self.spool = os.path.join(Config.spool_dir, "scripts")
		self.scripts = {}
		if not os.path.exists(self.spool):
			os.makedirs(self.spool)
		
		for scriptFile in glob.glob(os.path.join(self.spool, "*")):
			script = {}
			base = os.path.basename(scriptFile)
			scriptID, ext = os.path.splitext(base)
			try:
				f = open(scriptFile)
				script["id"] = scriptID
				script["type"] = self.ext2type(ext[1:])
				script["data"] = f.read()
				f.close()
			except Exception:
				Logger.exception("Scripts::init: Failed to parse scripts")
				continue
			
			self.scripts[scriptID] = script
			
	
	
	def synchronize(self):
		response = self.communicationInstance.send_packet("/scripts/sync")
		if response is False:
			Logger.error("Scripts::synchronize request on SessionManager failed")
			return False
		
		document = self.communicationInstance.get_response_xml(response)
		if document is None:
			Logger.warm("Scripts::synchronize response not XML")
			return False
		
		rootNode = document.documentElement
		if rootNode.nodeName != "scripts":
			Logger.error("response not valid %s"%(rootNode.toxml()))
			return False
		
		scripts_old = self.scripts
		scripts_id_old = scripts_old.keys()
		scripts_id_new = []
		scripts_new = {}
		for node in rootNode.getElementsByTagName("script"):
			script = self.xml2script(node)
			if script is None:
				Logger.error("response not valid")
				return False
			
			scripts_id_new.append(script["id"])
			scripts_new[script["id"]] = script
			
			if script["id"] not in scripts_id_old or script["data"] != scripts_old[script["id"]] :
				self.addScript(script)
		
		for script_id in scripts_id_old:
			if script_id not in scripts_id_new:
				self.delScript(script_id)
				
		self.scripts = scripts_new
		return True
	
	
	def xml2script(self, node):
		script = {}
		
		try:
			script["id"] = node.getAttribute("id")
			script["name"] = node.getAttribute("name")
			script["type"] = node.getAttribute("type")
			script["data"] = base64.decodestring(node.childNodes[0].nodeValue)
		except:
			return None
		
		return script
	
	
	@staticmethod
	def type2ext(typeName):
		lt = typeName.lower()
		for k in Scripts.supportedExtention:
			if Scripts.supportedExtention[k] == lt:
				return k
		return "unknow"
	
	
	@staticmethod
	def ext2type(ext):
		if Scripts.supportedExtention.has_key(ext.lower()):
			return Scripts.supportedExtention[ext.lower()]
		return "unknow"
	
	
	def addScript(self, script_):
		self.saveScript(script_)
		self.scripts[script_["id"]] = script_
		return True
	
	
	def delScript(self, script_id_):
		script = self.scripts[script_id_]
		ext = self.type2ext(script["type"])
		path = os.path.join(self.spool, script["id"]+"."+ext)
		
		if os.path.exists(path):
			os.remove(path)
		
		if self.scripts.has_key(script_id_):
			del(self.scripts[script_id_])
	
	
	def getList(self):
		scripts = {}
		for scriptID in self.scripts:
			scripts[scriptID] = self.scripts[scriptID]
			
		return scripts
	
	
	def saveScript(self, script_):
		ext = self.type2ext(script_["type"])
		path = os.path.join(self.spool, script_["id"])
		path += "."+ext
		try:
			f= file(path, "w")
		except:
			Logger.error("Unable to open file '%s'"%(path))
			return False
		data = script_["data"];
		data = data.replace("\r","")
		f.writelines(data)
		f.close()
		
		return True
		
