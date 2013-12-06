# -*- coding: utf-8 -*-

# Copyright (C) 2009-2013 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2011
# Author David LECHEVALIER <david@ulteo.com> 2011
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
# Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
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
import time
import threading
import os
import Queue
import socket
import traceback

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
		self.running = False
		
		self.fileHandler = None
		self.consoleHandler = None
		
		self.queue = None
		self.filename = filename
		
		self.thread = None
		self.lock = threading.Lock()
		
		self.hooks = []
		
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
	
	
	def close(self):
		if self.fileHandler is not None:
			if self.fileHandler.stream is not None:
				self.fileHandler.stream.close()
				self.fileHandler.stream = None
	
	
	def setThreadedMode(self, mode):
		if mode is True:
			if not self.isThreaded() and self.queue is not None:
				self.thread = threading.Thread(name="log", target=self.run)
				self.thread.start()
		else:
			if self.isThreaded():
				self.running = False
				self.thread.join()
				
				self.lock.acquire()
				self.queue.close()
				self.queue = None
				self.lock.release()
	
	
	def isThreaded(self):
		return self.thread is not None and self.thread.isAlive()
	
	
	def run(self):
		self.running = True
		while not self.queue._closed and self.running:
			# Python 2.6: raise Empty even when queue is closed.
			
			try:
				# Request queue with a timeout or the close() method freeze on Windows
				(func, obj) = self.queue.get(True, 4)
			except (EOFError, IOError, socket.error):
				break
			except Queue.Empty, e:
				continue
			f = getattr(self, func)
			f(obj)
	
	
	def process(self, func, obj):
		obj = "[%d] %s" % (os.getpid(), obj)
		
		self.lock.acquire()
		if not self.isThreaded() and self.queue is not None:
			try:
				self.queue.put_nowait((func, obj))
			except (EOFError, socket.error):
				return
		else:
			# TODO (6987) : useless ? So remove later 
			#if self.fileHandler is not None and self.fileHandler.stream is None:
			#	self.fileHandler.stream = self.fileHandler._open()
			f = getattr(self, func)
			f(obj)
		
		for hook in self.hooks:
			f = getattr(hook, func[len("log_"):])
			f(obj)
		
		self.lock.release()
	

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
	
	
	def setQueue(self, queue, mode):
		self.queue = queue
		self.setThreadedMode(mode)
	
	# Static methods
	
	@classmethod 
	def initialize(cls, name, loglevel, filename=None, stdout=False, threaded=False):
		instance = Logger(name, loglevel, filename, stdout)
		if threaded:
			instance.setThreadedMode(threaded)
		
		cls.setInstance(instance)
	
	@classmethod 
	def setInstance(cls, instance):
		old_instance = cls._instance
		cls._instance = instance
		
		if old_instance is not None:
			old_instance.setThreadedMode(False)
	
	
	@classmethod 
	def registerHook(cls, hook):
		if not cls._instance:
			return
		
		cls._instance.hooks.append(hook)
	
	
	@classmethod 
	def unregisterHook(cls, hook):
		if not cls._instance:
			return
		
		if hook not in cls._instance.hooks:
			return
		
		cls._instance.hooks.remove(hook)
	
	
	@classmethod
	def info(cls, message):
		if not cls._instance:
			return
		
		if cls._instance.loglevel&cls.INFO != cls.INFO:
			return
		
		cls._instance.process('log_info', message)
	
	
	@classmethod
	def warn(cls, message):
		if not cls._instance:
			return
		
		if cls._instance.loglevel&cls.WARN != cls.WARN:
			return
		
		cls._instance.process('log_warn', message)
	
	
	@classmethod
	def error(cls, message):
		if not cls._instance:
			return
		
		if cls._instance.loglevel&cls.ERROR != cls.ERROR:
			return
		
		cls._instance.process('log_error', message)
	
	
	@classmethod
	def exception(cls, message):
		if not cls._instance:
			return
		
		if cls._instance.loglevel&cls.ERROR != cls.ERROR:
			return
		
		exc = traceback.format_exc()
		cls._instance.process('log_error', message + '\n' + exc)
	
	
	@classmethod
	def debug(cls, message):
		if not cls._instance:
			return
		
		if cls._instance.loglevel&cls.DEBUG != cls.DEBUG:
			return
		
		cls._instance.process('log_debug', message)
	
	
	@classmethod
	def debug2(cls, message):
		if not cls._instance:
			return
		
		if cls._instance.loglevel&cls.DEBUG_2 != cls.DEBUG_2:
			return
		
		cls._instance.process('log_debug2', message)
	
	
	@classmethod
	def debug3(cls, message):
		if not cls._instance:
			return
		
		if cls._instance.loglevel&cls.DEBUG_3 != cls.DEBUG_3:
			return
		
		cls._instance.process('log_debug3', message)
	
	
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
