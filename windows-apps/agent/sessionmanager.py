# -*- coding: utf-8 -*-

# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
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

class SessionManagerRequest:
	def __init__(self, conf, log):
		self.url = conf["session_manager"]
		self.fqdn = conf["hostname"]
		self.web_port = conf["web_port"]
		self.log = log

	def server_status(self, status):
		values =  {'status'  : status,
				'fqdn' : self.fqdn,
				'web_port' : self.web_port}
		url = "%s/webservices/server_status.php?%s"%(self.url, urllib.urlencode(values))
		print 'SessionManagerRequest::server_status url ',url
		
		req = urllib2.Request(url)
		try:
			f = urllib2.urlopen(req)
		except IOError, e:
			self.log.debug("SessionManagerRequest::server_status error"+str(e))
			self.log.error("SessionManagerRequest error when said %s"%(status))
			return False
		
		return True
	
	def ready(self):
		print "SessionManagerRequest::ready"
		ret = self.server_status("ready")
		print 'ret ',ret
		return ret
	
	def down(self):
		return self.server_status("down")
	
	def broken(self):
		return self.server_status("broken")
