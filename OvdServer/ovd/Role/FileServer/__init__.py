# -*- coding: utf-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
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

import time
from xml.dom.minidom import Document

from ovd.Role import AbstractRole
from ovd.Config import Config
from ovd.Logger import Logger
from ovd.Platform import Platform

from Dialog import Dialog


class FileServer(AbstractRole):
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		self.dialog = Dialog(self)
		self.has_run = False
	
	def init(self):
		Logger.info("FileServer init")
		
		return True
	
	@staticmethod
	def getName():
		return "FileServer"
	
	
	def stop(self):
		pass
	
	
	def run(self):
		self.has_run = True
		while 1:
			time.sleep(30)
			Logger.debug("FileServer run loop")
