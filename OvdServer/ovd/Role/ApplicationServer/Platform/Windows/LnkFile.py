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
import pythoncom
from win32com.shell import shell
import win32process

from Msi import Msi
import Util
from ovd.Logger import Logger

def clone(srcFile, dstFile, path, args):
	pythoncom.CoInitialize()
	
	shortcut_src = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
	shortcut_src.QueryInterface(pythoncom.IID_IPersistFile).Load(srcFile)
	
	iconLocation = shortcut_src.GetIconLocation()[0]
	if len(iconLocation) == 0:
		iconLocation = shortcut_src.GetPath(0)[0]
	
	workingDirectory = shortcut_src.GetWorkingDirectory()
	description = shortcut_src.GetDescription()
	
	
	shortcut_dst = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
	try:
		shortcut_dst.SetPath(path)
	except:
		Logger.warn("LnkFile::clone: unable to setPath. Check that the following command is available on the system: '%s'"%(path))
		return False
	
	shortcut_dst.SetArguments(args)
	shortcut_dst.SetIconLocation(iconLocation, 0)
	shortcut_dst.SetWorkingDirectory(workingDirectory)
	shortcut_dst.SetDescription(description)
	
	shortcut_dst.QueryInterface(pythoncom.IID_IPersistFile).Save(dstFile, 0)
	return True


def getTarget(filename):
	cmd = None
	try:
		msi = Msi()
		cmd = msi.getTargetFromShortcut(filename)
	except WindowsError,e:
		Logger.warn("LnkFile::getTarget: Unable to init Msi")
		msi = None
	
	if cmd is None:
		pythoncom.CoInitialize()
		
		shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
		try:
			shortcut.QueryInterface(pythoncom.IID_IPersistFile).Load(filename)
		except pythoncom.com_error, err:
			Logger.warn("LnkFile::getTarget: Unable to load file '%s': %s"%(filename, str(err)))
			return None
		
		command = shortcut.GetPath(0)[0]
		if not os.path.exists(command) and win32process.IsWow64Process():
			command = Util.clean_wow64_path(command)
		
		cmd = "%s %s"%(command, shortcut.GetArguments())
	
	return cmd
