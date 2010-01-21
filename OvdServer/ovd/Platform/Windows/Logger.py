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

import servicemanager

import ovd

class Logger(ovd.Logger):
	def __init__(self, name, loglevel, file = None, stdout=False, win32LogService=False):
		ovd.Logger.__init__(self, name, loglevel, file, stdout)
		self.win32LogService = False
		
		if win32LogService is True:
			self.win32LogService = True
	
	def log_info(self, message):
		if ovd.Logger.log_info(self, message) is False:
			return False

		if self.win32LogService:
			servicemanager.LogInfoMsg(message)

	
	def log_warn(self, message):
		if ovd.Logger.log_warn(self, message) is False:
			return False

		if self.win32LogService:
			servicemanager.LogWarningMsg(message)
	
	def log_error(self, message):
		if ovd.Logger.log_error(self, message) is False:
			return False
		
		if self.win32LogService:
			servicemanager.LogErrorMsg(message)

	# Static methods

	@staticmethod 
	def initialize(name, loglevel, file=None, stdout=False, win32LogService=False):
		instance = Logger(name, loglevel, file, stdout, win32LogService)
		Logger._instance = instance
	
