# -*- coding: utf-8 -*-

# Copyright (C) 2010-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
# Author David LECHEVALIER <david@ulteo.com> 2012
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


import locale

class System:
	@staticmethod
	def get_default_config_dir():
		raise NotImplementedError()
	
	@staticmethod
	def get_default_spool_dir():
		raise NotImplementedError()
	
	@staticmethod
	def get_default_data_dir():
		raise NotImplementedError()
	
	@staticmethod
	def get_default_log_dir():
		raise NotImplementedError()
	
	@staticmethod
	def getVersion():
		raise NotImplementedError()
	
	@staticmethod
	def getCPUInfos():
		raise NotImplementedError()
	
	@staticmethod
	def getCPULoad():
		raise NotImplementedError()
	
	@staticmethod
	def getRAMUsed():	
		raise NotImplementedError()
	
	@staticmethod
	def getRAMTotal():
		raise NotImplementedError()
	
	@staticmethod
	def getADDomain():
		raise NotImplementedError()
	
	@staticmethod
	def DeleteDirectory(path):
		raise NotImplementedError()
	
	@staticmethod
	def groupCreate(name_):
		raise NotImplementedError()
	
	@staticmethod
	def groupExist(name_):
		raise NotImplementedError()
	
	@staticmethod
	def groupMember(name_):
		raise NotImplementedError()
	
	@staticmethod
	def userRemove(user_):
		raise NotImplementedError()
	
	@staticmethod
	def userAdd(login_, displayName_, password_, groups_):
		raise NotImplementedError()

	@staticmethod
	def userExist(name_):
		raise NotImplementedError()
	
	
	@staticmethod
	def tcp_server_allow_reuse_address():
		raise NotImplementedError()

	@staticmethod
	def local_encode(data):
		if type(data) is not unicode:
			return data

		try:
			encoding = locale.getpreferredencoding()
		except:
			encoding = "UTF-8"
		
		try:
			ret = data.encode(encoding)
		except:
			ret = data

		return ret
	
	
	@staticmethod
	def prepareForSessionActions():
		raise NotImplementedError()
