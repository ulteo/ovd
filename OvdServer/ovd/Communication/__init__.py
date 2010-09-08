# -*- coding: utf-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
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

from ovd.Config import Config
from ovd.Logger import Logger
from ovd import util

class Communication:
	STATUS_INIT    = 0
	STATUS_RUNNING = 1
	STATUS_STOP    = 2
	STATUS_ERROR   = 3
	
	def __init__(self, dialogInterfaces):
		self.dialogInterfaces = dialogInterfaces
		self.session_manager = Config.session_manager
		self.status = Communication.STATUS_INIT
	
	def run(self):
		pass
	
	def stop(self):
		pass
	
	def process(self, request):
		if not self.isSessionManagerRequest(request):
			return False
		
		try:
			_, domain, path = request["path"].split("/", 2)
		except ValueError:
			return None
		
		
		request["path"] = "/"+path

		for dialog in self.dialogInterfaces:
			if dialog.getName() == domain:
				return dialog.process(request)
		
		Logger.warn("Unknown domain for request %s"%(str(request)))
		return None

	
	def isSessionManagerRequest(self, request):
		buffer = self.session_manager
		
		if not util.isIP(buffer):
			try:
				buffer = socket.gethostbyname(buffer)
			except Exception, err:
				Logger.error("Communication::isSessionManagerRequest: fail to get address info for '%s'"%(buffer))
				Logger.debug("gethostbyname: %s"%(err))
				return False
		
		return  request["client"] == buffer
	
	def getStatus(self):
		return self.status

