#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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
import getopt
import getpass
import os
import random
import re
import signal
import sys
import tempfile
import threading
import time
import urllib
import urllib2
from xml.dom import minidom
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

        print "[INFO]",message

    def log_warn(self, message):
        if self.loglevel&Logger.WARN != Logger.WARN:
            return

        print "[WARN]",message

    def log_error(self, message):
        if self.loglevel&Logger.ERROR != Logger.ERROR:
            return

        print "[ERROR]",message

    def log_debug(self, message):
        if self.loglevel&Logger.DEBUG != Logger.DEBUG:
            return

        print "[DEBUG]",message

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


def str2hex(str_):
    return str_.encode('hex')

def hex2str(hex_):
    try:
        return hex_.decode('hex')
    except TypeError:
        Logger.error("Cant decode this string '%s'"%(str(hex_)))
        sys.exit(1)


def parse_access(data):
    
    res = {}
    dom = minidom.parseString(data)

    node = dom.getElementsByTagName('ssh')
    if len(node) != 1:
        Logger.warn("Bad xml result")
        return False

    node = node[0]
    for (attr, m) in [('host','host'), ('user', 'login'), ('passwd', 'pass') ]:
        if not node.hasAttribute(attr):
            Logger.warn("Bad xml result")
            return False

        res['ssh_'+m] = node.getAttribute(attr)


    res['ssh_port'] = node.getElementsByTagName('port')[0].firstChild.data

    node = dom.getElementsByTagName('vnc')
    if len(node) != 1:
        Logger.warn("Bad xml result")
        return False

    node = node[0]
    for (attr, m) in [('passwd', 'pass'), ('port', 'port')]:
        if not node.hasAttribute(attr):
            Logger.warn("Bad xml result")
            return False

        res['vnc_'+m] = node.getAttribute(attr)

    if node.hasAttribute('quality'):
        buf = node.getAttribute('quality')
        if buf not in ['lowest', 'medium', 'high', 'highest']:
            Logger.warn("Warning: doesn't support quality '%s'"%(buf))
        res['vnc_quality'] = buf

    res["vnc_pass"] = hex2str(res["vnc_pass"])
    res["ssh_pass"] = hex2str(res["ssh_pass"])
    return res


def launch_ssh(host, user, password, extra):
    cmd_args = ["/usr/bin/ssh", "-l", user]
    for arg in extra:
        cmd_args.append(arg)
    cmd_args.append(host)
    Logger.debug("SSH command '%s'"%(" ".join(cmd_args)))
    
    # Fork a child process, using a new pseudo-terminal as the child's controlling terminal.
    pid, fd = os.forkpty()
    # If Child; execute external process
    if pid == 0:
        os.execv(cmd_args[0], cmd_args)
        # Should not appear
        sys.exit(0)
     
    # if parent, read/write with child through file descriptor
    buf = os.read(fd, 1000)

    while "password:" not in buf:
        # Logger.debug("new ssh output: '%s'"%(buf))
        time.sleep(0.3)
        buf += os.read(fd, 1)

    # write password
    os.write(fd, password + "\n")
    
    # Get password prompt; ignore
    for line in buf.splitlines():
        Logger.debug("SSH out: %s"%(line))

    pid2 = os.fork()
    if pid2 == 0:
        # read response from child process
        res = ""
        s = os.read(fd,1 )
        while s:
            res += s
            try:
                s = os.read(fd, 1)
            except:
                # if we can't read the fd the subprocess ended,
                # and the session is likely to be ended
                break
        sys.exit(0)

    return pid,pid2


