# -*- coding: UTF-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author David LECHEVALIER <david@ulteo.com> 2010
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

import ctypes
import os
import win32file

from ovd.Platform.System import System


FILE_ATTRIBUTE_SYSTEM =        0x4    #system file
FILE_ATTRIBUTE_REPARSE_POINT = 0x400  #symbolic link

def copyDirOverride(src, dst, exception=None):
	src = System.local_encode(src)
	dst = System.local_encode(dst)
	if not os.path.isdir(src):
		return
	
	if exception is None:
		exception = []
	
	try:
		attr = win32file.GetFileAttributes(src)
		if attr & FILE_ATTRIBUTE_SYSTEM and attr & FILE_ATTRIBUTE_REPARSE_POINT:
			return 
		win32file.SetFileAttributes(dst, attr)
	except:
		#print "Unable to setAttribute of",dst
		pass
	
	if not os.path.isdir(dst):
		os.makedirs(dst)

	dirs = None
	try:
		dirs = os.listdir(src)
	except Exception, err:
		return
	for f in dirs:
		if f in exception:
			continue
		path_src = os.path.join(src, f)
		path_dst = os.path.join(dst, f)
		
		if os.path.isdir(path_src):
			copyDirOverride(path_src, path_dst, exception) 
		else:
			if f.startswith("UsrClass.dat"):
				continue
			attr = None
			if os.path.exists(path_dst):
				attr = win32file.GetFileAttributes(path_dst)
				os.remove(path_dst)
			
			try:
				win32file.CopyFile(path_src, path_dst, False)
			except:
				pass
			try:
				if attr is not None:
					win32file.SetFileAttributes(path_dst, attr)
			except:
				#print "Unable to setAttribute of",path_dst
				pass


def get_from_PATH(name):
	try:
		env_var = os.environ["PATH"]
	except KeyError:
		return None

	for p in env_var.split(os.pathsep):
		path = os.path.join(p, name)
		if os.path.exists(path):
			return path
	
	return None


def Wow64DisableWow64FsRedirection():
	k32 = ctypes.windll.kernel32
	wow64 = ctypes.c_long(0)
	k32.Wow64DisableWow64FsRedirection(ctypes.byref(wow64))
	
	# The win32file package is supposed to have a Wow64DisableWow64FsRedirection function according 
	# to the documentation http://docs.activestate.com/activepython/2.7/pywin32/win32file__Wow64DisableWow64FsRedirection_meth.html
	# But when loading the package, the function does not exists
	
	return wow64


def Revert64DisableWow64FsRedirection(value_):
	k32 = ctypes.windll.kernel32
	k32.Wow64RevertWow64FsRedirection(value_)


def clean_wow64_path(path):
	if os.environ.has_key("PROGRAMW6432") and path.startswith(os.environ["PROGRAMFILES"]):
		return path.replace(os.environ["PROGRAMFILES"], os.environ["PROGRAMW6432"])
	
	if os.environ.has_key("COMMONPROGRAMW6432") and path.startswith(os.environ["COMMONPROGRAMFILES"]):
		return path.replace(os.environ["COMMONPROGRAMFILES"], os.environ["COMMONPROGRAMW6432"])
	
	return path
