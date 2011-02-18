# -*- coding: UTF-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
# Author David LECHEVALIER <david@ulteo.com> 2011
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
from ovd.Platform import Platform
from Platform import Platform as RolePlatform
from Session import Session

from xml.dom.minidom import Document


class Manager:
	ts_group_name = RolePlatform.TS.getUsersGroup()
	ovd_group_name = "OVDUsers"

	def __init__(self, smManager):
		self.smManager = smManager

	def send_session_status(self, session):
		try:
			doc = Document()
			rootNode = doc.createElement('session')
			rootNode.setAttribute("id", session.id)
			rootNode.setAttribute("status", session.status)
			if session.status == Session.SESSION_STATUS_DESTROYED and session.end_status is not None:
				rootNode.setAttribute("reason", session.end_status)
			
			doc.appendChild(rootNode)
		except Exception, e:
			print str(e)
		
		response = self.smManager.send_packet("/session/status", doc)
		Logger.debug2("ApplicationServer: send_session_status: %s"%(response))
		if response is False:
			Logger.warn("ApplicationServer: unable to send session status")
		else:
			response.close()
			response = None
	
	
	def purgeGroup(self):
		while True:
			users = Platform.System.groupMember(self.ovd_group_name)
			
			if users is None:
				return False
			
			if users == []:
				return True
			
			for user in users:
				# todo : check if the users is connected, if yes logoff his session
				if not Platform.System.userRemove(user):
					return False
			
		return False


	def session_switch_status(self, session, status):
		session.switch_status(status)
		Logger.info("Session %s switch status %s"%(session.id, session.status))
		self.send_session_status(session)
	

