# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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

from ovd.Role.Role import Role as AbstractRole
from ovd.Config import Config
from ovd.Logger import Logger
from Dialog import Dialog
from reverseproxy import *

import os


class Role(AbstractRole):

	@staticmethod
	def getName():
		return "Gateway"


	def __init__(self, main_instance):
		AbstractRole.__init__(self, main_instance)
		self.dialog = Dialog(self)
		self.has_run = False

		self.HTTPS_PORT = 443
		self.RDP_PORT = 3389

		self.rproxy = None


	def init(self):
		Logger.info("Gateway init")

		self.pem = os.path.join(Config.conf_dir, "gateway.pem")
		if os.path.exists(self.pem):
			Logger.error("Gateway role need a certificate (%s)" % self.pem)
			return False

		return True


	def stop(self):
		self.has_run = False
		if self.rproxy:
			self.rproxy.close()
		asyncore.ExitNow()


	def run(self):
		self.has_run = True
		self.rproxy = ReverseProxy(self.pem, self.HTTPS_PORT, Config.session_manager, self.HTTPS_PORT, self.RDP_PORT)
		Logger.info('Gateway:: running')
		self.status = Role.STATUS_RUNNING
		asyncore.loop()


	def getReporting(self, node):
		pass
