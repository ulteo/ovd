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
	
	
	@staticmethod
	def read(raw_data):
		Config.raw_data = raw_data
		
		if not Config.raw_data.has_key("main") or type(Config.raw_data["main"]) is not dict:
			report_error("Missing 'main' part")
			return False
		
		if Config.raw_data["main"].has_key("roles"):
			Config.roles = Config.parse_list(Config.raw_data["main"]["roles"])
			Config.manage_role_aliases(Config.roles)
		
		if Config.raw_data["main"].has_key("session_manager"):
			a = Config.raw_data["main"]["session_manager"]
			if len(a)>0:
				Config.session_manager = a.strip()
		
		if Config.raw_data["main"].has_key("server_allow_reuse_address"):
			a = Config.raw_data["main"]["server_allow_reuse_address"].lower().strip()
			if a not in ["true", "false"]:
				report_error("Invalid value for configuration key 'server_allow_reuse_address', allowed values are true/false")
			
			Config.server_allow_reuse_address = (a == "true")
		
		
		if not Config.raw_data.has_key("log") or type(Config.raw_data["log"]) is not dict:
			return True
		
		if Config.raw_data["log"].has_key("file"):
			Config.log_file = Config.raw_data["log"]["file"]
			
		if Config.raw_data["log"].has_key("level"):
			Config.log_level = 0
			
			debug_count = 0
			for item in Config.raw_data["log"]["level"].split(' '):
				item = item.lower()
				if item == "debug":
					debug_count+= 1
				if Config.LOGS_FLAGS_ALIASES.has_key(item):
					Config.log_level|= Config.LOGS_FLAGS_ALIASES[item]
			
			if debug_count>1:
				Config.log_level|= Logger.DEBUG_2
				if debug_count>=3:
					Config.log_level|= Logger.DEBUG_3
		
		if Config.raw_data["log"].has_key("thread"):
			Config.log_threaded = (Config.raw_data["log"]["thread"].lower() == "true")
		
		return True
	
	
	@staticmethod
	def is_valid():
		if len(Config.roles) == 0:
			report_error("No role given")
			return False
		
		if Config.session_manager is None:
			report_error("No session manager given")
			return False
		if " " in Config.session_manager:
			report_error("Invalid session manager given")
			return False
	#	if not is_host(Config.session_manager):
	#		return False
		
		if Config.log_file is not None:
			try:
				f = file(Config.log_file, "a")
				f.close()
			except IOError:
				report_error("Unable to write into log file '%s'"%(Config.log_file))
				return False
		
		return True
	
	
	@staticmethod
	def manage_role_aliases(l):
		for item in list(l):
			if not Config.ROLES_ALIASES.has_key(item):
				continue
			
			l.remove(item)
			v = Config.ROLES_ALIASES[item]
			if v not in l:
				l.append(v)
	
	
	@staticmethod
	def parse_list(data):
		return dict.fromkeys(data.split()).keys()
	
	
	@staticmethod
	def get_role_dict(role):
		if not Config.raw_data.has_key(role):
			return {}
		
		return Config.raw_data[role]
