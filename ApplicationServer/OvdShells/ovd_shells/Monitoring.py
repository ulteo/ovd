# -*- coding: utf-8 -*-

# Copyright (C) 2009, 2010 Ulteo SAS
# http://www.ulteo.com
# Author Gauvain POCENTEK <gauvain@ulteo.com> 2009
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
import time

from Module import Module

class Monitoring(Module):
	def beforeStartApp(self):
		path = os.path.join(os.environ['OVD_SESSION_DIR'], "instances")
		buf = "%d %d%s"%(os.getpid(), self.application.id, os.linesep)
		
		self.lock()
		
		f = file(path, 'a')
		f.write(buf)
		f.close()
		
		self.unlock()
	
	
	def afterStartApp(self):
		path = os.path.join(os.environ['OVD_SESSION_DIR'], "instances")
		subject = str(os.getpid())
		
		self.lock()
		
		f = file(path, "r")
		buf = f.readlines()
		f.close()
		
		buf2 = []
		for line in buf:
			if line.startswith(subject):
				continue
			
			buf2.append(line)
		
		f = file(path, "w")
		buf = f.writelines(buf2)
		f.close()
		
		self.unlock()
	
	def lock(self):
		path = os.path.join(os.environ['OVD_SESSION_DIR'], "instances_lock")
		
		while True:
			while os.path.isfile(path):
				time.sleep(0.2)
		
			f = file(path, 'w')
			f.write(str(os.getpid()))
			f.close()
			
			f = file(path, 'r')
			pid = int(f.read())
			f.close()
			
			if pid == os.getpid():
				return True
	
	def unlock(self):
		path = os.path.join(os.environ['OVD_SESSION_DIR'], "instances_lock")
		
		if not os.path.isfile(path):
			return
		
		f = file(path, 'r')
		pid = int(f.read())
		f.close()
		if pid != os.getpid():
			return
		
		os.remove(path)
