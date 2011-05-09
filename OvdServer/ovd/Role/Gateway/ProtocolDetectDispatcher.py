# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
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

import re

from Communicator import SSLCommunicator
from ovd.Logger import Logger
from Communicator import \
	ClientCommunicator, ClientCommunicatorRewriter, \
	OvdServerCommunicator, SessionManagerCommunicator


class ProtocolDetectDispatcher(SSLCommunicator):

	rdp_ptn = re.compile('\x03\x00.*Cookie: .*token=([\-\w]+);.*')
	http_ptn = re.compile('((?:HEAD)|(?:GET)|(?:POST)) (.*) HTTP/(.\..)')

	def __init__(self, conn, f_ctrl, sm, rdp_port):
		SSLCommunicator.__init__(self, conn)
		self.f_ctrl = f_ctrl
		self.sm = sm
		self.rdp_port = rdp_port


	def writable(self):
		# This class doesn't have to write anything,
		# It's just use to detect the protocol
		return False


	def handle_read(self):
		if SSLCommunicator.handle_read(self) is -1:
			return

		request = self._buffer.split('\n', 1)[0]
		request = request.rstrip('\n\r').decode("utf-8", "replace")

		# find protocol
		rdp  = ProtocolDetectDispatcher.rdp_ptn.match(request)
		http = ProtocolDetectDispatcher.http_ptn.match(request)

		try:
			# RDP case
			if rdp:
				token = rdp.group(1)
				fqdn = self.f_ctrl.send(("digest_token", token))
				if not fqdn:
					raise Exception('token authorization failed for: ' + token)
				OvdServerCommunicator((fqdn, self.rdp_port), ClientCommunicator(self.socket, self._buffer))

			# HTTP case
			elif http:
				Logger.debug("ProtocolDetectDispatcher:: request: http %s" % request)
				path = http.group(2)

				if not (path == '/ovd' or path.startswith("/ovd/")):
					raise Exception('wrong HTTP path: ' + path)

				if path == "/ovd/client/start.php":
					cli = ClientCommunicatorRewriter(self.socket, self._buffer, self.f_ctrl)
				else:
					cli = ClientCommunicator(self.socket, self._buffer)
				SessionManagerCommunicator(self.sm, cli)

			# protocol error
			else:
				raise Exception('bad first request line: ' + request)

		except Exception, err:
			Logger.error("ProtocolDetectDispatcher::handle_read error %s %s" % (type(err), err))
			self.handle_close()
