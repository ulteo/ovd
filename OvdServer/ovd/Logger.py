# -*- coding: utf-8 -*-

# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009
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


import logging.handlers
import sys

class Logger:
	_instance = None
	
	ERROR = 8
	WARN = 4
	INFO = 2
	DEBUG = 1
	
	def __init__(self, name, loglevel, file = None, stdout = False):
		self.logging = None
		self.loglevel = loglevel
		
		self.fileHandler = None
		self.consoleHandler = None
		
		self.file = file
		
		if file is not None or stdout is not False:	
			formatter = logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s')
			self.logging = logging.getLogger(name)
			self.logging.setLevel(logging.DEBUG)
		
		if file is not None:
			self.fileHandler = logging.handlers.RotatingFileHandler(file, maxBytes=1000000, backupCount=2)
			self.fileHandler.setFormatter(formatter)
			self.logging.addHandler(self.fileHandler)
	
		if stdout is not False:
			self.consoleHandler = logging.StreamHandler(sys.stdout)
			self.consoleHandler.setFormatter(formatter)
			self.logging.addHandler(self.consoleHandler)
			
	def __del__(self):
		if self.logging is not None:
			if self.fileHandler is not None:
				self.logging.removeHandler(self.fileHandler)

			if self.consoleHandler is not None:
				self.logging.removeHandler(self.consoleHandler)

	def log_info(self, message):
		if self.loglevel&Logger.INFO != Logger.INFO:
			return False
		
		if self.logging is not None:
			self.logging.info(message)
	
	def log_warn(self, message):
		if self.loglevel&Logger.WARN != Logger.WARN:
			return False
		
		if self.logging is not None:
			self.logging.warn(message)
	
	def log_error(self, message):
		if self.loglevel&Logger.ERROR != Logger.ERROR:
			return False
		
		if self.logging is not None:
			self.logging.error(message)
	
	def log_debug(self, message):
		if self.loglevel&Logger.DEBUG != Logger.DEBUG:
			return False
		
		if self.logging is not None:
			self.logging.debug(message)
	
	# Static methods

	@staticmethod 
	def initialize(name, loglevel, file=None, stdout=False):
		instance = Logger(name, loglevel, file, stdout)
		Logger.setInstance(instance)
		
	@staticmethod 
	def setInstance(instance):
		Logger._instance = instance
	
	@staticmethod
	def info(message):
		#print "LOG",message
		if not Logger._instance:
			return
		Logger._instance.log_info(message)
	
	@staticmethod
	def warn(message):
		#print "LOG",message
		if not Logger._instance:
			return
		Logger._instance.log_warn(message)
	
	@staticmethod
	def error(message):
		#print "LOG",message
		if not Logger._instance:
			return
		Logger._instance.log_error(message)
	
	
	@staticmethod
	def debug(message):
		#print "LOG",message
		if not Logger._instance:
			return
		Logger._instance.log_debug(message)