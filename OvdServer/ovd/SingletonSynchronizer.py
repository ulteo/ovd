# -*- coding: UTF-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

from Config import Config
from Logger import Logger

class SingletonSynchronizer:
	
	def __init__(self):
		self.config = {}
		self.roleConfig = {}
		
	def backup(self):
		for k in Config.__dict__.keys():
			self.config[k] = Config.__dict__[k]
		
		for role in Config.roles:
			RoleConfig = __import__("ovd.Role.%s.Config"%(role), {}, {}, "Config")

			self.roleConfig[role] = {}
			for k in RoleConfig.Config.__dict__.keys():
				self.roleConfig[role][k] = RoleConfig.Config.__dict__[k]
	
	def restore(self):
		for k in self.config.keys():
			Config.__dict__[k] = self.config[k]
		
		for role in self.roleConfig.keys():
			RoleConfig = __import__("ovd.Role.%s.Config"%(role), {}, {}, "Config")
			for k in self.roleConfig[role].keys():
				RoleConfig.Config.__dict__[k] = self.roleConfig[role][k]
		
		Logger.initialize("OVD", Config.log_level)
