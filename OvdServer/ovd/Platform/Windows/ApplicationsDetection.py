# -*- coding: UTF-8 -*-
# Copyright (C) 2009 Ulteo SAS
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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

import locale
import md5
import os
import pythoncom
from win32com.shell import shell, shellcon

from ovd.Logger import Logger
import mime
from Msi import Msi


class ApplicationsDetection:
	def __init__(self):
		self.mimetypes = mime.MimeInfos()
		
		try:
			self.msi = Msi()
		except WindowsError,e:
			Logger.warn("getApplicationsXML_nocache: Unable to init Msi")
			self.msi = None
			
		pythoncom.CoInitialize()

		try:
			self.path = str(shell.SHGetSpecialFolderPath(None, shellcon.CSIDL_COMMON_STARTMENU))
		except:
			Logger.error("getApplicationsXML_nocache : no  ALLUSERSPROFILE key in environnement")
			self.path = os.path.join('c:\\', 'Documents and Settings', 'All Users', 'Start Menu')

	def find_lnk(self):
		ret = []
		for root, dirs, files in os.walk(self.path):
			for name in files:
				l = os.path.join(root,name)
				if os.path.isfile(l) and l[-3:] == "lnk":
					ret.append(l)
					
		return ret
	
	@staticmethod
	def isBan(name_):
		name = name_.lower()
		for ban in ['uninstall', 'update']:
			if ban in name:
				return True
		return False

	@staticmethod
	def _compare_commands(cm1, cm2):
		if cm1.lower().find(cm2.lower()) != -1:
			return True
		return False

	def get(self):
		applications = {}
		
		(_, encoding) = locale.getdefaultlocale()
		
		files = self.find_lnk()
		
		for filename in files:
			shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
			shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(filename)
			
			if not os.path.splitext(shortcut.GetPath(0)[0])[1].lower() == ".exe":
				continue
			
			
			name = os.path.basename(filename)[:-4]
			if self.isBan(name):
				continue
			
			application = {}
			application["id"] = md5.md5(filename).hexdigest()
			application["name"] = unicode(name, encoding)
			application["command"] = unicode(shortcut.GetPath(0)[0], encoding)
			application["filename"] = unicode(filename, encoding)
			
			if len(shortcut.GetDescription())>0:
				application["description"] = unicode(shortcut.GetDescription(), encoding)
				
			if len(shortcut.GetIconLocation()[0])>0:
				application["icon"]  = unicode(shortcut.GetIconLocation()[0], encoding)

			# Find the mime types linked to the application
			# TODO: there is probably a faster way to handle this
			
			
			mimes = []
			for extension in self.mimetypes.extensions:
				ext = self.mimetypes.ext_keys[extension]
				
				for app_path in ext["apps"]:
					if not ext["type"] in mimes and self._compare_commands(app_path, application["command"]):
						mimes.append(ext["type"])
						break
			
			application["mimetypes"] = mimes
			

			if self.msi is not None:
				application["command"] = self.msi.getTargetFromShortcut(filename)
				if application["command"] is None:
					application["command"] = shortcut.GetPath(0)[0] + " " + shortcut.GetArguments()
					
			applications[application["id"]] = application
			
		
		return applications
	
	
	def getIcon(self, filename):
		return None
	
	
		#try :
			#args = {}
			#args2 = cgi.parse_qsl(self.path[self.path.index('?')+1:])
			#for (k,v) in args2:
				#args[k] = base64.decodestring(v).decode('utf-8')
		#except Exception, err:
			#args = {}
		
		#if args.has_key('desktopfile'):
			#if args['desktopfile'] != '':
				#if os.path.exists(args['desktopfile']):
					#pythoncom.CoInitialize()
					#shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
					#shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(args['desktopfile'])
					#if (os.path.splitext(shortcut.GetPath(0)[0])[1].lower() == ".exe"):
						#exe_file = shortcut.GetPath(0)[0]
						#path_bmp = tempfile.mktemp()+'.bmp'
						
						#command = """"%s" "%s" "%s" """%(os.path.join(self.server.daemon.install_dir, 'extract_icon.exe'), exe_file, path_bmp)
						#p = utils.Process()
						#status = p.run(command)
						#Logger.debug("status of extract_icon %s (command %s)"%(status, command))
						
						#if os.path.exists(path_bmp):
							#path_png = tempfile.mktemp()+'.png'
							
							#command = """"%s" -Q -O "%s" "%s" """%(os.path.join(self.server.daemon.install_dir, 'bmp2png.exe'), path_png, path_bmp)
							#p = utils.Process()
							#status = p.run(command)
							#Logger.debug("status of bmp2png %s (command %s)"%(status, command))
							
							#f = open(path_png, 'rb')
							#self.send_response(httplib.OK)
							#self.send_header('Content-Type', 'image/png')
							#self.end_headers()
							#self.wfile.write(f.read())
							#f.close()
							#os.remove(path_bmp)
							#os.remove(path_png)
						#else :
							#Logger.debug("webservices_icon error 500")
							#self.send_error(httplib.INTERNAL_SERVER_ERROR)
					#else :
						#Logger.debug("webservices_icon send default icon")
						#f = open('icon.png', 'rb')
						#self.send_response(httplib.OK)
						#self.send_header('Content-Type', 'image/png')
						#self.end_headers()
						#self.wfile.write(f.read())
						#f.close()
				#else:
					#Logger.debug("webservices_icon no right argument1")
			#else:
				#Logger.debug("webservices_icon no right argument2" )
		#else:
			#Logger.debug("webservices_icon no right argument3" )
	
	
	