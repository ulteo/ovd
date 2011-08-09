# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Gaëtan COLLET <gaetan@ulteo.com> 2011
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

from ovd.Logger import Logger

import threading
from Queue import Queue

class ActionManager(threading.Thread):
	def __init__(self, role_instance, queue):
		threading.Thread.__init__(self)
		
		self.role_instance = role_instance
		self.queue = queue
	
	
	def run(self):
		Logger.debug("ActionManager::run")
		
		while True:
			try:
				(request, obj) = self.queue.get(True, 4)
			except Queue.Empty, err:
				continue
			# This error is ue to the sigterm sended by the init script
			except TypeError:
				return
			except (EOFError, socket.error):
				return
			
			if request == "clone":
				self.action_clone(request)
	
	
	def create(self, name, master):
		Logger.info("ActionManager Clone ....")
