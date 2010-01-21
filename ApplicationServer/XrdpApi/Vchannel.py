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

import os
import socket
import struct

def _VchannelGetSocket():
	if not os.environ.has_key("DISPLAY"):
		return None
	
	path = "/tmp/channel_socket%s"%(os.environ["DISPLAY"])
	if not os.path.isfile(path):
		return None
	
	# todo if not read/write access ...
	
	return path


def VchannelOpen(name):
	path = _VchannelGetSocket()
	if path is None:
		return False
	
	s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
	s.connect(path)
	
	VchannelWrite(name)
	return s

def VchannelClose(s):
	return s.close()


def VchannelRead(s):
	data = s.recv(4)
	if len(data) == 0:
		return None
	
	size = struct.unpack('>I',data)[0]
	if size == 0:
		return None
	
	data = s.recv(size)
	return data

def VchannelWrite(s, data):
	
	buf = struct.pack('>I', len(data))
	
	s.send(buf)
	s.send(data)
