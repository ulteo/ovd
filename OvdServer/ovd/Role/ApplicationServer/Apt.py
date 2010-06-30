# -*- coding: UTF-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
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

import commands
import os
from Queue import Queue
from threading import Thread

from ovd.Logger import Logger

class Apt(Thread):
	def __init__(self):
		Thread.__init__(self)
		
		self.directory = "/var/spool/ulteo/ovd/apt"
		self.queue = Queue()
		
		self.i = 0
	
	
	def init(self):
		if not os.path.exists(self.directory):
			os.makedirs(self.directory)
		else:
			s,o = commands.getstatusoutput("rm -rf %s/*"%(self.directory))
	
	
	def pushRequest(self, request):
		self.queue.put(request)
	
	
	def createRequest(self):
		request = {}
		request["id"] = str(self.i)
		os.mkdir(self.directory+"/"+request["id"])
		self.i+= 1
		
		return request
	
	def getRequestStatus(self, rid):
		d = self.directory+"/"+rid
		if not os.path.exists(d):
			return "unknown"
		
		f = d+"/status"
		if not os.path.exists(f):
			return "in progress"
		
		f = file(f, "r")
		buf = f.read().strip()
		f.close()
		
		if buf != "0":
			return "error"
		
		return "success"
	
	def getRequestLog(self, rid, log):
		f = self.directory+"/"+rid+"/"+log
		if not os.path.exists(f):
			return None
		
		f = file(f, "r")
		buf = f.read()
		f.close()
		
		return buf
	
	
	def run(self):
		while True:
			request = self.queue.get()
			print "perform request: ",request
			
			if request["order"] == "upgrade":
				command = "dist-upgrade"
			
			elif request["order"] == "install":
				command = "install "+" ".join(request["packages"])
			
			elif request["order"] == "remove":
				command = "autoremove --purge "+" ".join(request["packages"])
			
			ret = self.perform(request, command)
			if not ret:
				Logger.error("Apt error on request: "+str(request))
			else:
				Logger.debug("Apt finish request succefully")
	
	
	def perform(self, request, command):
		d = os.path.join(self.directory, request["id"])
		
		cmd = "apt-get update >>%s/stdout 2>>%s/stderr"%(d, d)
		ret,o = commands.getstatusoutput(cmd)
		if ret != 0:
			f = file("%s/status"%(d), "w")
			f.write(str(ret))
			f.close()
			return False
		
		cmd = "DEBIAN_FRONTEND=noninteractive DEBIAN_PRIORITY=critical DEBCONF_NONINTERACTIVE_SEEN=true apt-get --yes --force-yes %s >>%s/stdout 2>>%s/stderr"%(command, d, d)
		ret, o = commands.getstatusoutput(cmd)
		if ret != 0:
		#	Logger.error(...)
			return False
			
		f= file("%s/status"%(d), "w")
		f.write(str(ret))
		f.close()
		return True
