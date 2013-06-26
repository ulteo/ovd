# -*- coding: utf-8 -*-

# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Miguel Angel Garcia <mgarcia@pressenter.com.ar> 2012
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

import Queue, threading, socket, multiprocessing
from UserDict import UserDict

from ovd.Logger import Logger
from Config import Config
from Context import Context
from SessionsRepository import SessionsRepository
from Utils import HTTP_403


class ApplicationDefinition(object):
    def __init__(self, app_id, name, rule, base_path, request_processor, start_path):
        self.id = app_id
        self.name = name
        self.rule = rule
        self.base_path = base_path
        self.request_processor = request_processor
        self.start_path = start_path


    def handles(self, communicator):
        host = communicator.http.get_header("X-Forwarded-Host") or \
                communicator.http.get_header("Host")
        if(self.rule.match(host)):
            return True
        return False


    def process(self, communicator):
        # Create Context
        sess_id = SessionsRepository.get_session_id(communicator)
        session = SessionsRepository.get(sess_id) if sess_id else None
        if session is None:
            communicator.send(HTTP_403)
            return

        if self.id not in session['published_applications']:
            communicator.send(HTTP_403)
            return

        path = communicator.http.path[len(self.base_path):]
        context = Context(communicator, session, path)
        self.request_processor.process(context)
        SessionsRepository.set(sess_id, session)


class ApplicationsRepository(object):
    _instance = None
    queue_in = None
    queue_out = None
    lock = None
    
    def __init__(self):
        Logger.info('[WebApps] ApplicationsRepository init')
        
        self.applications = []
        
        self.looping = False
        self.queue_in = multiprocessing.Queue()
        self.queue_out = multiprocessing.Queue()
        self.lock = threading.Lock()
        self.thread = threading.Thread(name="webapp_config", target=self.run)

    def start(self):
        Logger.info("[WebApps] ApplicationsRepository start")
        self.looping = True
        self.thread.start()

    def stop(self):
        Logger.info("[WebApps] ApplicationsRepository stop")
        self.looping = False
        
    def run(self):
        while self.looping:
            try:
                # Request queue with a timeout or the close() method freeze on Windows
                (func_name, args, kwargs) = self.queue_in.get(True, 1)
            except (EOFError, IOError, socket.error):
                Logger.exception("[WebApps] unexpected end of ApplicationsRepository loop")
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
                Logger.error('[WebApps] using not initialized ApplicationsRepository')
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
                    Logger.error('[WebApps] no response from ApplicationsRepository')
                    break
                else:
                    return result
        finally:
            self.lock.release()

    def _register(self, app_def):
        Logger.info('[WebApps] registering new app in ApplicationsRepository')
        self.applications.append(app_def)
        return True

    def _list(self):
        return self.applications
        
    def _reset(self):
        Logger.debug('[WebApps] ApplicationsRepository reset')
        self.applications = []
        return True
  
    @classmethod 
    def initialize(cls):
        instance = ApplicationsRepository()
        cls.setInstance(instance)
        return instance

    @classmethod 
    def setInstance(cls, instance):
        if cls._instance is None:
            Logger.debug('[WebApps] ApplicationsRepository instance set')
            cls._instance = instance
        else:
            Logger.debug('[WebApps] ApplicationsRepository instance already set')
    
    @classmethod
    def register(cls, data):
        if cls._instance:
            return cls._instance.process('_register', data)
        Logger.error('[WebApps] using not initialized ApplicationsRepository')

    @classmethod
    def list(cls):
        if cls._instance:
            return cls._instance.process('_list')
        Logger.error('[WebApps] using not initialized ApplicationsRepository')

    @classmethod
    def reset(cls):
        if cls._instance:
            return cls._instance.process('_reset')
        Logger.error('[WebApps] using not initialized ApplicationsRepository')

    @classmethod
    def get_by_id(cls, app_id):
        if cls._instance is None:
            Logger.error('[WebApps] using not initialized ApplicationsRepository')
        
        for app_def in cls._instance.process('_list'):
            if app_def.id == app_id:
                return app_def

    @classmethod
    def get_name_by_id(cls, app_id):
        return cls.get_by_id(app_id).name
