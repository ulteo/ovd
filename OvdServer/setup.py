#!/usr/bin/python
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

import sys
from distutils.core import setup,Extension

import py2exe
sys.argv.append("py2exe")

try:
	import modulefinder
	import win32com,sys
	for p in win32com.__path__[1:]:
		modulefinder.AddPackagePath("win32com", p)
	for extra in ["win32com.shell","win32com.mapi"]:
		__import__(extra)
		m = sys.modules[extra]
		for p in m.__path__[1:]:
			modulefinder.AddPackagePath(extra, p)
except ImportError:
	# no build path setup, no worries.
	pass


setup(
	zipfile = "lib/shared.zip",

	console = ["simpleServer.py"],
	)
