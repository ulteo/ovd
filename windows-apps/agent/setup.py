#!/usr/bin/python
# -*- coding: UTF-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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

from distutils.core import setup,Extension
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

import py2exe,glob
sys.argv.append("py2exe")

class Target:
	def __init__(self, **kw):
		self.__dict__.update(kw)
		self.version = "2.5"
		self.company_name = "Ulteo"
		self.copyright = "GPL"
		self.name = "OVD Agent"

# a NT service, modules is required
myservice = Target(
	# used for the versioninfo resource
	description = "Ulteo Open Virtual Desktop agent",
	# what to build.  For a service, the module name (not the filename) must be specified!
	modules = ["OVD"]
	)

excludes = ["pywin", "pywin.debugger", "pywin.debugger.dbgcon",
			"pywin.dialogs", "pywin.dialogs.list"]

setup(
	options = {"py2exe": {"typelibs":
						# typelib for WMI
						[('{565783C6-CB41-11D1-8B02-00600806D9B6}', 0, 1, 2)],
						# create a compressed zip archive
						#"compressed": 1,
						#"optimize": 2,
						"excludes": excludes}},
	# The lib directory contains everything except the executables and the python dll.
	# Can include a subdirectory name.
	zipfile = "lib/shared.zip",

	service = [myservice],
	data_files=[(".", ["icon.png"])],
	scripts = ['communication.py', 'mime.py', 'Msi.py', 'OVD.py', 'sessionmanager.py', 'utils.py']
	)
