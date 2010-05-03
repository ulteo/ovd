#!/usr/bin/python
# -*- coding: UTF-8 -*-

# Copyright (C) 2008,2009 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2008,2009
# Author Julien LANGLOIS <julien@ulteo.com> 2009
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
import base64
from xml.dom.minidom import Document
import utils
import pythoncom
import tempfile
import servicemanager
from win32com.shell import shell
import threading
import traceback
import win32api
import win32ts
from Logger import Logger

class Web(SimpleHTTPRequestHandler):
	def do_GET(self):
		root_dir = '/applicationserver'
		try:
			if self.server.daemon.isSessionManagerRequest(self.client_address[0]) == False:
				self.response_error(401)
				return
			
			Logger.debug("do_GET "+self.path)
			if self.path == root_dir+"/webservices/server_status.php":
				self.webservices_server_status()
			elif self.path == root_dir+"/webservices/server_monitoring.php":
				self.webservices_server_monitoring()
			elif self.path == root_dir+"/webservices/server_type.php":
				self.webservices_server_type()
			elif self.path == root_dir+"/webservices/server_version.php":
				self.webservices_server_version()
			elif self.path == root_dir+"/webservices/applications.php":
				self.webservices_applications()
			elif self.path.startswith(root_dir+"/webservices/icon.php"):
				self.webservices_icon()
			elif self.path.startswith(root_dir+"/webservices/server_log.php"):
				self.webservices_server_log()
			else:
				self.response_error(404)
				return
			
		except Exception, err:
			exception_type, exception_string, tb = sys.exc_info()
			trace_exc = "".join(traceback.format_tb(tb))
			Logger.debug("do_GET error '%s' '%s'"%(trace_exc, str(exception_string)))
	
	def do_POST(self):
		root_dir = '/applicationserver/webservices'
		try:
			if self.server.daemon.isSessionManagerRequest(self.client_address[0]) == False:
				self.response_error(401)
				return
			
			if self.path.startswith(root_dir+"/loggedin"):
				self.webservices_loggedin(self.path[len(root_dir+"/loggedin"):])
			elif self.path.startswith(root_dir+"/logoff"):
				self.webservices_logoff(self.path[len(root_dir+"/logoff"):])
			else:
				self.response_error(404)
				return
			
		except Exception, err:
			exception_type, exception_string, tb = sys.exc_info()
			trace_exc = "".join(traceback.format_tb(tb))
			Logger.debug("do_POST error '%s' '%s'"%(trace_exc, str(exception_string)))
	
	def response_error(self, code):
		self.send_response(code)
		self.send_header('Content-Type', 'text/html')
		self.end_headers()
		self.wfile.write('')
	
	def log_request(self,l):
		pass
	
	@staticmethod
	def error2xml(code, message=None):
		doc = Document()
		
		rootNode = doc.createElement("error")
		rootNode.setAttribute("id", code)
		
		if message is not None:
			textNode = doc.createTextNode(message)
			rootNode.appendChild(textNode)
		
		doc.appendChild(rootNode)
		return doc
	
	def webservices_answer(self, content):
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/xml')
		self.end_headers()
		self.wfile.write(content.toprettyxml())
		return
	
	def webservices_server_status(self):
		self.send_response(200, 'OK')
		self.send_header('Content-Type', 'text/plain')
		self.end_headers()
		self.wfile.write(self.server.daemon.getStatusString())
	
	def webservices_server_monitoring(self):
		doc = self.server.daemon.xmlMonitoring()
		
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
				args[k] = base64.decodestring(v).decode('utf-8')
		except Exception, err:
			args = {}
		
		if args.has_key('desktopfile'):
			if args['desktopfile'] != '':
				if os.path.exists(args['desktopfile']):
					pythoncom.CoInitialize()
					shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
					shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(args['desktopfile'])
					if (os.path.splitext(shortcut.GetPath(0)[0])[1].lower() == ".exe"):
						exe_file = shortcut.GetPath(0)[0]
						path_bmp = tempfile.mktemp()+'.bmp'
						
						command = """"%s" "%s" "%s" """%(os.path.join(self.server.daemon.install_dir, 'extract_icon.exe'), exe_file, path_bmp)
						p = utils.Process()
						status = p.run(command)
						Logger.debug("status of extract_icon %s (command %s)"%(status, command))
						
						if os.path.exists(path_bmp):
							path_png = tempfile.mktemp()+'.png'
							
							command = """"%s" -Q -O "%s" "%s" """%(os.path.join(self.server.daemon.install_dir, 'bmp2png.exe'), path_png, path_bmp)
							p = utils.Process()
							status = p.run(command)
							Logger.debug("status of bmp2png %s (command %s)"%(status, command))
							
							f = open(path_png, 'rb')
							self.send_response(200)
							self.send_header('Content-Type', 'image/png')
							self.end_headers()
							self.wfile.write(f.read())
							f.close()
							os.remove(path_bmp)
							os.remove(path_png)
						else :
							Logger.debug("webservices_icon error 500")
							self.send_response(500)
					else :
						Logger.debug("webservices_icon send default icon")
						f = open('icon.png', 'rb')
						self.send_response(200)
						self.send_header('Content-Type', 'image/png')
						self.end_headers()
						self.wfile.write(f.read())
						f.close()
				else:
					Logger.debug("webservices_icon no right argument1")
			else:
				Logger.debug("webservices_icon no right argument2" )
		else:
			Logger.debug("webservices_icon no right argument3" )
	
	def webservices_server_log(self):
		try :
			args = {}
			args2 = cgi.parse_qsl(self.path[self.path.index('?')+1:])
			for (k,v) in args2:
				args[k] = v.decode('utf-8')
		except Exception, err:
			Logger.debug("webservices_server_log error decoding args %s"%(err))
			args = {}
		
		if args.has_key('type'):
			if args['type'] == 'web':
				if os.path.isfile(self.server.daemon.conf["log_file"]):
					f = open(self.server.daemon.conf["log_file"], 'rb')
					self.send_response(200)
					self.send_header('Content-Type', 'text/plain')
					self.end_headers()
					self.wfile.write(f.read())
					f.close()
				else:
					self.send_header('Content-Type', 'text/plain')
					self.end_headers()
					self.wfile.write('')
			else :
				Logger.debug("webservices_server_log errorA 400")
				self.send_response(400)
		else :
			Logger.debug("webservices_server_log errorB 400")
			self.send_response(400)
	
	def webservices_loggedin(self, arg):
		try:
			_, login, domain = arg.split("/", 3)
		except:
			Logger.debug("webservices_loggedin: usage error not enough argument")
			return self.webservices_answer(self.error2xml("usage"))
		
		if len(login) == 0:
			Logger.debug("webservices_loggedin: usage error empty login")
			return self.webservices_answer(self.error2xml("usage"))
		
		if len(domain) == 0:
			domain = "local"
		
		Logger.debug("webservices_loggedin: login '%s'"%(login))
		found = False
		sessions = win32ts.WTSEnumerateSessions(None)
		for session in sessions:
			if not 0 < session["SessionId"] < 65536:
				continue
			
			l_ = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
			if login != l_:
				continue
				
			d_ = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSDomainName)
			computerName = win32api.GetComputerName()
			if d_.lower() == computerName.lower():
				if domain == "ad":
					continue
			else:
				if domain == "local":
					continue
			
			found = True
			break
		
		doc = Document()
		rootNode = doc.createElement("user")
		rootNode.setAttribute("id", login)
		rootNode.setAttribute("loggedin", str(found).lower())
		doc.appendChild(rootNode)
		return self.webservices_answer(doc)
	
	def webservices_logoff(self, arg):
		try:
			_, login, domain = arg.split("/", 3)
		except:
			Logger.debug("webservices_loggedin: usage error not enough argument")
			return self.webservices_answer(self.error2xml("usage"))
		
		if len(login) == 0:
			Logger.debug("webservices_loggedin: usage error empty login")
			return self.webservices_answer(self.error2xml("usage"))
		
		if len(domain) == 0:
			domain = "local"
		
		Logger.debug("webservices_logoff: login '%s'"%(login))
		found = None
		sessions = win32ts.WTSEnumerateSessions(None)
		for session in sessions:
			if not 0 < session["SessionId"] < 65536:
				continue
			
			l_ = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
			if login != l_:
				continue
			
			d_ = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSDomainName)
			computerName = win32api.GetComputerName()
			if d_.lower() == computerName.lower():
				if domain == "ad":
					continue
			else:
				if domain == "local":
					continue
			
			found = session["SessionId"]
			break;
		
		if found is None:
			return self.webservices_answer(self.error2xml("not found"))
		
		doc = Document()
		rootNode = doc.createElement("session")
		rootNode.setAttribute("id", str(found))
		
		Logger.debug("webservices_logoffADUser: start thread logoff")
		th = threading.Thread(target=self.perform_logoff, args=[session["SessionId"]])
		th.start()
		rootNode.setAttribute("status", "logged off")
		
		doc.appendChild(rootNode)
		return self.webservices_answer(doc)
	
	
	def perform_logoff(self, session_id):
		try:
			Logger.debug("perform_logoff: start logoff %d"%(session_id))
			ret = win32ts.WTSLogoffSession(None, session_id, True)
			Logger.debug("perform_logoff: finish logoff %d ret: %s"%(session_id, str(ret)))
		except Exception, e:
			Logger.debug("perform_logoff: exception %s"%(e))

