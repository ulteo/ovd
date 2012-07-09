# -*- coding: utf-8 -*-

# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
# Author Laurent CLOUET <laurent@ulteo.com> 2010
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

import ntsecuritycon
import os
import platform
import pythoncom
import win32api
import win32com
import win32com.client
from win32com.shell import shell, shellcon
import win32con
import win32netcon
import win32net
import win32security

from ovd.Logger import Logger
from ovd.Platform.System import System as AbstractSystem


class System(AbstractSystem):
	@staticmethod
	def getName():
		return "windows"

	@staticmethod
	def get_default_config_dir():
		d = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
		return os.path.join(d, "ulteo", "ovd")
	
	@staticmethod
	def get_default_spool_dir():
		d = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
		return os.path.join(d, "ulteo", "ovd", "spool")
	
	@staticmethod
	def get_default_data_dir():
		d = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
		return os.path.join(d, "ulteo", "ovd", "data")
	
	@staticmethod
	def get_default_log_dir():
		d = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
		return os.path.join(d, "ulteo", "ovd", "log")
	
	@staticmethod
	def getVersion():
		try:
			pythoncom.CoInitialize()
			wmi = win32com.client.Dispatch("WbemScripting.SWbemLocator")
			wmi_serv = wmi.ConnectServer(".")
			windows_server = wmi_serv.ExecQuery("Select Caption from Win32_OperatingSystem")
			
			buffer = windows_server[0].Caption
			if buffer is unicode:
				buffer = buffer.encode('utf-8')
		
		except Exception, err:
			Logger.warn("System::getVersion: version except '%s'"%(str(err)))
			buffer = platform.version()
		
		return buffer
	
	@staticmethod
	def getCPUInfos():
		pythoncom.CoInitialize()
		wmi = win32com.client.Dispatch("WbemScripting.SWbemLocator")
		wmi_serv = wmi.ConnectServer(".")
		cpus = wmi_serv.ExecQuery("Select * from Win32_Processor")
	
		try:
			name = cpus[0].Name
		except Exception, e:
			Logger.error("getCPUInfos %s"%(str(e)))
			return (1, "Unknown")

		nb_core = 0
		for cpu in cpus:
			try:
				nb_core += cpu.NumberOfLogicalProcessors
			except Exception, e:
				#On Windows Server 2003, Windows XP, and Windows 2000:  This property is not available
				nb_core +=1
		
		return (nb_core, name)
	
	@staticmethod
	def getCPULoad():
		pythoncom.CoInitialize()
		wmi = win32com.client.Dispatch("WbemScripting.SWbemLocator")
		wmi_serv = wmi.ConnectServer(".")
		cpus = wmi_serv.ExecQuery("Select * from Win32_PerfFormattedData_PerfOS_Processor where Name='_Total'")
	
		try:
			load = 0.0
			for cpu in cpus:
				load+= int(cpu.PercentProcessorTime)
			load = load / float(len(cpus)*100)
		
		except Exception, err:
			Logger.warn("getCPULoad: %s"%(str(err)))
			return 0.0
		
		return load
	
	@staticmethod
	def getRAMUsed():	
		infos = win32api.GlobalMemoryStatusEx()
	
		try:
			total = infos["TotalPhys"]/1024
			free = infos["AvailPhys"]/1024
	
		except Exception, e:
			Logger.warn("getRAMUsed: %s"%(str(e)))
			return 0
		
		return total - free
	
	
	@staticmethod
	def getRAMTotal():
		infos = win32api.GlobalMemoryStatusEx()
		
		try:
			total = infos["TotalPhys"]/1024
		
		except Exception, e:
			Logger.warn("getRAMTotal: %s"%(str(e)))
			return 0.0
		
		return total
	
	
	@staticmethod
	def getADDomain():
		try:
			domain = win32api.GetComputerNameEx(win32con.ComputerNameDnsDomain)
		except Exception, e:
			Logger.warn("System::getADDomain: exception '%s'"%(str(e)))
			return False
			
		return domain

	
	@staticmethod
	def DeleteDirectory(path):
		for file in os.listdir(path):
			filename = os.path.join(path, file)
			win32api.SetFileAttributes(filename, win32con.FILE_ATTRIBUTE_NORMAL)
			if os.path.isdir(filename):
				System.DeleteDirectory(filename)
			else:
				try:
					os.remove(filename)
				except WindowsError, e:
					if e[0] == 32 : # The file is used by an other processus
						Logger.debug("The file %s is used by an other processus"%(filename))
						continue
					raise e
		win32api.SetFileAttributes(path, win32con.FILE_ATTRIBUTE_NORMAL)
		try:
			os.rmdir(path)
		except WindowsError, e:
			if e[0] == 145 : # The directory is not empty
				Logger.debug("The directory %s is not empty"%(path))
				return
			raise e
	
	@staticmethod
	def groupCreate(name_):
		try:
			data = {}
			data['name'] = name_
			data['comment'] = ''
		
			win32net.NetLocalGroupAdd(None, 1, data)
		except win32net.error, e:
			Logger.error("SessionManagement createGroupOVD: '%s'"%(str(e)))
			return False
	
		return True
	
	@staticmethod
	def groupExist(name_):
		try:
			win32net.NetLocalGroupGetInfo(None, name_, 0)
		except win32net.error, e:
			return False
		
		return True
	
	@staticmethod
	def groupMember(name_):
		try:
			(users, _, _) = win32net.NetLocalGroupGetMembers(None, name_, 1)
		
		except win32net.error, e:
			Logger.error("groupMember: '%s'"%(str(e)))
			return None
		
		members = []
		for user in users:
			members.append(user["name"])
		
		return members
	
	@staticmethod
	def userRemove(user_):
		try:
			win32net.NetUserDel(None, user_)
		
		except win32net.error, e:
			Logger.error("userRemove: '%s'"%(str(e)))
			return False
		
		return True
	
	
	@staticmethod
	def userAdd(login_, displayName_, password_, groups_):
		userData = {}
		userData['name'] = login_
		userData['full_name'] = displayName_
		userData['password'] = password_
		userData['flags']  = win32netcon.UF_DONT_EXPIRE_PASSWD
		userData['flags'] |= win32netcon.UF_NORMAL_ACCOUNT
		userData['flags'] |= win32netcon.UF_PASSWD_CANT_CHANGE
		userData['flags'] |= win32netcon.UF_SCRIPT

		userData['priv'] = win32netcon.USER_PRIV_USER
		userData['primary_group_id'] = ntsecuritycon.DOMAIN_GROUP_RID_USERS
		#userData['password_expired'] = 0 #le mot de passe n'expire pas
		userData['acct_expires'] =  win32netcon.TIMEQ_FOREVER

		try:
			win32net.NetUserAdd(None, 3, userData)
		except Exception, e:
			Logger.error("unable to create user: "+str(e))
			raise e

		data = [ {'domainandname' : login_} ]
		for group in groups_:
			try:
				win32net.NetLocalGroupAddMembers(None, group, 3, data)
			except Exception, e:
				Logger.error("unable to add user %s to group '%s'"%(login_, group))
				raise e
		
		return True

	@staticmethod
	def userExist(name_):
		try:
			win32net.NetUserGetInfo(None, name_, 0)
		except win32net.error, e:
			return False
		
		return True
	
	
	@staticmethod
	def tcp_server_allow_reuse_address():
		return False
	
	
	@staticmethod
	def prepareForSessionActions():
		try:
			# Get some privileges to load the hive
			priv_flags = win32security.TOKEN_ADJUST_PRIVILEGES | win32security.TOKEN_QUERY 
			hToken = win32security.OpenProcessToken (win32api.GetCurrentProcess (), priv_flags)
			backup_privilege_id = win32security.LookupPrivilegeValue (None, "SeBackupPrivilege")
			restore_privilege_id = win32security.LookupPrivilegeValue (None, "SeRestorePrivilege")
			win32security.AdjustTokenPrivileges (
				hToken, 0, [
				(backup_privilege_id, win32security.SE_PRIVILEGE_ENABLED),
				(restore_privilege_id, win32security.SE_PRIVILEGE_ENABLED)
				]
			)
		except Exception, e:
			Logger.error("Failed to obtain more provilege"%(str(e)))
