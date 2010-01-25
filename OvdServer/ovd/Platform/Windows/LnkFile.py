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

import pythoncom
from win32com.shell import shell

from Msi import Msi

def clone(srcFile, dstFile, path, args):
	pythoncom.CoInitialize()
	
	shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
	shortcut.QueryInterface(pythoncom.IID_IPersistFile).Load(srcFile)
	
	icon = shortcut.GetIconLocation()[0]
	if len(icon) == 0:
		icon = shortcut.GetPath(0)[0]
	
	shortcut.SetPath(path)
	shortcut.SetArguments(args)
	shortcut.SetIconLocation(icon, 0)
	
	shortcut.QueryInterface(pythoncom.IID_IPersistFile).Save(dstFile, 0)


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
		shortcut.QueryInterface(pythoncom.IID_IPersistFile).Load(filename)
		
		cmd = "%s %s"%(shortcut.GetPath(0)[0], shortcut.GetArguments())
	
	return cmd
