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
import sys
from win32com.shell import shell, shellcon
from ovd.Logger import Logger
import ConfigParser
import win32file
import ctypes
import codecs
import io



class GPO:
	GUID = "[{42B5FAAE-6536-11D2-AE5A-0000F87571E3}{40B66650-4972-11D1-A7CA-0000F87571E3}]"
	GPT_KEY = "gPCUserExtensionNames"
	GPT_FILE = r"GroupPolicy\gpt.ini"
	INI_SCRIPT_FILE = r"GroupPolicy\User\Scripts\scripts.ini"
	LOGON = u"Logon"
	LOGOFF = u"Logoff"


	def __init__(self):
		sysDir = shell.SHGetFolderPath(0, shellcon.CSIDL_SYSTEM, 0, 0)
		self.iniScriptFile = os.path.join(sysDir, GPO.INI_SCRIPT_FILE)
		self.gptFile = os.path.join(sysDir, GPO.GPT_FILE)
		self.gpos = {}
		self.useUTF16 = True
	
	
	def disableSysWow64(self, value):
		kernel32 = ctypes.windll.kernel32
		v = ctypes.c_long(0)
		if (value):
			kernel32.Wow64DisableWow64FsRedirection(ctypes.byref(v))
		else:
			kernel32.Wow64EnableWow64FsRedirection(v)
	
	
	def parse(self):
		# we need to disable syswow64 redirection
		self.disableSysWow64(True)
		command = None
		parameter = None
		
		if not os.path.exists(self.iniScriptFile):
			self.disableSysWow64(False)
			return True
		
		f = open(self.iniScriptFile, 'r')
		data = f.read()
		f.close()
		
		if data.startswith(codecs.BOM_UTF16_LE):
			data = data.lstrip(codecs.BOM_UTF16_LE)
			data = data.decode("utf-16-le")
			self.useUTF16 = True
		
		bufferStream = io.StringIO(data)
		action = None
		
		for line in bufferStream.readlines():
			line = line.strip()
			if len(line) == 0:
				continue
			if line[0] == '[' and line[-1] == ']':
				action = line.strip("[]")
				self.gpos[action] = []
				continue
			
			if u'=' not in line:
				continue
			
			kv = line.split("=")
			if kv is not None and len(kv) == 2:
				if u"CmdLine" in kv[0]:
					command = kv[1]
				if u"Parameters" in kv[0]:
					parameter = kv[1]
				
				if command is not None and parameter is not None:
					self.gpos[action].append((command, parameter))
					command = None
					parameter = None
		
		self.disableSysWow64(False)
		return True
	
	
	def contain(self, GPOType, command, parameter):
		if GPOType not in [GPO.LOGON, GPO.LOGOFF]:
			Logger.error("GPO of type %s is not supported"%(GPOType))
			return False
		
		if not self.gpos.has_key(GPOType):
			return False
		
		return (command, parameter) in self.gpos[GPOType]
	
	
	def add(self, GPOType, command, parameter):
		if GPOType not in [GPO.LOGON, GPO.LOGOFF]:
			Logger.error("GPO of type %s is not supported"%(GPOType))
			return False
		
		if not self.gpos.has_key(GPOType):
			self.gpos[GPOType] = []

		self.gpos[GPOType].append((command, parameter))
		
		return True
	
	
	def remove(self, GPOType, command, parameter):
		if GPOType not in [GPO.LOGON, GPO.LOGOFF]:
			Logger.error("GPO of type %s is not supported")
			return False
		
		if not self.gpos.has_key(GPOType):
			return True
		
		if (command, parameter) in self.gpos[GPOType]:
			self.gpos[GPOType].remove((command, parameter))
	
	
	def updateGPT(self):
		# we need to disable syswow64 redirection
		self.disableSysWow64(True)
		if not os.path.exists(self.gptFile):
			Logger.error("Failed to update gpt file")
			self.disableSysWow64(False)
			return False
		
		f = open(self.gptFile, 'r')
		data = f.read()
		f.close()
		
		if GPO.GUID in data:
			return True
		
		data = data.replace("%s="%(GPO.GPT_KEY), "%s=%s"%(GPO.GPT_KEY, GPO.GUID))
		
		f = open(self.gptFile, 'w')
		f.write(data)
		f.close()
		
		self.disableSysWow64(False)
	
	
	def save(self):
		# check GPT_FILE
		self.updateGPT()
		
		self.disableSysWow64(True)
		buffer = ""
		
		for action in self.gpos:
			buffer += "[%s]\r\n"%(action)
			
			index = 0
			for (program, parameters) in self.gpos[action]:
				buffer += "%iCmdLine=%s\r\n"%(index, program)
				buffer += "%iParameters=%s\r\n"%(index, parameters)
				index += 1
		
		try:
			print "mkdir ", os.path.dirname(self.iniScriptFile)
			os.makedirs(os.path.dirname(self.iniScriptFile))
			f = open(self.iniScriptFile, 'wb+')
			f.truncate()
			
			if self.useUTF16:
				buffer = "\r\n"+buffer
				buffer = buffer.encode("utf-16-le")
				buffer = codecs.BOM_UTF16_LE+buffer
			
			f.write(buffer)
			f.close()
		except Exception, e:
			Logger.error("Failed to add a GPO: %s"%(str(e)))
		
		self.disableSysWow64(False)

	
	def dump(self):
		if self.gpos == {}:
			Logger.info("There is no GPO")
			return
		
		for action in self.gpos:
			print "%s rules"%(action)
			
			for (program, parameters) in self.gpos[action]:
				Logger.info("\t - %s %s"%(program, parameters))
	
	
	
if __name__ == "__main__":
	gpo = GPO()
	if not gpo.parse():
		Logger.error("Failed to parse GPO")
		sys.exit(1)
	
	gpo.add(GPO.LOGON, u"C:\\windows\\system32\\cmd.exe", u"test ")
	gpo.add(GPO.LOGOFF, u"C:\\windows\\system32\\cmd2.exe", u"")
	gpo.remove(GPO.LOGOFF, u"Dbgview.exe", u"")
	gpo.dump()
	gpo.save()


