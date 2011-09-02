# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
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

from pyinotify import ExcludeFilter, ThreadedNotifier, WatchManager, WatchManagerError

# Usefull for inotify debug information
# import pyinotify
# pyinotify.log.setLevel(10)

from ovd.Logger import Logger
from Rec import Rec


class DirectoryWatcher(WatchManager):
	def __init__(self):
		WatchManager.__init__(self)

	
	def start(self):
		self.inotify = ThreadedNotifier(self)
		self.inotify.start()
		self.inotify.join()


	def stop(self):
		self.inotify.stop()
	
	
	def add_monitor_path(self, path):
		if path is None:
			Logger.error("FS: unable to monitor None directory")
			return False
		
		exclude1 = "^%s/conf.Windows*"%(path)
		exclude2 = "^%s/conf.Linux*"%(path)
		exc_filter = ExcludeFilter([exclude1, exclude2])
		
		try:
			self.add_watch(path=path, mask=Rec.mask, proc_fun=Rec(), rec=True, auto_add=True, exclude_filter=exc_filter)
		except WatchManagerError, e:
			Logger.error("FS: unable to monitor directory %s, %s"%(path, str(e)))
			return False
		
		return False
	
	
	def rm_monitor_path(self, path):
		wd = self.get_wd(path)
		if wd is None:
			return False
		
		try:
                        self.rm_watch(wd, rec=True)
		except WatchManagerError, e:
			Logger.error("FS: unable to monitor directory %s, %s"%(path, str(e)))
			return False
		
		return True
	
