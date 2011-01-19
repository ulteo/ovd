# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
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

import glob
import os
from xml.dom.minidom import Document

from ovd.Config import Config
from ovd.Logger import Logger


class ApplicationsStatic:
	def __init__(self, communictionInstance):
		self.communictionInstance = communictionInstance
		
		self.spool = os.path.join(Config.spool_dir, "static_applications")
		if not os.path.exists(self.spool):
			os.makedirs(self.spool)
		
		self.applications = {}
	
	
	def synchronize(self):
		response = self.communictionInstance.send_packet("/applications/static")
		if response is False:
			Logger.error("ApplicationsStatic::synchronize request on SessionManager failed")
			return False
		
		document = self.communictionInstance.get_response_xml(response)
		if document is None:
			Logger.warn("ApplicationsStatic::synchronize response not XML")
			return False
		
		rootNode = document.documentElement
		if rootNode.nodeName != "applications":
			Logger.error("response not valid %s"%(rootNode.toxml()))
			return False
		
		applications_old = self.getList()
		applications_id_old = applications_old.keys()
		applications_id_new = []
		applications_new = {}
		
		for node in rootNode.getElementsByTagName("application"):
			application = self.xml2application(node)
			if application is None:
				Logger.error("response not valid")
				return False
			
			applications_id_new.append(application["id"])
			application["filename"] = self.getApplicationPath(application["id"])
			applications_new[application["id"]] = application
			
			if application["id"] not in applications_id_old or application["revision"] != applications_old[application["id"]]:
				if not self.addApplication(application):
					Logger.error("uanble to add application")
					return False
		
		# Delete old applications no longer exists
		for application_id in applications_id_old:
			if application_id not in applications_id_new:
				self.delApplication(application_id)
		
		self.applications = applications_new
		return True
	
	
	def addApplication(self, application_):
		if not self.getIcon(application_["id"]):
			Logger.error("Unable to get icon for application %s"%(application_["id"]))
			return False
		
		if not self.createShortcut(application_):
			Logger.error("Unable to create shortcut for application %s"%(application_["id"]))
			return False
		
		path = os.path.join(self.spool, application_["id"]+".revision")
		try:
			f= file(path, "w")
		except:
			Logger.error("Unable to open file '%s'"%(path))
			return False
		
		f.writelines(application_["revision"])
		f.close()
		
		application_["filename"] = self.getApplicationPath(application_["id"])
		self.applications[application_["id"]] = application_
		
		
		return True
	
	
	def delApplication(self, application_id_):
		for ext in self.getFilesExtensions() + ["png", "revision"]:
			path = os.path.join(self.spool, application_id_+"."+ext)
			
			if os.path.exists(path):
				os.remove(path)
		
		if self.applications.has_key(application_id_):
			del(self.applications[application_id_])
	
	
	def getList(self):
		applications = {}
		
		path = os.path.join(self.spool, "*.revision")
		for f in glob.glob(path):
			name = os.path.basename(f)[:-len(".revision")]
			
			continue_ = True
			for ext in self.getFilesExtensions() + ["png"]:
				path2 = os.path.join(self.spool, name+"."+ext)
				if not os.path.exists(path2):
					continue_ = False
					break
			
			if continue_ is False:
				self.delApplication(name)
				continue
			
			try:
				f = file(f, "r")
			except IOError, err:
				self.delApplication(name)
				continue
			
			revision = f.readline().strip()
			f.close()
			
			applications[name] = revision
		
		return applications
	
	
	def getIcon(self, id_):
		doc = Document()
		rootNode = doc.createElement('application')
		rootNode.setAttribute("id", id_)
		doc.appendChild(rootNode)
		
		response = self.communictionInstance.send_packet("/application/icon", doc)
		if response is False or not response.headers.has_key("Content-Type"):
			return False
		
		contentType = response.headers["Content-Type"].split(";")[0]
		if not contentType == "image/png":
			Logger.error("content type: %s"%(contentType))
			print response.read()
			return None
		
		data = response.read()
		
		
		path = os.path.join(self.spool, id_+".png")
		try:
			f= file(path, "wb")
		except:
			Logger.error("Unable to open file '%s'"%(path))
			return False
		
		f.write(data)
		f.close()
		
		return True
	
	
	@staticmethod
	def xml2application(node):
		application = {}
		
		try:
			application["id"] = node.getAttribute("id")
			application["revision"] = node.getAttribute("revision")
			application["name"] = node.getAttribute("name")
			application["description"] = node.getAttribute("description")
			application["command"] = node.getAttribute("command")
			application["mimetypes"] = []
			for mimeNode in node.getElementsByTagName("mime"):
				application["mimetypes"].append(mimeNode.getAttribute("type"))
		
		except:
			return None
		
		return application
	
	
	def getApplicationPath(self, id_):
		raise NotImplementedError()
	
	@staticmethod
	def getFilesExtensions():
		raise NotImplementedError()
	
	def createShortcut(self, application_):
		raise NotImplementedError()
