#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008, 2010, 2011
# Author Laurent CLOUET <laurent@ulteo.com> 2009, 2010
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

import commands
import cookielib
import datetime
import getopt
import getpass
import os
import re
import signal
import sys
import threading
import time
import urllib
import urllib2
from xml.dom import minidom
from xml.dom.minidom import Document
from xml.parsers.expat import ExpatError

class Logger:
    _instance = None

    ERROR = 8
    WARN = 4
    INFO = 2
    DEBUG = 1

    def __init__(self, loglevel):
        self.loglevel = loglevel

    def log_info(self, message):
        if self.loglevel&Logger.INFO != Logger.INFO:
            return

        print str(datetime.datetime.now().ctime()), "[INFO]", message

    def log_warn(self, message):
        if self.loglevel&Logger.WARN != Logger.WARN:
            return

        print str(datetime.datetime.now().ctime()), "[WARN]", message

    def log_error(self, message):
        if self.loglevel&Logger.ERROR != Logger.ERROR:
            return

        print str(datetime.datetime.now().ctime()), "[ERROR]", message

    def log_debug(self, message):
        if self.loglevel&Logger.DEBUG != Logger.DEBUG:
            return

        print str(datetime.datetime.now().ctime()), "[DEBUG]", message

    # Static methods
    @staticmethod 
    def initialize(loglevel):
        instance = Logger(loglevel)
        Logger._instance = instance

    @staticmethod
    def info(message):
        if not Logger._instance:
            return
        Logger._instance.log_info(message)

    @staticmethod
    def warn(message):
        if not Logger._instance:
            return
        Logger._instance.log_warn(message)

    @staticmethod
    def error(message):
        if not Logger._instance:
            return
        Logger._instance.log_error(message)

    @staticmethod
    def debug(message):
        if not Logger._instance:
            return
        Logger._instance.log_debug(message)


