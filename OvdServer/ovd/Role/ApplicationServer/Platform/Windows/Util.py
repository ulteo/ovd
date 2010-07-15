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
import win32file


def copyDirOverride(src, dst):
	if not os.path.isdir(src):
		return
	
	if not os.path.isdir(dst):
		os.makedirs(dst)
	
	try:
		attr = win32file.GetFileAttributes(src)
		win32file.SetFileAttributes(dst, attr)
	except:
		#print "Unable to setAttribute of",dst
		pass
	
	for f in os.listdir(src):
		path_src = os.path.join(src, f)
		path_dst = os.path.join(dst, f)
		
		if os.path.isdir(path_src):
			copyDirOverride(path_src, path_dst) 
		else:
			attr = None
			if os.path.exists(path_dst):
				attr = win32file.GetFileAttributes(path_dst)
				os.remove(path_dst)
			
			win32file.CopyFile(path_src, path_dst, False)
			try:
				if attr is not None:
					win32file.SetFileAttributes(path_dst, attr)
			except:
				#print "Unable to setAttribute of",path_dst
				pass
