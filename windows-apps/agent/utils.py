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

import os
import platform
import win32api
import win32pipe
import win32file
import win32process
import win32security
import win32con
import msvcrt
import pythoncom
from win32com.shell import shell, shellcon
import socket
import mimetypes

def myOS():
	if (len([b for b in ["windows","microsoft","microsoft windows"] if platform.system().lower() in b]) >0):
		return "windows"
	else:
		return platform.system().lower()


def isIP(address):
	try:
		socket.inet_ntoa(address)
	except Exception, err:
		return False
	return True


if myOS() == "windows":
	class Process:
		def run(self, cmdline):
			# security attributes for pipes
			sAttrs = win32security.SECURITY_ATTRIBUTES()
			sAttrs.bInheritHandle = 1
	
			# create pipes
			hStdin_r,  self.hStdin_w  = win32pipe.CreatePipe(sAttrs, 0)
			self.hStdout_r, hStdout_w = win32pipe.CreatePipe(sAttrs, 0)
			self.hStderr_r, hStderr_w = win32pipe.CreatePipe(sAttrs, 0)
	
			# set the info structure for the new process.
			StartupInfo = win32process.STARTUPINFO()
			StartupInfo.hStdInput  = hStdin_r
			StartupInfo.hStdOutput = hStdout_w
			StartupInfo.hStdError  = hStderr_w
			StartupInfo.dwFlags = win32process.STARTF_USESTDHANDLES
			# Mark doesn't support wShowWindow yet.
			StartupInfo.dwFlags = StartupInfo.dwFlags | win32process.STARTF_USESHOWWINDOW
			StartupInfo.wShowWindow = win32con.SW_HIDE
			
			# Create new output read handles and the input write handle. Set
			# the inheritance properties to FALSE. Otherwise, the child inherits
			# the these handles; resulting in non-closeable handles to the pipes
			# being created.
			pid = win32api.GetCurrentProcess()
	
			tmp = win32api.DuplicateHandle(
				pid,
				self.hStdin_w,
				pid,
				0,
				0,     # non-inheritable!!
				win32con.DUPLICATE_SAME_ACCESS)
			# Close the inhertible version of the handle
			win32file.CloseHandle(self.hStdin_w)
			self.hStdin_w = tmp
			tmp = win32api.DuplicateHandle(
				pid,
				self.hStdout_r,
				pid,
				0,
				0,     # non-inheritable!
				win32con.DUPLICATE_SAME_ACCESS)
			# Close the inhertible version of the handle
			win32file.CloseHandle(self.hStdout_r)
			self.hStdout_r = tmp
	
			# start the process.
			hProcess, hThread, dwPid, dwTid = win32process.CreateProcess(
					None,   # program
					cmdline,# command line
					None,   # process security attributes
					None,   # thread attributes
					1,      # inherit handles, or USESTDHANDLES won't work.
							# creation flags. Don't access the console.
					0,      # Don't need anything here.
							# If you're in a GUI app, you should use
							# CREATE_NEW_CONSOLE here, or any subprocesses
							# might fall victim to the problem described in:
							# KB article: Q156755, cmd.exe requires
							# an NT console in order to perform redirection.. 
					None,   # no new environment
					None,   # current directory (stay where we are)
					StartupInfo)
			# normally, we would save the pid etc. here...
	
			# Child is launched. Close the parents copy of those pipe handles
			# that only the child should have open.
			# You need to make sure that no handles to the write end of the
			# output pipe are maintained in this process or else the pipe will
			# not close when the child process exits and the ReadFile will hang.
			win32file.CloseHandle(hStderr_w)
			win32file.CloseHandle(hStdout_w)
			win32file.CloseHandle(hStdin_r)
			self.stdout = os.fdopen(msvcrt.open_osfhandle(self.hStdout_r, 0), "rb")
			out = self.stdout.read()
			self.stderr = os.fdopen(msvcrt.open_osfhandle(self.hStderr_r, 0), "rb")
			err = self.stderr.read()
			status = win32process.GetExitCodeProcess(hProcess)
			return [status,out,err]

def encode_multipart_formdata(fields, files):
	BOUNDARY = '----------ThIs_Is_tHe_bouNdaRY_$'
	endlines = '\r\n'
	L = []
	for (key, value) in fields:
		L.append('--' + BOUNDARY)
		L.append('Content-Disposition: form-data; name="%s"' % key)
		L.append('')
		L.append(value)
	for (key, filename, value) in files:
		L.append('--' + BOUNDARY)
		L.append('Content-Disposition: form-data; name="%s"; filename="%s"' % (key, filename))
		L.append('Content-Type: %s' % get_content_type(filename))
		L.append('')
		L.append(value)
	L.append('--' + BOUNDARY + '--')
	L.append('')
	body = endlines.join(L)
	content_type = 'multipart/form-data; boundary=%s' % BOUNDARY
	return (content_type, body)
def get_content_type(filename):
	return mimetypes.guess_type(filename)[0] or 'application/octet-stream'

def array_flush(array):
	array.reverse()
	for i in xrange(len(array)):
		yield array.pop()
