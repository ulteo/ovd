# -*- coding: utf-8 -*-

# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
# Author David LECHEVALIER <david@ulteo.com> 2010, 2012
# Author Laurent CLOUET <laurent@ulteo.com> 2010
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

import Langs
import Reg

class User(AbstractUser):
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
		if self.infos.has_key("locale"):
			userData['country_code'] =  Langs.getLCID(self.infos["locale"])

		try:
			win32net.NetUserAdd(None, 3, userData)
		except Exception, e:
			Logger.error("unable to create user: "+str(e))
			return False
		
		
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
		
		
		if self.infos.has_key("shell"):
			shell = "%s.exe"%(self.infos["shell"])
			shell_path = None
			
			try:
				shell_path = Util.get_from_PATH(shell)
			except Exception, e:
				Logger.error("unable to get path from '%s' [%s]"%(str(shell), str(e)))
			
			if shell_path is None:
				Logger.warn("'%s' can not be started"%(str(shell)))
			else:
				shell = shell_path

			try:
				win32ts.WTSSetUserConfig(None, self.name , win32ts.WTSUserConfigInitialProgram, shell)
				win32ts.WTSSetUserConfig(None, self.name , win32ts.WTSUserConfigfInheritInitialProgram, False)
			except Exception, e:
				Logger.error("Unable to configure user initial program [%s]"%(str(e)))
				return False
	
	
	def exists(self):
		users,_,_ = win32net.NetUserEnum(None, 0)
		#print users
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
			# Unload user reg
			win32api.RegUnLoadKey(win32con.HKEY_USERS, sid)
			win32api.RegUnLoadKey(win32con.HKEY_USERS, sid+'_Classes')
		except Exception, e:
			Logger.warn("Unable to unload user reg: %s"%(str(e)))
			return False
		
		return True
	
	
	def destroy(self):
		sid = self.getSid()
		if sid is None:
			return
		
		succefulDelete = False
		try:
			win32profile.DeleteProfile(sid)
			succefulDelete = True
		except:
			pass
		
		if not succefulDelete:
			if not self.unload(sid):
				Logger.error("Unable to unload User reg key for user %s"%(self.name))
				return False
			try:
				win32profile.DeleteProfile(sid)
				succefulDelete = True
			except Exception, e:
				Logger.warn("Unable to unload user reg: %s"%(str(e)))
				
				try:
					path = r"SOFTWARE\Microsoft\Windows NT\CurrentVersion\ProfileList\%s"%(sid)
					Reg.DeleteTree(win32con.HKEY_LOCAL_MACHINE, path)
				except Exception, err:
					Logger.warn("RegDeleteTree of %s return: %s"%(path, str(err)))
					return False
				
				# Todo: remove the directory
				#Platform.DeleteDirectory(userdir)
		
		try:
			win32net.NetUserDel(None, self.name)
		except Exception, err:
			Logger.error("Unable to delete user: %s"%(str(err)))
			return False
		
		return True
