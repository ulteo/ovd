# -*- coding: utf-8 -*-

# Copyright (C) 2010-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
# Author Thomas MOUTON <thomas@ulteo.com> 2012
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

import locale
import struct
import time


class OvdAppChannel:
	NAME = "ovdapp"
	
	ORDER_INIT	= 0x00
	ORDER_START	= 0x01
	ORDER_STARTED	= 0x02
	ORDER_STOPPED	= 0x03
	ORDER_EXIT	= 0x04
	ORDER_STOP	= 0x05
	ORDER_CANT_START= 0x06
	ORDER_START_WITH_ARGS = 0x07
	ORDER_KNOWN_DRIVES  = 0x20
	
	DIR_TYPE_SHARED_FOLDER = 0X01
	DIR_TYPE_RDP_DRIVE     = 0x02
	DIR_TYPE_KNOWN_DRIVES  = 0x03
	DIR_TYPE_HTTP_URL      = 0x10
	
	
	@staticmethod
	def getInitPacket():
		return struct.pack(">B", OvdAppChannel.ORDER_INIT)
	
	
	@staticmethod
	def getDrivesMessage(uids):
		buf = struct.pack(">B", OvdAppChannel.ORDER_KNOWN_DRIVES)
		buf+= struct.pack("<I", len(uids))
		for uid in uids:
			d = uid.encode("UTF-16LE")
			buf+= struct.pack("<I", len(d))
			buf+= d
		
		return buf
	
	
	@classmethod
	def build_packet_ORDER_CANT_START(cls, token):
		buf = struct.pack("<B", cls.ORDER_CANT_START)
		buf+= struct.pack("<I", token)
		
		return buf
	
	
	@classmethod
	def build_packet_ORDER_STARTED(cls, app_id, token):
		buf = struct.pack("<B", cls.ORDER_STARTED)
		buf+= struct.pack("<I", app_id)
		buf+= struct.pack("<I", token)
		
		return buf
	
	
	@classmethod
	def build_packet_ORDER_STOPPED(cls, token):
		buf = struct.pack("<B", cls.ORDER_STOPPED)
		buf+= struct.pack("<I", token)
		
		return buf
	
	
	@classmethod
	def parse_packet(cls, packet):
		if len(packet) < 1:
			return (None, "Packet length error")
		
		order = struct.unpack('>B', packet[0])[0]
		if order == OvdAppChannel.ORDER_START:
			return cls.packet_ORDER_START(packet)
		
		elif order == OvdAppChannel.ORDER_START_WITH_ARGS:
			return cls.packet_ORDER_START_WITH_ARGS(packet)
		
		elif order == OvdAppChannel.ORDER_STOP:
			return cls.packet_ORDER_STOP(packet)
		
		elif order == OvdAppChannel.ORDER_EXIT:
			return (OvdAppChannel.ORDER_EXIT, None)
		
		return (None, "unknown message %X"%(order))
	
	
	@classmethod
	def packet_ORDER_START(cls, packet):
		if len(packet) < 9:
			return (None, "Packet length error")
		
		token = struct.unpack('<I', packet[1:5])[0]
		app_id = struct.unpack('<I', packet[5:9])[0]
		
		return (cls.ORDER_START, (token, app_id))
	
	
	@classmethod
	def packet_ORDER_STOP(cls, packet):
		if len(packet) < 5:
			return (None, "Packet length error")
		
		token = struct.unpack('<I', packet[1:5])[0]
		
		return (cls.ORDER_STOP, (token))
	
	
	@classmethod
	def packet_ORDER_START_WITH_ARGS(cls, packet):
		try:
			encoding = locale.getpreferredencoding()
		except locale.Error, err:
			encoding = "UTF-8"
		
		if len(packet) < 13 + 4 + 4:
			return (None, "Packet length error")
		
		token = struct.unpack('<I', packet[1:5])[0]
		app_id = struct.unpack('<I', packet[5:9])[0]
		
		dir_type = struct.unpack('>B', packet[9])[0]
		if dir_type not in [cls.DIR_TYPE_SHARED_FOLDER, cls.DIR_TYPE_RDP_DRIVE, cls.DIR_TYPE_KNOWN_DRIVES, cls.DIR_TYPE_HTTP_URL]:
			return (None, "Message ORDER_START_WITH_ARGS: unknown dir type %X"%(dir_type))
		
		ptr = 10
		l = struct.unpack('<I', packet[ptr:ptr+4])[0]
		if len(packet) < ptr + l + 4:
			return (None, "Packet length error")
		
		ptr+= 4
		
		share = packet[ptr:ptr+l]
		try:
			share = share.decode("UTF-16LE")
			share = share.encode(encoding)
		except:
			return (None, "Message ORDER_START_WITH_ARGS: share argument is not UTF-16-LE srting")
		
		ptr+= l
		
		#elif dir_type == self.DIR_TYPE_KNOWN_DRIVES:
			#if len(packet) < ptr + 16:
				#return (None, "Packet length error")
			#
			#share = packet[ptr:ptr+16]
			#ptr+= 16
		
		#else:
			#return (None, "Message ORDER_START_WITH_ARGS: unknown dir type %X"%(dir_type))
		
		l = struct.unpack('<I', packet[ptr:ptr+4])[0]
		if len(packet) < ptr + l:
			return (None, "Packet length error")
		
		ptr+= 4
		
		path = packet[ptr:ptr+l]
		try:
			path = path.decode("UTF-16LE")
			path = path.encode(encoding)
		except:
			return (None, "Message ORDER_START_WITH_ARGS: path argument is not UTF-16-LE srting")
		
		ptr+=l
		
		return (cls.ORDER_START_WITH_ARGS, (token, app_id, dir_type, share, path))
