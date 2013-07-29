# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author David LECHEVALIER <david@ulteo.com> 2013
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

import glob
from win32com.shell import shell, shellcon
import win32file
import pywintypes
import os

from ovd_shells.Shortcuts import Shortcuts as AbstractShortcuts
import _platform as Platform

class Shortcuts(AbstractShortcuts):
	def __init__(self, profile):
		self.windowsDesktopDir = shell.SHGetFolderPath(0, shellcon.CSIDL_DESKTOPDIRECTORY, 0, 0)
		self.windowsProgramsDir = shell.SHGetFolderPath(0, shellcon.CSIDL_PROGRAMS, 0, 0)
		if profile is not None and profile.has_key("local_path"):
			self.windowsDesktopDir = os.path.join(profile["local_path"], "Data", "Desktop")
	
	
	def synchronize(self, path):
		for p in glob.glob(os.path.join(path, "*")):
			self.install(os.path.join(path, p))
	
	
	def install(self, shortcut):
		dstFile = os.path.join(self.windowsProgramsDir, os.path.basename(shortcut))
		if os.path.exists(dstFile):
			os.remove(dstFile)
		
		try:
			win32file.CopyFile(shortcut, dstFile, True)
			Platform.deleteOnclose(dstFile)
		except pywintypes.error, err:
			if err[0] == 5: # Access is denied
				print "Session::Windows::install_shortcut Access is denied on copy of '%s' to '%s'"%(shortcut, dstFile)
			else:
				# other error
				print "Session::Windows::install_shortcut error on copy of '%s' to '%s', wintypes error %s"%(shortcut, dstFile, err[0])
		
		if not os.path.exists(self.windowsDesktopDir):
			os.makedirs(self.windowsDesktopDir)
			
		dstFile = os.path.join(self.windowsDesktopDir, os.path.basename(shortcut))
		if os.path.exists(dstFile):
			os.remove(dstFile)
		try:
			win32file.CopyFile(shortcut, dstFile, True)
			Platform.deleteOnclose(dstFile)
		except pywintypes.error, err:
			if err[0] == 5: # Access is denied
				print "Session::Windows::install_shortcut Access is denied on copy of '%s' to '%s'"%(shortcut, dstFile)
				return
			# other error
			print "Session::Windows::install_shortcut error on copy of '%s' to '%s', wintypes error %s"%(shortcut, dstFile, err[0])
			return
