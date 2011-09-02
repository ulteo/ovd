# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import socket
import re

from ovd.Logger import Logger


class Protocol:
	HTTP = 80
	HTTPS = 443
	RDP = 3389
	SSL = 443



class Config:
	general = None
	address = "0.0.0.0"
	port = Protocol.SSL
	max_process = 10
	max_connection = 100
	process_timeout = 60
	web_client = None
	admin_redirection = False
	http_keep_alive = True

	@staticmethod
	def init(infos):
		if infos.has_key("address"):
			Config.address = infos["address"]
		
		if infos.has_key("port") and infos["port"].isdigit():
			try:
				Config.port = int(infos["port"])
			except ValueError:
				Logger.error("Invalid int number for port")
		
		if infos.has_key("max_process"):
			try:
				Config.max_process = int(infos["max_process"])
			except ValueError:
				Logger.error("Invalid int number for max_process")
		
		if infos.has_key("max_connection"):
			try:
				Config.max_connection = int(infos["max_connection"])
			except ValueError:
				Logger.error("Invalid int number for max_process")
		
		if infos.has_key("process_timeout"):
			try:
				Config.process_timeout = int(infos["process_timeout"])
			except ValueError:
				Logger.error("Invalid int number for process_timeout")
		
		if infos.has_key("web_client"):
			r = re.match("(?P<protocol>https?)://(?P<host>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(:(?P<port>\d+))?/?$",
				infos["web_client"])
			try:
				if r is None:
					raise Exception("malformed")
				socket.inet_aton(r.group('host'))
				port = r.group('port')
				if port is not None:
					port = int(port)
					if not (port > 0 and port < 65536):
						raise Exception("incorrect port")
			except socket.error:
				Logger.error("Invalid conf for Web Client: incorrect IP")
			except Exception, e:
				Logger.error("Invalid conf for Web Client: " + str(e))
			else:
				protocol = r.group('protocol')
				if port is None:
					port = getattr(Protocol, protocol.upper())
				Config.web_client = (getattr(Protocol, protocol.upper()), r.group('host'), port)
		
		if infos.has_key("admin_redirection"):
			if infos["admin_redirection"].lower() == "true":
				Config.admin_redirection = True
			elif infos["admin_redirection"].lower() == "false":
				Config.admin_redirection = False
			else:
				Logger.error("Invalid value for 'admin_redirection' option")

		if infos.has_key("http_keep_alive"):
			if infos["http_keep_alive"].lower() == "false":
				Config.http_keep_alive = False
			elif infos["http_keep_alive"].lower() == "true":
				Config.http_keep_alive = True
			else:
				Logger.error("Invalid value for 'http_keep_alive' option")
		
		return True
