#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
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

import os
import utils
import commands
import getopt
import logging
import logging.handlers
import signal
import sys
import time
from BaseHTTPServer import HTTPServer
import communication
import threading
import platform
import locale
from win32com.shell import shell
import pythoncom,os,socket,urllib2,urllib
from xml.dom.minidom import Document
import re
import wmi

import utils
from sessionmanager import SessionManagerRequest

class UlteoSlave:
	def __init__(self, conf):
		self.conf = conf
		self.init_log()
		self.log.info("init")
		self.smr = SessionManagerRequest(self.conf, self.log)
		self.broken = False
		self.applicationsXML = None
		self.webserver = HTTPServer( ("", int(self.conf["web_port"])), communication.Web)
		self.webserver.daemon = self
		self.webserver.log = self.log
		self.thread_web = threading.Thread(target=self.webserver.serve_forever)
		self.wmi = wmi.WMI()
		try:
			self.version_os = self.wmi.Win32_OperatingSystem ()[0].Name.split('|')[0]
		except Exception, err:
			self.version_os = platform.version()

	def loop(self):
		self.thread_web.start()
		if not self.smr.ready():
			self.broken = True
			self.stop()
		
		while 1:
			time.sleep(10)
	
	
	def stop(self, Signum=None, Frame=None):
		self.log.info("stop")
		sys.exit(0)


	def init_log(self):
		formatter = logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s')

		self.log = logging.getLogger('ovd')
		self.log.setLevel(logging.DEBUG)

		if self.conf["log_file"]:
			handler = logging.handlers.RotatingFileHandler(self.conf["log_file"], maxBytes=1000000, backupCount=2)
			handler.setFormatter(formatter)
			#logging.getLogger().addHandler(handler)
			self.log.addHandler(handler)

		if not self.conf["daemonize"]:
			console = logging.StreamHandler(sys.stdout)
			console.setFormatter(formatter)
#            logging.getLogger().addHandler(console)
			self.log.addHandler(console)

	
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
			if ( shortcut.GetPath(0)[0][-3:] == "exe"):
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

def usage():
	print "Usage: %s [-c|--config-file= filename] [-h|--help] [-d|--daemonize]"%(sys.argv[0])
	print "\t-c|--config-file filename: load filename as configuration file instead default one"
	print "\t-h|--help: print this help"
	print "\t-d|--daemonize: detash the process once launch, run in background"
	print

def main():
	if os.environ.has_key('APPDATA'):
		appdata =  os.environ['APPDATA']
	else:
		print "ERROR: main no APPDATA key in environnement"
		appdata = "c:\\"
	
	name = "ulteo-ovd"

	conf = {}
	conf["daemonize"] = False
	conf["conf_file"] = os.path.abspath(os.path.join(appdata, 'ulteo', 'ovd', '%s.conf'%(name)))
	conf["log_file"] = os.path.abspath(os.path.join(appdata, 'ulteo', 'ovd', 'log', '%s.log'%(name)))
	
	conf["log_flags"] = ["info", "warn", "error"]
	conf["hostname"] = None
	conf["web_port"] = None
	
	try:
		opts, args = getopt.getopt(sys.argv[1:], 'c:dh:', ['config-file=', 'daemonize', 'help'])

	except getopt.GetoptError, err:
		print >> sys.stderr, str(err)
		usage()
		sys.exit(2)
	
	conf_cmdline = {}
	
	for o, a in opts:
		if o in ("-h", "--help"):
			usage()
			sys.exit()
		elif o in ("-d", "--daemonize"):
			conf_cmdline["daemonize"] = True
	
	
	if conf_cmdline.has_key("conf_file"):
		conf["conf_file"] = conf_cmdline["conf_file"]
	
	try:
		conf = load_shell_config_file(conf)
	except Exception, err:
		print >> sys.stderr, "invalid config file: "+str(err)
		sys.exit(2)
	
	# Overwrite option set by command line
	for key in conf_cmdline.keys():
		if conf.has_key(key):
			conf[key] = conf_cmdline[key]

	if not is_conf_valid(conf):
		print >> sys.stderr, "invalid configuration"
		sys.exit(2)

	if conf["daemonize"]:
		daemonize()

	slave = UlteoSlave(conf)
	signal.signal(signal.SIGINT, slave.stop)
	signal.signal(signal.SIGTERM, slave.stop)
	slave.loop()


def daemonize():
	# do the UNIX double-fork magic, see Stevens' "Advanced
	# Programming in the UNIX Environment" for details (ISBN 0201563177)

	for i in range(2):
		try:
			pid = os.fork()
			if pid > 0:
				# exit first parent
				sys.exit(0)
		except OSError, e:
			print >>sys.stderr, "Unable to fork: %d (%s)" % (e.errno, e.strerror)
			sys.exit(1)


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


if __name__ == "__main__":
	main()
