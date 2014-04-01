# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import time
import os
import ctypes
import socket
import struct
import httplib
import socket
import urllib2
import win32ts
import time
import multiprocessing
import Queue

from xml.dom import minidom
from xml.dom.minidom import Document

from ovd.Logger import Logger
from ovd.Role.Role import Role as AbstractRole
from ovd.Config import Config

from Config import Config as Conf
from SessionManagement import SessionManagement
from Session import Session
from Manager import Manager
from User import User

import Util

class Role(AbstractRole):
	
	session_spooler = None
	
	@staticmethod
	def getName():
		return "Guest"
		
		
	def __init__(self, main_instance):
		
		AbstractRole.__init__(self, main_instance)
		Logger.info("Guest role::__init__")
		
		self.loop = True
		self.url = None
		self.host = Conf.hypervisor
		self.port = "1112"
		self.session = None
		self.session_spooler = multiprocessing.Queue()
		self.session_sync = multiprocessing.Queue()
		self.manager = Manager(self.main_instance.smRequestManager)
		self.session_manager = SessionManagement(self.manager, self.session_spooler, self.session_sync)
		self.current_session_status = None
		
		
	def init(self):
		Logger.info("Guest role::init")
		return True
	
	
	def launch(self):
		name = self.get_guest_name_from_hypervisor()
		
		Logger.debug("Role:: name : "+name)
				
		if name is False:
			return False
		
		self.write_name(name)
		
		self.send_couple_ip_nom()
		
		
	def order_stop(self):
		AbstractRole.order_stop(self)
		
		self.session_manager.looping = False
		self.loop = False
		
		
	def run(self):
		self.status = Role.STATUS_RUNNING
		self.launch()
		self.session_manager.start()
		Logger.info("Guest role::run begin")
		while self.loop:
			
			while True :
				try:
					session = self.session_sync.get(True,4)
				except Queue.Empty, e:
					break
				else:
					self.session = session
											
			if self.session is not None :
				ts_id = Util.getSessionID(self.session.user.name)
				
				if ts_id is not None :
					ts_status = Util.getState(ts_id)
					
					if ts_status == "logged" and self.current_session_status != "logged" :
						self.manager.session_switch_status(self.session, Session.SESSION_STATUS_ACTIVE)
						Logger.info("Role::SESSION LOGGED")
						self.current_session_status = ts_status
						continue
					
					if ts_status == "disconnected" and self.current_session_status != "disconnected" :
						self.manager.session_switch_status(self.session, Session.SESSION_STATUS_INACTIVE)
						Logger.info("Role::SESSION_DISCONNECTED")
						self.current_session_status = ts_status
						continue
					
				else :
					if self.current_session_status != "disconnected" and self.session.status != Session.SESSION_STATUS_INITED:
						self.manager.session_switch_status(self.session, Session.SESSION_STATUS_INACTIVE)
						Logger.info("Role::SESSION DISCONNECTED")
						self.current_session_status = "disconnected"
			
			time.sleep(2)
		
		Logger.info("Guest role::run end")
		self.status = Role.STATUS_STOP
		
		
	def finalize(self):
		Logger.info("Guest role::finalize")
		
		
	def getReporting(self, node):
		Logger.info("Guest role::getReporting")
		
		
	def perform_dns_request(self):
	
		try:
			buf = socket.getaddrinfo(self.host, self.port, socket.AF_INET, 0, socket.SOL_TCP)
		except socket.gaierror, err:
			raise Exception("Unable to resolv %s in IPv4 address"%(self.host))
		if len(buf)==0:
			raise Exception("Unable to resolv %s in IPv4 address"%(self.host))
		
		(addr,port) = buf[0][4]
		self.url = "http://%s:%d/hypervisor"%(addr, port)
		
		
	def get_guest_name_from_file(self):
		fichier_name = open(Config.spool_dir+"\guest_name.txt","r")
		name = fichier_name.readline()
		fichier_name.close()
		return name
		
		
	"""
		Write the Guest name in the file guest_name.txt
	"""
	def write_name(self, name):
		
		if not os.path.exists(Config.spool_dir):
			os.makedirs(Config.spool_dir)
			
		file_name = open(Config.spool_dir+"\guest_name.txt","w")
		file_name.write(name)
		file_name.close()
		
		
	def get_guest_name_from_hypervisor(self):
		
		
		try :
			document = Document()
			rootNode = document.createElement("mac")
			rootNode.setAttribute("address", str(Util.get_mac()))
			document.appendChild(rootNode)
		except Exception, e :
			return str(e)
		
		response = self.send_packet("/vm/name", document, "192.168.45.1", "8080")
		
		while response is False :
			#time.sleep(5)
			response = self.send_packet("/vm/name", document, "192.168.45.1", "8080")
		
		if response is False:
			Logger.warn("Guest::send_hypervisor_mac unable to send request")
			return False

		document = minidom.parseString(response.read())
		
		if document is None:
			Logger.warn("Guest::send_hypervisor_mac not XML response")
			return None
		
		rootNode = document.documentElement
		
		if rootNode.nodeName != "mac":
			return None
		
		name =  rootNode.getAttribute("name")
		
		return name
		
		
	"""
		Call the function send_packet_ip and get the response from the session manager
	"""
	def send_couple_ip_nom(self):
		guest_name = self.get_guest_name_from_file()
		guest_name = guest_name[10:]
				
		try:
			document = Document()
			rootNode = document.createElement("vm")
			rootNode.setAttribute("id", guest_name)
			node_ip = document.createElement("ip")
			node_ip.setAttribute("value", Util.get_ip())
			rootNode.appendChild(node_ip)
			document.appendChild(rootNode)
		except Exception, e:
			print str(e)
			
		response = self.send_packet("/vm/info", document, "192.168.45.1", "8080")
				
		if response is False:
			Logger.warn("Guest::send_hypervisor_mac unable to send request")
			return False
			
		Logger.debug("Guest::send_couple_ip_nom")
		
		return True
		
		
	"""
		Build the XML which will be send to the session manager
		- Guest id
		- Guest ip
		Send XML to the Session Manager
		Return a stream which contains the response from the SM
	"""
	def send_packet(self,path, document, ip, port):
		
		req = urllib2.Request("http://"+ip+":"+port+""+ path)
		
		req.add_header("Host", "%s:%s"%(ip, port))
		
		req.add_header("Content-type", "text/xml; charset=UTF-8")
		
		req.add_data(document.toxml())
							
		try:
			stream = urllib2.urlopen(req)
		except IOError, e:
			Logger.debug("Guest::send_packet path: "+path+" error: "+str(e))
			return False
		except httplib.BadStatusLine, err:
			Logger.debug("Guest::send_packet path: "+path+" not receive HTTP response"+str(err))
			return False
		
		return stream
