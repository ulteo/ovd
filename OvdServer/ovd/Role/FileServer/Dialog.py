# -*- coding: utf-8 -*-

# Copyright (C) 2008-2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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

import httplib
import urllib
import urllib2
import socket
from xml.dom import minidom
from xml.dom.minidom import Document

from ovd.Communication.Dialog import Dialog as AbstractDialog
from ovd.Logger import Logger
from ovd import util

from Config import Config
from Share import Share


class Dialog(AbstractDialog):
	def __init__(self, role_instance):
		self.role_instance = role_instance
	
	@staticmethod
	def getName():
		return "fs"
	
	
	def process(self, request):
		path = request["path"]
		
		if request["method"] == "GET":
			Logger.debug("do_GET "+path)
			
			if path == "/shares":
				return self.req_list_all(request)
		
			elif path == "/statistics":
				return self.req_statistics(request)
			
			#elif path.startswith("/share/"):
				#buf = path[len("/share/"):]
				
				#buf = buf.split("/")
				#if buf[0] == "create":
					#if len(buf) != 2:
						#return None
					
					#doc = Document()
					#rootNode = doc.createElement('share')
					#rootNode.setAttribute("id", buf[1])
					#doc.appendChild(rootNode)
					#request["data"] = doc.toxml()
					
					#return self.req_share_create(request)
				
				#elif buf[0] == "delete":
					#if len(buf) != 2:
						#return None
					
					#doc = Document()
					#rootNode = doc.createElement('share')
					#rootNode.setAttribute("id", buf[1])
					#doc.appendChild(rootNode)
					#request["data"] = doc.toxml()
					
					#return self.req_share_delete(request)
				
				#elif buf[0] == "users":
					
					#if len(buf) < 4:
						#return None
					
					#if buf[1] == "add":
						#doc = Document()
						#rootNode = doc.createElement('share')
						#rootNode.setAttribute("id", buf[2])
						#doc.appendChild(rootNode)
						
						#for user in buf[3:]:
							#(login, passwd) = user.split(":", 2)
							
							#node = doc.createElement('user')
							#node.setAttribute("login", login)
							#node.setAttribute("password", passwd)
							#rootNode.appendChild(node)
						
						#request["data"] = doc.toxml()
						#return self.req_share_add_users(request)
					
					#elif buf[1] == "del":
						#doc = Document()
						#rootNode = doc.createElement('share')
						#rootNode.setAttribute("id", buf[2])
						#doc.appendChild(rootNode)
						
						#for user in buf[3:]:
							#node = doc.createElement('user')
							#node.setAttribute("login", user)
							#rootNode.appendChild(node)
						
						#request["data"] = doc.toxml()
						
						#return self.req_share_del_users(request)
				
				return None
			
		
		elif request["method"] == "POST":
			if path == "/share/create":
				return self.req_share_create(request)
			
			elif path == "/share/delete":
				return self.req_share_delete(request)
			
			elif path == "/share/users/add":
				return self.req_share_add_users(request)
			
			elif path == "/share/users/del":
				return self.req_share_del_users(request)
		
		return None
	
	def req_list_all(self, request):
		shares = self.role_instance.get_existing_shares()
		infos  = self.role_instance.get_disk_size_infos()
		
		doc = Document()
		rootNode = doc.createElement('shares')
		rootNode.setAttribute("total_size", str(infos[0]))
		rootNode.setAttribute("free_size", str(infos[1]))
		doc.appendChild(rootNode)
		
		for share in shares:
			node = doc.createElement('share')
			node.setAttribute("id", share.name)
			node.setAttribute("status", str(share.status()))
			rootNode.appendChild(node)
		
		return self.req_answer(doc)
	
	def req_statistics(self, request):
		infos  = self.role_instance.get_disk_size_infos()
		
		doc = Document()
		rootNode = doc.createElement('statistics')
		sizeNode = doc.createElement('size')
		sizeNode.setAttribute("total", str(infos[0]))
		sizeNode.setAttribute("free", str(infos[1]))
		rootNode.appendChild(sizeNode)
		doc.appendChild(rootNode)
		
		return self.req_answer(doc)
	
	def req_share_create(self, request):
		try:
			document = minidom.parseString(request["data"])
			roodNode = document.documentElement
			if roodNode.nodeName != "share":
				raise Exception("invalid root node")
			
			share_id = roodNode.getAttribute("id")
			if len(share_id)==0 or "/" in share_id:
				raise Exception("invalid root node")
		
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		share = Share(share_id, Config.spool)
		if self.role_instance.shares.has_key(share_id) or share.status() is not Share.STATUS_NOT_EXISTS:
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "already_exists")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if not share.create():
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "system_error")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		return self.share2xml(share)
	
	def req_share_delete(self, request):
		try:
			document = minidom.parseString(request["data"])
			roodNode = document.documentElement
			if roodNode.nodeName != "share":
				raise Exception("invalid root node")
			
			share_id = roodNode.getAttribute("id")
			if len(share_id)==0 or "/" in share_id:
				raise Exception("invalid root node")
		
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if self.role_instance.shares.has_key(share_id):
			share = self.role_instance.shares[share_id]
		else:
			share = Share(share_id, Config.spool)
			share.delete()
		
		return self.share2xml(share)
	
	
	def share2xml(self, share):
		doc = Document()
		rootNode = doc.createElement('share')
		rootNode.setAttribute("id", share.name)
		rootNode.setAttribute("status", str(share.status()))
		doc.appendChild(rootNode)
		return self.req_answer(doc)
	
	
	def req_share_add_users(self, request):
		try:
			document = minidom.parseString(request["data"])
			
			rootNode = document.documentElement
			if rootNode.nodeName != "share":
				raise Exception("invalid root node")
			
			share_id = rootNode.getAttribute("id")
			
			userNodes = rootNode.getElementsByTagName("user")
			if len(userNodes)==0:
				raise Exception("usage")
			
			users = []
			for node in userNodes:
				login = node.getAttribute("login")
				password = node.getAttribute("password")
				
				users.append((login, password))
		
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		if self.role_instance.shares.has_key(share_id):
			share = self.role_instance.shares[share_id]
		else:
			share = Share(share_id, Config.spool)
			if share.status is Share.STATUS_NOT_EXISTS:
				doc = Document()
				rootNode = doc.createElement('error')
				rootNode.setAttribute("id", "not_exists")
				doc.appendChild(rootNode)
				return self.req_answer(doc)
			
			self.role_instance.shares[share_id] = share
		
		for (user,password) in users:
			if not share.add_user(user, password):
				doc = Document()
				rootNode = doc.createElement('error')
				rootNode.setAttribute("id", "system_error")
				doc.appendChild(rootNode)
				return self.req_answer(doc)
		
		share.enable()
		return self.share2xml(share)
	
	
	def req_share_del_users(self, request):
		try:
			document = minidom.parseString(request["data"])
			
			rootNode = document.documentElement
			if rootNode.nodeName != "share":
				raise Exception("invalid root node")
			
			share_id = rootNode.getAttribute("id")
			
			userNodes = rootNode.getElementsByTagName("user")
			if len(userNodes)==0:
				raise Exception("usage")
			
			users = []
			for node in userNodes:
				login = node.getAttribute("login")
				
				users.append(login)
		
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		if not self.role_instance.shares.has_key(share_id):
			return self.share2xml(Share(share_id, Config.spool))
			
		share = self.role_instance.shares[share_id]
		
		ret = True
		for user in users:
			if not share.del_user(user):
				ret = False
		
		
		if len(share.users) == 0:
			if not share.disable():
				ret = False
			
			del(self.role_instance.shares[share_id])
		
		if not ret:
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "system_error")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		return self.share2xml(share)
