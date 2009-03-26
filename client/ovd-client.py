#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2008
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


def str2hex(str_):
    return str_.encode('hex')

def hex2str(hex_):
    try:
        return hex_.decode('hex')
    except TypeError:
        print "Cant decode this string", hex_
        sys.exit(1)


def parse_applet_div(data):
    r = """.*name="HOST"\ value="(.*)".*name="PORT"\ value="(\d+)".*name="ENCPASSWORD"\ value="([^"]+)".*name="ssh.host"\ value="([^"]+)".*name="ssh.port"\ value="(.*)".*name="ssh.user"\ value="(.*)".*name="ssh.password"\ value="([^"]*)".*"""
    
    r = re.compile(r , re.M | re.S | re.X )
    p = r.match(data)
    if p == None:
        print "failed"
        return None

    res = {}
    res["ssh_host"] = p.groups()[0]
    res["vnc_port"] = p.groups()[1]
    res["vnc_pass"] = p.groups()[2]
    res["ssh_port"] = p.groups()[4]
    res["ssh_login"] = p.groups()[5]
    res["ssh_pass"] = p.groups()[6]

    res["vnc_pass"] = hex2str(res["vnc_pass"])
    res["ssh_pass"] = hex2str(res["ssh_pass"])

    return res

def parse_applet_div2(data):
    res = {}
    dom = minidom.parseString(data)

    node = dom.getElementsByTagName('ssh')
    if len(node) != 1:
        print "Bad xml result"
        return False

    node = node[0]
    for (attr, m) in [('host','host'), ('user', 'login'), ('passwd', 'pass') ]:
        if not node.hasAttribute(attr):
            print "Bad xml result"
            return False

        res['ssh_'+m] = node.getAttribute(attr)


    res['ssh_port'] = node.getElementsByTagName('port')[0].firstChild.data

    node = dom.getElementsByTagName('vnc')
    if len(node) != 1:
        print "Bad xml result"
        return False

    node = node[0]
    for (attr, m) in [('host','host'), ('passwd', 'pass'), ('port', 'port')]:
        if not node.hasAttribute(attr):
            print "Bad xml result"
            return False

        res['vnc_'+m] = node.getAttribute(attr)

    res["vnc_pass"] = hex2str(res["vnc_pass"])
    res["ssh_pass"] = hex2str(res["ssh_pass"])
    return res


def launch_ssh(host, user, password, extra):
    cmd_args = ["/usr/bin/ssh", "-l", user]
    for arg in extra:
        cmd_args.append(arg)
    cmd_args.append(host)
    # print "exec: '%s'"%(" ".join(cmd_args))
    
    # Fork a child process, using a new pseudo-terminal as the child's controlling terminal.
    pid, fd = os.forkpty()
    # If Child; execute external process
    if pid == 0:
        os.execv(cmd_args[0], cmd_args)
        # Should not appear
        sys.exit(0)
     
    # if parent, read/write with child through file descriptor
    time.sleep(0.2)
    # Get password prompt; ignore
    r = os.read(fd, 1000)
    print "read"
    print r
    time.sleep(0.2)
    # write password
    os.write(fd, password + "\n")
    time.sleep(0.2)

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

        cookiejar = cookielib.CookieJar()
        self.urlOpener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookiejar))


    def doLogin(self):
        url = self.base_url+"/ajax/login.php"

        values =  {'do_login': 1, 'login'  : self.conf['login'], 'password' : self.conf['password']}
        data = urllib.urlencode(values)
        request = urllib2.Request(url, data)
        
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                print "Le service n'est pas disponbile"
                return False
            
            print "HTTP request return code %d (%s)" % (exc.code, exc.msg)
            print " * return: ", exc.read()
            return False

        except urllib2.URLError, exc:
            print "Echec. Cause:", exc.reason
            return False

        return True

    def doStartSession(self):
        url = self.base_url+"/startsession.php"
        values = {}
 
        request = urllib2.Request(url, urllib.urlencode(values))
              
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                print "Le service n'est pas disponbile"
                print exc.read()
                return False
            
            print "HTTP request return code %d (%s)" % (exc.code, exc.msg)
            print " * return: ", exc.read()
            return False

        except urllib2.URLError, exc:
            print "Echec. Cause:", exc.reason
            return False

        self.cm_url = os.path.dirname(url.geturl())
        return True

    def doStartSession_cm(self):
        values =  {'width': self.conf["geometry"][0], 'height': self.conf["geometry"][1]}
        # url = self.cm_url+"/startsession.php"
        url = "%s/start.php?%s"%(self.cm_url, urllib.urlencode(values))
        request = urllib2.Request(url, urllib.urlencode(values))

        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                print "Le service n'est pas disponbile"
                return False
            
            print "HTTP request return code %d (%s)" % (exc.code, exc.msg)
            print " * return: ", exc.read()
            return False

        except urllib2.URLError, exc:
            print "Echec. Cause:", exc.reason
            return False

        print url.geturl()
        print url.read()
        return True

    def doSessionStatus(self):
        url = "%s/whatsup.php"%(self.cm_url)

        request = urllib2.Request(url)
        
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                print "Le service n'est pas disponbile"
                return False
            
            print "HTTP request return code %d (%s)" % (exc.code, exc.msg)
            print " * return: ", exc.read()
            return False

        except urllib2.URLError, exc:
            print "Echec. Cause:", exc.reason
            return False


        data = url.read()
        dom = minidom.parseString(data)

        sessionNode = dom.getElementsByTagName('session')
        if len(sessionNode) != 1:
            print "Bad xml result"
            return False

        sessionNode = sessionNode[0]
        if not sessionNode.hasAttribute('status'):
            print "Bad xml result"
            return False

        status = sessionNode.getAttribute('status')

        try:
            status = int(status)
        except exceptions.ValueError, err:
            print "Bad xml result"
            return False

        return status

    def do_getAppletParameters(self):
        #values =  {'html': 1}
        url = "%s/access.php"%(self.cm_url)
