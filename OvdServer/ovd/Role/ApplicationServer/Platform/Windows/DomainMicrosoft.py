# -*- coding: UTF-8 -*-

# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author David LECHEVALIER <david@ulteo.com> 2011, 2013
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
import time
import win32api

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.DomainMicrosoft import DomainMicrosoft as AbstractDomainMicrosoft
from ApplicationsDetection import ApplicationsDetection


class DomainMicrosoft(AbstractDomainMicrosoft):
	def getUsername(self):
		username = self.session.user.name
		
		if '@' in self.session.user.name:
			username = self.session.user.name.split('@')[0]
		
		return username
	
	
	def onSessionCreate(self):
		self.session.user.name = self.getUsername()
		self.session.init_user_session_dir(os.path.join(self.session.SPOOL_USER, self.session.user.name))
		self.session.succefully_initialized = True
		self.session.install_desktop_shortcuts()
		return True
	
	
	def doCustomizeRegistry(self, hive):
		return True
	
	
	def onSessionEnd(self):
		return True
