# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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
import subprocess
import sys

class ExternalAppsClient:
	def __init__(self, directory_):
		self.directory = directory_
		self.sm = None
		self.token = None
		self.log_file = os.path.join(self.directory, "dump-externalapps.txt")
	
	
	def load_config(self, config):
		if config.sm is None:
			return False
		
		if config.external_apps_token is None:
			return False
		
		self.sm = config.sm
		self.token = config.external_apps_token
		
		return True
	
	
	def start(self):
		cmd = self.get_base_command()
		if cmd is None:
			return False
		
		cmd = self.get_final_command(cmd)
		
		folder = os.curdir
		if self.need_specific_working_directory():
			folder = self.get_working_directory()
			if folder is None:
				print "folder is none"
				return False
		
		try:
			log_stream = file(self.log_file, "a")
		except IOError:
			print "Unable to open log file %s"%(self.log_file)
			log_stream = sys.stdout
		
		my_env = self.get_env()
		p = subprocess.Popen(args = cmd, env = my_env, shell = True, cwd = folder, stdout = log_stream, stderr = subprocess.STDOUT)
		return True
	
	
	def launch(self, cmd):
		raise NotImplementedError("must be redeclared")
	
	
	def get_env(cls):
		raise NotImplementedError("must be redeclared")
	
	
	@classmethod
	def get_base_command(cls):
		raise NotImplementedError("must be redeclared")
	
	
	def get_final_command(self, base_cmd):
		return '%s -s "%s" -t "%s"'%(base_cmd, self.sm, self.token)
	
	
	@classmethod
	def need_specific_working_directory(cls):
		raise NotImplementedError("must be redeclared")
	
	
	@classmethod
	def get_working_directory(cls):
		raise NotImplementedError("must be redeclared")