class Dialog:
    def __init__(self, conf):
        self.conf = conf
        self.base_url = "https://%s/ovd/client"%(conf["host"])
        self.sessionProperties = {}

        cookiejar = cookielib.CookieJar()
        self.urlOpener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookiejar))

        self.desktopStatus = -1
        self.sessionStatus = -1

    def doStartSession(self, args = {}):
        url = self.base_url+"/start.php"
        
        doc = Document()
        sessionNode = doc.createElement('session')
        sessionNode.setAttribute("mode", "desktop")
        userNode = doc.createElement("user")
        userNode.setAttribute("login", self.conf["login"])
        userNode.setAttribute("password", self.conf["password"])
        sessionNode.appendChild(userNode)
        
        if args.has_key("start-apps"): # launch applications at the session startup
            startappsNode = doc.createElement("start")
            for appid in args["start-apps"]:
                appNode = doc.createElement("application")
                appNode.setAttribute("id", appid)
                startappsNode.appendChild(appNode)
            sessionNode.appendChild(startappsNode)
        doc.appendChild(sessionNode)
        
        request = urllib2.Request(url, doc.toxml())
              
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                Logger.info("The service is not available")
                return False

            Logger.debug("HTTP request return code %d (%s)" % (exc.code, exc.msg))
            return False

        except urllib2.URLError, exc:
            Logger.warn("Startsession failure: %s"%(exc.reason))
            return False

        headers = url.info()
        if not headers['Content-Type'].startswith('text/xml'):
            Logger.warn("Invalid response format")
            Logger.debug('response format %s'%(headers['Content-Type']))
            return False

        data = url.read()
        try:
            dom = minidom.parseString(data)
        except ExpatError:
            Logger.warn("Invalid XML result")
            Logger.debug("data received %s"%(data))
            return False

        node = dom.getElementsByTagName('session')
        if len(node) != 1:
            Logger.warn("No session root node")
            Logger.debug("data received %s"%(data))
            return False

        node = node[0]
        if not node.hasAttribute("mode"):
                Logger.warn("Missing attribute mode")
                Logger.debug("data received %s"%(data))
                return False
        self.sessionProperties["mode"] = node.getAttribute("mode")
        
        for attr in ['shareable', 'persistent', 'multimedia', 'redirect_client_printers']:
            if node.hasAttribute(attr):
                buf = node.getAttribute(attr)
            else:
                buf = "false"

            if buf == 'true' or buf == '1':
                buf = True
            elif buf == 'false' or buf == '0':
                buf = False
            else:
                Logger.warn("Invalid attribure %s value (%s)"%(attr, buf))
                return False

            self.sessionProperties[attr] = buf

        userNode = node.getElementsByTagName('user')
        if len(userNode) != 1:
            Logger.warn("Missing node user")
            Logger.debug("data received %s"%(data))
            return False
        userNode = userNode[0]
        if not userNode.hasAttribute("displayName"):
            Logger.warn("Missing attribute displayName on node user")
            Logger.debug("data received %s"%(data))
            return False
        self.sessionProperties["user_dn"] = userNode.getAttribute("displayName")

        node = node.getElementsByTagName('server')
        if len(node) < 1:
            Logger.warn("No server child node from root node")
            Logger.debug("data received %s"%(data))
            return False

        node = node[0]
        self.access = {}
        for attr in ['fqdn', 'login', 'password']:
            if not node.hasAttribute(attr):
                Logger.warn("Missing attribute %s"%(str(attr)))
                Logger.debug("data received %s"%(data))
                return False
            self.access[attr] = node.getAttribute(attr)

        return True

    def doSessionStatus(self):
        url = "%s/session_status.php"%(self.base_url)
        request = urllib2.Request(url)
        
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                Logger.warn("Service failure")
                return False
            
            Logger.debug("HTTP request return code %d (%s)" % (exc.code, exc.msg))
            Logger.debug(" * return: %s"%(str(exc.read())))
            return False

        except urllib2.URLError, exc:
            Logger.warn("Service failure: %s"%(str(exc.reason)))
            return False

        headers = url.info()
        if not headers['Content-Type'].startswith('text/xml'):
            Logger.warn("Invalid response format")
            Logger.debug(" * format: %s"%(str(headers['Content-Type'])))
            return False

        data = url.read()
        try:
            dom = minidom.parseString(data)
        except ExpatError:
            Logger.warn("Invalid XML result")
            Logger.debug("data received %s"%(data))
            return False

        sessionNode = dom.getElementsByTagName('session')
        if len(sessionNode) != 1:
            Logger.warn("Bad xml result")
            Logger.debug("data received %s"%(data))
            return False

        sessionNode = sessionNode[0]
        if not sessionNode.hasAttribute('status'):
            Logger.warn("Bad xml result")
            Logger.debug("data received %s"%(data))
            return False

        self.sessionStatus = sessionNode.getAttribute('status')

        return self.sessionStatus


    def do_call_exit(self):
        if d.sessionProperties["persistent"]:
            mode = "suspend"
        else:
            mode = "logout"
        
        document = Document()
        rootNode = document.createElement('logout')
        rootNode.setAttribute("mode", mode)
        document.appendChild(rootNode)
        
        url = "%s/logout.php"%(self.base_url)
        request = urllib2.Request(url)
        request.add_header("Content-type", "text/xml; charset=UTF-8")
        request.add_data(document.toxml())
        
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                Logger.warn("Service failurek")
                return False

            Logger.debug("HTTP request return code %d (%s)" % (exc.code, exc.msg))
            Logger.debug(" * return: %s"%(str(exc.read())))
            return False

        except urllib2.URLError, exc:
            Logger.warn("Failure: %s"%(str(exc.reason)))
            return False

        return True


    def check_whatsup(self):
        Logger.debug("Begin check")

        old_status = "created"
        while 1==1:
            status = self.doSessionStatus()
            if status != old_status:
                Logger.info("Status changed: %s -> %s"%(old_status, status))
                old_status = status
            if status == "logged":
                time.sleep(55)
            time.sleep(5)

    def launch(self):
        cmd = []
        cmd.append("rdesktop")
        cmd.append("-u")
        cmd.append(self.access["login"])
        cmd.append("-p")
        cmd.append(self.access["password"])
        if self.conf['fullscreen']:
            cmd.append("-f")
        else:
            cmd.append("-g")
            cmd.append("x".join(self.conf["geometry"]))
        cmd.append("-z")
        cmd.append("-T")
        cmd.append('"Ulteo OVD - %s"'%(self.sessionProperties["user_dn"]))

        if self.sessionProperties["multimedia"]:
             cmd.append("-r")
             cmd.append("sound:local")
        
        if self.sessionProperties["redirect_client_printers"]:
            status, out = commands.getstatusoutput("LANG= lpstat -d -p")
            print "printer status: ",status
            print "out: ",out
            print "=="

            
            if status in [127, 32512]:
                Logger.warn("Missing cupsys-client, unable to detect local printers")
            else:
                printers = []
                lines = out.splitlines()

                line = lines[0]
                if line.startswith("system default destination:"):
                    buf = line[len("system default destination:"):].strip()
                    printers.append(buf)

                for line in lines[1:]:
                    buf = line.split(" ")
                    if buf[0] != "printer":
                        continue
                    buf = buf[1]
                    if buf not in printers:
                        printers.append(buf)

                for printer in printers:
                    cmd.append("-r")            
                    cmd.append("printer:%s"%(printer))

        if self.conf.has_key('quality'):
            if self.conf['quality'] == 'lowest':
                bpp = 8

            elif self.conf['quality'] == 'medium':
                bpp = 16

            elif self.conf['quality'] == 'high':
                bpp = 24
            elif self.conf['quality'] == 'highest':
                bpp = 32
                
            cmd.append("-a")
            cmd.append(str(bpp))

        cmd.append(self.access["fqdn"])

        t = threading.Thread(target=self.check_whatsup)
        t.start()

        cmd = " ".join(cmd)
        Logger.debug("RDP command: '%s'"%(cmd))

        flag_continue = True
        try_ = 0

        while try_<5 and flag_continue:
            t0 = time.time()
            try:
                status, out = commands.getstatusoutput(cmd)
            except KeyboardInterrupt: # ctrl+c of the user
                Logger.info("Interrupt from user")
                status = 0

            t1 = time.time()

            if t1-t0<2 and status == 256:
                Logger.warn("Unable to connect to RDP server, sleep and try again (%d/5)"%(try_+1))
                time.sleep(0.3)
                try_+= 1
            else:
                flag_continue = False

        if status!=0:
            Logger.info("rdesktop return status %d and \n%s\n==="%(status, out))
            self.do_call_exit()

        if t.isAlive():
            t._Thread__stop()

        Logger.debug("end")
        return True

