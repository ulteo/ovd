# -*- coding: utf-8 -*-

# Copyright (C) 2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2012
# Author David LECHEVALIER <david@ulteo.com> 2012
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

from ovd.LoggerHook import LoggerHook
from ovd.Platform.System import System

class SessionLogger(LoggerHook):
	def __init__(self):
		self.log = []
	
	
	def add(self, level, message):
		log = "%s [%s] %s"%(time.strftime("%Y-%m-%d %H:%M:%S"), level, message)
		self.log.append(System.local_encode(log))
	
	def get_full_log(self):
		return "\n".join(self.log)
	
	
	def info(self, message):
		self.add("info", message)
	
	
	def warn(self, message):
		self.add("warn", message)
	
	
	def error(self, message):
		self.add("error", message)
	
	
	def debug(self, message):
		self.add("debug", message)
