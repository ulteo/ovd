# -*- coding: UTF-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author David LECHEVALIER <david@ulteo.com> 2010
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

import os
import win32ts
import win32api
import win32net
import win32netcon
import socket

from ovd.Logger import Logger
from ovd.Config import Config

"""
Return ip of the Guest
"""
def get_ip() :
	
	ip = ""
	
	while ip == "":
	
		ip = socket.gethostbyname(socket.gethostname())
				
		if ip != "" :
			Logger.debug("IP du GUEST : "+ip)
			return ip


"""
Return mac address of the Guest
"""
def get_mac():
		ipconfig = os.popen('ipconfig /all').readlines()
		for ligne in ipconfig:
			if 'physical' in ligne.lower():
				mac = ligne.split(':')[1].strip()
				return mac


"""
Return mac address of the Guest
"""
def get_macaddress(host):
		
	""" Returns the MAC address of a network host, requires >= WIN2K. """
	
	# Check for api availability
	try:
		SendARP = ctypes.windll.Iphlpapi.SendARP
	except:
		raise NotImplementedError('Usage only on Windows 2000 and above')
		
	# Doesn't work with loopbacks, but let's try and help.
	if host == '127.0.0.1' or host.lower() == 'localhost':
		host = socket.gethostname()
	
	# gethostbyname blocks, so use it wisely.
	try:
		inetaddr = ctypes.windll.wsock32.inet_addr(host)
		if inetaddr in (0, -1):
			raise Exception
	except:
		hostip = socket.gethostbyname(host)
		inetaddr = ctypes.windll.wsock32.inet_addr(hostip)
	
	buffer = ctypes.c_buffer(6)
	addlen = ctypes.c_ulong(ctypes.sizeof(buffer))
	if SendARP(inetaddr, 0, ctypes.byref(buffer), ctypes.byref(addlen)) != 0:
		raise WindowsError('Retreival of mac address(%s) - failed' % host)
	
	# Convert binary data into a string.
	macaddr = ''
	for intval in struct.unpack('BBBBBB', buffer):
		if intval > 15:
			replacestr = '0x'
		else:
			replacestr = 'x'
			macaddr = ''.join([macaddr, hex(intval).replace(replacestr, '')])
	
	return macaddr.upper()


def getState(session_id):
		state = win32ts.WTSQuerySessionInformation(None, session_id, win32ts.WTSConnectState)
		if state in [win32ts.WTSActive, win32ts.WTSConnected, win32ts.WTSInit]:
			return "logged"
		
		if state == win32ts.WTSDisconnected:
			return "disconnected"
		
		return "unknown"


def getSessionID(username_):
	domain_ = None
	if "@" in username_:
		(username_, domain_) = username_.split("@", 1)
		
	localdomain = win32api.GetComputerName()
		
	sessions = win32ts.WTSEnumerateSessions(None)
		
	for session in sessions:
		if not 0 < session["SessionId"] < 65536:
			continue
			
		try:
			login = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSUserName)
			if login.lower() != username_.lower():
				continue
				
			domain = win32ts.WTSQuerySessionInformation(None, session["SessionId"], win32ts.WTSDomainName)
			if domain_ is not None and domain.lower() == localdomain.lower():
				Logger.debug("Ts session %d is not from the domain user %s but from a local user"%(session["SessionId"], username_))
				continue
				
			elif domain_ is None and domain.lower() != localdomain.lower():
				Logger.debug("Ts session %d is not from the local user %s but from a domain user"%(session["SessionId"], username_))
				continue
				
		except pywintypes.error, err:
			if err[0] == 7007: # A close operation is pending on the session.
				session_closing.append(session)
			if err[0] == 7022: # Session not found.
				continue
			else:
				Logger.warn("Unable to list session %d"%(session["SessionId"]))
				Logger.debug("WTSQuerySessionInformation returned %s"%(err))
			continue
			
		return session["SessionId"]
