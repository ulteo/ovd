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

import glob
import os
import sys

from ovd_shells.Platform import _platform as Platform
from ovd_shells.Platform.Novell import Novell

def redirect_to_dump():
	path = os.path.join(Platform.getUserSessionDir(), "dump.txt")
	try:
		dirname = os.path.dirname(path)
		if not os.path.exists(dirname):
			os.makedirs(dirname)
		
		buf = file(path, "a", 0)
	except IOError, err:
		return
	
	sys.stderr = buf
	sys.stdout = buf
	print "#################################################"


def loadUserEnv(d):
	path = os.path.join(d, "env")
	try:
		f = file(path, "r")
	except:
		return
	
	lines = f.readlines()
	f.close()
	
	for line in lines:
		line = line.strip()
		try:
			key,value = line.split("=", 1)
		except:
			continue
		
		os.environ[key] = value

def manageAutoStartApplication(d):
	for path in glob.glob(os.path.join(d, "to_start", "*")):
		f = file(path, "r")
		lines = f.readlines()
		f.close()
		
		cmd = "startovdapp %s"%(lines[0].strip())
		if len(lines) > 1:
			cmd+= ' "%s"'%(lines[1].strip())
		
		Platform.launch(cmd)

def startModules():
	novell = Novell()
	novell.perform()