class Dialog:
    def __init__(self, conf):
        self.conf = conf
        self.base_url = conf["url"]
        self.sessionProperties = {}

        cookiejar = cookielib.CookieJar()
        self.urlOpener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookiejar))

        self.desktopStatus = -1
        self.sessionStatus = -1

    def doLogin(self):
        url = self.base_url+"/ajax/login.php"

        values =  {'do_login': 1, 'login'  : self.conf['login'], 'password' : self.conf['password']}
        data = urllib.urlencode(values)
        request = urllib2.Request(url, data)
        
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                Logger.info("The service is not available")
                return False

            Logger.debug("HTTP request return code %d (%s)"%(exc.code, exc.msg))
            Logger.debug(" * return: %s"%(str(exc.read())))
            return False

        except urllib2.URLError, exc:
            Logger.warn("Login failure: %s"%(str(exc.reason)))
            return False

        return True

    def doStartSession(self, args = {}):
        url = self.base_url+"/startsession.php"
        values = {"session_mode":"desktop"}
        for (k,v) in args.items():
            if not values.has_key(k):
                values[k] = v
            else:
                Logger.warn("Cannot overwrite option '%s' to '%s'"%(k, v))
 
        request = urllib2.Request(url, urllib.urlencode(values))
              
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
            return False

        data = url.read()
        try:
            dom = minidom.parseString(data)
        except ExpatError:
            Logger.warn("Invalid XML result")
            return False

        node = dom.getElementsByTagName('session')
        if len(node) != 1:
            Logger.warn("No session root node")
            return False

        node = node[0]
        for attr in ['mode', 'shareable', 'persistent']:
            if not node.hasAttribute(attr):
                Logger.warn("Missing attribute %s"%(str(attr)))
                return False
            buf = node.getAttribute(attr)
            if attr in ['shareable', 'persistent']:
                if buf == 'true':
                    buf = True
                elif buf == 'false':
                    buf = False
                else:
                    Logger.warn("Invalid attrbiture %s value (%s)"%(attr, buf))
                    return False

            self.sessionProperties[attr] = buf

        node = node.getElementsByTagName('aps')
        if len(node) != 1:
            Logger.warn("No aps child node from root node")
            return False

        node = node[0]
        for attr in ['protocol', 'server', 'port', 'location']:
            if not node.hasAttribute(attr):
                Logger.warn("Missing attribute %s"%(str(attr)))
                return False

        self.aps_url = "%s://%s:%s%s"%(node.getAttribute('protocol'),
                                      node.getAttribute('server'),
                                      node.getAttribute('port'),
                                      node.getAttribute('location'))
        return True

    def doInitSession(self):
        values =  {'width': self.conf["geometry"][0], 'height': self.conf["geometry"][1]}
        url = "%s/start.php?%s"%(self.aps_url, urllib.urlencode(values))
        request = urllib2.Request(url, urllib.urlencode(values))

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
            Logger.warn("Init session failure %s"%(str(exc.reason)))
            return False

        #print url.geturl()
        #print url.read()
        return True

    def doSessionStatus(self):
        values =  {'application_id': 'desktop'}
        url = "%s/whatsup.php?%s"%(self.aps_url, urllib.urlencode(values))
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
            return False

        data = url.read()
        try:
            dom = minidom.parseString(data)
        except ExpatError:
            Logger.warn("Invalid XML result")
            return False

        sessionNode = dom.getElementsByTagName('session')
        if len(sessionNode) != 1:
            Logger.warn("Bad xml result")
            return False

        sessionNode = sessionNode[0]
        if not sessionNode.hasAttribute('status'):
            Logger.warn("Bad xml result")
            return False

        buf = sessionNode.getAttribute('status')

        try:
            self.sessionStatus = int(buf)
        except exceptions.ValueError, err:
            Logger.warn("Bad xml result")
            return False

        node = sessionNode.getElementsByTagName('application')
        if len(node) != 1:
            Logger.warn("missing child node application")
            return False

        node = node[0]
        if not node.hasAttribute('status'):
            Logger.warn("Missing attribute status to application node")
            return False

        buf = node.getAttribute('status')
        try:
            self.desktopStatus = int(buf)
        except exceptions.ValueError, err:
            Logger.warn("Invalid application status %s"%(str(buf)))
            return False

        return self.sessionStatus

    def getSessionAccess(self):
        url = "%s/access.php?application_id=desktop"%(self.aps_url)
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
            Logger.warn("Failure: %s"%(str(exc.reason)))
            return False


        self.infos = parse_access(url.read())
        if self.infos == None:
            return False

        return True


    def do_call_exit(self):
        if d.sessionProperties["persistent"]:
            url = "%s/suspend.php"%(self.aps_url)
        else:
            url = "%s/exit.php"%(self.aps_url)
        request = urllib2.Request(url)
        
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
        Logger.debug("Begin check print")

        old_status = 2
        while 1==1:
            status = self.doSessionStatus()
            if status != old_status:
                Logger.info("Status changed: %d -> %d"%(old_status, status))
                old_status = status
            time.sleep(2)


    def get_vnc_extra_parameters(self):
        compress = 9
        quality = None
        color8bits = None

        if self.conf.has_key('quality'):
            if self.conf['quality'] == 'lowest':
                quality = 8
                color8bits = True

            elif self.conf['quality'] == 'medium':
                quality = 7

            elif self.conf['quality'] == 'high':
                quality = 8

            elif self.conf['quality'] == 'highest':
                quality = 9
        else:
            if self.infos['vnc_quality'] == 'lowest':
                quality = 8
                color8bits = True

            elif self.infos['vnc_quality'] == 'medium':
                quality = 7

            elif self.infos['vnc_quality'] == 'high':
                quality = 8

            elif self.infos['vnc_quality'] == 'highest':
                quality = 9


        return (compress, quality, color8bits)

    def launch(self):
        self.infos["ssh_port"] = self.infos["ssh_port"].split(",")[0]
        local_port = random.randrange(1024, 65536)

        pid,pid2 = launch_ssh(self.infos["ssh_host"], self.infos["ssh_login"], self.infos["ssh_pass"], 
                         ["-N", "-o", "StrictHostKeyChecking=no",
                          "-L", "%d:localhost:%s"%(local_port, self.infos["vnc_port"]),
                          '-p', self.infos["ssh_port"]])
        if pid<=0 or pid2<=0:
            Logger.warn("Error: ssh pid is %d"%(pid))
            return False

        Logger.debug("sleeping to be sure ssh is ok")
        time.sleep(2)

        t = threading.Thread(target=self.check_whatsup)
        t.start()

        # Vnc managment
        vnc_file = tempfile.mktemp()

        vnc_args = []
        vnc_args.append("xtightvncviewer")
        vnc_args.append("-passwd")
        vnc_args.append(vnc_file)

        vnc_args.append("-encodings")
        vnc_args.append("Tight")

        if self.conf['fullscreen']:
            vnc_args.append("-fullscreen")


        (compress, quality, color8bits) = self.get_vnc_extra_parameters()
        
        if compress is not None:
            vnc_args.append("-compresslevel")
            vnc_args.append(str(compress))

        if quality is not None:
            vnc_args.append("-quality")
            vnc_args.append(str(quality))

        if color8bits is not None and color8bits is True:
            vnc_args.append("-bgr233")
            vnc_args.append("-x11cursor")
            
        vnc_args.append("localhost::%s"%(local_port))

        vnc_cmd = " ".join(vnc_args)

        f = file(vnc_file, "w")
        f.write(self.infos["vnc_pass"])
        f.close()
        os.chmod(vnc_file, 0400)

        Logger.debug("launch vnc: '%s'"%(vnc_cmd))
        try:
            status, out = commands.getstatusoutput(vnc_cmd)
        except KeyboardInterrupt: # ctrl+c of the user
            Logger.info("Interrupt from user")
            status = 0
        if status!=0:
            Logger.info("vnc return status %d and \n%s\n==="%(status, out))
            self.do_call_exit()

        os.remove(vnc_file)
        # end of vnc

        if t.isAlive():
            t._Thread__stop()

        os.kill(pid2, signal.SIGTERM)
        os.kill(pid, signal.SIGTERM)

        Logger.debug("end")
        return True


