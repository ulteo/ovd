# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import ntsecuritycon
import sys
import win32api
import win32con
import win32net
import win32netcon
import win32profile
import win32security
import win32ts

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.User import User as AbstractUser
import Util

class User:
	
	def __init__(self, name_, infos_ = {}):
		
		self.name = name_
		self.infos = infos_
		self.home = None
		self.created = False
	
	
	def create(self):
		
		userData = {}
		userData['name'] = self.name
		
		if self.infos.has_key("displayName"):
			userData['full_name'] = self.infos["displayName"]
			
		if self.infos.has_key("password"):
			userData['password'] = self.infos["password"]
		
		userData['flags']  = win32netcon.UF_DONT_EXPIRE_PASSWD
		userData['flags'] |= win32netcon.UF_NORMAL_ACCOUNT
		userData['flags'] |= win32netcon.UF_PASSWD_CANT_CHANGE
		userData['flags'] |= win32netcon.UF_SCRIPT
		
		userData['priv'] = win32netcon.USER_PRIV_USER
		userData['primary_group_id'] = ntsecuritycon.DOMAIN_GROUP_RID_USERS
		userData['password_expired'] = 0 # password never expire
		userData['acct_expires'] =  win32netcon.TIMEQ_FOREVER
		
		try:
			win32net.NetUserAdd(None, 3, userData)
		except Exception, e:
			Logger.error("unable to create user: "+str(e))
			raise e
		
		self.post_create()
		
		return True
		
		
	def post_create(self):
		data = [ {'domainandname' : self.name} ]
		
		if self.infos.has_key("groups"):
			for group in  self.infos["groups"]:
				try:
					win32net.NetLocalGroupAddMembers(None, group, 3, data)
				except Exception, e:
					Logger.error("unable to add user %s to group '%s'"%(self.name, group))
					return False
			
		#Ajout du nouvel utilisateur au groupe des remote desktop users
		win32net.NetLocalGroupAddMembers(None, "Remote Desktop Users", 3, data)
		
		
	def exists(self):
		users,_,_ = win32net.NetUserEnum(None, 0)
		for user in users:
			if user['name'] == self.name:
				return True
		return False
		
		
	def getSid(self):
		#get the sid
		try:
			sid, _, _ = win32security.LookupAccountName(None, self.name)
			sid = win32security.ConvertSidToStringSid(sid)
		except Exception,e:
			Logger.warn("Unable to get SID: %s"%(str(e)))
			return None
		
		return sid
		
		
	def unload(self, sid):
		try:
			#Unload user reg
			win32api.RegUnLoadKey(win32con.HKEY_USERS, sid)
			win32api.RegUnLoadKey(win32con.HKEY_USERS, sid+'_Classes')
		except Exception, e:
			Logger.warn("Unable to unload user reg: %s"%(str(e)))
			return False
		
		return True
		
		
	def get_home(self):
		return self.home
