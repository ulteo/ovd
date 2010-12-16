# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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

from OpenSSL import SSL

import asyncore
import socket
import re
import xml.etree.ElementTree as parser

from ovd.Logger import Logger


xmlError = {
	500 : "Gateway XML parsing failed",
	501 : "Gateway failed to produce XML replacement",
	-1 : "error message unknown",
}


def getXMLError(id):
	rootnode = parser.Element("error")
	rootnode.attrib["id"] = str(id)
	try:
		msg = xmlError[id]
	except:
		msg = xmlError[-1]
	Logger.error(msg)
	rootnode.attrib["message"] = msg
	return parser.tostring(rootnode)



class receiver(asyncore.dispatcher):

	def __init__(self, conn, req):
		asyncore.dispatcher.__init__(self,conn)
		self.to_remote_buffer = ''
		self.from_remote_buffer = req
		self.sender = None


	def handle_read(self):
		try:
			read = self.recv(8192)
			self.from_remote_buffer += read
		except SSL.ZeroReturnError:
			pass
		except:
			Logger.debug('%s::handle_read error' % self.__class__.__name__)
			self.close()


	def writable(self):
		return len(self.to_remote_buffer) > 0


	def handle_write(self):
		try:
			sent = self.send(self.to_remote_buffer)
			self.to_remote_buffer = self.to_remote_buffer[sent:]
		except SSL.WantWriteError:
			pass
		except:
			Logger.debug('%s::handle_write error' % self.__class__.__name__)
			self.close()


	def handle_close(self):
		self.close()
		if self.sender:
			self.sender.close()



class receiverXMLRewriter(receiver):

	def __init__(self, conn, req, proxy):
		receiver.__init__(self, conn, req)
		self.hasRewrited = False
		self.proxy = proxy

	
	def writable(self):
		if len(self.to_remote_buffer) == 0:
			return False

		if self.hasRewrited:
			return True

		pattern = re.compile("<error.+\/>", re.I | re.U)
		if pattern.search(self.to_remote_buffer):
			self.hasRewrited = True
			return True

		pattern = re.compile("<session .*>.+<\/session>", re.I | re.U)
		xml = pattern.search(self.to_remote_buffer)
		if not xml:
			return False

		xml_length_before = len(self.to_remote_buffer)
		newxml = self.rewriteXML(xml.group(), self.proxy)
		self.to_remote_buffer = pattern.sub(newxml, self.to_remote_buffer, count=1)
		xml_length_after = len(self.to_remote_buffer)

		pattern = re.compile("Content-Length: ([0-9]+)", re.I | re.U)
		content_lenght = pattern.search(self.to_remote_buffer)
		if content_lenght:
			new_content_length = int(content_lenght.group(1)) + xml_length_after - xml_length_before
			self.to_remote_buffer = pattern.sub("Content-Length: %d" % new_content_length, self.to_remote_buffer, count=1)

		self.hasRewrited = True
		return True


	def rewriteXML(xml, proxy):
		Logger.debug('receiverXMLRewriter::rewriteXML')
		try:
			xml = parser.XML(xml)
			if xml.tag.upper() == "SESSION" and xml.findall("server") and xml.findall("user"):
				for element in xml.getiterator():
					if element.tag.upper() == "SESSION":
						element.attrib["mode_gateway"] = "on"
						subNodes = element.getchildren()
						for node in subNodes:
							if node.tag.upper() == "SERVER":
								node.attrib["token"] = proxy.insertToken(node.attrib["fqdn"])
								del node.attrib["fqdn"]
						break
				return parser.tostring(xml)
			else:
				return getXMLError(501)
		except:
			return getXMLError(500)

	rewriteXML = staticmethod(rewriteXML)
