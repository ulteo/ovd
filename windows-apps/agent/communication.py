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
import sys
import platform
import base64
from xml.dom.minidom import Document
import wmi
import utils
import pythoncom
import Image
import tempfile
import servicemanager
from win32com.shell import shell
import pythoncom
import traceback

def log_debug(msg_):
	servicemanager.LogInfoMsg(str(msg_))

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
		try:
			self.server.daemon.log.debug("do_GET "+self.path)
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
			elif self.path == "/webservices/test.php":
				t = 5/0
				
			else:
				self.send_response(404)
				self.send_header('Content-Type', 'text/html')
				self.end_headers()
				self.wfile.write('')
			
		except Exception, err:
			exception_type, exception_string, tb = sys.exc_info()
			trace_exc = "".join(traceback.format_tb(tb))
			self.server.daemon.log.debug("do_GET error %s %s"%(trace_exc, str(exception_string)))
			log_debug("do_GET error %s %s"%(trace_exc, str(exception_string)))
	
	def log_request(self,l):
		pass
	
	def webservices_server_status(self):
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/plain')
		self.end_headers()
		self.wfile.write(self.server.daemon.getStatusString())
	
	def webservices_server_monitoring(self):
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
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/plain')
		self.end_headers()
		self.wfile.write('windows')
	
	def webservices_server_version(self):
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/plain')
		self.end_headers()
		self.wfile.write(self.server.daemon.version_os)
	
	def webservices_applications(self):
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/xml')
		self.end_headers()
		self.wfile.write(self.server.daemon.getApplicationsXML())
	
	def webservices_icon(self):
		try :
			args = {}
			args2 = cgi.parse_qsl(self.path[self.path.index('?')+1:])
			for (k,v) in args2:
				args[k] = base64.decodestring(v)
		except Exception, err:
			args = {}
		
		if args.has_key('desktopfile'):
			if args['desktopfile'] != '':
				if os.path.exists(args['desktopfile']):
					pythoncom.CoInitialize()
					shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
					shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(args['desktopfile'])
					if ( shortcut.GetPath(0)[0][-3:] == "exe"):
						exe_file = shortcut.GetPath(0)[0]
						path_bmp = tempfile.mktemp()+'.bmp'
						
						command = """"%s" "%s" "%s" """%(os.path.join(self.server.daemon.install_dir, 'extract_icon.exe'), exe_file, path_bmp)
						p = utils.Process()
						status = p.run(command)
						self.server.daemon.log.debug("status of extract_icon %s (command %s)"%(status, command))
						
						if os.path.exists(path_bmp):
							path_png = tempfile.mktemp()+'.png'
							im = Image.open(path_bmp)
							im.save(path_png)
							
							f = open(path_png, 'rb')
							self.send_response(200)
							self.send_header('Content-Type', 'image/png')
							self.end_headers()
							self.wfile.write(f.read())
							f.close()
							os.remove(path_bmp)
							os.remove(path_png)
						else :
							self.server.daemon.log.debug("webservices_icon error 500")
							self.send_response(500)
					else :
						self.server.daemon.log.debug("webservices_icon send default icon")
						f = open('icon.png', 'rb')
						self.send_response(200)
						self.send_header('Content-Type', 'image/png')
						self.end_headers()
						self.wfile.write(f.read())
						f.close()
				else:
					self.server.daemon.log.debug("webservices_icon no right argument1")
			else:
				self.server.daemon.log.debug("webservices_icon no right argument2" )
		else:
			self.server.daemon.log.debug("webservices_icon no right argument3" )
			


