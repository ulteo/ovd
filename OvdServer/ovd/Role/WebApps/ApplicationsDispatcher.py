# -*- coding: utf-8 -*-

# Copyright (C) 2010-2014 Ulteo SAS
# http://www.ulteo.com
# Author Miguel Angel Garcia <mgarcia@pressenter.com.ar> 2012
# Author Ania WSZEBOROWSKA <anna.wszeborowska@stxnext.pl> 2013
# Author Wojciech LICHOTA <wojciech.lichota@stxnext.pl> 2013
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2013, 2014
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

import urlparse

from ovd.Logger import Logger
from ApplicationsRepository import ApplicationsRepository
from Context import Context
from Config import Config
from SessionsRepository import SessionsRepository, Session
from Utils import HTTP_200_status_header, HTTP_200_status_content


class ApplicationsDispatcher(object):
	CONNECT = '/webapps/connect?'
	DISCONNECT = '/webapps/disconnect?'
	OPEN = '/webapps/open?'
	
	class EDispatchError(Exception):
		pass
	
	
	@staticmethod
	def redirect(communicator, location, cookieName = None, cookieValue = None, cookieDomain = None, cookiePath = None):
		data = '<html><head><title>Moved</title></head><body><h1>Moved</h1></body></html>'
		buffer = 'HTTP/1.1 302 Moved\r\n'
		buffer += 'Location: %s\r\n'%(location)
		
		if cookieName is not None:
			buffer += 'Set-Cookie: %s="%s"; Domain=%s; Path=%s; HttpOnly\r\n'%(cookieName, cookieValue, cookieDomain, cookiePath)
		
		buffer += 'Content-Type: text/html\r\n'
		buffer += 'Content-Length: %i\r\n\r\n'%(len(data))
		buffer += data
		
		communicator.send(buffer)
	
	
	@staticmethod
	def process(communicator):
		path = communicator.http.path
		referer = communicator.http.get_header("Referer")
		host = communicator.http.get_header("X-Forwarded-Host") or \
				communicator.http.get_header("Host")
		Logger.debug("[WebApps] Client requested " + host + path)
		for app_def in ApplicationsRepository.list():
			if app_def.handles(communicator):
				app_def.process(communicator)
				return
		
		# check referer
		if Config.mode == Config.MODE_PATH and referer is not None:
			url = urlparse.urlparse(referer)
			for app_def in ApplicationsRepository.list():
				if url.path.startswith(app_def.base_path):
					# redirect
					new_location = (url.path+'$ROOT$'+path).replace("//", "/")
					ApplicationsDispatcher.redirect(communicator, new_location)
					return
		
		
		if path.startswith(ApplicationsDispatcher.DISCONNECT):
			qs = urlparse.parse_qs(path[len(ApplicationsDispatcher.DISCONNECT):])
			Logger.debug("DEBUG: {0}".format(qs))
			if qs.get('user') and qs.get('pass') and qs.get('id'):
				user = qs['user'][0]
				session = SessionsRepository.find(user, qs['pass'][0])
				Logger.debug("DEBUG: user: {0}, session: {1}".format(user,session))
				if session is not None:
					Logger.debug("[WebApps] session {0} switch status to disconnected".format(session.id))
					sess_id = session.id
					session.switch_status(Session.SESSION_STATUS_INACTIVE)
					SessionsRepository.set(sess_id, session)
					send_buffer = HTTP_200_status_content.format(qs['id'][0], "disconnected")
					send_buffer = HTTP_200_status_header.format(len(send_buffer)) + '\r\n\r\n' + send_buffer
					communicator.send(send_buffer)
					return
				else:
					Logger.warn("[WebApps] no session for user {0}".format(user))
		
		if path.startswith(ApplicationsDispatcher.CONNECT):
			qs = urlparse.parse_qs(path[len(ApplicationsDispatcher.CONNECT):])
			if qs.get('user') and qs.get('pass') and qs.get('id'):
				user = qs['user'][0]
				session = SessionsRepository.find(user, qs['pass'][0])
				if session is not None:
					sess_id = session.id
					if session.status in [Session.SESSION_STATUS_INITED, Session.SESSION_STATUS_INACTIVE]:
						session.switch_status(Session.SESSION_STATUS_ACTIVE)
						SessionsRepository.set(sess_id, session)
						send_buffer = HTTP_200_status_content.format(qs['id'][0], "ready")
						send_buffer = HTTP_200_status_header.format(len(send_buffer)) + '\r\n\r\n' + send_buffer
						communicator.send(send_buffer)
						return
					else:
						Logger.warn("[WebApps] can't login to not new session id={0}".format(sess_id))
				else:
					Logger.warn('[WebApps] no session for user {0}'.format(user))
		
		## if user is redirected from ovd client let check his credentials
		## and redirect to domain when app works
		if path.startswith(ApplicationsDispatcher.OPEN):
			qs = urlparse.parse_qs(path[len(ApplicationsDispatcher.OPEN):])
			if qs.get('user') and qs.get('pass') and qs.get('id'):
				app_id = qs['id'][0]
				user = qs['user'][0]
				session = SessionsRepository.find(user, qs['pass'][0])
				if session is not None and app_id:
					sess_id = session.id
					if session.status == Session.SESSION_STATUS_ACTIVE:
						app_name = ApplicationsRepository.get_name_by_id(app_id)
						if app_name:
							if app_id in session['published_applications']:
								session.switch_status(Session.SESSION_STATUS_ACTIVE)
								SessionsRepository.set(sess_id, session)
								Logger.info('[WebApps] session id={0} for user {1} activated'.format(sess_id, user))
								
								prot = Config.connection_secure and 'https' or 'http'
								
								if Config.mode == Config.MODE_DOMAIN:
									new_host = '{0}://{1}.{2}{3}'.format(prot, app_name, host, ApplicationsRepository.get_by_id(app_id).start_path)
									host_wo_port = host.split(':')[0]
									ApplicationsDispatcher.redirect(communicator, new_host, Config.ulteo_session_cookie, sess_id, "."+host_wo_port, "/")
									return
								else: # mode path
									new_host = '{0}://{1}/webapps/{2}{3}'.format(prot, host, app_name, ApplicationsRepository.get_by_id(app_id).start_path)
									cookie_path = '/webapps/%s%s'%(app_name, ApplicationsRepository.get_by_id(app_id).start_path)
									host_wo_port = host.split(':')[0]
									ApplicationsDispatcher.redirect(communicator, new_host, Config.ulteo_session_cookie, sess_id, host_wo_port, cookie_path)
									return
							else:
								Logger.warn('[WebApps] user {0} is not allowed to open {1}'.format(user, app_id))
						else:
							Logger.warn('[WebApps] no config for app with id {0}'.format(app_id))
					else:
						Logger.warn('[WebApps] can\'t open all when session id={0} is not active'.format(sess_id))
				else:
					Logger.warn('[WebApps] no session for user {0}'.format(user))
					
		raise ApplicationsDispatcher.EDispatchError()
