# -*- coding: utf-8 -*-

# Copyright (C) 2009 - 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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

from threading import Thread
from ovd.Logger import Logger


class Role:
	STATUS_INIT    = 0
	STATUS_RUNNING = 1
	STATUS_STOP    = 2
	STATUS_ERROR   = 3
	STATUS_STOPPING= 4
	
	def __init__(self, main_instance):
		self.main_instance = main_instance
		self.status = Role.STATUS_INIT
		self.thread = Thread(name="role_%s" % (self.getName()), target=self.run)
		self.loop = True
	
	def init(self):
		raise NotImplementedError()
	
	def run(self):
		raise NotImplementedError()
	
	def order_stop(self):
		Logger.info("%s role::stop"%(self.getName()))
		self.status = Role.STATUS_STOPPING
		
	def force_stop(self):
		self.loop = False
	
	def stopped(self):
		return self.status == Role.STATUS_STOP
	
	def stopping(self):
		return self.status == Role.STATUS_STOPPING
	
	def finalize(self):
		raise NotImplementedError()
	
	@staticmethod
	def getName():
		raise NotImplementedError()
	
	def getStatus(self):
		return self.status
	
	def getReporting(self, node):
		raise NotImplementedError()
	
	def switch_to_production(self):
		pass
