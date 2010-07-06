# -*- coding: UTF-8 -*-
# Copyright (C) 2010 Ulteo SAS
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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
from pyinotify import ProcessEvent, EventsCodes
import stat

from ovd.Logger import Logger

from Config import Config

class Rec(ProcessEvent):
	mask = EventsCodes.IN_CREATE 
	
	def process_IN_CREATE(self, event_k):
		path = os.path.join(event_k.path,event_k.name)
		os.chown(path, Config.uid, Config.gid)
		
		if os.path.isdir(path):
			Logger.debug("FileServer::Rec chmod dir %s"%(path))
			os.chmod(path, stat.S_IRWXU | stat.S_IRWXG | stat.S_ISGID)
		else:
			Logger.debug("FileServer::Rec chmod file %s"%(path))
			os.chmod(path, stat.S_IRUSR | stat.S_IWUSR | stat.S_IRGRP | stat.S_IWGRP)
