# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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

class VirtualChannel:
	def __init__(self, name_):
		self.name = name_
		
	def Open(self):
		""" must be redeclared"""
		
		return False
	
	def Close(self):
		""" must be redeclared"""
		
		return False
		
	
	def Read(self, size):
		""" must be redeclared"""
		
		return False
	
	def Write(self, message):
		""" must be redeclared"""
		
		return False
