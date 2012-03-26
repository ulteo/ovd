# -*- coding: utf-8 -*-

# Copyright (C) 2010-2012 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud Legrand <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
# Author Julien LANGLOIS <julien@ulteo.com> 2011
# Author David LECHEVALIER <david@ulteo.com> 2012
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

from Communicator import RdpClientCommunicator, RdpServerCommunicator, \
	HttpClientCommunicator, SSLCommunicator
from Config import Protocol
from Config import Config
from ovd.Logger import Logger

from OpenSSL import SSL
import time



class ProtocolException(Exception):
	pass


class ProtocolDetectDispatcher(SSLCommunicator):
	
	rdp_ptn = re.compile('\x03\x00.*Cookie: .*token=([\-\w]+);.*')
	http_ptn = re.compile('((?:HEAD)|(?:GET)|(?:POST)) (.*) HTTP/(.\..)')
	
	def __init__(self, conn, f_ctrl, ssl_ctx):
		SSLCommunicator.__init__(self, conn)
		self.ssl_ctx = ssl_ctx
		self.f_ctrl = f_ctrl
		self.lastPacketTime = time.time()
	
	
	def writable(self):
		# This class doesn't have to write anything,
		# It's just use to detect the protocol
		return False
	
	def readable(self):
		if time.time() - self.lastPacketTime > Config.connection_timeout:
			Logger.error("ProtocolDetectDispatcher::connection timeout")
			self.handle_close()
			return False
		
		return True
	
	def handle_read(self):
		try:
			if SSLCommunicator.handle_read(self) is -1:
				return
		except SSL.Error, e:
			# empty connection opened (chrome for example)
			if e.args[0][0][1] in ['SSL23_READ', 'SSL3_READ_BYTES']:
				self.handle_close()
				return
			else:
				raise
		
		self.lastPacketTime = time.time()
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
				Logger.debug("ProtocolDetectDispatcher:: request: RDP (%s -> %s)" % (token, fqdn))
				if not fqdn:
					raise ProtocolException('token authorization failed for: ' + token)
				
				client = RdpClientCommunicator(self.socket)
				client._buffer = self._buffer
				client.communicator = RdpServerCommunicator((fqdn, Protocol.RDP), communicator=client)
			
			# HTTP case
			elif http:
				client = HttpClientCommunicator(self.socket, self.f_ctrl, self.ssl_ctx)
				client._buffer = self._buffer
				if client.make_http_message() is not None:
					client._buffer = client.process()
			
			# protocol error
			else:
				# Check if the packet size is larger than a common HTTP first line
				if len(self._buffer) > Config.http_max_header_size:
					raise ProtocolException('bad first request line: ' + request)
				return
		
		except ProtocolException, err:
			Logger.error("ProtocolDetectDispatcher::handle_read: %s" % repr(err))
			self.handle_close()
