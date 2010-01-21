#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008-2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008-2009
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

import getopt
import os
import signal
import sys

from ovd.Communication.HttpServer import HttpServer as Communication
#from ovd.Communication.UnixSocketServer import UnixSocketServer as Communication
from ovd.Config import Config
from ovd.Logger import Logger
from ovd.SlaveServer import SlaveServer
from ovd.Platform import Platform


def usage():
	print "Usage: %s [-c|--config-file= filename] [-h|--help]"%(sys.argv[0])
	print "\t-c|--config-file filename: load filename as configuration file instead default one"
	print "\t-h|--help: print this help"
	print

def main():
	config_file = os.path.join(Platform.getInstance().get_default_config_dir(), "ulteo-ovd.conf")

	try:
		opts, args = getopt.getopt(sys.argv[1:], 'c:h', ['config-file=', 'help'])
	
	except getopt.GetoptError, err:
		print >> sys.stderr, str(err)
		usage()
		sys.exit(2)
	
	conf_cmdline = {}
	
	for o, a in opts:
		if o in ("-c", "--config-file"):
			config_file = a
		elif o in ("-h", "--help"):
			usage()
			sys.exit()
	
	if not Config.read(config_file):
		print >> sys.stderr, "invalid configuration file '%s'"%(config_file)
		sys.exit(1)
	
	if not Config.is_valid():
		print >> sys.stderr, "invalid config"
		sys.exit(1)
	
	Logger.initialize("simpleServer", Logger.INFO | Logger.WARN | Logger.ERROR | Logger.DEBUG, None, True)
	
	
	server = SlaveServer(Communication)
	signal.signal(signal.SIGINT, server.stop)
	signal.signal(signal.SIGTERM, server.stop)
	server.loop()
	if not server.stopped:
		server.stop()


if __name__ == "__main__":
	main()
