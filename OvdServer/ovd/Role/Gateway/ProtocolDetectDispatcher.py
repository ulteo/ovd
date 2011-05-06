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

import asyncore
import re

from ovd.Logger import Logger
from receiver import receiver, receiverXMLRewriter
from sender import sender, senderHTTP

from OpenSSL import SSL


class ProtocolDetectDispatcher(asyncore.dispatcher):

	rdp_ptn = re.compile('\x03\x00.*Cookie: .*token=([\-\w]+);.*')
	http_ptn = re.compile('((?:HEAD)|(?:GET)|(?:POST)) (.*) HTTP/(.\..)')

	def __init__(self, conn, f_ctrl, sm, rdp_port):
		asyncore.dispatcher.__init__(self, conn)
		self.f_ctrl = f_ctrl
		self.sm = sm
		self.rdp_port = rdp_port


	def readable(self):
		# hack to support SSL layer
		if self.socket.pending() > 0:
			self.handle_read_event()
		return True


	def writable(self):
		# This class doesn't have to write anything,
		# It's just use to detect the protocol
		return False


	def handle_read(self):
		try:
			r = self.recv(4096)
		except SSL.SysCallError:
			Logger.debug3("ProtocolDetectDispatcher::handle_read SSL.SysCallError")
			self.handle_close()
		except SSL.ZeroReturnError:
			Logger.debug3("ProtocolDetectDispatcher::handle_read SSL.ZeroReturnError")
			self.close()
		except SSL.WantReadError:
			return

		request = r.split('\n', 1)[0]
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
				sender((fqdn, self.rdp_port), receiver(self.socket, r))

			# HTTP case
			elif http:
				Logger.debug("ProtocolDetectDispatcher:: request: http %s" % request)
				path = http.group(2)

				if not (path == '/ovd' or path.startswith("/ovd/")):
					raise Exception('wrong HTTP path: ' + path)

				if path == "/ovd/client/start.php":
					rec = receiverXMLRewriter(self.socket, r, self.f_ctrl)
				else:
					rec = receiver(self.socket, r)
				senderHTTP(self.sm, rec)

			# protocol error
			else:
				raise Exception('bad first request line: ' + request)

		except Exception, err:
			Logger.error("ProtocolDetectDispatcher::handle_read error %s %s" % (type(err), err))
			self.handle_close()
