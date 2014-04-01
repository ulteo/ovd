# -*- coding: utf-8 -*-

# Copyright (C) 2009-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2011
# Author David LECHEVALIER <david@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
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
from ovd.Platform.System import System

def report_error(message):
	print >>sys.stderr, "Invalid configuration:",message


class Config:
	# generic
	infos = {}
	
	roles = []
	stop_timeout = 600  # in second (10m)
	ROLES_ALIASES = {"aps":"ApplicationServer", "fs":"FileServer"}
	
	LOGS_FLAGS_ALIASES = {
			"error" : Logger.ERROR,
			"warn"  : Logger.WARN,
			"info"  : Logger.INFO,
			"debug" : Logger.DEBUG,
			"normal": Logger.INFO | Logger.WARN | Logger.ERROR,
			"*"     : Logger.INFO | Logger.WARN | Logger.ERROR | Logger.DEBUG,
		}	
	
	log_level = Logger.INFO | Logger.WARN | Logger.ERROR | Logger.DEBUG
	log_file = os.path.join(System.get_default_log_dir(), "slaveserver.log")
	log_threaded = False
	
	conf_dir = System.get_default_config_dir()
	spool_dir = System.get_default_spool_dir()
	
	# OVD servers communication
	session_manager = None
	SM_SERVER_PORT = 1111
	SLAVE_SERVER_PORT = 1112
	server_allow_reuse_address = System.tcp_server_allow_reuse_address()
	
	
	@classmethod
	def read(cls, raw_data):
		cls.raw_data = raw_data
		
		if not cls.raw_data.has_key("main") or type(cls.raw_data["main"]) is not dict:
			report_error("Missing 'main' part")
			return False
		
		if cls.raw_data["main"].has_key("roles"):
			cls.roles = cls.parse_list(cls.raw_data["main"]["roles"])
			cls.manage_role_aliases(cls.roles)
		
		if cls.raw_data["main"].has_key("session_manager"):
			a = cls.raw_data["main"]["session_manager"]
			if len(a)>0:
				cls.session_manager = a.strip()
		
		if cls.raw_data["main"].has_key("stop_timeout"):
			a = cls.raw_data["main"]["stop_timeout"]
			try:
				a = int(a)
				if a > 0:
					cls.stop_timeout = a
			except ValueError:
				report_error("Invalid value for configuration key 'stop_timeout', need a time in seconde")
		
		if cls.raw_data["main"].has_key("server_allow_reuse_address"):
			a = cls.raw_data["main"]["server_allow_reuse_address"].lower().strip()
			if a not in ["true", "false"]:
				report_error("Invalid value for configuration key 'server_allow_reuse_address', allowed values are true/false")
			
			cls.server_allow_reuse_address = (a == "true")
		
		
		if not cls.raw_data.has_key("log") or type(cls.raw_data["log"]) is not dict:
			return True
		
		if cls.raw_data["log"].has_key("file"):
			cls.log_file = cls.raw_data["log"]["file"]
			
		if cls.raw_data["log"].has_key("level"):
			cls.log_level = 0
			
			debug_count = 0
			for item in cls.raw_data["log"]["level"].split(' '):
				item = item.lower()
				if item == "debug":
					debug_count+= 1
				if cls.LOGS_FLAGS_ALIASES.has_key(item):
					cls.log_level|= cls.LOGS_FLAGS_ALIASES[item]
			
			if debug_count>1:
				cls.log_level|= Logger.DEBUG_2
				if debug_count>=3:
					cls.log_level|= Logger.DEBUG_3
		
		if cls.raw_data["log"].has_key("thread"):
			cls.log_threaded = (cls.raw_data["log"]["thread"].lower() == "true")
		
		return True
	
	
	@classmethod
	def is_valid(cls):
		if len(cls.roles) == 0:
			report_error("No role given")
			return False
		
		if cls.session_manager is None:
			report_error("No session manager given")
			return False
		if " " in cls.session_manager:
			report_error("Invalid session manager given")
			return False
	#	if not is_host(cls.session_manager):
	#		return False
		
		if cls.log_file is not None:
			try:
				f = file(cls.log_file, "a")
				f.close()
			except IOError:
				report_error("Unable to write into log file '%s'"%(cls.log_file))
				return False
		
		return True
	
	
	@classmethod
	def manage_role_aliases(cls, l):
		for item in list(l):
			if not cls.ROLES_ALIASES.has_key(item):
				continue
			
			l.remove(item)
			v = cls.ROLES_ALIASES[item]
			if v not in l:
				l.append(v)
	
	
	@classmethod
	def parse_list(cls, data):
		return dict.fromkeys(data.split()).keys()
	
	
	@classmethod
	def get_role_dict(cls, role):
		if not cls.raw_data.has_key(role):
			return {}
		
		return cls.raw_data[role]
