# -*- coding: utf-8 -*-

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

import os
import sys

from ovd.Logger import Logger
from ovd.Platform import Platform

class Config:
	# generic
	infos = {}
	
	roles = []
	ROLES_ALIASES = {"aps":"ApplicationServer", "fs":"FileServer"}
	
	LOGS_FLAGS_ALIASES = {
			"error" : Logger.ERROR,
			"warn"  : Logger.WARN,
			"info"  : Logger.INFO,
			"debug" : Logger.DEBUG,
			"normal": Logger.INFO | Logger.WARN | Logger.ERROR,
			"*"     : Logger.INFO | Logger.WARN | Logger.ERROR | Logger.DEBUG,
		}	
	
	log_level = Logger.INFO | Logger.WARN | Logger.ERROR
	log_file = os.path.join(Platform.System.get_default_log_dir(), "slaveserver.log")
	
	# OVD servers communication
	session_manager = None
	SM_SERVER_PORT = 1111
	SLAVE_SERVER_PORT = 1112
	
	
	@staticmethod
	def read(filename):
		try:
			Config.infos = Config.parse_file(filename)
		except Exception, err:
			print >> sys.stderr, "invalid configuration file '%s'"%(filename)
			print >> sys.stderr, err
			return False
		
		if Config.infos.has_key("ROLES"):
			Config.roles = []
			buf = Config.infos["ROLES"].split(' ')
			for b in buf:
				b = b.strip()
				if len(b)==0:
					continue
				
				if Config.ROLES_ALIASES.has_key(b):
					b = Config.ROLES_ALIASES[b]
				
				Config.roles.append(b)
		
		if Config.infos.has_key("session_manager"):
			Config.session_manager = Config.infos["session_manager"]
		
		if Config.infos.has_key("LOG_FILE"):
			Config.log_level = Config.infos["LOG_FILE"]
			
		if Config.infos.has_key("LOG_LEVEL"):
			Config.log_level = 0
			
			for item in Config.infos["LOG_LEVEL"].split(' '):
				if Config.LOGS_FLAGS_ALIASES.has_key(item):
					Config.log_level|= Config.LOGS_FLAGS_ALIASES[item]
		
		return True
	
	
	@staticmethod
	def is_valid():
		if len(Config.roles) == 0:
			print >>sys.stderr, "No role given"
			return False
		
		for role in Config.roles:
			try:
				__import__("ovd.Role.%s.Role"%(role))
			
			except ImportError:
				print >>sys.stderr, "Unsupported role '%s'."%(role)
				print >>sys.stderr, "Please be sure this role exists and is correctly installed"
				return False
		
		
		if Config.session_manager is None:
			print >>sys.stderr, "No session manager given"
			return False
		if " " in Config.session_manager:
			print >>sys.stderr, "Invlid session manager given"
			return False
	#	if not is_host(Config.session_manager):
	#		return False
		
		if Config.log_file is not None:
			try:
				f = file(Config.log_file, "w")
				f.close()
			except IOError:
				print >>sys.stderr, "Unable to write into '%s'"%(Config.log_file)
				return False
		
		return True
	
	
	@staticmethod
	def parse_file(file_):
		### Reconized syntax:
		###	key = value
		###     # a comment
		###     key = value # comment
		###     key = "value # or other value" # and a final comment
		
		f = file(file_, 'r')
		lines = f.readlines()
		f.close()
		
		infos = {}
		for line in lines:
			line = line.strip()
		
			if len(line) == 0:
				continue
		
			if line.startswith("#"):
				continue
		
			(key, value) = line.split('=', 1)
			key = key.strip()
			value = value.strip()
			
			if len(key) == 0 or len(value) == 0:
				# Exception !
				continue
			
			if value.startswith('"'):
				loop = True
				pos_end = 1
				while loop:
					pos_end = value.find('"', pos_end)
					if pos_end == -1:
						# Exception !
						continue
				
					if value[pos_end - 1] != '\\':
						loop = False
				
				if pos_end == -1:
					continue
				
				buffer = value[:pos_end+1].strip()
				if not buffer.startswith("#"):
					# Exception !
					continue
				
				value = value[1:pos_end]
			
			elif "#" in value:
				value = value[:value.find("#")]
			
			
			infos[key] = value
		
		return infos