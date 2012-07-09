# -*- coding: utf-8 -*-

# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
# Author David LECHEVALIER <david@ulteo.com> 2012
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

import xrdp


from ovd.Role.ApplicationServer.TS import TS as AbstractTS
from ovd.Platform.System import System



class TS (AbstractTS):
	@staticmethod
	def getList():
		sessions = xrdp.SessionGetList()
		return sessions.keys()
	
	
	@staticmethod
	def getUsersGroup():
		return "tsusers"
	
	
	@staticmethod
	def getSessionID(username_):
		return xrdp.SessionGetId(System.local_encode(username_))
	
	
	@staticmethod
	def getState(session_id):
		status = xrdp.SessionGetStatus(session_id)
		
		if status == xrdp.SESSION_STATUS_ACTIVE:
			return TS.STATUS_LOGGED
		elif status == xrdp.SESSION_STATUS_DISCONNECTED:
			return TS.STATUS_DISCONNECTED
			
		return TS.STATUS_UNKNOWN
	
	
	@staticmethod
	def logoff(session_id):
		return xrdp.SessionLogoff(session_id)

