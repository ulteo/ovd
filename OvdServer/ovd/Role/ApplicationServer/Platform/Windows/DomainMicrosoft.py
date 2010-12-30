# -*- coding: UTF-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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

from Waiter import Waiter

from ovd.Role.ApplicationServer.DomainMicrosoft import DomainMicrosoft as AbstractDomainMicrosoft


class DomainMicrosoft(AbstractDomainMicrosoft):
	def onSessionStarts(self):
		mylock = Waiter(self.session)
		
		t0 = time.time()
		while mylock.init() is False:
			d = time.time() - t0
			if d>20:
				return False
			
			time.sleep(0.5)
		
		self.session.set_user_profile_directories(mylock.userprofile, mylock.appdata)
		
		self.session.init_user_session_dir(os.path.join(mylock.appdata, "ulteo", "ovd"))
		
		return mylock.unlock()
	
	
	def doCustomizeRegistry(self, hive):
		return True
