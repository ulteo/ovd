# -*- coding: UTF-8 -*-

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


from ovd.Role.ApplicationServer.DomainMicrosoft import DomainMicrosoft as AbstractDomainMicrosoft
from ovd.Platform.System import System
from ovd.Logger import Logger
import os
import pwd
import grp


class DomainMicrosoft(AbstractDomainMicrosoft):
	def __init__(self):
		self.session = None
		self.ovdGroups = ["'video'", "'audio'", "'pulse'", "'pulse-access'", "'fuse'", "'tsusers'"]
	
	
        def manage_user(self):
		return False
	
	
	def getGroupList(self, username):
		if username is None:
			return None
		
		try:		
			gids = [g.gr_gid for g in grp.getgrall() if username in g.gr_mem]
			gid = pwd.getpwnam(username).pw_gid
			gids.append(grp.getgrgid(gid).gr_gid)
			return ["'%s'"%(grp.getgrgid(gid).gr_name) for gid in gids]
		except Exception, e:
			Logger.error("Failed to get groups of the user %s: %s"%(username, str(e)))
			return None
	
	
	def getUsername(self):
		username = self.session.user.name
		
		if '@' in self.session.user.name:
			username = self.session.user.name.split('@')[0]
		
		return username
	
	
	def tuneGroups(self, username, groups):
		if groups is None or len(groups) == 0:
			return False
		
		groupString = ','.join(groups)
		cmd = u"usermod -G %s %s"%(groupString, username)
		p = System.execute(System.local_encode(cmd))
		if p.returncode != 0:
			Logger.error("UserAdd return %d (%s)"%(p.returncode, p.stdout.read()))
			return False
		
		return True
	
	
	def onSessionCreate(self):
		username = self.getUsername()
		self.session.user.name = username
		self.session.user.groups = self.getGroupList(username)
		if self.session.user.groups is None:
			return False

		groups = set(self.ovdGroups + self.session.user.groups)
		
		if not self.tuneGroups(username, groups):
			Logger.error("Failed to customize groups of the user %s"%(username))
		
		return self.session.install_client()
	
	
	def onSessionStarts(self):
		return True
	
	
	def onSessionEnd(self):
		username = self.getUsername()
		if self.session.user.groups is None:
			return True
		
		if not self.tuneGroups(username, self.session.user.groups):
			Logger.error("Failed to restaure groups of the user %s"%(username))
		
		return True
