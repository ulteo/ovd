# -*- coding: utf-8 -*-

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
import win32api
import win32con
import zipfile

def DeleteR(path):
	if os.path.isdir(path):
		for file in os.listdir(path):
			filename = os.path.join(path, file)
			win32api.SetFileAttributes(filename, win32con.FILE_ATTRIBUTE_NORMAL)
			if not DeleteR(filename):
				return False
			
		win32api.SetFileAttributes(path, win32con.FILE_ATTRIBUTE_NORMAL)
		try:
			os.rmdir(path)
		except:
			return False
	
	else:
		try:
			os.remove(path)
		except:
			return False
	
	return True


def zip(filename, dirname, base):
	def _zip(f, d, base):
		for name in os.listdir(d):
			path = os.path.join(d, name)
			
			#print " push",path
			if os.path.isfile(path):
				f.write(path, os.path.join(base, name), zipfile.ZIP_DEFLATED)
			else:
				_zip(f, path, os.path.join(base, name))
	
	
	f = zipfile.ZipFile(filename, "w")
	_zip(f, dirname, base)
	f.close()


def unzip(filename, dirname, level=0):
	zfobj = zipfile.ZipFile(filename)

	for name in zfobj.namelist():
		if name.endswith('/'):
			continue
		
		if level is not 0:
			buf = name.split("/", level)
			final_name = buf[-1]
		else:
			final_name = name
		
		d = os.path.join(dirname, os.path.dirname(final_name))
		if not os.path.isdir(d):
			#print "create",d
			os.makedirs(d)
		
		outfile = file(os.path.join(dirname, final_name), 'wb')
		outfile.write(zfobj.read(name))
		outfile.close()
	
	zfobj.close()
