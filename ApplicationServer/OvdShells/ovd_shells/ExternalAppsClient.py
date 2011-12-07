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
	
	
	def generate_conf_file(self):
		# for now the java client does not support a regular ini file
		#try:
			#configP = ConfigParser.ConfigParser()
			
			#configP.add_section('user')
			#configP.add_section('server')
			
			#configP.set("user", "token", token_)
			#configP.set("server", "host", sm_)
			
			#f = open(path_file_,'w')
			#configP.write(f)
			#f.close() # ..??
			#return True
		#except Exception, err:
			#return False
		try:
			f = open(self.configuration_file,'w')
			f.write("[token]\ntoken=%s\n\n[server]\nhost=%s\n\n"%(self.token, self.sm))
			f.close()
		except Exception, err:
			print "err ",err
			return False
		
		return True
	
	
	def launch(self):
		raise NotImplementedError("must be redeclared")
