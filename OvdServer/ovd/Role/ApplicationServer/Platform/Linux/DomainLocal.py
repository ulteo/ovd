# -*- coding: UTF-8 -*-

# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author David LECHEVALIER <david@ulteo.com> 2013
# Author Julien LANGLOIS <julien@ulteo.com> 2013
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
from ovd.Role.ApplicationServer.DomainLocal import DomainLocal as AbstractDomainLocal
from ovd.Role.ApplicationServer.Config import Config


class DomainLocal(AbstractDomainLocal):
	ovd_session_required_groups = ["video", "audio", "pulse", "pulse-access", "fuse", "tsusers"]
	
	def __init__(self):
		AbstractDomainLocal.__init__(self)
		self.groups_before_ovd_session = []
	
	
	def manage_user(self):
		return False
	
	
	def onSessionCreate(self):
		if not self.session.user.exists():
			Logger.error("Unable to create session for user '%s': user doesn't not exists and use local users ..."%(self.session.user.name))
			return False
		
		self.groups_before_ovd_session = self.session.user.get_groups()
		if self.groups_before_ovd_session is None:
			return False
		
		groups = set(self.ovd_session_required_groups + self.groups_before_ovd_session)
		if not self.session.user.set_groups(groups):
			Logger.error("Failed to customize groups of the user %s"%(self.session.user.name))
			return False
		
		if self.force_local_password:
			if Config.override_password_method is Config.OVERRIDE_PASSWORD_METHOD_CUSTOM:
				ret = self.session.user.set_custom_password()
			else:
				ret = self.session.user.set_password()
			
			if not ret:
				return False
		
		if not self.session.install_client():
			return False
		
		return True
	
	
	def onSessionStarts(self):
		return True
	
	
	def onSessionEnd(self):
		if not self.session.user.set_groups(self.groups_before_ovd_session):
			Logger.error("Failed to restore groups of the user %s"%(username))
		
		if self.force_local_password:
			if Config.override_password_method is Config.OVERRIDE_PASSWORD_METHOD_CUSTOM:
				ret = self.session.user.disable_custom_password()
			else:
				ret = self.session.user.disable_password()
			
			if not ret:
				return False
		
		return True
