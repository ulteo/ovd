# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
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

import re
import xml.etree.ElementTree as parser

from Communicator import SSLCommunicator
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



class receiver(SSLCommunicator):

	def __init__(self, conn, req):
		SSLCommunicator.__init__(self, conn)
		self._buffer = req



class receiverXMLRewriter(receiver):

	def __init__(self, conn, req, f_ctrl):
		receiver.__init__(self, conn, req)
		self.hasRewrited = False
		self.f_ctrl = f_ctrl

		self.response_ptn = re.compile("<response.+\/>", re.I | re.U)
		self.session_ptn  = re.compile("<session .*>.+<\/session>", re.I | re.U)


	def writable(self):
		if len(self.communicator._buffer) == 0:
			return False

		if self.hasRewrited:
			return True

		if self.response_ptn.search(self.communicator._buffer):
			self.hasRewrited = True
			return True

		xml = self.session_ptn.search(self.communicator._buffer)
		if not xml:
			return False

		xml_length_before = len(self.communicator._buffer)
		newxml = self.rewriteXML(xml.group())
		self.communicator._buffer = self.session_ptn.sub(newxml, self.communicator._buffer, count=1)
		xml_length_after = len(self.communicator._buffer)

		pattern = re.compile("Content-Length: ([0-9]+)", re.I | re.U)
		content_lenght = pattern.search(self.communicator._buffer)
		if content_lenght:
			new_content_length = int(content_lenght.group(1)) + xml_length_after - xml_length_before
			self.communicator._buffer = pattern.sub("Content-Length: %d" % new_content_length, self.communicator._buffer, count=1)

		self.hasRewrited = True
		return True


	def rewriteXML(self, xml):
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
								cmd = ('insert_token', node.attrib["fqdn"])
								node.attrib["token"] = self.f_ctrl.send(cmd)
								del node.attrib["fqdn"]
						break
				return parser.tostring(xml)
			else:
				return getXMLError(501)
		except Exception, e:
			print e
			return getXMLError(500)
