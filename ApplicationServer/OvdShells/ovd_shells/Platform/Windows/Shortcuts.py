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
import win32api
import pythoncom
import win32com.client
from win32com.shell import shell, shellcon
import win32file
import threading
import time
import pywintypes
import os
from SessionEndHandler import SessionEndHandler

from ovd_shells.Shortcuts import Shortcuts as AbstractShortcuts
import _platform as Platform

class Shortcuts(AbstractShortcuts):
	def __init__(self):
		self.windowsDesktopDir = shell.SHGetFolderPath(0, shellcon.CSIDL_DESKTOPDIRECTORY, 0, 0)
		self.windowsProgramsDir = shell.SHGetFolderPath(0, shellcon.CSIDL_PROGRAMS, 0, 0)
		self.installedShortcut = []
	
	
	def synchronize(self, path):
		if Platform.getVersion() > 6.1:
			self.defaultProgram = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_PROGRAMS, 0, 0)
			desktopLNKFile = os.path.join(self.windowsProgramsDir, "desktop.lnk")
			
			try:
				if not os.path.exists(desktopLNKFile):
					win32file.CopyFile(os.path.join(self.defaultProgram,"desktop.lnk"), desktopLNKFile, True)
			except Exception, e:
				print "Failed to copy file: ", str(e)
			
			threading.Thread(target=self.server2012Integration, args=(path,)).start()
			for p in glob.glob(os.path.join(path, "*")):
				self.installedShortcut.append(p)
				self.installToDesktop(os.path.join(path, p))
			
			self.handler = SessionEndHandler()
			self.handler.register(self.cleanup)
			self.handler.start()
			return

		for p in glob.glob(os.path.join(path, "*")):
			self.installToDesktop(os.path.join(path, p))
			self.installToStartMenu(os.path.join(path, p))
	
	
	def installToStartMenu(self, shortcut):
		shortcut = Platform.toUnicode(shortcut)
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
	
	
	def installToDesktop(self, shortcut):
		shortcut = Platform.toUnicode(shortcut)
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
	
	
	def server2012Integration(self, path):
		time.sleep(2)
		pythoncom.CoInitialize()
			
		for p in glob.glob(os.path.join(path, "*")):
			self.pinToStart(os.path.join(path, p))
		
	
	def pinToStart(self, lnkPath):
		sh = win32com.client.Dispatch('Shell.Application')
		sh.ShellExecute(lnkPath, None, None, "pintostartscreen", 10)
	
	def cleanup(self):
		for s in self.installedShortcut:
			dstFile = os.path.join(self.windowsProgramsDir, os.path.basename(s))
			if os.path.exists(dstFile):
				try:
					os.remove(dstFile)
				except Exception, e:
					print "Failed to remote %s: %s"%(dstFile, str(e))
