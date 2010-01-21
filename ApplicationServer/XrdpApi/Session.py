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
import struct
from xml.dom import minidom

SESSION_STATUS_DISCONNECTED	= "DISCONNECT"
SESSION_STATUS_ACTIVE		= "ACTIVE"
SESSION_STATUS_UNKNOWN		= "UNKNOWN"
SESSION_STATUS_CLOSED		= "CLOSED"


"""
Protocol:
	datagrame [ size_t , data ]
	  size_t: unsigned int 32 bits big indian
	  data: utf-8 (xml)
	
	request:
	  <request type="" action="" [id=""] />
	   type: sessions -> action: list
	   type: session  -> id=$ID action: status
	                            action: logoff
	
	response:
	 <error>Message</errror>
	 
	  <response>
	    <session id="" status="" username=""/>
	  </response>
	  
	  <response>
	    <sessions>
	      <session id="" status="" username=""/>
	      <session id="" status="" username=""/>
	    </sessions>
	  </response>
"""

def _SessionGetSocket():
	filename = "/tmp/management"
	
	if not os.path.exists(filename):
		return None
	
	# ToDo: check is rw access on the file
	
	return filename


def _SessionProcessRequest(request):
	path = _SessionGetSocket()
	if path is None:
		return False
	
	s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
	try:
		s.connect(path)
	except socket.error,err:
		print "Unable to connect: ",err
		return False	
	
	data = request.toxml()
		
	s.send(struct.pack('>I', len(data)))
	s.send(data)
	
	buffer = s.recv(4)
	try:
		packet_len = struct.unpack('>I', buffer)[0]
	except Exception, e:
		print "packet recv syntax error"
		s.close()
		return None
	
	data = s.recv(packet_len)
	s.close()
	
	try:
		document = minidom.parseString(data)
	except:
		print "server didn't return XML"
		return None
	
	if document.documentElement.nodeName == "error":
		print "server return error"
		return None
	

	return document


def _SessionFormatRequest(type_, action_, id_ = None):
	doc = minidom.Document()
	rootNode = doc.createElement("request")
	rootNode.setAttribute("type", type_)
	rootNode.setAttribute("action", action_)
	if id_ is not None:
		rootNode.setAttribute("id", id_)
		
	doc.appendChild(rootNode)
	return doc




def SessionGetList():
	l = {}
	
	doc = _SessionProcessRequest(_SessionFormatRequest("sessions", "list"))
	if doc is None:
		return l
	
	sessionNodes = doc.getElementsByTagName("session")
	for node in sessionNodes:
		session = {}
		
		for key in ["id", "username", "status"]:
			session[key] = node.getAttribute(key)
		
		l[session["id"]] = session
	
	return l


def SessionGetStatus(session_id):
	doc = _SessionProcessRequest(_SessionFormatRequest("session", "status", session_id))
	
	if doc is None:
		return SESSION_STATUS_UNKNOWN
	
	sessions = doc.getElementsByTagName("session")
	if len(sessions) == 0:
		return SESSION_STATUS_UNKNOWN
	
	sessionNode = sessions[0]
	status = sessionNode.getAttribute("status")
	return status


def SessionLogoff(session_id):
	doc = _SessionProcessRequest(_SessionFormatRequest("session", "logoff", session_id))
	if doc is None:
		return False
	
	sessions = doc.getElementsByTagName("session")
	if len(sessions) == 0:
		return False
	
	sessionNode = sessions[0]
	status = sessionNode.getAttribute("status")
	if status != SESSION_STATUS_CLOSED:
		return False
	
	return True
