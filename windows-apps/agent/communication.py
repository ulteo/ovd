#!/usr/bin/python
# -*- coding: UTF-8 -*-

# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2008
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


from SimpleHTTPServer import SimpleHTTPRequestHandler
import cgi
import os
import platform
import base64
from xml.dom.minidom import Document
import wmi
import utils
import pythoncom
if utils.myOS() == "windows":
	from ctypes import *
	from ctypes.wintypes import DWORD
	SIZE_T = c_ulong
	
	class _MEMORYSTATUS(Structure):
		_fields_ = [("dwLength", DWORD),
					("dwMemoryLength", DWORD),
					("dwTotalPhys", SIZE_T),
					("dwAvailPhys", SIZE_T),
					("dwTotalPageFile", SIZE_T),
					("dwAvailPageFile", SIZE_T),
					("dwTotalVirtual", SIZE_T),
					("dwAvailVirtualPhys", SIZE_T)]
		def show(self):
			for field_name, field_type in self._fields_:
				print field_name, getattr(self, field_name)
		
		def TotalPhys(self):
			for field_name, field_type in self._fields_:
				if 'dwTotalPhys' == field_name:
					return int(getattr(self, field_name))/1024
			return 0
		
		def AvailPhys(self):
			for field_name, field_type in self._fields_:
				if 'dwAvailPhys' == field_name:
					return int(getattr(self, field_name))/1024
			return 0
		
		def UsedPhys(self):
			return self.TotalPhys() - self.AvailPhys()


class Web(SimpleHTTPRequestHandler):
	def do_GET(self):
		print 'do_GET ',self.path
		if self.path == "/webservices/server_status.php":
			self.webservices_server_status()
		elif self.path == "/webservices/server_monitoring.php":
			self.webservices_server_monitoring()
		elif self.path == "/webservices/server_type.php":
			self.webservices_server_type()
		elif self.path == "/webservices/server_version.php":
			self.webservices_server_version()
		elif self.path == "/webservices/applications.php":
			self.webservices_applications()
		elif self.path.startswith("/webservices/icon.php"):
			self.webservices_icon()
		
		else:
			self.send_response(404)
			self.send_header('Content-Type', 'text/html')
			self.end_headers()
			self.wfile.write('')
	
	def do_POST(self):
		print 'do_POST ',self.path
		self.send_response(404)
		self.send_header('Content-Type', 'text/html')
		self.end_headers()
		self.wfile.write('')
	
	def log_request(self,l):
		pass
	
	def webservices_server_status(self):
		self.server.log.debug('webservices_server_status')
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/plain')
		self.end_headers()
		self.wfile.write(self.server.daemon.getStatusString())
	
	def webservices_server_monitoring(self):
		self.server.log.debug('webservices_server_monitoring')
		doc = Document()
		monitoring = doc.createElement('monitoring')
		doc.appendChild(monitoring)
		cpu = doc.createElement('cpu')
		try:
			pythoncom.CoInitialize()
			wmi_obj = wmi.WMI()
			cpus = wmi_obj.Win32_Processor()
			if type(cpus) == type([]): # list
				cpu.setAttribute('nb_cores', str(len(cpus)))
				cpu_name = cpus[0].Name
				text = doc.createTextNode(cpu_name)
				cpu.appendChild(text)
				
				load = 0
				for cpu_wmi in cpus:
					load += cpu_wmi.LoadPercentage
				load = load / float(len(cpus)*100)
				cpu.setAttribute('load', str(load))
		except Exception, err:
			pass
		
		monitoring.appendChild(cpu)
		
		memstatus = _MEMORYSTATUS()
		windll.kernel32.GlobalMemoryStatus(byref(memstatus))
		
		ram = doc.createElement('ram')
		ram.setAttribute('total', str(memstatus.TotalPhys()))
		ram.setAttribute('used', str(memstatus.UsedPhys()))
		monitoring.appendChild(ram)
		
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/xml')
		self.end_headers()
		self.wfile.write(doc.toxml())
	
	def webservices_server_type(self):
		self.server.log.debug('webservices_server_type')
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/plain')
		self.end_headers()
		self.wfile.write('windows')
	
	def webservices_server_version(self):
		self.server.log.debug('webservices_server_version')
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/plain')
		self.end_headers()
		self.wfile.write(self.server.daemon.version_os)
	
	def webservices_applications(self):
		self.server.log.debug('webservices_applications')
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/xml')
		self.end_headers()
		self.wfile.write(self.server.daemon.getApplicationsXML())
	
	def webservices_icon(self):
		#print 'webservices_icon'
		#try :
			#args = {}
			#args2 = cgi.parse_qsl(self.path[self.path.index('?')+1:])
			#for (k,v) in args2:
				#args[k] = base64.decodestring(v)
		#except Exception, err:
			#args = {}
		#print 'args ',args
		#if args.has_key('desktopfile'):
			#if args['desktopfile'] != '':
				#if os.path.exists(args['desktopfile']):
					#print 'desktopfile exist'
		
		#if args.has_key('path'):
			#if args['path'] != '':
				#if os.path.exists(args['path']):
					#print 'path exist'
		path_png = 'icon.png'
		f = open(path_png, 'rb')
		self.send_response(200)
		self.send_header('Content-Type', 'image/png')
		self.end_headers()
		self.wfile.write(f.read())
		f.close()


