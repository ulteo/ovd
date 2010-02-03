# -*- coding: utf-8 -*-

# Copyright (C) 2009,2010 Ulteo SAS
# http://www.ulteo.com
# Author Gauvain POCENTEK <gauvain@ulteo.com>
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
# Author Laurent CLOUET <laurent@ulteo.com> 2010
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

from Platform import _platform as Platform

class Application:
	def __init__(self, id_, args_):
		self.id = id_
		self.args = args_
		
		self.basedir = None
		if os.environ.has_key("OVD_SESSION_DIR"):
			self.basedir = os.environ["OVD_SESSION_DIR"]
	
	
	def isAvailable(self):
		if self.basedir is None:
			print "basedir none"
			return False
		
		if not os.path.isfile(os.path.join(self.basedir, "matching", str(self.id))):
			print "not file"
			return False
		
		return True
	
	
	def getBaseCommand(self):
		filename = os.path.join(self.basedir, "matching", str(self.id))
		
		f = file(filename, "r")
		line = f.readline()
		f.close()
		
		return line
	
	def getFinalCommand(self):
		cmd = self.getBaseCommand()
		
		return Platform.transformCommand(cmd, self.args)

