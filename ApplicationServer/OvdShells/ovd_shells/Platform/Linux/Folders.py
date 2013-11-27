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

from ovd_shells.Folders import Folders as AbstractFolders

class Folders(AbstractFolders):
	def __init__(self):
		self.shares = {}
	
	
	def registerShares(self):
		if not os.environ.has_key("OVD_SESSION_DIR"):
			return
		
		shares_def_dir = os.path.join(os.environ["OVD_SESSION_DIR"], "shares")
		if not os.path.isdir(shares_def_dir):
			return
		
		for path in glob.glob(os.path.join(shares_def_dir, "*")):
			name = os.path.basename(path)
			f = file(path, "r")
			content = f.readline().strip()
			f.close()
			
			if not os.path.exists(content):
				continue
			
			self.shares[name] = content
	
	
	def getPathFromID(self, id_):
		if not self.shares.has_key(id_):
			return None
		
		return self.shares[id_]
