# Copyright (C) 2013-2014 Ulteo SAS
# http://www.ulteo.com
# Author David LECHEVALIER <david@ulteo.com> 2013
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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
import atexit
import glob
import subprocess

from ovd_shells.Shortcuts import Shortcuts as AbstractShortcuts

class Shortcuts(AbstractShortcuts):
	def __init__(self):
		self.installedShortcut = []
		atexit.register(self.cleanup)
	
	
	def synchronize(self, config, path):
		if config.desktop_icons:
			for p in glob.glob(os.path.join(path, "*")):
				shortcut = os.path.join(path, p)
				self.installedShortcut.append(shortcut)
				print "Shortcuts::install '%s'"%(shortcut)
				try:
					subprocess.Popen(["xdg-desktop-icon", "install", "--novendor", shortcut])
				except:
					print "Shortcuts::install '%s' failed"%(shortcut)
	
	
	def cleanup(self):
		for shortcut in self.installedShortcut:
			print "Shortcuts::uninstall '%s'"%(shortcut)
			try:
				subprocess.Popen(["xdg-desktop-icon", "uninstall", shortcut])
			except:
				print "Shortcuts::uninstall '%s' failed"%(shortcut)
