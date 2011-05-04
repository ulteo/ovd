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

class Role:
	STATUS_INIT    = 0
	STATUS_RUNNING = 1
	STATUS_STOP    = 2
	STATUS_ERROR   = 3
	
	def __init__(self, main_instance):
		self.main_instance = main_instance
		self.status = Role.STATUS_INIT
		self.thread = Thread(name="role_%s" % (self.getName()), target=self.run)

		#TODO: check if this variable is really useful
		self.has_run = False
	
	def init(self):
		raise NotImplementedError()
	
	def run(self):
		raise NotImplementedError()
	
	def stop(self):
		raise NotImplementedError()
	
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
