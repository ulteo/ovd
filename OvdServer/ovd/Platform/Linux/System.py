# -*- coding: utf-8 -*-

# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
# Author Samuel BOVEE <samuel@ulteo.com> 2010
# Author David LECHEVALIER <david@ulteo.com> 2012
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
import grp
import locale
import os
import platform
import pwd
import time

from ovd.Logger import Logger

from ovd.Platform.System import System as AbstractSystem


class System(AbstractSystem):
	@staticmethod
	def getName():
		return "linux"
	
	@staticmethod
	def get_default_config_dir():
		return "/etc/ulteo/ovd"
	
	@staticmethod
	def get_default_spool_dir():
		return "/var/spool/ulteo/ovd/slaveserver"
	
	@staticmethod
	def get_default_data_dir():
		return "/var/lib/ulteo/ovd/slaveserver"
	
	@staticmethod
	def get_default_log_dir():
		return "/var/log/ulteo/ovd"
	
	@staticmethod
	def getVersion():
		try:
			f = file("/etc/issue", "r")
			buffer = f.readline()
			buffer = buffer.replace("\\n", "")
			buffer = buffer.replace("\\l", "")
			buffer = buffer.replace("\n", "")
			buffer = buffer.encode('utf-8')
		
		except Exception, err:
			Logger.warn("System::getVersion: version except '%s'"%(str(err)))
			buffer = platform.version()
		
		return buffer
	
	@staticmethod
	def _getCPULoad():
		try:
			fd = file("/proc/stat", "r")
			line = fd.readline()
			fd.close()
		except Exception, e:
			Logger.error("System::getCPULoad %s"%(str(e)))
			return (0.0, 0.0)
		
		
		values = line.strip().replace("  ", " ").split(" ")
		
		load = float(values[1]) + float(values[2]) + float(values[3])
		total = load + float(values[4])
		
		return (load, total)
	
	@staticmethod
	def getCPULoad():
		(load1, total1) = System._getCPULoad()
		time.sleep(1)
		(load2, total2) = System._getCPULoad()
	
		if total2 - total1 == 0:
			return 0.0
			
		return ((load2 - load1) / (total2 - total1))
	
	@staticmethod
	def parseProcFile(filename_):
		try:
			fd = file(filename_, "r")
			lines = fd.readlines()
			fd.close()
		except Exception, e:
			Logger.error("System::_getMeminfo %s"%(str(e)))
			return {}
		
		infos = {}
		for line in lines:
			line = line.strip()
			if not ":" in line:
				continue
			
			k,v = line.split(":", 1)
			infos[k.strip()] = v.strip()
		
		return infos
	
	@staticmethod
	def getCPUInfos():
		infos = System.parseProcFile("/proc/cpuinfo")
		
		try:
			name = infos["model name"]
			nb = int(infos["processor"]) + 1
			
		except Exception, e:
			Logger.error("getCPUInfos %s"%(str(e)))
			return (1, "Unknown")
		
		return (nb, name)
	
	@staticmethod
	def _getMeminfo():
		try:
			fd = file("/proc/meminfo", "r")
			lines = fd.readlines()
			fd.close()
		except Exception, e:
			Logger.error("System::_getMeminfo %s"%(str(e)))
			return {}
		
		infos = {}
		for line in lines:
			line = line.strip()
			if not ":" in line:
				continue
			
			k,v = line.split(":", 1)
			
			v = v.strip()
			if " " in v:
				v,_ = v.split(" ", 1)
			
			infos[k.strip()] = v.strip()
		
		return infos
	
	@staticmethod
	def getRAMUsed():
		infos = System._getMeminfo()
		
		try:
			total = int(infos["MemTotal"])
			free = int(infos["MemFree"])
			cached = int(infos["Cached"])
			buffers = int(infos["Buffers"])
		except Exception, e:
			Logger.warn("getRAMUsed: %s"%(str(e)))
			return 0.0
		
		return total - (free + cached + buffers)
	
	
	@staticmethod
	def getRAMTotal():
		infos = System._getMeminfo()
		
		try:
			total = int(infos["MemTotal"])
		
		except Exception, e:
			Logger.warn("getRAMTotal: %s"%(str(e)))
			return 0.0
		
		return total
	
	
	@staticmethod
	def getADDomain():
		return False
	
	
	@staticmethod
	def logoff(user, domain):
		raise Exception("Not implementer")
	
	@staticmethod
	def DeleteDirectory(path):
		os.system("rm -rf '%s'"%(path))
	
	@staticmethod
	def groupCreate(name_):
		cmd = "groupadd %s"%(name_)
		
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("groupCreate return %d (%s)"%(s, o))
			return False
		
		return True
	
	@staticmethod
	def groupExist(name_):
		try:
			grp.getgrnam(name_)
		except KeyError:
			return False
		
		return True
	
	@staticmethod
	def groupMember(name_):
		try:
			group = grp.getgrnam(name_)
		except KeyError, err:
			Logger.error("groupMember: '%s'"%(str(err)))
			return None
		
		return group[3]
	
	@staticmethod
	def userRemove(name_):
		cmd = "userdel --force  --remove %s"%(name_)
		
		s,o = commands.getstatusoutput(cmd)
		if s == 3072:
			Logger.debug("mail dir error: '%s' return %d => %s"%(cmd, s, o))
		elif s != 0:
			Logger.error("userRemove return %d (%s)"%(s, o))
			return False
		
		return True
	
	@staticmethod
	def userAdd(login_, displayName_, password_, groups_):
		cmd = "useradd -m -k /dev/null %s"%(login_)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("userAdd return %d (%s)"%(s, o))
			return False
		
		cmd = 'echo "%s:%s" | chpasswd'%(login_, password_)
		s,o = commands.getstatusoutput(cmd)
		if s != 0:
			Logger.error("userAdd return %d (%s)"%(s, o))
			return False
		
		for group in groups_:
			cmd = "adduser %s %s"%(login_, group)
			s,o = commands.getstatusoutput(cmd)
			if s != 0:
				Logger.error("userAdd return %d (%s)"%(s, o))
				return False
		
		return True
	
	
	
	@staticmethod
	def userExist(name_):
		try:
			pwd.getpwnam(System.local_encode(name_))
		except KeyError:
			return False
		
		return True
	
	
	@staticmethod
	def tcp_server_allow_reuse_address():
		return True
	
	
	@staticmethod
	def prepareForSessionActions():
		pass
