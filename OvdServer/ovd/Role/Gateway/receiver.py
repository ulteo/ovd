# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
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

from Communicator import SSLCommunicator
import XML


class receiver(SSLCommunicator):

	def __init__(self, conn, req):
		SSLCommunicator.__init__(self, conn)
		self._buffer = req



class receiverXMLRewriter(receiver):

	def __init__(self, conn, req, f_ctrl):
		receiver.__init__(self, conn, req)
		self.hasRewrited = False
		self.f_ctrl = f_ctrl


	def writable(self):
		if len(self.communicator._buffer) == 0:
			return False

		if self.hasRewrited:
			return True

		if XML.response_ptn.search(self.communicator._buffer):
			self.hasRewrited = True
			return True

		xml = XML.session_ptn.search(self.communicator._buffer)
		if xml:
			self.communicator._buffer = XML.rewrite(self.communicator._buffer, xml, self.f_ctrl)
			self.hasRewrited = True
		return bool(xml)