def handler_signal(signum, frame):
    d.do_call_exit() # d is the client (global variable)

def usage():
    print "Usage: %s [options] ovd_sm_host"%(sys.argv[0])
    print
    print "Options:"
    print "\t--extra-startsession-opt=key:value[,key1:value1...]"
    print "\t--start-apps=value[,value1,value2,...]"
    print "\t-f|--fullscreen"
    print "\t-g|--geometry=WIDTHxHEIGHT"
    print "\t-h|--help"
    print "\t-l|--login=username"
    print "\t-p|--password=PASSWORD"
    print "\t-q|--quality=lowest|medium|high|highest"
    print "\t--quiet"
    print "\t--verbose"
    print

conf = {}
conf["fullscreen"] = False
conf["geometry"] = "800x600"
conf["login"] = os.environ["USER"]

logger_flags = Logger.ERROR | Logger.INFO | Logger.WARN

extra_args = {}

try:
    opts, args = getopt.getopt(sys.argv[1:], 'fg:hl:p:q:', ['extra-startsession-opt=',
                                                            'fullscreen',
                                                            'geometry=',
                                                            'help',
                                                            'login=',
                                                            'password=',
                                                            'quality=',
                                                            'quiet',
                                                            'verbose',
                                                            'start-apps='])
    
except getopt.GetoptError, err:
    print >> sys.stderr, str(err)
    usage()
    sys.exit(2)

if not len(args)>0:
    print >> sys.stderr, "Missing host"
    usage()
    sys.exit(2)

conf["host"] = args[0]
for o, a in opts:
    if o in ("-l", "--login"):
        conf["login"] = a
    elif o in ("-p", "--password"):
        conf["password"] = a
    elif o in ("-f", "--fullscreen"):
        conf["fullscreen"] = True
    elif o in ("-g", "--geometry"):
        conf["geometry"] = a
    elif o in ("-h", "--help"):
        usage()
        sys.exit()
    elif o in ("-q", "--quality"):
        if a.lower() not in ['lowest', 'medium', 'high', 'highest']:
            print >> sys.stderr, "Invalid quality option",a
            usage()
            sys.exit(2)
        conf['quality'] = a.lower()
    elif o == "--quiet":
        logger_flags = Logger.ERROR
    elif o == "--extra-startsession-opt":
        items = a.split(",")
        if len(items)==0:
            print >> sys.stderr, "Invalid extra-startsession-opt option",a
            usage()
            sys.exit(2)
        
        for item in a.split(","):
            (k,v) = item.split(":")
            if len(k)==0 or len(v)==0:
                print >> sys.stderr, "Invalid extra-startsession-opt option",item
                usage()
                sys.exit(2)
            
            extra_args[k] = v
    elif o == "--start-apps":
        if a == "":
            print >> sys.stderr, "Invalid start-apps option",a
            usage()
            sys.exit(2)
        items = a.split(",")
        extra_args['start-apps'] = items
    elif o == "--verbose":
        logger_flags |= Logger.DEBUG
        

if conf["fullscreen"] == True:
    (status, out) = commands.getstatusoutput('xdpyinfo |grep dimensions:')
    s = re.search('([0-9]+)x([0-9]+) pixels', out)
    if s is None:
        print >> sys.stderr, "Unable to get the screen resolution"
        sys.exit(2)

    conf["geometry"] = s.groups()
else:
    conf["geometry"] = conf["geometry"].split("x")
    if len(conf["geometry"])!=2:
        print >> sys.stderr, "Invalid geometry ",conf["geometry"]
        usage()
        sys.exit(2)

# Initialize the Logger instance
Logger.initialize(logger_flags)

if not conf.has_key("password"):
    Logger.info("Connect to '%s' with user '%s'"%(conf["host"], conf["login"]))
    conf["password"] = getpass.getpass("Password please: ")

d = Dialog(conf)

if not d.doStartSession(extra_args):
    Logger.error("Unable to startsession")
    sys.exit(2)

signal.signal(signal.SIGTERM, handler_signal)

Logger.debug("Session properties: %s"%(str(d.sessionProperties)))
if d.sessionProperties["mode"] != 'desktop':
    Logger.error("Doesn't support session mode '%s'"%(str(d.sessionProperties["mode"])))
    sys.exit(0)


status = -1
while status not in ["ready"]:
    status = d.doSessionStatus()
    Logger.debug("status %s"%(str(status)))

    if type(status) == type(False):
        Logger.error("Error in get status")
        sys.exit(5)
    
    time.sleep(2.0)
    if not status in ["init", "ready"]:
        Logger.error("Session not 'init' or 'ready' (%s) => exit"%(status))
        sys.exit(4)

d.launch()

Logger.debug("end")




