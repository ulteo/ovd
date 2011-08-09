# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
# Author Gaëtan COLLET <gaetan@ulteo.com> 2011
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

from ovd.Logger import Logger

class Config:
	
	ulteo_pool_path = "/mnt/ulteo_pool"
	libvirt_uri = None
	ulteo_pool_name = "ulteo-ovd"
	session_manager = "10.42.1.28"
	network_name = None
	
	lan = "192.168.45.1"
	port = 8080
	
	@staticmethod
	def init(infos):
		Logger.info("Hypervisor role Config::init")
		if not infos.has_key("libvirt_uri"):
			Logger.error("Missing libvirt_uri in the slaveserver.conf")
			return False
		
		Config.libvirt_uri = infos["libvirt_uri"]
		
		if not infos.has_key("session_manager"):
			Logger.error("Missing session_manager ip in the slaveserver.conf")
			return False
			
		Config.session_manager = infos["session_manager"]
			
		if not infos.has_key("network_name"):
			Logger.error("Missing a network name fot the virtual machines network in the slaveserver.conf")
			return False
			
		Config.network_name = infos["network_name"]
				    
		return True
