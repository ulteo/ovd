# -*- coding: UTF-8 -*-

# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009-2010
# Author David LECHEVALIER <david@ulteo.com> 2010, 2012
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

import glob
import hashlib
import locale
import os
import random
import shutil
import time

from ovd.Config import Config
from ovd.Logger import Logger
from ovd.Platform import Platform

import Platform as RolePlatform

class Session:
	Ulteo_apps = ["startovdapp", "UlteoOVDIntegratedLauncher"]
	
	SESSION_STATUS_UNKNOWN = "unknown"
	SESSION_STATUS_ERROR = "error"
	SESSION_STATUS_INIT = "init"
	SESSION_STATUS_INITED = "ready"
	SESSION_STATUS_ACTIVE = "logged"
	SESSION_STATUS_INACTIVE = "disconnected"
	SESSION_STATUS_WAIT_DESTROY = "wait_destroy"
	SESSION_STATUS_DESTROYED = "destroyed"
	
	SESSION_END_STATUS_NORMAL = "logout"
	SESSION_END_STATUS_SHUTDOWN = "shutdown"
	SESSION_END_STATUS_ERROR = "internal"
	
	MODE_DESKTOP = "desktop"
	MODE_APPLICATIONS = "applications"
	
	def __init__(self, id_, mode_, user_, parameters_, applications_):
		self.id = id_
		self.user = user_
		self.mode = mode_
		self.parameters = parameters_
		self.profile = None
		self.applications = applications_
		self.application_to_start = []
		self.instanceDirectory = None
		self.used_applications = {}
		self.external_apps_token = None
		self.end_status = None
		self.user_session_dir = None
		self.locked = False
		
		self.domain = None
		
		self.log = []
		self.switch_status(Session.SESSION_STATUS_INIT)
	
	def init(self):
		raise NotImplementedError()
	
	def init_user_session_dir(self, user_session_dir):
		self.user_session_dir = user_session_dir
		if os.path.isdir(self.user_session_dir):
			Platform.System.DeleteDirectory(self.user_session_dir)
		
		try:
			os.makedirs(self.user_session_dir)
		except WindowsError, e:
			if e[0] == 183 : # The directory already exist
				Logger.debug("The directory %s already exist"%(self.user_session_dir))
		
		self.instanceDirectory = os.path.join(self.user_session_dir, "instances")
		self.matchingDirectory = os.path.join(self.user_session_dir, "matching")
		self.shortcutDirectory = os.path.join(self.user_session_dir, "shortcuts")
		
		os.mkdir(self.instanceDirectory)
		os.mkdir(self.matchingDirectory)
		os.mkdir(self.shortcutDirectory)

		for application in self.applications:
			cmd = RolePlatform.Platform.ApplicationsDetection.getExec(application["filename"])
			if cmd is None:
				Logger.error("Session::install_client unable to extract command from app_id %s (%s)"%(application["id"], application["filename"]))
				continue
			
			f = file(os.path.join(self.matchingDirectory, application["id"]), "w")
			f.write(cmd)
			f.close()
		
		
		if self.external_apps_token is not None:
			f = open(os.path.join(self.user_session_dir, "sm"), "w")
			f.write(Config.session_manager+"\n")
			f.close()
			
			f = open(os.path.join(self.user_session_dir, "token"), "w")
			f.write(self.external_apps_token+"\n")
			f.close()
		
		
		if len(self.application_to_start) > 0:
			apps2start_dir = os.path.join(self.user_session_dir, "to_start")
			os.mkdir(apps2start_dir)
			
			for application in self.application_to_start:
				id_ = hashlib.md5("%f%f"%(random.random(), time.time())).hexdigest()
				
				f = open(os.path.join(apps2start_dir, id_), "w")
				f.write(application["id"])
				if (application.has_key("arg")):
					f.write("\n"+application["arg"])
				f.close()
	
	
	def post_install(self):
		if self.user_session_dir is not None:
			f = file(os.path.join(self.user_session_dir, "nolock"), "w")
			f.close()
	
	
	def install_desktop_shortcuts(self):
		for application in self.applications:
			final_file = os.path.join(self.shortcutDirectory, self.get_target_file(application))
			#Logger.debug("install_client %s %s %s"%(str(application["filename"]), str(final_file), str(application["id"])))
			
			ret = self.clone_shortcut(application["filename"], final_file, "startovdapp", [application["id"]])
			if not ret:
				Logger.warn("Unable to clone shortcut '%s' to '%s'"%(application["filename"], final_file))
				continue
			
			self.install_shortcut(final_file)
	
	
	def setApplicationToStart(self, application_to_start):
		self.application_to_start = application_to_start
	
	def setExternalAppsToken(self, external_apps_token):
		self.external_apps_token = external_apps_token
	
	def setDomain(self, domain):
		self.domain = domain
		self.domain.setSession(self)
	
	def install_client(self):
		pass
	
	def uninstall_client(self):
		pass
	
	def clone_shortcut(self, src, dst, command, args):
		pass
	
	def install_shortcut(self, shortcut):
		pass
	
	def get_target_file(self, application):
		pass
	
	def switch_status(self, status_):
		self.log.append((time.time(), status_))
		self.status = status_
	
	
	def getUsedApplication(self):
		if self.status in [Session.SESSION_STATUS_ACTIVE, Session.SESSION_STATUS_INACTIVE] and self.instanceDirectory is not None:
			(_, encoding) = locale.getdefaultlocale()
			if encoding is None:
				encoding = "UTF8"
			
			applications = {}
			for path in glob.glob(os.path.join(self.instanceDirectory, "*")):
				basename = os.path.basename(path)
				
				if type(basename) is unicode:
					name = basename
				else:
					name = unicode(basename, encoding)
				
				if not os.path.isfile(path):
					continue
				
				f = file(path, "r")
				data = f.read().strip()
				f.close()
				
				applications[name] = unicode(data, encoding)

			self.used_applications = applications
		return self.used_applications
	
	
	def cleanupShortcut(self, path):
		if self.mode != Session.MODE_DESKTOP:
			return
		
		shortcut_ext = RolePlatform.Platform.ApplicationsDetection.shortcut_ext
		
		if not os.path.exists(path):
			return
		
		try:
			contents = os.listdir(path)
		except Exception, err:
			Logger.warn("Unable to list content of the directory %s (%s)"%(path, str(err)))
			return
		
		for content in contents:
			target = None
			l = os.path.join(path, content)
			if not os.path.isfile(l):
				continue
			
			if not os.path.splitext(l)[1] == shortcut_ext:
				continue
			
			try:
				target = RolePlatform.Platform.ApplicationsDetection.getExec(l)
			except Exception, e:
				Logger.debug("Unable to get the desktop target of %s %s"%(l, str(e)))
				target = None
			
			if target is None:
				continue
			
			for app in self.Ulteo_apps:
				if app.lower() in target.lower():
					Logger.debug("removing shortcut %s"%(target))
					try:
						os.remove(l)
					except Exception, e:
						Logger.debug("Unable to delete the desktop target %s %s"%(l, str(e)))

	
	def archive_shell_dump(self):
		spool = os.path.join(Config.spool_dir, "sessions dump archive")
		if not os.path.exists(spool):
			os.makedirs(spool)
		
		if self.user_session_dir is None:
			Logger.warn("Unable to dump shell archive")
			return

		for path in glob.glob(os.path.join(self.user_session_dir, "dump*.txt")):
			name = os.path.basename(path)
			
			dst = os.path.join(spool, "%s %s-%s"%(self.id, self.user.name, name))
			shutil.copyfile(path, dst)
			
			try:
				if self.domain.manage_user():
					os.remove(path)
			except:
				pass