#, urllib.urlencode(values))
        request = urllib2.Request(url)
        
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                print "Le service n'est pas disponbile"
                return False
            
            print "HTTP request return code %d (%s)" % (exc.code, exc.msg)
            print " * return: ", exc.read()
            return False

        except urllib2.URLError, exc:
            print "Echec. Cause:", exc.reason
            return False


        self.infos = parse_applet_div2(url.read())
        if self.infos == None:
            return False

        return True


    def do_call_exit(self):
        url = "%s/exit.php"%(self.cm_url)
        request = urllib2.Request(url)
        
        try:
            url = self.urlOpener.open(request)

        except urllib2.HTTPError, exc:
            if exc.code == 500:
                print "Le service n'est pas disponbile"
                return False
            
            print "HTTP request return code %d (%s)" % (exc.code, exc.msg)
            print " * return: ", exc.read()
            return False

        except urllib2.URLError, exc:
            print "Echec. Cause:", exc.reason
            return False

        return True


    def check_whatsup(self):
        print "Begin check print"

        old_status = 2
        while 1==1:
            status = self.doSessionStatus()
            if status != old_status:
                print "Status changed: ",old_status," -> ",status
                old_status = status
            time.sleep(2)

    def launch(self):
        self.infos["ssh_port"] = self.infos["ssh_port"].split(",")[0]
        local_port = random.randrange(1024, 65536)

        pid,pid2 = launch_ssh(self.infos["ssh_host"], self.infos["ssh_login"], self.infos["ssh_pass"], 
                         ["-N", "-o", "StrictHostKeyChecking=no",
                          "-L", "%d:localhost:%s"%(local_port, self.infos["vnc_port"]),
                          '-p', self.infos["ssh_port"]])
        if pid<=0 or pid2<=0:
            print "Error: ssh pid is %d"%(pid)
            return False

        print "sleeping to be sure ssh is ok"
        time.sleep(2)

        t = threading.Thread(target=self.check_whatsup)
        t.start()

        # Vnc managment
        vnc_file = tempfile.mktemp()
        vnc_cmd = "vncviewer -passwd %s localhost::%s"%(vnc_file, local_port)

        f = file(vnc_file, "w")
        f.write(self.infos["vnc_pass"])
        f.close()
        os.chmod(vnc_file, 0400)

        print "launch vnc: '%s'"%(vnc_cmd)
        status, out = commands.getstatusoutput(vnc_cmd)
        if status!=0:
            print "vnc return status %d and \n%s\n==="%(status, out)

        os.remove(vnc_file)
        # end of vnc

        if t.isAlive():
            t._Thread__stop()

        os.kill(pid2, signal.SIGTERM)
        os.kill(pid, signal.SIGTERM)

        self.do_call_exit()
        print "end"
        return True


def usage():
    print "Usage: %s [-l|--login=username] [-p|--password=PASSWORD] [-h|--help] [-g|--geometry=WIDTHxHEIGHT] sm_url"%(sys.argv[0])
    print


conf = {}
conf["geometry"] = "800x600"
conf["login"] = os.environ["USER"]

try:
    opts, args = getopt.getopt(sys.argv[1:], 'l:p:g:h', ['login=', 'password=', 'geometry=','help'])
    
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
    if o in ("-g", "--geometry"):
        conf["geometry"] = a
    elif o in ("-h", "--help"):
        usage()
        sys.exit()


conf["geometry"] = conf["geometry"].split("x")
if len(conf["geometry"])!=2:
    print >> sys.stderr, "Invalid geometry ",conf["geometry"]
    usage()
    sys.exit(2)

if not conf.has_key("password"):
    print "Connect to '%s' with user '%s'"%(conf["url"], conf["login"])
    conf["password"] = getpass.getpass("Password please: ")

d = Dialog(conf)
if not d.doLogin():
    print "Unable to login"
    sys.exit(1)

if not d.doStartSession():
    print "Unable to startsession"
    sys.exit(2)

status = -1
while status not in [0,10]:
    status = d.doSessionStatus()
    print "status ", status

    if type(status) == type(False):
        print "Error in get status"
        sys.exit(5)
    
    time.sleep(0.5)
    if not status in [-1, 0, 10]:
        print "Session not 0 or -1 (%d) => exit"%(status)
        sys.exit(4)


if not d.doStartSession_cm():
    print "Unable to init session on cm"
    sys.exit(3)


status = 0
while status != 2:
    status = d.doSessionStatus()
    print "status ", status
    if type(0) == type(False):
        print "Error in get status"
        sys.exit(5)

    time.sleep(0.5)
    if status == 3 or status == -1:
        print "Session not exist anymore (%d)"%(status)
        sys.exit(4)

if not d.do_getAppletParameters():
    print "Unable to get parameters from html"
    sys.exit(5)

d.launch()

print "end"




