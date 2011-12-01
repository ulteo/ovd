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

import ConfigParser
import os

from ovd import Config as ConfigModule

class ConfigReader:
	@classmethod
	def process(cls, filename = None):
		raise NotImplementedError()
	
	
	@classmethod
	def read_ini(cls, filename):
		if not os.path.isfile(filename):
			ConfigModule.report_error("No such file '%s'"%(filename))
			return False
		
		parser = ConfigParser.ConfigParser()
		parser.read(filename)
		
		data = {}
		
		for section in parser.sections():
			data[section] = {}
			for k,v in parser.items(section):
				data[section][k] = v
		
		return data
