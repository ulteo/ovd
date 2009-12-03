# -*- coding: utf-8 -*-

# Copyright (C) 2008-2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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

import urllib
import urllib2
import utils
from Logger import Logger

class SessionManagerRequest:
	def __init__(self, conf):
		self.url = conf["session_manager"]
		self.fqdn = conf["hostname"]
		self.web_port = conf["web_port"]

	def server_status(self, status):
		values =  {'status'  : status,
				'fqdn' : self.fqdn,
				'web_port' : self.web_port}
		url = "%s/webservices/server_status.php?%s"%(self.url, urllib.urlencode(values))
		Logger.debug('SessionManagerRequest::server_status url '+url)
		
		req = urllib2.Request(url)
		try:
			f = urllib2.urlopen(req)
		except IOError, e:
			Logger.debug("SessionManagerRequest::server_status error"+str(e))
			Logger.errot("SessionManagerRequest error when said %s"%(status))
			return False
		
		return True
	
	def ready(self):
		Logger.debug("SessionManagerRequest::ready")
		ret = self.server_status("ready")
		return ret
	
	def down(self):
		Logger.debug("SessionManagerRequest::down")
		return self.server_status("down")
	
	def broken(self):
		return self.server_status("broken")
	
	def monitoring(self, xml_):
		urlOpener = urllib2.build_opener()
		form_vars=[('fqdn',self.fqdn)]
		form_files=[]
		
		form_files.append(('xml','monitoring.xml',xml_))
		
		data = utils.encode_multipart_formdata(form_vars, form_files)
		(content_type,body) = data
		url = "%s/webservices/server_monitoring.php"%(self.url)
		request = urllib2.Request(url,body)
		request.add_header('content-type', content_type)
		try:
			f = urllib2.urlopen(request)
		except IOError, e:
			Logger.debug("SessionManagerRequest::monitoring error"+str(e))
			return False
		
		return True
