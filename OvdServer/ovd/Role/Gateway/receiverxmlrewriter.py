# -*- coding: utf-8 -*-
# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
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

from ovd.Logger import Logger
import socket
import asyncore
import re
import xml.etree.ElementTree as parser

def getXMLError(id):
	rootnode = parser.Element("error")
	rootnode.attrib["id"] = str(id)
	message = "Unknown message"
	
	if id == 501:
		message = "Gateway failed to produce XML replacement"
	else :
		message = "Gateway failed for unknown reason"

	Logger.error(message)
	rootnode.attrib["message"] = message
	return parser.tostring(rootnode)

class receiverXMLRewriter(asyncore.dispatcher):
	def __init__(self, conn, req, proxy):
		try:
			asyncore.dispatcher.__init__(self,conn)
			self.to_remote_buffer = ''
			self.from_remote_buffer = req
			self.sender = None
			self.hasRewrited = False
			self.proxy = proxy
		except:
			Logger.error('receiverXMLRewriter:: Error In Core Receiver Module...')
			self.close()

	def handle_read(self):
		try:
			read = self.recv(8192)
			self.from_remote_buffer += read
		except:
			self.close()

	def writable(self):
		try:
			if not self.hasRewrited:
				pattern = re.compile("<error.+\/>", re.I | re.U)
				test = re.search(pattern, str(self.to_remote_buffer))
				
				if test:
					self.hasRewrited = True
					return (len(str(self.to_remote_buffer)) > 0)
				
				pattern = re.compile("<session.+<\/session>", re.I | re.U)
				test = re.search(pattern, str(self.to_remote_buffer))
				
				if test:
					self.hasRewrited = True
					newxml = self.rewriteXML(test.group())
					
					try:
						self.to_remote_buffer = pattern.sub(newxml, str(self.to_remote_buffer), count = 1)
					except:
						Logger.error('receiverXMLRewriter:: XML detected but replace failed')
					return (len(str(self.to_remote_buffer)) > 0)
					
				else:
					return (False)
				
			else:
				return (len(str(self.to_remote_buffer)) > 0)
			
		except:
			Logger.error('receiverXMLRewriter:: Rewriting XML failed')
			return (False)

	def handle_write(self):
		sent = self.send(self.to_remote_buffer)
		self.to_remote_buffer = self.to_remote_buffer[sent:]

	def handle_close(self):
		self.close()
		
		if self.sender:
			self.sender.close()

	def getSessionNode(self, session):
		iter = session.getiterator()
		
		for element in iter:
			
			if element.tag.upper() == "SESSION":
				element.attrib["mode_gateway"] = "on"
				subNodes = element.getchildren()
				
				for node in subNodes:
					
					if node.tag.upper() == "SERVER":
						node.attrib["token"] = self.proxy.insertToken(node.attrib["fqdn"])
						del node.attrib["fqdn"]
				
				break

	def rewriteXML(self, xml):
		try:
			new = parser.XML(xml)
			
			if new.tag.upper() == "SESSION" and new.findall("server") and new.findall("user"):
				self.getSessionNode(new)
				return parser.tostring(new)
			else:
				return getXMLError(501)
			
		except:
			return getXMLError(500)
