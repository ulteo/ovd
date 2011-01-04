# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import threading

class Thread(threading.Thread):
	def __init__(self, *args, **keywords):
		threading.Thread.__init__(self, *args, **keywords)
		
		self.continue_run = True
		self.continue_run_lock = threading.Lock()
	
	
	def thread_continue(self):
		self.continue_run_lock.acquire()
		r = self.continue_run
		self.continue_run_lock.release()
		return r
	
	
	def order_stop(self):
		self.continue_run_lock.acquire()
		self.continue_run = False
		self.continue_run_lock.release()
		
		if self.isAlive():
			self._Thread__stop()
