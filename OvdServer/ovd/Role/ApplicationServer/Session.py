# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009-2010
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

import locale
import os
import time

class Session:
	SESSION_STATUS_UNKNOWN = "unknown"
	SESSION_STATUS_ERROR = "error"
	SESSION_STATUS_INIT = "init"
	SESSION_STATUS_INITED = "ready"
	SESSION_STATUS_ACTIVE = "logged"
	SESSION_STATUS_INACTIVE = "disconnected"
	SESSION_STATUS_WAIT_DESTROY = "wait_destroy"
	SESSION_STATUS_DESTROYED = "destroyed"
	
	MODE_DESKTOP = "desktop"
	MODE_APPLICATIONS = "applications"
	
	def __init__(self, id_, mode_, user_, parameters_, applications_):
		self.id = id_
		self.user = user_
		self.mode = mode_
		self.parameters = parameters_
		self.applications = applications_
		self.instanceDirectory = None
		self.used_applications = {}
		
		self.log = []
		self.switch_status(Session.SESSION_STATUS_INIT)
	
	def install_client(self):
		pass
	
	def uninstall_client(self):
		pass

	def switch_status(self, status_):
		self.log.append((time.time(), status_))
		self.status = status_
	
	
	def getUsedApplication(self):
		if self.status in [Session.SESSION_STATUS_ACTIVE, Session.SESSION_STATUS_INACTIVE] and self.instanceDirectory is not None:
			(_, encoding) = locale.getdefaultlocale()
			if encoding is None:
				encoding = "UTF8"
			
			for basename in os.listdir(self.instanceDirectory):
				if type(basename) is unicode:
					name = basename
				else:
					name = unicode(basename, encoding)
				
				if self.used_applications.has_key(name):
					continue
				
				path = os.path.join(self.instanceDirectory, f)
				if not os.path.isfile(path):
					continue
				
				f = file(path, "r")
				data = f.read().strip()
				f.close()
				
				self.used_applications[name] = unicode(data, encoding)
		
		return self.used_applications
