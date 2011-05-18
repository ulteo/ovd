# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

from ovd.Logger import Logger
from ovd.Role.Role import Role as AbstractRole

class Role(AbstractRole):
	@staticmethod
	def getName():
		return "Dummy"
	
	
	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		Logger.info("Dummy role::__init__")
		self.loop = True
	
	
	def init(self):
		Logger.info("Dummy role::init")
		return True
	
	
	def stop(self):
		Logger.info("Dummy role::stop")
		self.loop = False
	
	
	def run(self):
		self.status = Role.STATUS_RUNNING
		Logger.info("Dummy role::run begin")
		while self.loop:
			Logger.info("Dummy role::run loop")
			time.sleep(2)
		
		Logger.info("Dummy role::run end")
		self.status = Role.STATUS_STOP
	
	
	def finalize(self):
		Logger.info("Dummy role::finalize")
	
	
	def getReporting(self, node):
		Logger.info("Dummy role::getReporting")
