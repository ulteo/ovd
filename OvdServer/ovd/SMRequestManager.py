# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2011
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

import httplib
import urllib2
from xml.dom import minidom
from xml.dom.minidom import Document

from ovd.Config import Config
from ovd.Logger import Logger


class SMRequestManager():
	STATUS_PENDING = "pending"
	STATUS_READY = "ready"
	STATUS_DOWN = "down"
	STATUS_BROKEN = "broken"
	
	def __init__(self):
		self.url = "http://%s:%d"%(Config.session_manager, Config.SM_SERVER_PORT)
		self.name = None
	
	
	def initialize(self):
		node = self.send_server_name()
		if node is None:
			raise Exception("invalid response")
		
		if not node.hasAttribute("name"):
			raise Exception("invalid response")
		
		self.name = node.getAttribute("name")
		return True
	
	
	def switch_status(self, status):
		if self.name is None:
			return False
		
		return self.send_server_status(status)
	
	
	@staticmethod
	def get_response_xml(stream):
		if not stream.headers.has_key("Content-Type"):
			return None
		
		contentType = stream.headers["Content-Type"].split(";")[0]
		if not contentType == "text/xml":
			Logger.error("content type: %s"%(contentType))
			print stream.read()
			return None
		
		try:
			document = minidom.parseString(stream.read())
		except:
			Logger.warn("No response XML")
			return None
		
		return document
	
	
	def send_server_name(self):
		url = "%s/server/name"%(self.url)
		Logger.debug('SMRequest::server_name url '+url)
		
		req = urllib2.Request(url)
		try:
			f = urllib2.urlopen(req)
		except IOError, e:
			Logger.debug("SMRequest::server_status error"+str(e))
			return None
		except httplib.BadStatusLine, err:
			Logger.debug("SMRequest::server_name not receive HTTP response"+str(err))
			return None
		
		document = self.get_response_xml(f)
		if document is None:
			Logger.warn("SMRequest:send_server_name not XML response")
			return None
		
		rootNode = document.documentElement
		
		if rootNode.nodeName != "server":
			return None
		
		return rootNode
	
	
	def send_packet(self, path, document = None):
		url = "%s%s"%(self.url, path)
		Logger.debug("SMRequest::send_packet url %s"%(url))
		
		req = urllib2.Request(url)
		req.add_header("Content-type", "text/xml; charset=UTF-8")
		
		if document is not None:
			rootNode = document.documentElement
			rootNode.setAttribute("name", str(self.name))
			req.add_data(document.toxml("UTF-8"))
		
		try:
			stream = urllib2.urlopen(req)
		except IOError, e:
			Logger.debug("SMRequest::send_packet path: "+path+" error: "+str(e))
			return False
		except httplib.BadStatusLine, err:
			Logger.debug("SMRequest::send_packet path: "+path+" not receive HTTP response"+str(err))
			return False
		
		return stream
	
	
	def send_server_status(self, status):
		doc = Document()
		rootNode = doc.createElement('server')
		rootNode.setAttribute("status", status)
		doc.appendChild(rootNode)
		response = self.send_packet("/server/status", doc)
		if response is False:
			Logger.warn("SMRequest::send_server_status Unable to send packet")
			return False
		
		document = self.get_response_xml(response)
		if document is None:
			Logger.warn("SMRequest::send_server_status response not XML")
			return False
		
		rootNode = document.documentElement
		
		if rootNode.nodeName != "server":
			Logger.error("SMRequest::send_server_status response not valid %s"%(rootNode.toxml()))
			return False
		
		if not rootNode.hasAttribute("name") or rootNode.getAttribute("name") != self.name:
			Logger.error("SMRequest::send_server_status response invalid name")
			return False
		
		if not rootNode.hasAttribute("status") or rootNode.getAttribute("status") != status:
			Logger.error("SMRequest::send_server_status response invalid status")
			return False
		
		return True
	
	
	def send_server_monitoring(self, doc):
		response = self.send_packet("/server/monitoring", doc)
		if response is False:
			return False
		
		document = self.get_response_xml(response)
		if document is None:
			Logger.warn("SMRequest::send_server_monitoring response not XML")
			return False
		
		rootNode = document.documentElement
		if rootNode.nodeName != "server":
			Logger.error("SMRequest::send_server_monitoring response not valid %s"%(rootNode.toxml()))
			return False
		
		if not rootNode.hasAttribute("name") or rootNode.getAttribute("name") != self.name:
			Logger.error("SMRequest::send_server_monitoring response invalid name")
			return False
		
		return True
