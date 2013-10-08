# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Miguel Angel Garcia <mgarcia@pressenter.com.ar> 2012
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

import Queue, threading, socket, multiprocessing
from UserDict import UserDict

from ovd.Logger import Logger
from Config import Config
from headers_utils import parse_request_headers, request_headers_get_cookies
import random

from ovd.SMRequestManager import SMRequestManager
from xml.dom.minidom import Document

class Session(UserDict):
	SESSION_STATUS_UNKNOWN = "unknown"
	SESSION_STATUS_ERROR = "error"
	SESSION_STATUS_INITED = "ready"
	SESSION_STATUS_ACTIVE = "logged"
	SESSION_STATUS_INACTIVE = "disconnected"
	SESSION_STATUS_WAIT_DESTROY = "wait_destroy"
	SESSION_STATUS_DESTROYED = "destroyed"
	
	MODE_DESKTOP = "desktop"
	MODE_APPLICATIONS = "applications"
	
	def __init__(self, id_, data):
		UserDict.__init__(self)
		self.id = id_
		self.update(data)
		self.status = Session.SESSION_STATUS_INITED
		self.sm_request_manager = SMRequestManager()
		self.sm_request_manager.initialize();
	
	def switch_status(self, status_):
		self.status = status_
		self.send_server_status()
	
	def credentials(self):
		return {
			'USE_CURRENT_USER_LOGIN': self.get('USER_LOGIN', ''),
			'USE_CURRENT_USER_PASSWD': self.get('USER_PASSWD', ''),
		}
	
	def send_server_status(self):
		doc = None;
		try:
			doc = Document()
			rootNode = doc.createElement('session')
			rootNode.setAttribute("id", self.id)
			rootNode.setAttribute("status", self.status)
			rootNode.setAttribute("role", "webapps")
			doc.appendChild(rootNode)
		except Exception, e:
			Logger.warn("WebappsServer: unable to format session status xml: %s"(str(e)))
		
		response = self.sm_request_manager.send_packet("/session/status", doc)
		Logger.debug2("WebappsServer: send_session_status: %s"%(response))
		
		if response is False:
			Logger.warn("WebappsServer: unable to send session status")
		else:
			response.close()
			response = None


class SessionsRepository(object):
	_instance = None
	queue_in = None
	queue_out = None
	lock = None
	
	def __init__(self):
		Logger.info('[WebApps] SessionsRepository init')
		
		self.sessions = {}
		
		self.looping = False
		self.queue_in = multiprocessing.Queue()
		self.queue_out = multiprocessing.Queue()
		self.lock = threading.Lock()
		self.thread = threading.Thread(name="webapps_session", target=self.run)
	
	def start(self):
		Logger.info("[WebApps] SessionsRepository start")
		self.looping = True
		self.thread.start()
	
	def stop(self):
		Logger.info("[WebApps] SessionsRepository stop")
		self.looping = False
	
	def run(self):
		while self.looping:
			try:
				# Request queue with a timeout or the close() method freeze on Windows
				(func_name, args, kwargs) = self.queue_in.get(True, 1)
			except (EOFError, IOError, socket.error):
				Logger.exception("[WebApps] unexpected end of SessionsRepository loop")
				break
			except Queue.Empty, e:
				continue
			
			func = getattr(self, func_name)
			result = func(*args, **kwargs)
			self.queue_out.put(result)
	
	def process(self, func_name, *args, **kwargs):
		self.lock.acquire()
		try:
			## send message to start operation
			if self.queue_in is None:
				Logger.error('[WebApps] using not initialized SessionsRepository')
				return
				
			try:
				self.queue_in.put((func_name, args, kwargs))
			except (EOFError, socket.error):
				Logger.exception('[WebApps] error when running {0}'.format(func_name))
				return

			## wait for response
			while True:
				try:
					result = self.queue_out.get(True, 5)
				except Queue.Empty, e:
					Logger.error('[WebApps] no response from SessionsRepository')
					break
				else:
					return result
		finally:
			self.lock.release()
	
	def _create(self, data):
		sess_id = data.pop('id')
		Logger.info('[WebApps] creating session id={0} for user {1}'.format(sess_id, data.get('login')))
		data.setdefault('cookies', {})
		session = Session(sess_id, data)
		self.sessions[sess_id] = session
		return session
	
	def _find(self, login, password):
		if not login or not password:
			return
		for session in self.sessions.values():
			if session.get('login') == login and session.get('password') == password:
				return session
	
	def _get(self, sess_id, check_active=True):
		session = self.sessions.get(sess_id)
		if session is None:
			Logger.warn('[WebApps] session id={0} not found'.format(sess_id))
			return
		if check_active and session.status != Session.SESSION_STATUS_ACTIVE:
			Logger.warn('[WebApps] session id={0} is not active'.format(sess_id))
			return
		return session
	
	def _set(self, sess_id, session):
		Logger.debug('[WebApps] session id={0} updated'.format(sess_id))
		self.sessions[sess_id] = session
	
	@classmethod 
	def initialize(cls):
		instance = SessionsRepository()
		cls.setInstance(instance)
		return instance
	
	@classmethod 
	def setInstance(cls, instance):
		if cls._instance is None:
			Logger.debug('[WebApps] SessionsRepository instance set')
			cls._instance = instance
		else:
			Logger.debug('[WebApps] SessionsRepository instance already set')
	
	@classmethod
	def create(cls, data):
		if cls._instance:
			return cls._instance.process('_create', data)
		Logger.error('[WebApps] using not initialized SessionsRepository')
	
	@classmethod
	def find(cls, login, password):
		if cls._instance:
			return cls._instance.process('_find', login, password)
		Logger.error('[WebApps] using not initialized SessionsRepository')
	
	@classmethod
	def get(cls, sess_id, check_active=True):
		if cls._instance:
			return cls._instance.process('_get', sess_id, check_active)
		Logger.error('[WebApps] using not initialized SessionsRepository')
	
	@classmethod
	def set(cls, sess_id, session):
		if cls._instance:
			return cls._instance.process('_set', sess_id, session)
		Logger.error('[WebApps] using not initialized SessionsRepository')
		
	@staticmethod
	def get_session_id(communicator):
		headers = parse_request_headers(communicator)
		cookies = request_headers_get_cookies(headers)
		if Config.ulteo_session_cookie in cookies:
			sess_id = cookies[Config.ulteo_session_cookie].strip('"')
			return sess_id
