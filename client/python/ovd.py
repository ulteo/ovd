#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008, 2010, 2011, 2012
# Author Laurent CLOUET <laurent@ulteo.com> 2009, 2010
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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
import commands
import cookielib
import urllib2
import threading
import time
import logging
from xml.dom import minidom
from xml.dom.minidom import Document
from xml.parsers.expat import ExpatError

DEFAULT_RDP_PORT = 3389


class OvdException(Exception):
	def __init__(self, message, log=None):
		if log == None:
			log = message
		else:
			log = message + " : " + log
		
		logging.error(log)
		Exception.__init__(self, message)


class OvdExceptionNotAvailable(OvdException):
	def __init__(self, log=None):
		OvdException.__init__(self, "Service not available", log)


class OvdExceptionInternalError(OvdException):
	def __init__(self, log=None):
		OvdException.__init__(self, "Internal error", log)


class Dialog:
	def __init__(self, conf):
		self.conf = conf
		self.base_url = "https://%s/ovd/client"%(conf["host"])
		self.sessionProperties = {}

		cookiejar = cookielib.CookieJar()
		self.urlOpener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookiejar))

		self.desktopStatus = -1
		self.sessionStatus = -1


	def doStartSession(self, args = {}):
		url = self.base_url+"/start.php"
		
		doc = Document()
		sessionNode = doc.createElement("session")
		sessionNode.setAttribute("mode", "desktop")
		if self.conf.has_key("language") and self.conf["language"]:
			sessionNode.setAttribute("language", self.conf["language"])
		
		userNode = doc.createElement("user")
		userNode.setAttribute("login", self.conf["login"])
		userNode.setAttribute("password", self.conf["password"])
		sessionNode.appendChild(userNode)
		
		if args.has_key("start-apps"): # launch applications at the session startup
			startappsNode = doc.createElement("start")
			for appid in args["start-apps"]:
				appNode = doc.createElement("application")
				appNode.setAttribute("id", appid)
				startappsNode.appendChild(appNode)
			sessionNode.appendChild(startappsNode)
		doc.appendChild(sessionNode)
		
		request = urllib2.Request(url, doc.toxml())
			  
		try:
			url = self.urlOpener.open(request)

		except urllib2.HTTPError, exc:
			if exc.code == 500:
				raise OvdExceptionNotAvailable()

			raise OvdExceptionInternalError("HTTP request return code %d (%s)" % (exc.code, exc.msg))
		except urllib2.URLError, exc:
			raise OvdExceptionNotAvailable("Startsession failure: %s"%(exc.reason))

		headers = url.info()
		if not headers["Content-Type"].startswith("text/xml"):
			logging.debug("response format %s"%(headers["Content-Type"]))
			raise OvdExceptionInternalError("Invalid response format")

		data = url.read()
		logging.debug("data received %s"%(data))
		try:
			dom = minidom.parseString(data)
		except ExpatError:
			raise OvdExceptionInternalError("Invalid XML result")

		node = dom.getElementsByTagName("response")
		if len(node) > 0:
			node = node[0]
			raise OvdException(node.getAttribute("code"))

		node = dom.getElementsByTagName("session")
		if len(node) != 1:
			raise OvdExceptionInternalError("No session root node")

		node = node[0]
		if not node.hasAttribute("mode"):
			raise OvdExceptionInternalError("Missing attribute mode")
		
		self.sessionProperties["mode"] = node.getAttribute("mode")
		
		for setting in node.getElementsByTagName("setting"):
			name = setting.getAttribute("name")
			value = setting.getAttribute("value")
			self.sessionProperties[name] = value
		
		for attr in ["shareable", "persistent", "multimedia", "redirect_client_printers", "redirect_smartcards_readers", "enhance_user_experience", "desktop_icons"]:
			if self.sessionProperties.has_key(attr):
				buf = self.sessionProperties[attr]
			else:
				buf = "false"

			if buf == "true" or buf == "1":
				self.sessionProperties[attr] = True
			elif buf == "false" or buf == "0":
				self.sessionProperties[attr] = False
			else:
				raise OvdExceptionInternalError("Invalid attribute %s value (%s)"%(attr, buf))
		
		userNode = node.getElementsByTagName("user")
		if len(userNode) != 1:
			raise OvdExceptionInternalError("Missing node user")
		
		userNode = userNode[0]
		if not userNode.hasAttribute("displayName"):
			raise OvdExceptionInternalError("Missing attribute displayName on node user")
		
		self.sessionProperties["user_dn"] = userNode.getAttribute("displayName")

		node = node.getElementsByTagName("server")
		if len(node) < 1:
			raise OvdExceptionInternalError("No server child node from root node")

		node = node[0]
		self.access = {"port": DEFAULT_RDP_PORT}
		for attr in ["fqdn", "login", "password"]:
			if not node.hasAttribute(attr):
				raise OvdExceptionInternalError("Missing attribute %s"%(str(attr)))
			
			self.access[attr] = node.getAttribute(attr)
		
		if node.hasAttribute("port"):
			try:
				self.access["port"] = int(node.getAttribute("port"))
			except ValueError:
				logging.warn("Port attribute is valid (%s)"%(node.getAttribute("port")))


	def doSessionStatus(self):
		url = "%s/session_status.php"%(self.base_url)
		request = urllib2.Request(url)
		
		try:
			url = self.urlOpener.open(request)

		except urllib2.HTTPError, exc:
			logging.debug(" * return: %s"%(str(exc.read())))
			if exc.code == 500:
				raise OvdExceptionNotAvailable()
			
			raise OvdExceptionInternalError("HTTP request return code %d (%s)" % (exc.code, exc.msg))
		except urllib2.URLError, exc:
			raise OvdExceptionNotAvailable("Service failure: %s"%(str(exc.reason)))

		headers = url.info()
		if not headers["Content-Type"].startswith("text/xml"):
			logging.debug(" * format: %s"%(str(headers["Content-Type"])))
			raise OvdExceptionInternalError("Invalid response format")

		data = url.read()
		logging.debug("data received %s"%(data))
		try:
			dom = minidom.parseString(data)
		except ExpatError:
			raise OvdExceptionInternalError("Invalid XML result")

		sessionNode = dom.getElementsByTagName("session")
		if len(sessionNode) != 1:
			raise OvdExceptionInternalError("Bad xml result")

		sessionNode = sessionNode[0]
		if not sessionNode.hasAttribute("status"):
			raise OvdExceptionInternalError("Bad xml result")

		self.sessionStatus = sessionNode.getAttribute("status")

		return self.sessionStatus


	def doLogout(self):
		if self.sessionProperties["persistent"]:
			mode = "suspend"
		else:
			mode = "logout"
		
		document = Document()
		rootNode = document.createElement("logout")
		rootNode.setAttribute("mode", mode)
		document.appendChild(rootNode)
		
		url = "%s/logout.php"%(self.base_url)
		request = urllib2.Request(url)
		request.add_header("Content-type", "text/xml; charset=UTF-8")
		request.add_data(document.toxml())
		
		try:
			url = self.urlOpener.open(request)

		except urllib2.HTTPError, exc:
			logging.debug(" * return: %s"%(str(exc.read())))
			if exc.code == 500:
				raise OvdExceptionNotAvailable()
			
			raise OvdExceptionInternalError("HTTP request return code %d (%s)" % (exc.code, exc.msg))
		except urllib2.URLError, exc:
			raise OvdExceptionNotAvailable("Failure: %s"%(str(exc.reason)))
	
	
	def getLaunchCommandRdesktop(self, params):
		status, out = commands.getstatusoutput("which rdesktop")
		if status != 0:
			status, out = commands.getstatusoutput("which rdesktop-vrdp")
		
		cmd = []
		cmd.append(out)
		cmd.append("-u")
		cmd.append(params["login"])
		cmd.append("-p")
		cmd.append(params["password"])
		if params["fullscreen"]:
			cmd.append("-f")
		else:
			cmd.append("-g")
			cmd.append("x".join(params.get("geometry", ("800", "600"))))
		cmd.append("-z")
		cmd.append("-T")
		cmd.append("\"Ulteo OVD - %s\""%(params["user_dn"]))
		
		if self.conf.has_key("keyboard") and self.conf["keyboard"]:
			cmd.append("-k")
			cmd.append(self.conf["keyboard"])
		
		if params["multimedia"]:
			cmd.append("-r")
			cmd.append("sound:local")
		
		for printer in params["printers"]:
			cmd.append("-r")
			cmd.append("'printer:%s'"%(printer))
		
		for drive in params["drives"]:
			cmd.append("-r")
			cmd.append("'disk:%s=%s'"%drive)
		
		cmd.append("-a")
		cmd.append(params.get("rdp_bpp", "16"))
		
		cmd.append(params["server_fqdn"])
		
		return cmd
	
	
	def getLaunchCommandFreeRDP(self, params):
		cmd = []
		cmd.append("xfreerdp")
		cmd.append("-u")
		cmd.append(params["login"])
		cmd.append("-p")
		cmd.append(params["password"])
		if params["fullscreen"]:
			cmd.append("-f")
		else:
			cmd.append("-g")
			cmd.append("x".join(params.get("geometry", ("800", "600"))))
		#cmd.append("-z")
		cmd.append("-T")
		cmd.append("\"Ulteo OVD - %s\""%(params["user_dn"]))
		
		if self.conf.has_key("keyboard") and self.conf["keyboard"]:
			cmd.append("-k")
			cmd.append(self.conf["keyboard"])
		
		if params["multimedia"]:
			 cmd.append("--plugin")
			 cmd.append("rdpsnd")
		
		cmd.append("--plugin")
		cmd.append("rdpdr")
		cmd.append("--data")
		for printer in params["printers"]:
			cmd.append("'printer:%s'"%(printer))
		
		for drive in params["drives"]:
			cmd.append("'disk:%s:%s'"%drive)
		
		cmd.append("--")
		
		if params.has_key("quality"):
			if params["quality"] == "lowest":
				bpp = 8
			elif params["quality"] == "medium":
				bpp = 16
			elif params["quality"] == "high":
				bpp = 24
			elif params["quality"] == "highest":
				bpp = 32
			
			cmd.append("-a")
			cmd.append(str(bpp))
		elif params.has_key("rdp_bpp"):
			cmd.append("-a")
			cmd.append(params["rdp_bpp"])
		
		cmd.append("-s")
		cmd.append("OvdDesktop")
		
		if not params["enhance_user_experience"]:
			cmd.append("--disable-wallpaper")
			cmd.append("--disable-full-window-drag")
			cmd.append("--disable-menu-animations")
			cmd.append("--disable-theming")
		else:
			cmd.append("--composition")
			cmd.append("--rfx")
			
		cmd.append("--ignore-certificate")
		
		cmd.append(params["server_fqdn"])
		
		return cmd
	
	
	def getLaunchCommand(self, params):
		if self.conf.get("client", None) == "freerdp":
			return self.getLaunchCommandFreeRDP(params)
		
		return self.getLaunchCommandRdesktop(params)
	
	
	def check_whatsup(self):
		logging.debug("Begin check")
		
		old_status = "created"
		while 1==1:
			status = self.doSessionStatus()
			if status != old_status:
				logging.info("Status changed: %s -> %s"%(old_status, status))
				old_status = status
			if status == "logged":
				time.sleep(55)
			time.sleep(5)
	
	
	def __get_user_dir(self, key):
		"""
		http://www.freedesktop.org/wiki/Software/xdg-user-dirs
			XDG_DESKTOP_DIR
			XDG_DOWNLOAD_DIR
			XDG_TEMPLATES_DIR
			XDG_PUBLICSHARE_DIR
			XDG_DOCUMENTS_DIR
			XDG_MUSIC_DIR
			XDG_PICTURES_DIR
			XDG_VIDEOS_DIR
		"""
		user_dirs_dirs = os.path.expanduser("~/.config/user-dirs.dirs")
		if os.path.exists(user_dirs_dirs):
			f = open(user_dirs_dirs, "r")
			for line in f.readlines():
				if line.startswith(key):
					return os.path.expandvars(line[len(key)+2:-2])
	
	
	def doLaunch(self):
		params = self.conf.copy()
		params.update(self.sessionProperties)
		params.update(self.access)
		
		params["printers"] = []
		if self.sessionProperties["redirect_client_printers"]:
			status, out = commands.getstatusoutput("LANG= lpstat -d -p")

			if status in [127, 32512]:
				logging.warn("Missing cupsys-client, unable to detect local printers")
			else:
				lines = out.splitlines()

				line = lines[0]
				if line.startswith("system default destination:"):
					buf = line[len("system default destination:"):].strip()
					params["printers"].append(buf)

				for line in lines[1:]:
					buf = line.split(" ")
					if buf[0] != "printer":
						continue
					buf = buf[1]
					if buf not in params["printers"]:
						params["printers"].append(buf)
				
		params["drives"] = []
		if self.sessionProperties["redirect_client_drives"] in ("full", ):
			rep = self.__get_user_dir("XDG_DESKTOP_DIR")
			if rep:
				params["drives"].append((os.path.basename(rep), rep))
			
			rep = self.__get_user_dir("XDG_DOCUMENTS_DIR")
			if rep:
				params["drives"].append((os.path.basename(rep), rep))
			
			f = open("/proc/mounts", "r")
			for line in f.readlines():
				parts = line.split(" ");
				if len(parts) < 3:
					continue
				device, mountPoint, fsType = parts[0:3];
				
				if not fsType in ("vfat", "fuseblk"):
					continue
				
				mountPoint = mountPoint.replace("\\040", " ")
				mountPoint = mountPoint.replace("\\011", "\t")
				mountPoint = mountPoint.replace("\\012", "\n")
				mountPoint = mountPoint.replace("\\134", "\\")
				
				params["drives"].append((os.path.basename(mountPoint), mountPoint))
		
		params["server_fqdn"] = self.access["fqdn"]
		if self.access["port"] != DEFAULT_RDP_PORT:
			  params["server_fqdn"] += ":%d"%(self.access["port"])

		logging.debug("Start sessions %r" % params)
		cmd = " ".join(self.getLaunchCommand(params))
		
		t = threading.Thread(target=self.check_whatsup)
		t.start()
		
		logging.debug("RDP command: '%s'"%(cmd))

		flag_continue = True
		try_ = 0

		while try_<5 and flag_continue:
			t0 = time.time()
			try:
				status, out = commands.getstatusoutput(cmd)
			except KeyboardInterrupt: # ctrl+c of the user
				logging.info("Interrupt from user")
				status = 0

			t1 = time.time()

			if t1-t0<2 and status == 256:
				logging.warn("Unable to connect to RDP server, sleep and try again (%d/5)"%(try_+1))
				time.sleep(0.3)
				try_+= 1
			else:
				flag_continue = False

		if status!=0:
			logging.info("rdesktop return status %d and \n%s\n==="%(status, out))
			self.doLogout()

		if t.isAlive():
			t._Thread__stop()

		logging.debug("end")
