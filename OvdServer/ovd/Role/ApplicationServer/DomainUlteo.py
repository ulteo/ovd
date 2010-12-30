# -*- coding: UTF-8 -*-

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


class DomainUlteo:
	def __init__(self):
		self.session = None
	
	
	def parse(self, node):
		return True
	
	
	def setSession(self, session):
		self.session = session
	
	def manage_user(self):
		return True
	
	
	def onSessionCreate(self):
		return True
	
	def onSessionStarts(self):
		return True
	
	def onSessionEnd(self):
		return True
