# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

import ctypes
import ctypes.wintypes
import sys
import win32con
import win32process

# Detect if running in wow64 mode or not
if win32process.IsWow64Process():
	PTR = ctypes.c_uint64
	ULONG = ctypes.c_ulonglong
	NT_QUERY_INFORMATION_PROCESS = ctypes.windll.ntdll.NtWow64QueryInformationProcess64
	NT_READ_VIRTUAL_MEMORY       = ctypes.windll.ntdll.NtWow64ReadVirtualMemory64
	
else:
	PTR = ctypes.c_size_t
	ULONG = ctypes.c_ulong
	NT_QUERY_INFORMATION_PROCESS = ctypes.windll.ntdll.NtQueryInformationProcess
	NT_READ_VIRTUAL_MEMORY       = ctypes.windll.ntdll.NtReadVirtualMemory


class PROCESS_BASIC_INFORMATION(ctypes.Structure):
	_fields_ = [
		("ExitStatus",                      PTR),
		("PebBaseAddress",                  PTR),
		("AffinityMask",                    PTR),
		("BasePriority",                    PTR),
		("UniqueProcessId",                 PTR),
		("InheritedFromUniqueProcessId",    PTR),
	]


def getProcessPeb(process_handle):
	pbi = PROCESS_BASIC_INFORMATION()
	pbi_length = ctypes.sizeof(pbi)
	read_length = ctypes.c_ulong()
	
	ret = NT_QUERY_INFORMATION_PROCESS(process_handle, 0, ctypes.byref(pbi), pbi_length, ctypes.byref(read_length))
	if ret != 0:
		print >> sys.stderr, "Error at _NtQueryInformationProcess"
		return None
	
	return pbi.PebBaseAddress


def getEnvironnmentBlock(pid):
	phandle = ctypes.windll.kernel32.OpenProcess (win32con.PROCESS_QUERY_INFORMATION | win32con.PROCESS_VM_READ, False,  pid)
	
	ppeb = getProcessPeb(phandle)
	if ppeb is None:
		return None
	
	addr = PTR(ppeb + 4*ctypes.sizeof(PTR)) # 0x10 32 bit, 0x20 64bit
	tmp = PTR()
	size_t = ULONG()
	
	ret = NT_READ_VIRTUAL_MEMORY(phandle, addr, ctypes.byref(tmp), ULONG(ctypes.sizeof(PTR)), ctypes.byref(size_t))
	if ret != 0:
		print >> sys.stderr,  "Error at read procParamAddr from peb address",hex(ppeb)
		return None
	
	procParamAddr = tmp.value
	addr = PTR(procParamAddr + 14 * ctypes.sizeof(PTR) + 16) # 0x48 32 bit, 0x80 64 bit
	
	ret = NT_READ_VIRTUAL_MEMORY(phandle, addr, ctypes.byref(tmp), ULONG(ctypes.sizeof(PTR)), ctypes.byref(size_t))
	if ret != 0:
		print >> sys.stderr,  "Error at read envBlockAddr from procParam address",hex(procParamAddr)
		return None
	
	envBlockAddr = tmp.value
	size = 1024
	cbuffer = ctypes.c_buffer (size)
	
	envBlock = ""
	FoundEnd = False
	piece = 0
	while not FoundEnd:
		addr = PTR(envBlockAddr + piece *size)
		
		ret = NT_READ_VIRTUAL_MEMORY(phandle, addr, ctypes.byref(cbuffer), ULONG(size), ctypes.byref(size_t))
		if ret != 0:
			print >> sys.stderr,  "Error at read envBlock[%d]"%(piece),hex(addr.value)
			return None
		
		pos = cbuffer.raw.find("\x00\x00\x00\x00")
		if pos == -1:
			envBlock+= cbuffer.raw
		else:
			envBlock+= cbuffer.raw[:pos+1] # add one \x00 because of utf16
			FoundEnd = True
		
		piece+= 1
	
	ctypes.windll.kernel32.CloseHandle(phandle)
	
	try:
		envBlock = envBlock.decode("utf-16-le")
	except UnicodeError, err:
		print err
		return None
	
	return envBlock


def envBlock2dict(block):
	env = {}
	for piece in block.split("\x00"):
		if "=" not in piece:
			print >> sys.stderr,  "Warning: invalid piece: '%s'"%(piece)
			continue
		
		k,v = piece.split("=", 1)
		env[k] = v
	
	return env


if __name__ == "__main__":
	try:
		pid = int(sys.argv[1])
	except:
		print >> sys.stderr,  "usage: test pid"
		sys.exit(1)
	
	print "PID: ",pid
	
	block = getEnvironnmentBlock(pid)
	if block is None:
		print >> sys.stderr,  "Unable to get memory block"
		sys.exit(1)
	
	env = envBlock2dict(block)
	keys = env.keys()
	keys.sort()
	for k in keys:
		print "  *",k," => ",env[k]
