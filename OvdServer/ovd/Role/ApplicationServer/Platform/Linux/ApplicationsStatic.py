# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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

#import ConfigParser
import os

from ovd.Logger import Logger
from ovd.Role.ApplicationServer.ApplicationsStatic import ApplicationsStatic as AbstractApplicationsStatic


class ApplicationsStatic(AbstractApplicationsStatic):
	@staticmethod
	def getFilesExtensions():
		return ["desktop"]
	
	
	def getApplicationPath(self, id_):
		return os.path.join(self.spool, id_+".desktop")
	
	
	def createShortcut(self, application_):
		lines = []
		lines.append("[Desktop Entry]")
		lines.append("Type=Application")
		lines.append("Terminal=false")
		lines.append("Version=1.0")
		lines.append("Icon=%s"%(os.path.join(self.spool, application_["id"]+".png")))
		lines.append("Name=%s"%(application_["name"]))
		lines.append("Comment=%s"%(application_["description"]))
		lines.append("Exec=%s"%(application_["command"]))
		lines.append("MimeType=%s"%(";".join(application_["mimetypes"])))
		#parser = ConfigParser.ConfigParser()
		#parser.add_section("Desktop Entry")
		#parser.set("Desktop Entry", "Type", "Application")
		#parser.set("Desktop Entry", "Version", "1.0")
		#parser.set("Desktop Entry", "Terminal", "false")
		#parser.set("Desktop Entry", "Icon", os.path.join(self.spool, application_["id"]+".png"))
		#parser.set("Desktop Entry", "Name", application_["name"])
		#parser.set("Desktop Entry", "Comment", application_["description"])
		#parser.set("Desktop Entry", "Exec", application_["command"])
		#parser.set("Desktop Entry", "MimeType", ";".join(application_["mimetypes"]))
		
		path = os.path.join(self.spool, application_["id"]+".desktop")
		try:
			f= file(path, "w")
		except:
			Logger.error("Unable to open file '%s'"%(path))
			return False
		
		f.writelines([s+"\n" for s in lines])
		#parser.write(f)
		f.close()
		
		return True
