#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008-2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
# Author Laurent CLOUET <laurent@ulteo.com> 2008-2009
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

import sessionmanager
from BaseHTTPServer import HTTPServer
from win32com.shell import shell, shellcon
from xml.dom.minidom import Document
import commands
import communication
import locale
import logging
import logging.handlers
import os
import platform
import pythoncom
import socket,urlparse
import servicemanager
import sys
import threading
import time
import win32service
import win32serviceutil
import wmi
import utils
from ctypes import *
from ctypes.wintypes import DWORD
SIZE_T = c_ulong

def log_debug(msg_):
	servicemanager.LogInfoMsg(str(msg_))
	
def is_conf_valid(conf):
	dirname = os.path.dirname(conf["log_file"])
	if len(dirname)>0 and not os.path.isdir(dirname):
		print >>sys.stderr, "No such directory '%s'"%(dirname)
		return False
	
	if conf["session_manager"] is None:
		print >>sys.stderr, "No Session manager specified"
		return False
	
	if conf["hostname"] is None:
		print >>sys.stderr, "No hostname specified"
		return False
	
	return True

def load_shell_config_file(conf):
	match = {}
	match["SERVERNAME"] = "hostname"
	match["SESSION_MANAGER_URL"] = "session_manager"
	match["LOG_FILE"] = "log_file"
	match["LOG_FLAGS"] = "log_flags"
	match["WEBPORT"] = "web_port"

	if not os.path.isfile(conf["conf_file"]):
		raise Exception("No such file '%s'"%(conf["conf_file"]))
	
	f = file(conf["conf_file"], "r")
	lines = f.readlines()
	f.close()
	
	for line in lines:
		line = line.strip()
		if len(line) == 0:
			continue
	
		if line.startswith("#"):
			continue
	
		if not "=" in line:
			continue
	
		key, value = line.split("=", 1)
		if len(key) == 0 or len(value) == 0:
			continue
	
		# We are not very strict because it's a configuration file
		# use by the old daemon software
		if not key in match.keys():
		#     raise Exception("Invalid key name '%s'"%(key))
			continue

		if utils.myOS() == "windows":
			# todo...
			out = line.split('=')[1]
		else:
			status, out = commands.getstatusoutput("%s && echo $%s"%(line, key))
			if status!=0:
				raise Exception("Invalid value for key '%s'"%(key))
		value = out
		if key == "LOG_FLAGS":
			value = value.split(" ")
		elif key in ["MAXLUCK", "MINLUCK"]:
			value = int(value)
		
		conf[match[key]] = value
	
	return conf

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

