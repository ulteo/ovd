# -*- coding: utf-8 -*-

# Copyright (C) 2009-2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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
from Queue import Queue
import sys
import time
import threading

class Logger:
	_instance = None
	
	ERROR = 1
	WARN = 2
	INFO = 4
	DEBUG = 8
	DEBUG_2 = 16
	DEBUG_3 = 32
	
	def __init__(self, name, loglevel, filename = None, stdout = False):
		self.logging = None
		self.loglevel = loglevel
		
		self.fileHandler = None
		self.consoleHandler = None
		
		self.filename = filename
		
		self.threaded = False
		self.thread = None
		
		if filename is not None or stdout is not False:	
			formatter = logging.Formatter('%(asctime)s [%(levelname)s]: %(message)s')
			self.logging = logging.getLogger(name)
			self.logging.setLevel(logging.DEBUG)
		
		if filename is not None:
			self.fileHandler = logging.handlers.RotatingFileHandler(filename, maxBytes=1000000, backupCount=2)
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
	
	
	def setThreadedMode(self, mode):
		if mode is True and self.threaded is False:
			self.queue = Queue()
			self.thread = threading.Thread(name="log", target=self.run)
			self.threaded = True
			self.thread.start()
		else:
			self.threaded = False
			if self.thread is not None and self.thread.isAlive():
				self.thread._Thread__stop()
	
	
	def run(self):
		while self.threaded:
			(func, obj) = self.queue.get()
			func(self, obj)
	
	
	def process(self, func, obj):
		if self.threaded:
			self.queue.put_nowait((func, obj))
		else:
			func(self, obj)
	

	def log_info(self, message):
		if self.logging is not None:
			self.logging.info(message)
	
	def log_warn(self, message):
		if self.logging is not None:
			self.logging.warn(message)
	
	def log_error(self, message):
		if self.logging is not None:
			self.logging.error(message)
	
	def log_debug(self, message):
		if self.logging is not None:
			self.logging.debug(message)
	
	def log_debug2(self, message):
		if self.logging is not None:
			self.logging.debug(message)
	
	def log_debug3(self, message):
		if self.logging is not None:
			self.logging.debug(message)
	
	# Static methods

	@staticmethod 
	def initialize(name, loglevel, filename=None, stdout=False, threaded=False):
		instance = Logger(name, loglevel, filename, stdout)
		if threaded:
			instance.setThreadedMode(True)
		
		Logger.setInstance(instance)
		
	@staticmethod 
	def setInstance(instance):
		old_instance = Logger._instance
		
		Logger._instance = instance
		
		if old_instance is not None and old_instance.threaded:
			old_instance.setThreadedMode(False)
	
	@staticmethod
	def info(message):
		if not Logger._instance:
			return
		
		if Logger._instance.loglevel&Logger.INFO != Logger.INFO:
			return
		
		Logger._instance.process(Logger._instance.__class__.log_info, message)
	
	@staticmethod
	def warn(message):
		if not Logger._instance:
			return
		
		if Logger._instance.loglevel&Logger.WARN != Logger.WARN:
			return
		
		Logger._instance.process(Logger._instance.__class__.log_warn, message)
	
	@staticmethod
	def error(message):
		if not Logger._instance:
			return
		
		if Logger._instance.loglevel&Logger.ERROR != Logger.ERROR:
			return
		
		Logger._instance.process(Logger._instance.__class__.log_error, message)
	
	
	@staticmethod
	def debug(message):
		if not Logger._instance:
			return
		
		if Logger._instance.loglevel&Logger.DEBUG != Logger.DEBUG:
			return
		
		Logger._instance.process(Logger._instance.__class__.log_debug, message)
	
	
	@staticmethod
	def debug2(message):
		if not Logger._instance:
			return
		
		if Logger._instance.loglevel&Logger.DEBUG_2 != Logger.DEBUG_2:
			return
		
		Logger._instance.process(Logger._instance.__class__.log_debug2, message)
	
	
	@staticmethod
	def debug3(message):
		if not Logger._instance:
			return
		
		if Logger._instance.loglevel&Logger.DEBUG_3 != Logger.DEBUG_3:
			return
		
		Logger._instance.process(Logger._instance.__class__.log_debug3, message)
	
	
	def get_time_from_line(self, line):
		l = len("2010-01-01 01:01:01")
		
		if len(line) < l:
			return 0
		
		buf = line[:l]
		try:
			t = time.strptime(buf, "%Y-%m-%d %H:%M:%S")
			return time.mktime(t)
		except:
			return None
