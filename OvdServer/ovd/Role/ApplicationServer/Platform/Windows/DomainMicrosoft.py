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

from ovd.Logger import Logger
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
		
		self.session.set_user_profile_directories(mylock.userprofile, mylock.userDir["AppData"])
		
		self.session.init_user_session_dir(os.path.join(mylock.userDir["AppData"], "ulteo", "ovd"))
		
		self.session.windowsProgramsDir = mylock.userDir["Programs"]
		self.session.windowsDesktopDir = mylock.userDir["Desktop"]
		self.session.install_desktop_shortcuts()
		
		self.session.succefully_initialized = True
		return mylock.unlock()
	
	
	def doCustomizeRegistry(self, hive):
		return True

	def onSessionEnd(self):
		for shortcut in self.session.installedShortcut:
			desktopShortcut = os.path.join(self.session.windowsDesktopDir, shortcut)
			programShortcut = os.path.join(self.session.windowsProgramsDir, shortcut)
			if os.path.exists(desktopShortcut):
				try:
					os.remove(desktopShortcut)
				except Exception, e:
					Logger.debug("Error while deleting the file %s [%s]"%(desktopShortcut), str(e))
			
			if os.path.exists(programShortcut):
				try:
					os.remove(programShortcut)
				except:
					Logger.debug("Error while deleting the file %s [%s]"%(programShortcut), str(e))

		return True

