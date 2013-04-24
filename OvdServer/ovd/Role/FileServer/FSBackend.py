# -*- coding: utf-8 -*-

# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author David LECHEVALIER <david@ulteo.com> 2013
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
import errno

from ovd.Logger import Logger
from ovd.Platform.System import System

from Config import Config


class FSBackend:
	def __init__(self):
		self.path = {}
		self.pid = None
		self.sharesFile = "/etc/ulteo/rufs/shares.conf"
		self.pidFile = "/tmp/FSBackend.pid"
	
	
	def add(self, share, quota, activated):
		if self.pid is None:
			try:
				f = open(self.pidFile, "r")
				pidStr = f.readline()
				if len(pidStr) == 0:
					Logger.error("Invalid FSBackend pid"%(pidStr))
					return False
				
				self.pid = int(pidStr)
			except Exception, e:
				Logger.error("Failed to get FSBackend pid"%(str(e)))
				return False
		
		try:
			# TODO use a file lock
			f = open(self.sharesFile, "rw")
			lines = f.readlines()
			out = ""
			found = False
			f.close()
			
			for line in lines:
				compo = line.split(',')
				if len(compo) != 3:
					Logger.error("The following line '%s' is not properly formated, removing it")
					continue
				
				if share == compo[0].strip():
					# updating entry
					out += "%s, %s, %s\n"%(share, quota, str(bool(activated)))
					found = True
					continue
				
				# we restore the entry
				out += line
			
			if not found:
				# we append a new entry
				out += "%s, %s, %s\n"%(share, quota, str(bool(activated)))
			
			f = open(self.sharesFile, "w+")
			f.write(out)
			f.close()
			
			# force share data relaod
			os.kill(self.pid, signal.SIGHUP)
			return True
			
			
		except Exception, e:
			Logger.error("Failed to add entry for the share '%s': %s"%(share, str(e)))
			return False
		
	
	
	def start(self):
		self.path["spool"] = Config.spool
		self.path["spool.real"] = Config.spool+".real"
		if os.path.ismount(self.path["spool"]):
			Logger.warn("Failed to start FS backend, %s is already mounted"%(self.path["spool"]))
			return False
		
		for p in self.path:
			try:
				os.makedirs(p)
			except OSError, err:
				if err[0] is not errno.EEXIST:
					Logger.error("Failed to create spool directory: %s %s"%(p, str(err)))
					return False
			
			try:
				os.lchown(p, Config.uid, Config.gid)
			except OSError, err:
				Logger.warn("Unable to change file owner for '%s'"%(p))
                                Logger.debug("lchown returned %s"%(err))
				return False

			if not os.path.exists(p):
				Logger.error("Spool directory %s do not exist"%(p))
				return False
		
		cmd = "RegularUnionFS \"%s\" \"%s\" -o user=%s -o fsconfig=%s"%(self.path["spool.real"], self.path["spool"], Config.user, Config.FSBackendConf)
		Logger.debug("Backend init command '%s'"%(cmd))
		p = System.execute(cmd)
		if p.returncode != 0:
			Logger.error("Failed to initialize spool directory (status: %d) %s"%(p.returncode, p.stdout.read()))
			return False
		
		return True
	
	
	def stop(self):
		print "Stopping FSBackend"
		if not os.path.ismount(self.path["spool"]):
			Logger.warn("FSBackend is already stopped")
			return True
		
		cmd = "umount \"%s\""%(self.path["spool"])
		Logger.debug("FSBackend release command '%s'"%(cmd))
		p = System.execute(cmd)
		if p.returncode == 0:
			print "Success"
			return True
		
		Logger.error("Failed to release FSBackend (status: %d) %s"%(p.returncode, p.stdout.read()))
		cmd = "umount -l \"%s\""%(self.path["spool"])
		Logger.debug("FSBackend failedback release command '%s'"%(cmd))
		p = System.execute(cmd)
		if p.returncode == 0:
			return True
		
		Logger.error("Unable to stop FSBackend")
		return False
