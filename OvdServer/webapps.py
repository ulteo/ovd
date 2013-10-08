#! /usr/bin/env python
# -*- coding: utf-8 -*-

# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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

import json
import asyncore
import socket
import re
from optparse import OptionParser

from ovd.Config import Config
from ovd.Exceptions import InterruptedException
from ovd.Logger import Logger

from ovd.Role.WebApps.Config import setup_app
from ovd.Role.WebApps.ApplicationsRepository import ApplicationsRepository
from ovd.Role.WebApps.SessionsRepository import SessionsRepository, Session
from ovd.Role.WebApps.ProtocolDetectDispatcher import HttpProtocolDetectDispatcher

class f_ctrl(object):
	pass

class WebAppsServer(asyncore.dispatcher):
	def __init__(self, host, port):
		asyncore.dispatcher.__init__(self)
		self.create_socket(socket.AF_INET, socket.SOCK_STREAM)
		self.set_reuse_addr()
		self.bind((host, port))
		self.listen(5)

	def handle_accept(self):
		pair = self.accept()
		if pair is not None:
			sock, addr = pair
			Logger.info('[WebApps] Incoming connection from %s' % repr(addr))
			handler = HttpProtocolDetectDispatcher(sock, f_ctrl())

def main():
	parser = OptionParser()
	parser.add_option("-V", "--verbose", action="store_true", dest="verbose", default=False, help="Print more informations")
	parser.add_option("-a", "--address", action="store_true", dest="address", default="127.0.0.1", help="TCP Bind ip address")
	parser.add_option("-p", "--port", action="store_true", dest="port", default=3000, help="TCP port to bind")
	parser.add_option("-c", "--config", action="store", type="string", dest="config", default=None, help="Json configuration file")
	parser.add_option("-v", "--value", action="append", nargs=2, type="string", dest="values", default=[], help="Configuration value")
	options, args = parser.parse_args()
	
	if not options.config:
		parser.error("You must supply a configuration file")

	loglevel = Logger.ERROR|Logger.WARN
	if options.verbose:
		loglevel |= Logger.INFO|Logger.DEBUG|Logger.DEBUG_2|Logger.DEBUG_3

	Logger.initialize("WEBAPPS", loglevel, stdout=True)
	Logger.info("Start webapps")
	
	repo = ApplicationsRepository.initialize()
	SessionsRepository.debug = True
	sess = SessionsRepository.initialize()
	sess.start()
	repo.start()
	
	my_sess = {'id': 1, 'published_applications': (1, ), 'login':'Test'}
	for k, v in options.values:
		my_sess[k] = v
	
	SessionsRepository.create(my_sess)
	SessionsRepository._instance.sessions[1].switch_status(Session.SESSION_STATUS_ACTIVE)

	server = WebAppsServer(options.address, int(options.port))
	try:
		config = json.load(open(options.config, "r"))
		for k, v in options.values:
			config['Configuration'][k]['value'] = v
		
		app_id = 1
		app_name = "app"
		appl = setup_app({"app":config}, app_id, app_name, "domain")
		if not appl:
			parser.error("Invalid parameters")
		
		appl.rule = re.compile('')
		ApplicationsRepository.register(appl)
		try:
			print "Connect to http://%s:%s/" % (options.address, options.port)
			asyncore.loop()
		except (InterruptedException, KeyboardInterrupt):
			Logger.info("interruption")
	
	finally:
		server.close()
		repo.stop()
		sess.stop()

if __name__ == "__main__":
	main()
