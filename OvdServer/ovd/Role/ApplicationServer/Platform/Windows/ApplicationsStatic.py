# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
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
import pythoncom
import re
from win32com.shell import shell

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.ApplicationsStatic import ApplicationsStatic as AbstractApplicationsStatic

from ApplicationsDetection import ApplicationsDetection


class ApplicationsStatic(AbstractApplicationsStatic):
	@staticmethod
	def getFilesExtensions():
		return ["lnk", "ico"]
	
	
	def getApplicationPath(self, id_):
		return os.path.join(self.spool, id_+".lnk")
	
	
	def createShortcut(self, application_):
		png_file = os.path.join(self.spool, application_["id"]+".png")
		ico_file = os.path.join(self.spool, application_["id"]+".ico")
		
		cmd = """"%s" "%s" "%s" """%("png2ico.exe", ico_file, png_file)
		
		status = ApplicationsDetection.execute(cmd, True)
		if status != 0:
			Logger.warn("createShortcut following command returned %d: %s"%(status, cmd))
			if os.path.exists(ico_file):
			  os.remove(ico_file)
			
			return False
		
		if not os.path.exists(ico_file):
			Logger.warn("createShortcut: No ico file returned")
			return False
		
		(executable, arguments) = self.extract_command(application_["command"])
		
		pythoncom.CoInitialize()
		
		shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
		try:
			shortcut.SetPath(executable)
		except:
			Logger.warn("Unable to shortcut SetPath. Check if the following command is available on the system: '%s'"%(executable))
			return False
		
		if arguments is not None:
			try:
				shortcut.SetArguments(arguments)
			except:
				Logger.warn("Unable to shortcut SetArguments ('%s')"%(arguments))
				return False
		
		shortcut.SetIconLocation(ico_file, 0)
		#shortcut.SetWorkingDirectory(workingDirectory)
		shortcut.SetDescription(application_["description"])
		
		shortcut.QueryInterface(pythoncom.IID_IPersistFile).Save(os.path.join(self.spool, application_["id"]+".lnk"), 0)
		return True
	
	
	@staticmethod
	def extract_command(command):
		l = [p for p in re.split("( |\\\".*?\\\"|'.*?')", command) if p.strip()]
		
		path = l[0]
		if len(command)==1:
			arguments = None
		else:
			arguments = " ".join(l[1:])
		
		if path[0] in ['"',"'"] and path[0] == path[-1]:
			path = path[1:-1]
		
		return (path, arguments)
