#! /usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2008, 2009, 2010, 2011
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
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

import signal
# prevent an unexpected crash due to early signals reception
signal.signal(signal.SIGINT, signal.SIG_IGN)
signal.signal(signal.SIGTERM, signal.SIG_IGN)

import getopt
import multiprocessing
import os
import sys
import time

from ovd.Communication.HttpServer import HttpServer as Communication
from ovd.Config import Config
from ovd.Exceptions import InterruptedException
from ovd.Logger import Logger
from ovd.Platform.System import System
from ovd.SlaveServer import SlaveServer


def stop(Signum, Frame):
	signal.signal(signal.SIGINT, signal.SIG_IGN)
	signal.signal(signal.SIGTERM, signal.SIG_IGN)
	Logger.info("Signal receive")
	raise InterruptedException(0, '')


def main(queue, config_file, pid_file):
	daemonize = bool(queue)
	
	def _exit(code, msg=''):
		if daemonize:
			queue.put((code, msg))
		else:
			return (code, msg)
	
	if not Config.read(config_file) and not Config.is_valid():
		_exit(1, "wrong config file")
	
	Logger.initialize("OVD", Config.log_level, Config.log_file, not daemonize, Config.log_threaded)
	
	server = SlaveServer(Communication)
	
	signal.signal(signal.SIGINT, stop)
	signal.signal(signal.SIGTERM, stop)
	
	try:
		if pid_file is not None:
			try:
				f = open(pid_file, "w")
				f.write(str(os.getpid()))
				f.close()
			except IOError:
				raise InterruptedException(2, "Unable to write pid-file '%s'" % pid_file)
		
		if not server.load_roles():
			raise InterruptedException(3, "Cannot load some Roles")
		
		if not server.init():
			raise InterruptedException(4, "Server initialization failed")
	
	except InterruptedException, e:
		code, msg = e.args
		return _exit(code, msg)
	
	else:
		try:
			registred = server.push_production()
			if registred:
				_exit(0)
			else:
				_exit(5, "Session manager was not reachable")
			while not registred:
				Logger.warn("Session Manager not connected. Sleeping for a while ...")
				time.sleep(60)
				registred = server.push_production()
			
			Logger.info("SlaveServer started")
			
			while not server.stopped:
				server.loop_procedure()
				time.sleep(30)
		
		except (InterruptedException, KeyboardInterrupt), e:
			Logger.info("SlaveServer interruption")
	
	finally:
		if not server.stopped:
			server.stop()
		
		if Config.log_threaded:
			Logger.initialize("OVD", Config.log_level, Config.log_file, not daemonize, False)
		
		Logger.info("SlaveServer stopped")
		if pid_file is not None and os.path.exists(pid_file):
			os.remove(pid_file)
	
	return _exit(0)



def usage():
	print "Usage: %s [-c|--config-file= filename] [-d|--daemonize] [-h|--help] [-p|--pid-file= filename]"%(sys.argv[0])
	print "\t-c|--config-file filename: load filename as configuration file instead default one"
	print "\t-d|--daemonize: start in background"
	print "\t-h|--help: print this help"
	print "\t-p|--pid-file filename: write process id in specified file"
	print


if __name__ == "__main__":
	# freeze_support must be the first line
	multiprocessing.freeze_support()
	
	config_file = os.path.join(System.get_default_config_dir(), "slaveserver.conf")
	daemonize = False
	pid_file = None
	
	try:
		opts, args = getopt.getopt(sys.argv[1:], 'c:dhp:', ['config-file=', 'daemonize', 'help', 'pid-file='])
	
	except getopt.GetoptError, err:
		print >> sys.stderr, str(err)
		usage()
		sys.exit(2)
	
	for o, a in opts:
		if o in ("-c", "--config-file"):
			config_file = a
		elif o in ("-d", "--daemonize"):
			daemonize = True
		elif o in ("-h", "--help"):
			usage()
			sys.exit()
		elif o in ("-p", "--pid-file"):
			pid_file = a
	
	if len(args) > 0:
		print >> sys.stderr, "Invalid argument '%s'"%(args[0])
		usage()
		sys.exit(2)
	
	if daemonize:
		q = multiprocessing.Queue()
		p = multiprocessing.Process(target=main, args=(q, config_file, pid_file))
		p.start()
		
		# hack: do not join children process at exit
		multiprocessing.process._current_process._children.remove(p)
		code, msg = q.get()
	else:
		code, msg = main(None, config_file, pid_file)
	
	if msg != '':
		print >> sys.stderr, msg
	sys.exit(code)
