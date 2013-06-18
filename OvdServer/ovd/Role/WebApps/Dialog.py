# -*- coding: utf-8 -*-

# Copyright (C) 2012-2013 Ulteo SAS
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

from xml.dom import minidom
from xml.dom.minidom import Document

from ovd.Communication.Dialog import Dialog as AbstractDialog
from ovd.Logger import Logger

from Config import Config, setup_apps
from SessionsRepository import SessionsRepository, Session

        
class Dialog(AbstractDialog):
    def __init__(self, role_instance):
        self.role_instance = role_instance
    
    @staticmethod
    def getName():
        return "webapps"
    
    def process(self, request):
        path = request["path"]
        
        if request["method"] == "GET":
            Logger.debug("[WebApps] do_GET "+path)

            if  path == "/sync":
                Logger.info("[WebApps] Starting config synchronization")
                setup_apps(reset=True)
                return self.req_answerText('OK')
            
            elif path.startswith("/session/status/"):
                buf = path[len("/session/status/"):]
                return self.req_session_status(buf)
            
            elif path.startswith("/session/destroy/"):
                buf = path[len("/session/destroy/"):]
                return self.req_session_destroy(buf)            
   
        elif request["method"] == "POST":
            Logger.debug("[WebApps] do_POST "+path)
            if path == "/session/create":
                return self.req_session_create(request)

        Logger.info("WebApps role Dialog::process(%s)"%(str(request)))
        return None

    def req_session_create(self, request):
        try:
            document = minidom.parseString(request["data"])
            session_node = document.documentElement
            
            if session_node.nodeName != "session":
                raise Exception("invalid root node")
            
            if not session_node.hasAttribute("id"):
                raise Exception("invalid root node")
            
            if not session_node.hasAttribute("mode"):
                raise Exception("invalid root node")
            
            session = {}
            session["id"] = session_node.getAttribute("id")
            session["mode"] = session_node.getAttribute("mode")
            
            if len(session["id"])==0:
                raise Exception("Missing attribute id")
            
            if session["mode"] == "desktop":
                session["mode"] = Session.MODE_DESKTOP
            elif session["mode"] == "applications":
                session["mode"] = Session.MODE_APPLICATIONS
            else:
                raise Exception("Missing attribute id")

            user_node = session_node.getElementsByTagName("user")[0]
            
            for attr in ["login", "password", "displayName", "USER_LOGIN", "USER_PASSWD"]:
                if not user_node.hasAttribute(attr):
                    raise Exception("invalid child node: missing attribute "+attr)
                
                session[attr] = user_node.getAttribute(attr)
            
            published_apps = []
            apps_node = session_node.getElementsByTagName("applications")[0]
            for app_node in apps_node.getElementsByTagName("application"):
                if not app_node.hasAttribute('id'):
                    raise Exception("invalid child node: missing attribute id")
                published_apps.append(app_node.getAttribute('id'))
            
            session['published_applications'] = published_apps

        except Exception, err:
            Logger.warn("Invalid xml input: "+str(err))
            doc = Document()
            root_node = doc.createElement('error')
            root_node.setAttribute("id", "usage")
            doc.appendChild(root_node)
            return self.req_answer(doc)
            
        session = SessionsRepository.create(session)
        
        return self.req_answer(self.session2xmlstatus(session))
        
    def req_session_status(self, session_id):
        session = SessionsRepository.get(session_id, check_active=False)
        if session is None:
            Logger.info("[WebApps] session id={0} not found".format(session_id))
            session = Session(session_id, {})
            session.status = Session.SESSION_STATUS_UNKNOWN
        
        return self.req_answer(self.session2xmlstatus(session))
    
    def req_session_destroy(self, session_id):
        session = SessionsRepository.get(session_id, check_active=False)
        if session is not None:
            session.switch_status(Session.SESSION_STATUS_DESTROYED)
            SessionsRepository.set(session_id, session)
            Logger.info("[WebApps] session id={0} destroyed".format(session_id))
        else:
            Logger.info("[WebApps] session id={0} not found".format(session_id))
            session = Session(session_id, {})
            session.status = Session.SESSION_STATUS_UNKNOWN
        
        return self.req_answer(self.session2xmlstatus(session))
        
    @staticmethod
    def session2xmlstatus(session):
        doc = Document()
        root_node = doc.createElement('session')
        root_node.setAttribute("id", session.id)
        root_node.setAttribute("status", session.status)
        root_node.setAttribute("webapps-port", str(Config.port))
        root_node.setAttribute("webapps-scheme", 'https' if Config.connection_secure else 'http')
        doc.appendChild(root_node)
        return doc