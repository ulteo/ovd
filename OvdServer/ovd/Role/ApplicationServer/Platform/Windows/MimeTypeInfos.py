# Copyright (C) 2009-2010 Ulteo SAS
# Author: Gauvain Pocentek <gauvain@ulteo.com>, 2009
# Author: Julien LANGLOIS <julien@ulteo.com>, 2010
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

import re
import win32api
import win32con


class MimeTypeInfos():
	def __init__(self):
		self.mime_types = {}
		self.file_extensions = {}
	
	def load(self):
		self.mime_types = self.extract_know_mime_ext_matching()
		
		for extension in self.mime_types.values():
		#for extension in [".pdf"]:
			#print "extension: ",extension
			commands = []
			
			commands1 = self.extract_commands_from_openwithlist(extension)
			commands2 = self.extract_commands_from_progids(extension)
			commands3 = self.extract_commands_from_default_action(extension)
			
			for command in commands1+commands2+commands3:
				#print "command",command
				if command not in commands:
					commands.append(command)
			
			#print "commands: ",commands
			self.file_extensions[extension] = commands
	
	
	def get_commands_from_mime_type(self, mime_type):
		if not self.mime_types.has_key(mime_type):
			return []
		
		extension = self.mime_types[mime_type]
		if not self.file_extensions.has_key(extension):
			return []
		
		return self.file_extensions[extension]
	
	
	def get_mime_types_from_command(self, command):
		mimes = []
		
		for (mime, extension) in self.mime_types.items():
			if self.contains_command(command, self.file_extensions[extension]):
				mimes.append(mime)
		
		return mimes
	
	@staticmethod
	def contains_command(command, commands):
		for command2 in commands:
			if command.lower() in command2.lower():
				return True
		
		return False
	
	
	@staticmethod
	def extract_know_mime_ext_matching():
		ret = {}
		
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_CLASSES_ROOT, r"MIME\Database\Content Type", 0, win32con.KEY_READ)
		except:
			return ret
		
		i = 0
		while True:
			try:
				mime_type = win32api.RegEnumKey(hkey, i)
				i+= 1
			except Exception, err:
				break
			
			try:
				hkey2 = win32api.RegOpenKey(win32con.HKEY_CLASSES_ROOT, r"MIME\Database\Content Type\%s"%(mime_type), 0, win32con.KEY_QUERY_VALUE)
			except:
				print "weird behavior 1"
				continue
			
			try:
				(o,t) = win32api.RegQueryValueEx(hkey2, "Extension")
			except:
				continue
			finally:
				win32api.RegCloseKey(hkey2)
			
			if t is not win32con.REG_SZ:
				continue
		
			ret[mime_type] = o
		
		win32api.RegCloseKey(hkey)
		return ret
	
	
	@staticmethod
	def extract_know_mime_ext_matching_alt():
		ret = {}
		
		startEnumerate = False
		i = 0
		while True:
			try:
				name = win32api.RegEnumKey(win32con.HKEY_CLASSES_ROOT, i)
				i+= 1
			except Exception, err:
				break
			
			if not name.startswith("."):
				if startEnumerate:
					# Assume the enumerate is in alpha order so no more .something
					# because the enumerate is too long
					break
				continue
			
			startEnumerate = True
			try:
				hkey2 = win32api.RegOpenKey(win32con.HKEY_CLASSES_ROOT, name, 0, win32con.KEY_QUERY_VALUE)
			except:
				print "weird behavior 1"
				continue
			
			try:
				(o,t) = win32api.RegQueryValueEx(hkey2, "Content Type")
			except:
				continue
			finally:
				win32api.RegCloseKey(hkey2)
			
			if t is not win32con.REG_SZ:
				continue
			
			if not ret.has_key(o):
				ret[o] = []
			ret[o].append(name)
		
		return ret
	
	
	
	@staticmethod
	def extract_commands_from_openwithlist(extension):
		commands = []
		openwithlist = MimeTypeInfos.get_applications_from_openwithlist(extension)
		
		for application in openwithlist:
			app_name = "Applications\%s"%(application)
			for command in MimeTypeInfos.get_commands_for_application(app_name):
				if command not in commands:
					commands.append(command)
		
		return commands
	
	
	@staticmethod
	def get_applications_from_openwithlist(extension):
		ret = []
		hkey = None
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_CLASSES_ROOT, r"%s\OpenWithList"%(extension), 0, win32con.KEY_READ)
			i = 0
			while True:
				try:
					ret.append(win32api.RegEnumKey(hkey, i))
					i += 1
				except:
					break
		except:
			pass
		finally:
			if hkey is not None:
				win32api.RegCloseKey(hkey)
		
		return ret
	
	
	@staticmethod
	def extract_commands_from_progids(extension):
		commands = []
		applications = MimeTypeInfos.get_applications_from_progids(extension)
		
		for application in applications:
			for command in MimeTypeInfos.get_commands_for_application(application):
				if command not in commands:
					commands.append(command)
		
		return commands
	
	
	@staticmethod
	def get_applications_from_progids(extension):
		ret = []
		hkey = None
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_CLASSES_ROOT, r"%s\OpenWithProgids"%extension, 0,  win32con.KEY_READ)
			i = 0
			while True:
				try:
					(value, _) = win32api.RegEnumValue(hkey, i)
					# Must be EnumValue !!! because no subkeys for progids !
					ret.append(value)
				
				except:
					break
		except:
			pass
		finally:
			if hkey is not None:
				win32api.RegCloseKey(hkey)
		
		return ret
	
	
	@staticmethod
	def extract_commands_from_default_action(extension):
		commands = []
		application = MimeTypeInfos.get_applications_from_default_action(extension)
		
		if application is not None:
			for command in MimeTypeInfos.get_commands_for_application(application):
				if command not in commands:
					commands.append(command)
		
		return commands
	
	
	@staticmethod
	def get_applications_from_default_action(extension):
		ret = None
		hkey = None
		
		try:
			hkey = win32api.RegOpenKey(win32con.HKEY_CLASSES_ROOT, extension, 0,  win32con.KEY_READ)
			(value, _) = win32api.RegQueryValueEx(hkey, None)
			
			ret = value
		except:
			pass
		finally:
			if hkey is not None:
				win32api.RegCloseKey(hkey)
		
		return ret
	
	
	@staticmethod
	def get_commands_for_application(application):
		ret = []
		hkey = None
		#print "get_commands_for_application",application
		
		for action in ["open", "edit", "read"]:
			try:
				#print "t0",r"%s\shell\%s\command"%(application, action)
				hkey = win32api.RegOpenKey(win32con.HKEY_CLASSES_ROOT, r"%s\shell\%s\command"%(application, action), 0, win32con.KEY_QUERY_VALUE)
				
				(command, _) = win32api.RegQueryValueEx(hkey, None)
				command = win32api.ExpandEnvironmentStrings(command)
				command = MimeTypeInfos.transform_command(command)
				
				if command not in ret:
					ret.append(command)
			except:
				pass
			finally:
				if hkey is not None:
					win32api.RegCloseKey(hkey)
		
		return ret
	
	
	@staticmethod
	def transform_command(cmd):
		r = re.compile(r'("?)%[l0-9]+("?)')
		cmd = r.sub(r'\1%f\2', cmd)
		r = re.compile(r'("?)%[L]+("?)')
		cmd = r.sub(r'\1%F\2', cmd)
		return cmd


if __name__ == "__main__":
	import time
	
	infos = MimeTypeInfos()
	
	t0 = time.time()
	infos.load()
	t1 = time.time()
	
	print "Load in %.2fs"%(t1-t0)
	print "%d results"%(len(infos.mime_types))
	
	for mime_type in infos.mime_types.keys():
		commands = infos.get_commands_from_mime_type(mime_type)
		#print "  *",mime_type," =>",commands
	
	cmd = r"C:\Program Files\Microsoft Office\Office12\WINWORD.EXE"
	t0 = time.time()
	res = infos.get_mime_types_from_command(cmd)
	t1 = time.time()
	print "For word: %d results in %.2fs"%(len(res), t1-t0)
	for r in res:
		print r
	
	
