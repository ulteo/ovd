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
	
	def __init__(self, instance_manager):
		self.im = instance_manager
		try:
			self.encoding = locale.getpreferredencoding()
		except locale.Error, err:
			self.encoding = "UTF-8"
	
	
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
	
	
	def run(self, vchannel):
		while True:
			# Read a complete packet
			# so we assume a maximum packet size is 2048
			packet = vchannel.Read(2048)
			if packet is None:
				print "error at read"
				time.sleep(0.5)
				continue
			
			if len(packet) < 1:
				print "Packet length error"
				continue
		  
			if len(packet) < 1:
				print "Packet length error"
				return None
			
			order = struct.unpack('>B', packet[0])[0]
			if order == self.ORDER_START:
				self.packet_ORDER_START(packet)
			
			elif order == self.ORDER_START_WITH_ARGS:
				self.packet_ORDER_START_WITH_ARGS(packet)
			
			elif order == self.ORDER_STOP:
				self.packet_ORDER_STOP(packet)
			
			elif order == self.ORDER_EXIT:
				# logoff
				return
			
			else:
				print "unknown message %X"%(order)
	
	
	def packet_ORDER_START(self, packet):
		if len(packet) < 9:
			print "Packet length error"
			return
		
		token = struct.unpack('<I', packet[1:5])[0]
		app_id = struct.unpack('<I', packet[5:9])[0]
		
		print "recv startapp order %d %d"%(token, app_id)
		self.im.pushJob((self.ORDER_START, token, app_id))
	
	
	def packet_ORDER_STOP(self, packet):
		if len(packet) < 5:
			print "Packet length error"
			return
		token = struct.unpack('<I', packet[1:5])[0]
		
		print "recv stop order %d"%(token)
		self.im.pushJob((self.ORDER_STOP, token))
	
	
	def packet_ORDER_START_WITH_ARGS(self, packet):
		if len(packet) < 13 + 4 + 4:
			print "Packet length error"
			return
		token = struct.unpack('<I', packet[1:5])[0]
		app_id = struct.unpack('<I', packet[5:9])[0]
		
		dir_type = struct.unpack('>B', packet[9])[0]
		if dir_type not in [self.DIR_TYPE_SHARED_FOLDER, self.DIR_TYPE_RDP_DRIVE, self.DIR_TYPE_KNOWN_DRIVES, self.DIR_TYPE_HTTP_URL]:
			print "Message ORDER_START_WITH_ARGS: unknown dir type %X"%(dir_type)
			return
		
		ptr = 10
		l = struct.unpack('<I', packet[ptr:ptr+4])[0]
		if len(packet) < ptr + l + 4:
			print "Packet length error"
			return
		ptr+= 4
		
		share = packet[ptr:ptr+l]
		try:
			share = share.decode("UTF-16LE")
			share = share.encode(self.encoding)
		except:
			print "Message ORDER_START_WITH_ARGS: share argument is not UTF-16-LE srting"
			return
		ptr+= l
		
		#elif dir_type == self.DIR_TYPE_KNOWN_DRIVES:
			#if len(packet) < ptr + 16:
				#print "Packet length error"
				#return
			#share = packet[ptr:ptr+16]
			#ptr+= 16
		
		#else:
			#print "Message ORDER_START_WITH_ARGS: unknown dir type %X"%(dir_type)
			#return
		
		l = struct.unpack('<I', packet[ptr:ptr+4])[0]
		if len(packet) < ptr + l:
			print "Packet length error"
			return
		ptr+= 4
		
		path = packet[ptr:ptr+l]
		try:
			path = path.decode("UTF-16LE")
			path = path.encode(self.encoding)
		except:
			print "Message ORDER_START_WITH_ARGS: path argument is not UTF-16-LE srting"
			return
		ptr+=l
		
		job = (self.ORDER_START, token, app_id, dir_type, share, path)
		print "recv startapp order %d %d %d %s %s"%(token, app_id, dir_type, share, path)
		self.im.pushJob(job)
