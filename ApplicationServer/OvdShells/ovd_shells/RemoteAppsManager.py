# -*- coding: utf-8 -*-

# Copyright (C) 2011-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011, 2012
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
import Queue
import time
import threading

from Platform import _platform as Platform
from ovd_shells.OvdAppChannel import OvdAppChannel

class RemoteAppsManager(threading.Thread):
	def __init__(self, instance_manager, vchannel, drives):
		threading.Thread.__init__(self)
		self.im = instance_manager
		self.vchannel = vchannel
		self.drives = drives
		self.use_known_drives = False
		
		self.jobs = Queue.Queue()
		
		try:
			self.encoding = locale.getpreferredencoding()
		except locale.Error, err:
			self.encoding = "UTF-8"
	
	
	def loop(self):
		connected = Platform.rdpSessionIsConnected();
		while True:
			channelConnected = Platform.rdpSessionIsConnected();
			
			if connected and not channelConnected:
				# Client has disconnected
				self.vchannel.Close()
			elif channelConnected and not connected:
				# Client has reconnected
				self.vchannel.Open()
				self.vchannel.Write(OvdAppChannel.getInitPacket())
				for instance in self.im.instances:
					self.vchannel.Write(OvdAppChannel.build_packet_ORDER_STARTED(instance[3], instance[1]))
			
			connected = channelConnected
			if not connected:
				time.sleep(0.5)
				continue
			
			# Read a complete packet
			# so we assume a maximum packet size is 2048
			packet = self.vchannel.Read(2048)
			if packet is None:
				print "error at read"
				time.sleep(0.5)
				continue
			
			(order, data) = OvdAppChannel.parse_packet(packet)
			if order is None:
				print "OvdAppChannel error: %s"%(data)
			
			
			if order == OvdAppChannel.ORDER_START:
				(token, app_id) = data
				
				print "recv startapp order %d %d"%(token, app_id)
				self.jobs.put((order, token, app_id))
			
			elif order == OvdAppChannel.ORDER_START_WITH_ARGS:
				(token, app_id, dir_type, share, path) = data
				
				if dir_type == OvdAppChannel.DIR_TYPE_SHARED_FOLDER:
					dir_type2 = self.im.DIR_TYPE_SHARED_FOLDER
				elif dir_type == OvdAppChannel.DIR_TYPE_RDP_DRIVE:
					dir_type2 = self.im.DIR_TYPE_RDP_DRIVE
				elif dir_type == OvdAppChannel.DIR_TYPE_KNOWN_DRIVES:
					dir_type2 = self.im.DIR_TYPE_KNOWN_DRIVES
				elif dir_type == OvdAppChannel.DIR_TYPE_HTTP_URL:
					dir_type2 = self.im.DIR_TYPE_HTTP_URL
				else:
					print "Message ORDER_START_WITH_ARGS: unknown dir type %X"%(dir_type)
					return
				
				print "recv startapp order %d %d %d %s %s"%(token, app_id, dir_type2, share, path)
				self.jobs.put((order, token, app_id, dir_type2, share, path))
			
			elif order == OvdAppChannel.ORDER_STOP:
				(token) = data
				
				print "recv stop order %d"%(token)
				self.jobs.put((order, token))
			
			elif order == OvdAppChannel.ORDER_EXIT:
				# logoff
				print "recv exit order"
				return
	
	
	def run(self):
		if self.use_known_drives:
			self.drives.rebuild()
			self.vchannel.Write(OvdAppChannel.getDrivesMessage(self.drives.getListUID()))
		
		t_init = 0
		while True:
			t0 = time.time()
			try:
				job = self.jobs.get_nowait()
			except Queue.Empty, e:
				job = None
			except IOError, e:
				if e.errno == 4:
					break
				else:
					raise e
			
			if job is not None:
				print "RemoteApps got job",job
				
				order = job[0]
				if order == OvdAppChannel.ORDER_START:
					(token, app) = job[1:3]
					
					if not self.im.start_app_empty(token, app):
						self.vchannel.Write(OvdAppChannel.build_packet_ORDER_CANT_START(token))
						continue
					
					self.vchannel.Write(OvdAppChannel.build_packet_ORDER_STARTED(app, token))
				  
				  
				elif order == OvdAppChannel.ORDER_START_WITH_ARGS:
					(token, app, dir_type, share, local_path) = job[1:6]
					
					if not self.im.start_app_with_arg(token, app, dir_type, local_path, share):
						self.vchannel.Write(OvdAppChannel.build_packet_ORDER_CANT_START(token))
						continue
					
					self.vchannel.Write(OvdAppChannel.build_packet_ORDER_STARTED(app, token))
				
				
				elif order == OvdAppChannel.ORDER_STOP:
					token = job[1]
					
					if not self.im.stop_app(token):
						continue
					
					self.vchannel.Write(OvdAppChannel.build_packet_ORDER_STOPPED(token))
			
			
			tokens = self.im.get_exited_instances()
			if tokens is not None:
				for token in tokens:
					self.vchannel.Write(OvdAppChannel.build_packet_ORDER_STOPPED(token))
			
			if job is None and len(tokens) == 0:
				time.sleep(0.1)
			
			
			t1 = time.time()
			t_init+= (t1 - t0)
			if t_init > 5:
				# We send channel init time to time to manage the reconnection
				self.vchannel.Write(OvdAppChannel.getInitPacket())
				
				if self.use_known_drives and self.drives.rebuild():
					self.vchannel.Write(OvdAppChannel.getDrivesMessage(self.drives.getListUID()))
				
				t_init = 0
	
	
	def stop(self):
		if self.isAlive():
			self._Thread__stop()
		
		t0 = time.time()
		self.im.kill_all_apps()
		while self.im.has_running_instances():
			if time.time() - t0 > 20:
				print "Still running instances after 20 seconds, exiting anyway ..."
				break
			
			tokens = self.im.get_exited_instances()
			for token in tokens:
				self.vchannel.Write(OvdAppChannel.build_packet_ORDER_STOPPED(token))
			
			if self.im.has_running_instances():
				self.im.kill_all_apps()
				time.sleep(1)
