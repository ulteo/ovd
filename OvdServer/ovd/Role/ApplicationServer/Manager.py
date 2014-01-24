# -*- coding: UTF-8 -*-

# Copyright (C) 2011-2014 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
# Author David LECHEVALIER <david@ulteo.com> 2011
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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

from ovd.Config import Config
from ovd.Logger import Logger
from ovd.Platform.System import System
from Platform.TS import TS
from Session import Session

import base64
import glob
import os
from xml.dom.minidom import Document


class Manager:
	ts_group_name = TS.getUsersGroup()
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
		except Exception:
			Logger.exception("ApplicationServer: send_session_status")
		
		response = self.smManager.send_packet("/session/status", doc)
		Logger.debug2("ApplicationServer: send_session_status: %s"%(response))
		if response is False:
			Logger.warn("ApplicationServer: unable to send session status")
		else:
			response.close()
			response = None
	
	
	def send_session_dump(self, session):
		dumps = {}
		
		for path in glob.glob(os.path.join(Config.spool_dir, "sessions dump archive", "%s %s-*"%(session.id, session.user.name))):
			try:
				f= file(path, "r")
			except IOError, err:
				continue
			
			data = f.read()
			f.close()
			
			name = os.path.basename(path)
			name = name[len("%s %s-"%(session.id, session.user.name)):]
			
			dumps[name] = data
		
		data = session.log.get_full_log()
		if len(data) > 0:
			dumps["server.log"] = data
		
		try:
			doc = Document()
			rootNode = doc.createElement('session')
			rootNode.setAttribute("id", session.id)
			
			for (name, data) in dumps.items():
				textNode = doc.createTextNode(base64.encodestring(data))
				
				node = doc.createElement('dump')
				node.setAttribute("name", name)
				node.appendChild(textNode)
				
				rootNode.appendChild(node)
			
			doc.appendChild(rootNode)
		except Exception:
			Logger.exception("ApplicationServer: send_session_dump")
		
		response = self.smManager.send_packet("/session/dump", doc)
		Logger.debug2("ApplicationServer: send_session_dump: %s"%(response))
		if response is False:
			Logger.warn("ApplicationServer: unable to send session dump")
		else:
			response.close()
			response = None
	
	
	def purgeGroup(self):
		while True:
			users = System.groupMember(self.ovd_group_name)
			
			if users is None:
				return False
			
			if users == []:
				return True
			
			for user in users:
				# todo : check if the users is connected, if yes logoff his session
				if not System.userRemove(user):
					return False
			
		return False
	
	
	def session_switch_status(self, session, status):
		if status == Session.SESSION_STATUS_DESTROYED:
			self.send_session_dump(session)
		
		session.switch_status(status)
		Logger.info("Session %s switch status %s"%(session.id, session.status))
		self.send_session_status(session)
