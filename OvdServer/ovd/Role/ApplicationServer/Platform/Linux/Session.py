# -*- coding: UTF-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
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

import os
import pwd
import shutil

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.Session import Session as AbstractSession

from ApplicationsDetection import ApplicationsDetection
from ovd.Platform import Platform

class Session(AbstractSession):
	
	SPOOL_USER = "/var/spool/ovd/"
	
	def install_client(self):
		d = os.path.join(self.SPOOL_USER, self.user.name)
		if os.path.exists(d):
			Platform.System.DeleteDirectory(d)
		os.makedirs(d)
		
		os.mkdir(os.path.join(d, "matching"))
		for (app_id, app_target) in self.applications:
			cmd = ApplicationsDetection.getExec(app_target)
			if cmd is None:
				Logger.error("Session::install_client unable to extract command from app_id %s (%s)"%(app_id, app_target))
				continue
			f = file(os.path.join(d, "matching", app_id), "w")
			f.write(cmd)
			f.close()
		
		instances_dir = os.path.join(d, "instances")
		os.mkdir(instances_dir, 0770)
		os.chown(instances_dir, pwd.getpwnam(self.user.name)[2], -1)
		
		xdg_dir = os.path.join(d, "xdg")
		os.mkdir(xdg_dir)
		
		xdg_app_d = os.path.join(xdg_dir, "applications")
		os.mkdir(xdg_app_d)
		
		for p in ["icons", "pixmaps", "mime", "themes"]:
			src_dir = os.path.join("/usr/share/", p)
			dst_dir =  os.path.join(xdg_dir, p)
			
			os.symlink(src_dir, dst_dir)
		
		
		for (app_id, app_target) in self.applications:
			target = os.path.join(xdg_app_d, "%s.desktop"%(app_id))
			
			cmd = """sed -r "s#^Exec=(.*)#Exec=startovdapp %s#" <"%s" >"%s" """%(app_id, app_target, target)
			ret = os.system(cmd)
			if ret != 0:
				Logger.warn("Following cmd return status %d: %s"%(ret, cmd))
				
		
		os.system('update-desktop-database "%s"'%(xdg_app_d))
	
		if self.parameters.has_key("desktop_icons"):
			path = os.path.join(xdg_app_d, ".show_on_desktop")
			f = file(path, "w")
			f.close()
		
		
		env_file_lines = []
		# Set the language
		if self.parameters.has_key("locale"):
			env_file_lines.append("LANG=%s.UTF-8\n"%(self.parameters["locale"]))
			env_file_lines.append("LC_ALL=%s.UTF-8\n"%(self.parameters["locale"]))
			env_file_lines.append("LANGUAGE=%s.UTF-8\n"%(self.parameters["locale"]))
		
		f = file(os.path.join(d, "env"), "w")
		f.writelines(env_file_lines)
		f.close()
	
	def uninstall_client(self):
		d = os.path.join(self.SPOOL_USER, self.user.name)
		xdg_dir = os.path.join(d, "xdg")
		
		for p in ["icons", "pixmaps", "mime", "themes"]:
			dst_dir =  os.path.join(xdg_dir, p)
			if os.path.islink(dst_dir):
				os.remove(dst_dir)
		
		if os.path.exists(d):
			shutil.rmtree(d)
