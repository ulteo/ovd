# -*- coding: utf-8 -*-

# Copyright (C) 2008-2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
# Author Laurent CLOUET <laurent@ulteo.com> 2009,2010
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
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

import httplib
import cgi
import base64
import time
from xml.dom.minidom import Document

from ovd.Communication.Dialog import Dialog as AbstractDialog
from ovd.Config import Config
from ovd.FileTailer import FileTailer
from ovd.Logger import Logger
from ovd.Platform import Platform


class Dialog(AbstractDialog):
	def __init__(self, server_instance):
		self.server = server_instance
	
	@staticmethod
	def getName():
		return "server"
	
	def process(self, request):
		path = request["path"]
		
		if request["method"] == "GET":
			Logger.debug("do_GET "+path)
			
			if path == "/configuration":
				return self.req_server_conf(request)
			
			elif path == "/monitoring":
				return self.req_server_monitoring(request)
			
			elif path == "/status":
				return self.req_server_status(request)

			elif path.startswith("/logs"):
				since = 0
				extra = path[len("/logs"):]
				if extra.startswith("/since/"):
					since_str = extra[len("/since/"):]
					if since_str.isdigit():
						since = int(since_str)
				elif len(extra) > 0:
					return None  
				
				return self.req_server_logs(request, since)
			
			return None
		
		elif request["method"] == "POST":
			return None
		
		return None
	
	
	def response_error(self, code):
		self.send_response(code)
		self.send_header('Content-Type', 'text/html')
		self.end_headers()
		self.wfile.write('')
	
	
	def req_server_status(self, request):
		doc = Document()
		rootNode = doc.createElement('server')
		rootNode.setAttribute("name", self.server.smRequestManager.name)
		rootNode.setAttribute("status", "ready")
		
		doc.appendChild(rootNode)
		return self.req_answer(doc)

	def req_server_logs(self, request, since):
		response = {}
		response["code"] = httplib.OK
		response["Content-Type"] = "text/plain"
		response["data"] = ""
		
		if Logger._instance is None or Logger._instance.filename is None:
			return response
		
		lines = []
		t = time.time()
		
		tailer = FileTailer(Logger._instance.filename)
		while t > since and tailer.hasLines():
			buf = tailer.tail(20)
			buf.reverse()
			
			for line in buf:
				t = Logger._instance.get_time_from_line(line)
				if t is None:
					continue
				
				if t<since:
					break  
				
				lines.insert(0, line)
		
		response["data"] = "\n".join(lines)
		return response
	
	
	def req_server_monitoring(self, request):
		doc = self.server.getMonitoring()
		if doc is None:
			return None
		
		return self.req_answer(doc)
	
	def req_server_conf(self, request):
		cpuInfos = Platform.System.getCPUInfos()
		ram_total = Platform.System.getRAMTotal()
		
		doc = Document()
		rootNode = doc.createElement('configuration')
		
		rootNode.setAttribute("type", Platform.System.getName())
		rootNode.setAttribute("version", Platform.System.getVersion())
		rootNode.setAttribute("ram", str(ram_total))
		rootNode.setAttribute("ulteo_system", str(self.server.ulteo_system).lower())
		
		cpuNode = doc.createElement('cpu')
		cpuNode.setAttribute('nb_cores', str(cpuInfos[0]))
		textNode = doc.createTextNode(cpuInfos[1])
		cpuNode.appendChild(textNode)
		
		rootNode.appendChild(cpuNode)
		
		for role in self.server.roles:
			roleNode = doc.createElement('role')
			roleNode.setAttribute('name', role.dialog.getName())
			rootNode.appendChild(roleNode)
		
		doc.appendChild(rootNode)
		return self.req_answer(doc)
