# -*- coding: utf-8 -*-

# Copyright (C) 2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2012
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
import sys
if sys.platform.startswith("linux"):
	import xdg.DesktopEntry

from Module import Module

class CheckWorkingDir(Module):
	"""
	The current Xfce version delivered in the Ulteo OVD chroot doesn't 
	support "Path" entrie in desktop files. So we have to force the behavior.
	"""
	
	def beforeStartApp(self):
		if not sys.platform.startswith("linux"):
			return
		
		wd = self.findDesktopFileWorkingDir()
		if wd is None:
			return
		
		if os.path.samefile(os.path.curdir, wd):
			return
		
		os.chdir(wd)
		
		
	def findDesktopFileWorkingDir(self):
		if not os.environ.has_key('OVD_APPS_DIR'):
			return None
		
		filename = os.path.join(os.environ['OVD_APPS_DIR'], "%d.desktop"%(self.application.id))
		
		try:
			entrie = xdg.DesktopEntry.DesktopEntry(filename)
		except:
			return None
		
		e = entrie.getPath()
		if len(e) == 0:
			return None
		
		return e
