# -*- coding: utf-8 -*-

# Copyright (C) 2009,2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
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
import signal
import time

from ovd_shell.InstanceManager import InstanceManager as AbstractInstanceManager

class InstanceManager(AbstractInstanceManager):
	def launch(self, cmd):
		pid = os.fork()
		
		if pid > 0:
			return pid
		
		# Child process
		ret = os.system(cmd)
		sys.exit(ret)
	
	
	def wait(self):
		haveWorked = False
		for (pid, instance) in self.instances:
			path = os.path.join("/proc", pid)
			if os.path.is_dir(path):
				continue
			
			self.onInstanceExited(self.instances[index])
			haveWorked = True
		
		if not haveWorked:
			time.sleep(0.1)
	
	
	def kill(self, pid):
		os.kill(signal.SIGTERM)

