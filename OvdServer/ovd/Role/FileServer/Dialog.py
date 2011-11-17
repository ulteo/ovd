# -*- coding: utf-8 -*-

# Copyright (C) 2008-2011 Ulteo SAS
# http://www.ulteo.com
# Author David LECHEVALIER <david@ulteo.com> 2011
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

from xml.dom import minidom
from xml.dom.minidom import Document

from ovd.Communication.Dialog import Dialog as AbstractDialog
from ovd.Logger import Logger
from ovd import util

from Config import Config
from Share import Share
from User import User


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
			
			if path == "/info":
				return self.req_info(request)
		
		elif request["method"] == "POST":
			if path == "/share/create":
				return self.req_share_create(request)
			
			elif path == "/share/delete":
				return self.req_share_delete(request)
			
			elif path == "/access/enable":
				return self.req_enable_user(request)
			
			elif path == "/access/disable":
				return self.req_disable_user(request)
		
		return None
	
	
	def req_info(self, request):
		doc = Document()
		rootNode = doc.createElement('info')
		self.role_instance.getReporting(rootNode)
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
		
		
		self.role_instance.shares[share_id] = share
		return self.share2xml(share)
	
	
	def req_share_delete(self, request):
		Logger.debug("FS:dialog::delete_share")
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
		
		if not self.role_instance.shares.has_key(share_id):
			Logger.debug("Unknown share '%s'"%(share_id))
			return self.share2xml(Share(share_id, Config.spool))
		
		share = self.role_instance.shares[share_id]
		share.delete()
		del(self.role_instance.shares[share_id])
		
		return self.share2xml(share)
	
	
	def share2xml(self, share):
		doc = Document()
		rootNode = doc.createElement('share')
		rootNode.setAttribute("id", share.name)
		rootNode.setAttribute("status", str(share.status()))
		doc.appendChild(rootNode)
		return self.req_answer(doc)
	
	def user2xml(self, user, exists):
		doc = Document()
		rootNode = doc.createElement('user')
		rootNode.setAttribute("login", user)
		if exists:
			status = "ok"
		else:
			status = "unknown"
		rootNode.setAttribute("status", status)
		doc.appendChild(rootNode)
		return self.req_answer(doc)
	
	
	def req_enable_user(self, request):
		try:
			document = minidom.parseString(request["data"])
			
			rootNode = document.documentElement
			if rootNode.nodeName != "session":
				raise Exception("invalid root node")
			
			user = rootNode.getAttribute("login")
			password = rootNode.getAttribute("password")
			if len(user) == 0 or len(password) == 0:
				raise Exception("empty parameters")
			
			shares = []
			for node in rootNode.getElementsByTagName("share"):
				shares.append(node.getAttribute("id"))
		
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		u = User(user)
		if u.existSomeWhere():
			Logger.warn("FS: Enable user %s but already exists in system: purging it"%(user))
			u.clean()
			
			if u.existSomeWhere():
				Logger.error("FS: unable to del user %s"%(user))
				doc = Document()
				rootNode = doc.createElement('error')
				rootNode.setAttribute("id", "system_error")
				rootNode.setAttribute("msg", "user already exists and cannot be deleted")
				doc.appendChild(rootNode)
				return self.req_answer(doc)
		
		if not u.create(password):
			Logger.error("FS: unable to create user %s"%(user))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "system_error")
			rootNode.setAttribute("msg", "user cannot be created")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		somethingWrong = False
		for share_id in shares:
			if not self.role_instance.shares.has_key(share_id):
				somethingWrong = True
				response = self.share2xml(Share(share_id, Config.spool))
				break
			
			share = self.role_instance.shares[share_id]
			if not share.add_user(user):
				somethingWrong = True
				doc = Document()
				rootNode = doc.createElement('error')
				rootNode.setAttribute("id", "system_error")
				rootNode.setAttribute("msg", "share cannot enable user")
				doc.appendChild(rootNode)
				response = self.req_answer(doc)
				break
			
			Logger.debug("FS: Add inotify watch to the directory %s"%(share.directory))
			self.role_instance.wm.add_monitor_path(share.directory)
		
		if somethingWrong:
			for share_id in shares:
				try:
					share = self.role_instance.shares[share_id]
				except:
					continue
				
				share.del_user(user)
			u.destroy()
			
			return response
		
		Logger.debug("FS:request req_enable_user return success")
		return self.req_answer(document)
	
	
	
	def req_disable_user(self, request):
		try:
			document = minidom.parseString(request["data"])
			
			rootNode = document.documentElement
			if rootNode.nodeName != "session":
				raise Exception("invalid root node")
			
			user = rootNode.getAttribute("login")
			if len(user) == 0:
				raise Exception("empty parameters")
			
		except Exception, err:
			Logger.warn("Invalid xml input: "+str(err))
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "usage")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		
		u = User(user)
		if not u.existSomeWhere():
			Logger.warn("FS: Cannot disable unknown user %s"%(user))
			return self.user2xml(user, False)
		
		somethingWrong = False
		
		for share in self.role_instance.shares.values():
			if share.has_user(user):
				if not share.del_user(user):
					somethingWrong = True
				
				Logger.debug("FS: Remove inotify from the directory %s"%(share.directory))
				self.role_instance.wm.rm_monitor_path(share.directory)
		
		if not u.destroy():
			somethingWrong = True
		
		if somethingWrong:
			doc = Document()
			rootNode = doc.createElement('error')
			rootNode.setAttribute("id", "system_error")
			doc.appendChild(rootNode)
			return self.req_answer(doc)
		
		Logger.debug("FS:request req_disable_user return success")
		return self.req_answer(document)
