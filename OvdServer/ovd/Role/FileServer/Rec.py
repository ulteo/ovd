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
from pyinotify import ProcessEvent
import pyinotify as EventsCodes
try:
	# Pyinotify API changed between v0.7 and v0.8
	#
	# events codes was in sub package 'EventsCodes' in v0.7
	# and is now in main package since v0.8
	EventsCodes.IN_CREATE
except AttributeError, err:
	from pyinotify import EventsCodes

import stat

from ovd.Logger import Logger

from Config import Config

class Rec(ProcessEvent):
	mask = EventsCodes.IN_CREATE | EventsCodes.IN_MOVED_TO
	
	def process(self, path):
		if os.path.isdir(path):
			Logger.debug("FileServer::Rec chmod dir %s"%(path))
			chmod_flag = stat.S_IRWXU | stat.S_IRWXG | stat.S_ISGID
		elif os.path.isfile(path):
			Logger.debug("FileServer::Rec chmod file %s"%(path))
			chmod_flag = stat.S_IRUSR | stat.S_IWUSR | stat.S_IRGRP | stat.S_IWGRP
		else:
			chmod_flag = None
		
		try:
			os.lchown(path, Config.uid, Config.gid)
			if chmod_flag is not None:
				os.chmod(path, chmod_flag)
		except OSError, err:
			if os.path.exists(path):
				Logger.warn("Unable to change file owner for '%s'"%(path))
				Logger.debug("lchown returned %s"%(err))
			else:
				Logger.debug2("FS:REC: path '%s' deleted before chown/chmod operations"%(path))
	
	
	def process_IN_CREATE(self, event_k):
		if event_k.name == ".htaccess":
			if event_k.path.startswith(Config.spool):
				buf = event_k.path[len(Config.spool)+1:].split("/")
				if len(buf) == 1:
					Logger.debug2("process_IN_CREATE doesn't change file attribute on root .htaccess file")
					return
		  
		path = os.path.join(event_k.path,event_k.name)
		self.process(path)
	
	
	def process_IN_MOVED_TO(self, event_k):
		path = os.path.join(event_k.path,event_k.name)
		self.process(path)
