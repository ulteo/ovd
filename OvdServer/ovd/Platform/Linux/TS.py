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

import os
import socket
from xml.dom import minidom
import xrdp

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.TS import TS as AbstractTS



class TS (AbstractTS):
	@staticmethod
	def getSessionID(username_):
		sessions = xrdp.SessionGetList()
		for session in sessions.values():
			login = session["username"]
			if login == username_:
				return session["id"]
			
		return None
	
	
	@staticmethod
	def getState(session_id):
		status = xrdp.SessionGetStatus(session_id)
		
		if status == xrdp.SESSION_STATUS_ACTIVE:
			return TS.STATUS_LOGGED
		elif status == xrdp.SESSION_STATUS_DISCONNECTED():
			return TS.STATUS_DISCONNECTED
			
		return TS.STATUS_UNKNOWN
	
	
	@staticmethod
	def logoff(session_id):
		return xrdp.SessionLogoff(session_id)

