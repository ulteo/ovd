# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2010
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
import hashlib
import locale
import os
import random
import shutil
import time

from ovd.Config import Config
from ovd.Logger import Logger
from ovd.Platform.System import System

class Session:
	
	SESSION_STATUS_UNKNOWN = "unknown"
	SESSION_STATUS_ERROR = "error"
	SESSION_STATUS_INIT = "init"
	SESSION_STATUS_INITED = "ready"
	SESSION_STATUS_ACTIVE = "logged"
	SESSION_STATUS_INACTIVE = "disconnected"
	SESSION_STATUS_WAIT_DESTROY = "wait_destroy"
	SESSION_STATUS_DESTROYED = "destroyed"
	
	SESSION_END_STATUS_NORMAL = "logout"
	SESSION_END_STATUS_SHUTDOWN = "shutdown"
	SESSION_END_STATUS_ERROR = "internal"
	
	MODE_DESKTOP = "desktop"
	MODE_APPLICATIONS = "applications"
	
	
	def __init__(self, id_, user_):
		self.id = id_
		self.user = user_
		self.end_status = None
		self.user_session_dir = None
		self.status = None	
		self.log = []
		self.switch_status(Session.SESSION_STATUS_INIT)
	
	
	def init(self):
		raise NotImplementedError()
	
	
	def post_install(self):
		if self.user_session_dir is not None:
			f = file(os.path.join(self.user_session_dir, "nolock"), "w")
			f.close()
	
	
	def install_client(self):
		pass
	
	
	def uninstall_client(self):
		pass
	
	
	def get_target_file(self, application):
		pass
	
	
	def switch_status(self, status_):
		self.log.append((time.time(), status_))
		self.status = status_
