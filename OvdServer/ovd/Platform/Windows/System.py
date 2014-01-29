# -*- coding: utf-8 -*-

# Copyright (C) 2009-2014 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2011, 2013
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2011
# Author David LECHEVALIER <david@ulteo.com> 2012, 2013, 2014
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2014
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
from Base.System import System as AbstractSystem


class System(AbstractSystem):
	@staticmethod
	def getName():
		return "windows"
	
	
	@staticmethod
	def get_default_config_dir():
		d = shell.SHGetFolderPath(0, shellcon.CSIDL_COMMON_APPDATA, 0, 0)
		return os.path.join(d, "ulteo", "ovd")
	
	
	@staticmethod
	def get_default_sys_dir():
		d = shell.SHGetFolderPath(0, shellcon.CSIDL_SYSTEM, 0, 0)
		return d
	
	
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
			
			buf = windows_server[0].Caption
			if buf is unicode:
				buf = buf.encode('utf-8')
		
		except Exception:
			Logger.exception("System::getVersion")
			buf = platform.version()
		
		return buf
	
	
	@staticmethod
	def getCPUInfos():
		pythoncom.CoInitialize()
		wmi = win32com.client.Dispatch("WbemScripting.SWbemLocator")
		wmi_serv = wmi.ConnectServer(".")
		cpus = wmi_serv.ExecQuery("Select * from Win32_Processor")
		
		try:
			name = cpus[0].Name
		except Exception:
			Logger.exception("getCPUInfos")
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
		
		except Exception:
			Logger.exception("getCPULoad")
			return 0.0
		
		return load
	
	
	@staticmethod
	def getRAMUsed():	
		infos = win32api.GlobalMemoryStatusEx()
		
		try:
			total = infos["TotalPhys"]/1024
			free = infos["AvailPhys"]/1024
		
		except Exception:
			Logger.exception("getRAMUsed")
			return 0
		
		return total - free
	
	
	@staticmethod
	def getRAMTotal():
		infos = win32api.GlobalMemoryStatusEx()
		
		try:
			total = infos["TotalPhys"]/1024
		
		except Exception:
			Logger.exception("getRAMTotal")
			return 0.0
		
		return total
	
	
	@staticmethod
	def getADDomain():
		try:
			domain = win32api.GetComputerNameEx(win32con.ComputerNameDnsDomain)
		except Exception:
			Logger.exception("System::getADDomain")
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
		except win32net.error:
			Logger.exception("SessionManagement createGroupOVD")
			return False
		
		return True
	
	
	@staticmethod
	def groupDelete(name_):
		try:
			win32net.NetLocalGroupDel(None, name_)
		except win32net.error:
			Logger.exception("SessionManagement createDeleteOVD")
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
		
		except win32net.error:
			Logger.exception("groupMember")
			return None
		
		members = []
		for user in users:
			members.append(user["name"])
		
		return members
	
	
	@staticmethod
	def userRemove(user_):
		try:
			win32net.NetUserDel(None, user_)
		
		except win32net.error:
			Logger.exception("userRemove")
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
			Logger.exception("unable to create user")
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
		except Exception:
			Logger.exception("Failed to obtain more provilege")
	
	
	@classmethod
	def getWindowsVersionName(cls):
		names = {
				"5.2": "2003",
				"6.0": "2008",
				"6.1": "2008R2",
				"6.2": "2012",
			}
		
		v = platform.version()[:3]
		return names.get(v, v)
	
	
	@staticmethod
	def setdacl(path, sid, dacl):
		dsi = win32security.GetFileSecurity(path, win32security.DACL_SECURITY_INFORMATION)
		dsi.SetSecurityDescriptorDacl(1, dacl, 0)
		win32security.SetFileSecurity(path, win32security.DACL_SECURITY_INFORMATION, dsi)
	
	
	@staticmethod
	def _rchown(path, sid, dacl):
		System.setdacl(path, sid, dacl)
		
		for item in os.listdir(path):
			itempath = os.path.join(path, item)
			System.setdacl(itempath, sid, dacl)
			
			if os.path.isdir(itempath):
				System._rchown(itempath, sid, dacl)
	
	
	@staticmethod
	def rchown(path, user):
		sid = None
		admins = None
		try:
			sid, d, type = win32security.LookupAccountName ("", user)
			admins = win32security.ConvertStringSidToSid("S-1-5-32-544")
		except:
			return False
		
		dacl = win32security.ACL()
		dacl.AddAccessAllowedAce(win32security.ACL_REVISION, ntsecuritycon.FILE_ALL_ACCESS, sid)
		dacl.AddAccessAllowedAce(win32security.ACL_REVISION, ntsecuritycon.FILE_ALL_ACCESS, admins)
		
		try:
			System._rchown(path, sid, dacl)
		except Exception, e:
			return False
		
		return True
