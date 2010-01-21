# -*- coding: UTF-8 -*-

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

import ntsecuritycon
import os
import random
import win32api
import win32con
import win32net
import win32netcon
import win32profile
import win32security

from Logger import Logger
from SessionManagement import SessionManagement

class User:
	def __init__(self, login, password, dn, groups):
		self.login = login
		self.dn = dn
		self.exist = False
		self.groups = groups
		
		TRY = 0
		MAX = 3
		while(TRY<MAX):
			if not self.existing():
				break
			
			if SessionManagement.isMemberGroupOVD(self.login):
				Logger.debug("Old users still exist, will use it but not a normal behavior")
				break
				
			self.login = self.login+str(random.randint(0, 100))
			TRY+=1
		
		if TRY==MAX:
			raise Exception("Unable to create user '%s' %d/%d"%(self.login, TRY, MAX))
			
		if self.existing():
			Logger.debug("destroy existing")
			self.destroy()
		self.create(login, password)


	def existing(self):
		users,_,_ = win32net.NetUserEnum(None, 0)
		#print users
		for user in users:
			if user['name'] == self.login:
				return True
		return False
		

	def create(self, dn, password):
		userData = {}
		userData['name'] = self.login
		userData['full_name'] = self.dn
		userData['password'] = password
		userData['flags']  = win32netcon.UF_DONT_EXPIRE_PASSWD
		userData['flags'] |= win32netcon.UF_NORMAL_ACCOUNT
		userData['flags'] |= win32netcon.UF_PASSWD_CANT_CHANGE
		userData['flags'] |= win32netcon.UF_SCRIPT

		userData['priv'] = win32netcon.USER_PRIV_USER
		userData['primary_group_id'] = ntsecuritycon.DOMAIN_GROUP_RID_USERS
		#userData['password_expired'] = 0 #le mot de passe n'expire pas
		userData['acct_expires'] =  win32netcon.TIMEQ_FOREVER

		try:
			win32net.NetUserAdd(None, 3, userData)
		except Exception, e:
			Logger.error("unable to create user: "+str(e))
			raise e

		data = [ {'domainandname' : self.login} ]
		for group in self.groups:
			try:
				win32net.NetLocalGroupAddMembers(None, group, 3, data)
			except Exception, e:
				Logger.error("unable to add user %s to group '%s'"%(self.login, group))
				raise e
		# win32ts.WTSSetUserConfig("", username , win32ts.WTSUserConfigInitialProgram,home+"/system_state/logon.bat" )
		# win32ts.WTSSetUserConfig("", username , win32ts.WTSUserConfigfInheritInitialProgram,0 )
		
		
		

	def unload(self, sid):
		try:
			# Unload user reg
			win32api.RegUnLoadKey(win32con.HKEY_USERS, sid)
			win32api.RegUnLoadKey(win32con.HKEY_USERS, sid+'_Classes')
		except Exception, e:
			print "Unable to unload user reg: ",str(e)
			return False


	def destroy(self):
		#get the sid
		try:
			sid, _, _ = win32security.LookupAccountName(None, self.login)
			sid = win32security.ConvertSidToStringSid(sid)
		except Exception,e:
			print "Unable to get SID",str(e)
			return False
		
		succefulDelete = False
		try:
			win32profile.DeleteProfile(sid)
			succefulDelete = True
		except:
			pass
		
		if not succefulDelete:
			if not self.unload(sid):
				Logger.error("Unable to unload User reg key")
				return False
			try:
				win32profile.DeleteProfile(sid)
				succefulDelete = True
			except Exception, e:
				print "Unable to unload user reg: ",str(e)
				raise e	

		try:
			win32net.NetUserDel(None, self.login)
			# toDo clean: HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows NT\CurrentVersion\ProfileList\DefaultUserProfile = Default User
		except Exception, e:
			Logger.error("Unable to delete user:",str(e))
			raise e
		
		return True