def usage():
    print "Usage: %s [options] ovd_sm_url"%(sys.argv[0])
    print
    print "Options:"
    print "\t--extra-startsession-opt=key:value[,key1:value1...]"
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
                                                            'verbose'])
    
except getopt.GetoptError, err:
    print >> sys.stderr, str(err)
    usage()
    sys.exit(2)

if not len(args)>0:
    print >> sys.stderr, "Missing sm_url"
    usage()
    sys.exit(2)

conf["url"] = args[0]
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
    elif o == "--verbose":
        logger_flags |= Logger.DEBUG
        

if conf["fullscreen"] == True:
    (status, out) = commands.getstatusoutput('xrandr |head -n 1')
    s = re.search('current ([0-9]+) x ([0-9]+)', out)
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
    Logger.info("Connect to '%s' with user '%s'"%(conf["url"], conf["login"]))
    conf["password"] = getpass.getpass("Password please: ")

d = Dialog(conf)
if not d.doLogin():
    Logger.error("Unable to login")
    sys.exit(1)

if not d.doStartSession(extra_args):
    Logger.error("Unable to startsession")
    sys.exit(2)

Logger.debug("Session properties: %s"%(str(d.sessionProperties)))
if d.sessionProperties["mode"] != 'desktop':
    Logger.error("Doesn't support session mode '%s'"%(str(d.mode)))
    self.do_call_exit()
    sys.exit(0)


status = -1
while status not in [0,10]:
    status = d.doSessionStatus()
    Logger.debug("status %s"%(str(status)))

    if type(status) == type(False):
        Logger.error("Error in get status")
        sys.exit(5)
    
    time.sleep(0.5)
    if not status in [-1, 0, 10]:
        Logger.error("Session not 0 or -1 (%d) => exit"%(status))
        sys.exit(4)


if not d.doInitSession():
    Logger.error("Unable to init session on aps")
    sys.exit(3)


status = 0
while status != 2 or d.desktopStatus != 2:
    status = d.doSessionStatus()
    Logger.debug("status %s"%(str(status)))
    if type(0) == type(False):
        Logger.error("Error in get status")
        sys.exit(5)

    time.sleep(0.5)
    if status == 3 or status == -1:
        Logger.error("Session not exist anymore (%d)"%(status))
        sys.exit(4)

if not d.getSessionAccess():
    Logger.error("Unable to get session parameters")
    sys.exit(5)

d.launch()

Logger.debug("end")




