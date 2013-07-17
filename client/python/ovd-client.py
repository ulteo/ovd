#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008, 2010, 2011, 2012
# Author Laurent CLOUET <laurent@ulteo.com> 2009, 2010
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

import commands
import getopt
import getpass
import os
import re
import signal
import sys
import time
import logging

from ovd import OvdException, Dialog


def handler_signal(signum, frame):
	d.doLogout() # d is the client (global variable)


def usage():
	print "Usage: %s [options] ovd_sm_host"%(sys.argv[0])
	print
	print "Options:"
	print "\t--extra-startsession-opt=key:value[,key1:value1...]"
	print "\t--start-apps=value[,value1,value2,...]"
	print "\t-f|--fullscreen"
	print "\t-g|--geometry=WIDTHxHEIGHT"
	print "\t-h|--help"
	print "\t-l|--login=username"
	print "\t-p|--password=PASSWORD"
	print "\t-q|--quality=lowest|medium|high|highest"
	print "\t--quiet"
	print "\t--verbose"
	print


conf = {}
conf["fullscreen"] = False
conf["geometry"] = "800x600"
conf["login"] = os.environ["USER"]

logger_flags = logging.INFO

extra_args = {}

try:
	opts, args = getopt.getopt(sys.argv[1:], "fg:hl:p:q:", ["extra-startsession-opt=", "fullscreen", "geometry=", "help", "login=", "password=", "quality=", "quiet", "verbose", "start-apps="])
except getopt.GetoptError, err:
	print >> sys.stderr, str(err)
	usage()
	sys.exit(2)

if not len(args)>0:
	print >> sys.stderr, "Missing host"
	usage()
	sys.exit(2)

conf["host"] = args[0]
for o, a in opts:
	if o in ("-l", "--login"):
		conf["login"] = a
	elif o in ("-p", "--password"):
		conf["password"] = a
	elif o in ("-f", "--fullscreen"):
		conf["fullscreen"] = True
	elif o in ("-g", "--geometry"):
		conf["geometry"] = a
	elif o in ("-h", "--help"):
		usage()
		sys.exit()
	elif o in ("-q", "--quality"):
		if a.lower() not in ["lowest", "medium", "high", "highest"]:
			print >> sys.stderr, "Invalid quality option",a
			usage()
			sys.exit(2)
		conf["quality"] = a.lower()
	elif o == "--quiet":
		logger_flags = logging.ERROR
	elif o == "--extra-startsession-opt":
		items = a.split(",")
		if len(items)==0:
			print >> sys.stderr, "Invalid extra-startsession-opt option",a
			usage()
			sys.exit(2)
		
		for item in a.split(","):
			(k,v) = item.split(":")
			if len(k)==0 or len(v)==0:
				print >> sys.stderr, "Invalid extra-startsession-opt option",item
				usage()
				sys.exit(2)
			
			extra_args[k] = v
	elif o == "--start-apps":
		if a == "":
			print >> sys.stderr, "Invalid start-apps option",a
			usage()
			sys.exit(2)
		items = a.split(",")
		extra_args["start-apps"] = items
	elif o == "--verbose":
		logger_flags = logging.DEBUG

if conf["fullscreen"] == True:
	(status, out) = commands.getstatusoutput("xdpyinfo |grep dimensions:")
	s = re.search("([0-9]+)x([0-9]+) pixels", out)
	if s is None:
		print >> sys.stderr, "Unable to get the screen resolution"
		sys.exit(2)

	conf["geometry"] = s.groups()
else:
	conf["geometry"] = conf["geometry"].split("x")
	if len(conf["geometry"])!=2:
		print >> sys.stderr, "Invalid geometry ",conf["geometry"]
		usage()
		sys.exit(2)

# Initialize the Logger instance
logging.basicConfig(level=logger_flags)

if not conf.has_key("password"):
	logging.info("Connect to '%s' with user '%s'"%(conf["host"], conf["login"]))
	conf["password"] = getpass.getpass("Password please: ")

d = Dialog(conf)

try:
	d.doStartSession(extra_args)
except OvdException as e:
	logging.error(e.message)
	sys.exit(2)

signal.signal(signal.SIGTERM, handler_signal)

logging.debug("Session properties: %s"%(str(d.sessionProperties)))
if d.sessionProperties["mode"] != "desktop":
	logging.error("Doesn't support session mode '%s'"%(str(d.sessionProperties["mode"])))
	sys.exit(0)


status = -1
while status not in ["ready"]:
	status = d.doSessionStatus()
	logging.debug("status %s"%(str(status)))

	if type(status) == type(False):
		logging.error("Error in get status")
		sys.exit(5)
	
	time.sleep(2.0)
	if not status in ["init", "ready"]:
		logging.error("Session not 'init' or 'ready' (%s) => exit"%(status))
		sys.exit(4)

d.doLaunch()
