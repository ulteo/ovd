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

import ctypes
import threading
import win32event

from ovd_shells.VirtualChannel import VirtualChannel as AbstractVirtualChannel

class VirtualChannel(AbstractVirtualChannel):
	def __init__(self, name_):
		AbstractVirtualChannel.__init__(self, name_)
		
		self.dll = ctypes.windll.LoadLibrary("Wtsapi32.dll")
		self._handle = None
		self.mutex = threading.Lock()
		
	def Open(self):
		self.mutex.acquire()
		self._handle =  self.dll.WTSVirtualChannelOpen(0, -1, self.name)
		self.mutex.release()
		
		return self._handle != None
	
	def Close(self):
		if self._handle is not None:
			self.mutex.acquire()
			self._handle =  self.dll.WTSVirtualChannelClose(self._handle)
			self.mutex.release()
		
	
	def Read(self, size):
		buffer = ctypes.create_string_buffer(size)
		buffer_len = ctypes.c_ulong(size)
		bytes_read = ctypes.c_ulong()
		
		while True:
			self.mutex.acquire()
			ret = self.dll.WTSVirtualChannelRead(self._handle, 500, ctypes.byref(buffer), buffer_len, ctypes.byref(bytes_read))
			self.mutex.release()
			if ret>0:
				return buffer.raw
	
	def Write(self, message):
		buffer = ctypes.create_string_buffer(len(message))
		buffer.raw = message
		buffer_len = ctypes.c_ulong(len(message))
		byte_written = ctypes.c_ulong()
		
		
		self.mutex.acquire()
		ret = self.dll.WTSVirtualChannelWrite(self._handle, buffer, buffer_len, ctypes.byref(byte_written))
		self.mutex.release()
		
		return True
