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

from ovd.Logger import Logger


response_ptn = re.compile("<response.+\/>", re.I | re.U)
session_ptn  = re.compile("<session .*>.+<\/session>", re.I | re.U)
content_length_ptn = re.compile("Content-Length: ([0-9]+)", re.I | re.U)


def rewrite(buf, xml, ctrl):
	Logger.debug("Gateway:: rewrite XML")

	def getXMLError(msg):
		rootnode = parser.Element("error")
		rootnode.attrib["id"] = str(id)
		Logger.error(msg)
		rootnode.attrib["message"] = msg
		return parser.tostring(rootnode)

	xml_length_before = len(buf)

	xml = parser.XML(xml.group())
	if xml.tag.upper() == "SESSION" and xml.findall("server") and xml.findall("user"):
		for element in xml.getiterator():
			if element.tag.upper() == "SESSION":
				element.attrib["mode_gateway"] = "on"
				subNodes = element.getchildren()
				for node in subNodes:
					if node.tag.upper() == "SERVER":
						cmd = ('insert_token', node.attrib["fqdn"])
						node.attrib["token"] = ctrl.send(cmd)
						del node.attrib["fqdn"]
				break
		newxml = parser.tostring(xml)
	else:
		newxml = getXMLError("Gateway failed to produce XML replacement")

	buf = session_ptn.sub(newxml, buf, count=1)

	xml_length_after = len(buf)
	content_lenght = content_length_ptn.search(buf)
	if content_lenght:
		new_content_length = int(content_lenght.group(1)) + xml_length_after - xml_length_before
		buf = content_length_ptn.sub("Content-Length: %d" % new_content_length, buf, count=1)

	return buf
