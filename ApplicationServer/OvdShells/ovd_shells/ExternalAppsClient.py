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

class ExternalAppsClient:
	def __init__(self, directory_):
		self.directory = directory_
		self.sm = None
		self.token = None
		self.configuration_file = os.path.join(os.path.expanduser('~'), ".integrateclient.conf")
		self.log_file = os.path.join(self.directory, "dump-externalapps.txt")
	
	
	def load_config(self):
		sm_file = os.path.join(self.directory, "sm")
		token_file = os.path.join(self.directory, "token")
		
		if not os.path.exists(sm_file):
			return False
		
		f = open(sm_file, "r")
		self.sm = f.readline().strip()
		f.close()
		
		if not os.path.exists(token_file):
			return False
		
		f = open(token_file, "r")
		self.token = f.readline().strip()
		f.close()
		
		return True
	
	
	def start(self):
		cmd = self.get_base_command()
		if cmd is None:
			return False
		
		cmd = self.get_final_command(cmd)
		
		try:
			ret = self.launch(cmd)
		except Exception, err:
			print "Unable to start external apps", err
			return False
		
		return ret is True
	
	
	def launch(self, cmd):
		raise NotImplementedError("must be redeclared")
	
	
	@classmethod
	def get_base_command(cls):
		raise NotImplementedError("must be redeclared")
	
	
	def get_final_command(self, base_cmd):
		return '%s -s "%s" -t "%s" -o "%s"'%(base_cmd, self.sm, self.token, self.log_file)
