# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
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

import glob
import os
import re
import signal
import sys


def findProcessWithEnviron(pattern):
	uid = os.getuid()
	pid = os.getpid()

	files = glob.glob("/proc/[0-9]*")
	for f in files:
		if not os.path.isdir(f):
			continue
		
		if not os.stat(f)[4] == uid:
			continue
		
		this_pid = int(os.path.basename(f))
	
		if this_pid == pid:
			continue
	
		try:
			f_ = file(os.path.join(f, 'environ'), 'r')
			buffer = f_.read()
			f_.close()
		except IOError, err:
			continue
	
		if not pattern in buffer:
			continue
		
		if isKnownProcess2Ignore(this_pid):
			continue
		
		return this_pid
	
	return None


def isKnownProcess2Ignore(pid):
	names_db = ["dbus", "gconf", "kded", "kdeinit", "knotify"]
	
	path = "/proc/%s/status"%(str(pid))
	try:
		f = file(path, "r")
	except IOError, err:
		return False
	
	content = f.read()
	f.close()
	
	for item in names_db:
		if item in content:
			return True
	
	return False


def existProcess(pid):
	return os.path.isdir(os.path.join("/proc", str(pid)))

def getUserSessionDir():
	return os.path.join("/var/spool/ulteo/ovd/", os.environ["USER"])
	

def startDesktop():
	os.environ["XDG_DATA_DIRS"] = os.path.join(os.environ["OVD_SESSION_DIR"], "xdg")
	os.environ["OVD_APPS_DIR"] = os.path.join(os.environ["OVD_SESSION_DIR"], "xdg", "applications")
	
	os.system("x-session-manager")

def launchIntegratedClient(configuration_file_):
	if os.path.exists(configuration_file_) == False:
		return False
	
	launch('OVDExternalAppsClient -c %s -o "%s"'%(configuration_file_, os.path.join(getUserSessionDir(), "dump-externalapps.txt")))
	return True

def launch(cmd, wait=False):
	# todo: use another way to use the wait parameter
	pid = os.fork()
	if pid > 0:
		if wait:
			os.waitpid(pid, 0)
		return pid
	
	# Child process
	os.execl("/bin/sh", "sh", "-c" , cmd)
	sys.exit(1)

def kill(pid):
	os.kill(pid, signal.SIGTERM)
	return True


#
# the subprocess.list2cmdline function doesn't
# take care about the "(" or ")" characters ...
#
def list2cmdline(args):
	return " ".join('"'+arg+'"' for arg in args)


def transformCommand(cmd_, args_):
		if "%F" in cmd_:
			return cmd_.replace("%F", list2cmdline(args_))
		
		if "%U" in cmd_:
			b = []
			for arg in args_:
				if "://" not in arg:
					b.append('file://%s'%(os.path.abspath(arg)))
				else:
					b.append(arg)
			return cmd_.replace("%U", list2cmdline(b))
		
		cmd = cmd_
		args = args_
		args.reverse()

		if not "%" in cmd and len(args) > 0:
			cmd +=" %f"
		
		while len(args)>0:
			i = cmd.find("%")
			try:
				tok = cmd[i+1]
			except Exception,e:
				tok = ""
		
			arg = args.pop()
			if tok == "u" and "://" not in arg:
				replace = "file://%s"%(os.path.abspath(arg))
			else:
				replace = arg
		
			cmd = cmd.replace("%"+tok, '"%s"'%(replace), 1)
		
		cmd = re.sub("%[a-z]", "", cmd)
		
		return cmd

def lock(t):
	return False
