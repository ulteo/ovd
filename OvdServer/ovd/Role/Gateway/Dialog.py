# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010
# Author Laurent CLOUET <laurent@ulteo.com> 2011
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
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

from ovd.Communication.Dialog import Dialog as AbstractDialog

class Dialog(AbstractDialog):
	def __init__(self, role_instance):
		self.role_instance = role_instance
	
	@staticmethod
	def getName():
		return "gateway"
	
	def process(self, request):
		return None
