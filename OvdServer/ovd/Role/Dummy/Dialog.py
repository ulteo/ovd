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

from ovd.Communication.Dialog import Dialog as AbstractDialog
from ovd.Logger import Logger

class Dialog(AbstractDialog):
	def __init__(self, role_instance):
		self.role_instance = role_instance
	
	@staticmethod
	def getName():
		return "dummy"
	
	def process(self, request):
		Logger.info("Dummy role Dialog::process(%s)"%(str(request)))
		return None
