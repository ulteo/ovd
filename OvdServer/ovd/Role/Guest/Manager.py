# -*- coding: UTF-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
# Author David LECHEVALIER <david@ulteo.com> 2011
# Author Gaëtan COLLET <gaetan@ulteo.com> 2011
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
from ovd.Platform.System import System
from Session import Session
from Config import Config

from xml.dom.minidom import Document
import urllib2


class Manager:
	
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
			
		req = urllib2.Request("http://"+Config.session_manager+":1111/session/status")
		
		req.add_header("Host", "%s:%s"%(Config.session_manager, "1111"))
		
		req.add_header("Content-type", "text/xml; charset=UTF-8")
		req.add_data(doc.toxml())
					
		try:
			stream = urllib2.urlopen(req)
		except IOError, e:
			Logger.debug("Guest::send_packet session status path: /session/status error: "+str(e))
			return False
		except httplib.BadStatusLine, err:
			Logger.debug("Guest::send_packet session status path: /session/status not receive HTTP response"+str(err))
			return False
		
		if stream is False:
			Logger.warn("Guest: unable to send session status")
		else:
			stream.close()
			stream = None
	
	
	def session_switch_status(self, session, status):
		session.switch_status(status)
		Logger.info("Session %s switch status %s"%(session.id, session.status))
		self.send_session_status(session)
