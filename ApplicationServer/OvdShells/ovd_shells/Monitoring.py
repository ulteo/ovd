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

from Module import Module

class Monitoring(Module):
	def beforeStartApp():
		path = os.path.join(os.environ['OVD_SESSID_DIR'], "apps")
		buf = "%d %d%s"%(os.getpid(), self.application.id, os.linesep)
		
		f = file(path, 'a')
		f.write(buf)
		f.close()
	
	
	def afterStartApp():
		path = os.path.join(os.environ['OVD_SESSID_DIR'], "apps")
		subject = str(os.getpid())
		
		f = file(path, "r")
		buf = f.readlines()
		f.close()
		
		buf2 = []
		for line in buf:
			if line.startswith(subject):
				continue
			
			buf2.append(line)
		
		f = file(path, "w")
		buf = f.witelines(buf2)
		f.close()
