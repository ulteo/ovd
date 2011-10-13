# -*- coding: utf-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2011
# Author David LECHEVALIER <david@ulteo.com> 2010
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

import ConfigParser
import os
import sys

from ovd.Logger import Logger
from ovd.Platform import Platform

def report_error(message):
	print >>sys.stderr, message


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
	log_file = os.path.join(Platform.System.get_default_log_dir(), "slaveserver.log")
	log_threaded = False
	
	conf_dir = Platform.System.get_default_config_dir()
	spool_dir = Platform.System.get_default_spool_dir()
	
	# OVD servers communication
	session_manager = None
	SM_SERVER_PORT = 1111
	SLAVE_SERVER_PORT = 1112
	
	server_allow_reuse_address = Platform.System.tcp_server_allow_reuse_address()
	
	@staticmethod
	def read(filename):
		Config.parser = ConfigParser.ConfigParser()
		try:
			Config.parser.read(filename)
		except Exception, err:
			report_error("invalid configuration file '%s'"%(filename))
			report_error(str(err))
			return False
		
		if Config.parser.has_option("main", "roles"):
			Config.roles = []
			buf = Config.parser.get("main", "roles").split(' ')
			for b in buf:
				b = b.strip()
				if len(b)==0:
					continue
				
				if Config.ROLES_ALIASES.has_key(b):
					b = Config.ROLES_ALIASES[b]
				
				if b in Config.roles:
					continue
				
				Config.roles.append(b)
		
		if Config.parser.has_option("main", "session_manager"):
			Config.session_manager = Config.parser.get("main", "session_manager")
		
		if Config.parser.has_option("log", "file"):
			Config.log_file = Config.parser.get("log", "file")
		
		if Config.parser.has_option("main", "server_allow_reuse_address"):
			a = Config.parser.get("server_allow_reuse_address").lower().strip()
			if a not in ["true", "false"]:
				report_error("invalid value for configuration key 'server_allow_reuse_address', allowed values are true/false")
			
			Config.server_allow_reuse_address = (a == "true")
		
		if Config.parser.has_option("log", "level"):
			Config.log_level = 0
			
			debug_count = 0
			for item in Config.parser.get("log", "level").split(' '):
				item = item.lower()
				if item == "debug":
					debug_count+= 1
				if Config.LOGS_FLAGS_ALIASES.has_key(item):
					Config.log_level|= Config.LOGS_FLAGS_ALIASES[item]
			
			if debug_count>1:
				Config.log_level|= Logger.DEBUG_2
				if debug_count>=3:
					Config.log_level|= Logger.DEBUG_3
		
		if Config.parser.has_option("log", "thread"):
			Config.log_threaded = (Config.parser.get("log", "thread").lower() == "true")
		
		return True
	
	
	@staticmethod
	def is_valid():
		if len(Config.roles) == 0:
			report_error("No role given")
			return False
		
		for role in Config.roles:
			try:
				__import__("ovd.Role.%s.Role"%(role))
				role_config = __import__("ovd.Role.%s.Config"%(role), {}, {}, "Config")
			
			except ImportError:
				import traceback
				print traceback.format_exc()
				report_error("Unsupported role '%s'."%(role))
				report_error("Please be sure this role exists and is correctly installed")
				return False
			
			infos = {}
			if Config.parser.has_section(role):
				infos = dict(Config.parser.items(role))
			
			if not role_config.Config.init(infos):
				return False
			role_config.Config.general = Config
		
		
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
				report_error("Unable to write into '%s'"%(Config.log_file))
				return False
		
		return True
	
