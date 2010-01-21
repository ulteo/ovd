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


def findProcessWithEnviron(pattern):
	uid = os.getuid()
	pid = os.getpid()

	files = glob.glob("/proc/[0-9]*")
	for f in files:
		if not os.path.isdir(f):
			continue
		
		if not os.stat(f)[4] == uid:
			continue
		
		this_pid = int(os.path.basename(f))
	
		if this_pid == pid:
			continue
	
		try:
			f_ = file(os.path.join(f, 'environ'), 'r')
			buffer = f_.read()
			f_.close()
		except IOError, err:
			continue
	
		if not pattern in buffer:
			continue
		
		return this_pid
	
	return None


def startDesktop():
	os.system("startovd")
