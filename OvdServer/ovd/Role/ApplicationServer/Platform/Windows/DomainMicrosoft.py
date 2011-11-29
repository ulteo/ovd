# -*- coding: UTF-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author David LECHEVALIER <david@ulteo.com> 2011
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
from ApplicationsDetection import ApplicationsDetection


class DomainMicrosoft(AbstractDomainMicrosoft):
	# This function, in the future, will replace the function located in the Session class
	def cleanupShortcut(self, path):
		shortcut_ext = ApplicationsDetection.shortcut_ext
		
		if not os.path.exists(path):
			return
		
		try:
			contents = os.listdir(path)
		except Exception, err:
			Logger.warn("Unable to list content of the directory %s (%s)"%(path, str(err)))
			return
		
		for content in contents:
			target = None
			l = os.path.join(path, content)
			if not os.path.isfile(l):
				continue
			
			if not os.path.splitext(l)[1] == shortcut_ext:
				continue
			
			try:
				target = ApplicationsDetection.getExec(l)
			except Exception, e:
				Logger.debug("Unable to get the desktop target of %s %s"%(l, str(e)))
				target = None
			
			if target is None:
				continue
			if "startovdapp" in target:
				Logger.debug("removing shortcut %s"%(l))
				try:
					os.remove(l)
				except Exception, e:
					Logger.debug("Unable to delete the desktop target %s %s"%(l, str(e)))


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
		self.cleanupShortcut(self.session.windowsProgramsDir)
		self.cleanupShortcut(self.session.windowsDesktopDir)
		
		self.session.install_desktop_shortcuts()
		
		self.session.succefully_initialized = True
		return mylock.unlock()
	
	
	def doCustomizeRegistry(self, hive):
		return True

	def onSessionEnd(self):
		return True

