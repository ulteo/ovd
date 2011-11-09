# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author David LECHEVALIER <david@ulteo.com> 2011
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

from ovd_shells.Drives import Drives as AbstractDrives


class Drives(AbstractDrives):
	mtabPath = "/etc/mtab"
	acceptedType = ["cifs", "nfs"]
	
	
	@staticmethod
	def getDrivesList():
		mtab = []
		userID = os.getuid()
		
		f = open(Drives.mtabPath, 'r')
		for l in f.readlines():
			cols = l.split(" ")
			if cols[2] not in Drives.acceptedType:
				continue
			
			try:
				fs_info = os.stat(cols[1])
			except OSError, e:
				print >> sys.stderr,  "Invalid entry in the mtab: %s (%s)"%(cols[1], str(e))
				continue
			
			if fs_info.st_uid == userID:
				mtab.append(cols[1])
		return mtab