class OVD(win32serviceutil.ServiceFramework):
	_svc_name_ = "OVD"
	_svc_display_name_ = "Ulteo OVD agent"
	_svc_description_ = "OVD agent providing monitoring capacities"
	
	def __init__(self,args):
		win32serviceutil.ServiceFramework.__init__(self,args)
		
		self.install_dir = os.path.dirname(os.path.abspath(sys.argv[0]))
		common_appdata = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
		
		name = "ulteo-ovd"
		conf = {}
		conf["conf_file"] = os.path.join(self.install_dir, '%s.conf'%(name))
		conf["log_file"] = os.path.abspath(os.path.join(common_appdata, 'ulteo', 'ovd', 'main.log'))
		
		conf["log_flags"] = ["info", "warn", "error"]
		conf["hostname"] = None
		conf["web_port"] = None
		log_debug("main 004 log_file "+conf["log_file"])
		
		try:
			conf = load_shell_config_file(conf)
		except Exception, err:
			print >> sys.stderr, "invalid config file: "+str(err)
			log_debug("invalid config file: "+str(err))
			log_debug("exit 22")
			sys.exit(2)
		
		if not is_conf_valid(conf):
			print >> sys.stderr, "invalid configuration"
			log_debug("exit 23")
			sys.exit(2)
	
		log_debug("init 01 "+str(conf))
		self.conf = conf
		self.monitoring_cpu_name = 'Unknow'
		self.monitoring_cpu_load = 0
		self.monitoring_cpu_number = 0
		
		self.init_log()
		self.log.info("init")
		self.smr = sessionmanager.SessionManagerRequest(self.conf, self.log)
		self.broken = False
		self.isAlive = True
		self.applicationsXML = None
		self.webserver = HTTPServer( ("", int(self.conf["web_port"])), communication.Web)
		self.webserver.daemon = self
		self.webserver.log = self.log
		self.thread_web = threading.Thread(target=self.webserver.serve_forever)

	def SvcDoRun(self):
		self.ReportServiceStatus(win32service.SERVICE_START_PENDING)
		self.ReportServiceStatus(win32service.SERVICE_RUNNING)
		pythoncom.CoInitialize()
		self.wmi = wmi.WMI()
		self.updateMonitoring()
		cpus = self.wmi.Win32_Processor()
		if type(cpus) == type([]): # list
			self.monitoring_cpu_name = cpus[0].Name
			self.monitoring_cpu_number = len(cpus)
		try:
			self.version_os = self.wmi.Win32_OperatingSystem ()[0].Name.split('|')[0].encode('ascii','ignore')
		except Exception, err:
			self.version_os = platform.version()
		
		#self.webserver.serve_forever()
		self.thread_web.start()
		if not self.smr.ready():
			self.broken = True
			servicemanager.LogInfoMsg("SessionManager does not get a 'ready', stopping agent")
			self.SvcStop()
		while self.isAlive:
			time.sleep(60)
			self.updateMonitoring()
			self.smr.monitoring(self.xmlMonitoring().toxml())
		
		servicemanager.LogInfoMsg("SvcDoRun 04 Stopped")
	
	def SvcStop(self):
		self.log.debug("OVD::SvcStop")
		servicemanager.LogInfoMsg("Stopping agent")
		self.ReportServiceStatus(win32service.SERVICE_STOP_PENDING)
		self.isAlive = False #this will make SvcDoRun() break the while loop at the next iteration.
		#self.webserver.shutdown()
		self.smr.down()

	def init_log(self):
		formatter = logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s')
	
		self.log = logging.getLogger('ovd')
		self.log.setLevel(logging.DEBUG)

		if self.conf["log_file"]:
			handler = logging.handlers.RotatingFileHandler(self.conf["log_file"], maxBytes=1000000, backupCount=2)
			handler.setFormatter(formatter)
			#logging.getLogger().addHandler(handler)
			self.log.addHandler(handler)
	
	def isSessionManagerRequest(self, client_ip):
		url_split = urlparse.urlsplit(self.conf['session_manager'])
		sm_host =  url_split[1]
		if utils.isIP(sm_host):
			return  client_ip == sm_host
		else:
			try:
				return client_ip == socket.gethostbyname(sm_host)
			except Exception, err:
				# exception if the getaddrinfo failed
				self.log.error("fail to get address info for '%s'"%(sm_host))
				return False
	
	def getStatusString(self):
		return 'ready'
	
	def getApplicationsXML(self):
		if self.applicationsXML == None:
			buf = self.getApplicationsXML_nocache()
			if buf != None and buf != '':
				self.applicationsXML = buf
				return buf
			else:
				return ''
		else:
			return self.applicationsXML
	
	def getApplicationsXML_nocache(self):
		self.log.debug("getApplicationsXML_nocache")
		def find_lnk(base_):
			ret = []
			for root, dirs, files in os.walk(base_):
				for name in files:
					l = os.path.join(root,name)
					if os.path.isfile(l) and l[-3:] == "lnk":
						ret.append(l)
			return ret
		def isBan(name_):
			name = name_.lower()
			for ban in ['uninstall', 'update']:
				if ban in name:
					return True
			return False
		
		pythoncom.CoInitialize()
		language, output_encoding = locale.getdefaultlocale()
		doc = Document()
		server = doc.createElement("applications")
		doc.appendChild(server)
		
		if os.environ.has_key('ALLUSERSPROFILE'):
			shortcut_list = find_lnk( os.environ['ALLUSERSPROFILE'])
		else:
			self.log.error("getApplicationsXML_nocache : no  ALLUSERSPROFILE key in environnement")
			shortcut_list = find_lnk( os.path.join('c:\\', 'Documents and Settings', 'All Users'))
		
		for filename in shortcut_list:
			shortcut = pythoncom.CoCreateInstance(shell.CLSID_ShellLink, None, pythoncom.CLSCTX_INPROC_SERVER, shell.IID_IShellLink)
			shortcut.QueryInterface( pythoncom.IID_IPersistFile ).Load(filename)
			if (os.path.splitext(shortcut.GetPath(0)[0])[1].lower() == ".exe"):
				application_name = os.path.basename(filename)[:-4]
				if isBan(application_name) == False:
					app = doc.createElement("application")
					app.setAttribute("name", unicode(os.path.basename(filename)[:-4], output_encoding))
					if unicode(shortcut.GetDescription(), output_encoding) != '':
						app.setAttribute("description", unicode(shortcut.GetDescription(), output_encoding))
					server.appendChild(app)
					exe = doc.createElement("executable")
					app.setAttribute("desktopfile", unicode(filename, output_encoding))
					
					if unicode(shortcut.GetIconLocation()[0], output_encoding) != '':
						exe.setAttribute("icon", unicode(shortcut.GetIconLocation()[0], output_encoding))
					exe.setAttribute("command", unicode(shortcut.GetPath(0)[0], output_encoding)+" "+unicode(shortcut.GetArguments(), output_encoding))
					
					app.appendChild(exe)
		
		return doc.toxml(output_encoding)
	
	def updateMonitoring(self):
		pythoncom.CoInitialize()
		cpus = wmi.WMI().Win32_Processor()
		if type(cpus) == type([]): # list
			load = 0.0
			for cpu_wmi in cpus:
				load += cpu_wmi.LoadPercentage
				load = load / float(len(cpus)*100)
			self.monitoring_cpu_load = load
	
	def xmlMonitoring(self):
		self.log.debug("OVD::xmlMonitoring")
		doc = Document()
		monitoring = doc.createElement('monitoring')
		doc.appendChild(monitoring)
		cpu = doc.createElement('cpu')
		cpu.setAttribute('nb_cores', str(self.monitoring_cpu_number))
		text = doc.createTextNode(self.monitoring_cpu_name)
		cpu.appendChild(text)
		cpu.setAttribute('load', str(self.monitoring_cpu_load))
		
		monitoring.appendChild(cpu)
		
		memstatus = _MEMORYSTATUS()
		windll.kernel32.GlobalMemoryStatus(byref(memstatus))
		
		ram = doc.createElement('ram')
		ram.setAttribute('total', str(memstatus.TotalPhys()))
		ram.setAttribute('used', str(memstatus.UsedPhys()))
		monitoring.appendChild(ram)
		
		return doc

