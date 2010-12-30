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


from DomainUlteo import DomainUlteo as Domain

class DomainNovell(Domain):
	def __init__(self):
		Domain.__init__(self)
		self.account = {}
		self.zenworks = False
	
	
	def parse(self, node):
		if node.hasAttribute("dlu"):
			self.zenworks = True
			return True
		
		try:
			for item in ["login", "password", "tree", "server"]:
				self.account[item] = node.getAttribute(item)
		except Exception, err:
			return False
		
		return True
	
	
	def manage_user(self):
		return not self.zenworks
