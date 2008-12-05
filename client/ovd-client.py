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


def str2hex(str_):
    return str_.encode('hex')

def hex2str(hex_):
    return hex_.decode('hex')


def getSessid(data):
#    print data
    r = """.*daemon_init\('.*',\ '([^']+)'.*"""
    r = re.compile(r , re.M | re.S | re.X )
    p = r.match(data)
    if p == None:
        print "failed"
        return None

    return p.groups()[0]

def parse_applet_div(data):
    r = """.*name="HOST"\ value="(.*)".*name="PORT"\ value="(\d+)".*name="ENCPASSWORD"\ value="([^"]+)".*name="ssh.host"\ value="([^"]+)".*name="ssh.port"\ value="(.*)".*name="ssh.user"\ value="(.*)".*name="ssh.password"\ value="([^"]*)".*"""
    
    r = re.compile(r , re.M | re.S | re.X )
    p = r.match(data)
    if p == None:
        print "failed"
        return None

    res = {}
    res["host"] = p.groups()[0]
    res["vnc_port"] = p.groups()[1]
    res["vnc_pass"] = p.groups()[2]
    res["ssh_port"] = p.groups()[4]
    res["ssh_login"] = p.groups()[5]
    res["ssh_pass"] = p.groups()[6]

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
            s = os.read(fd, 1)
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
        #print "url: ",url

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

        self.cm_url = os.path.dirname(url.geturl())
        self.ssid = getSessid(url.read())
        if self.ssid == None:
            print "Can't get sessid"
            return False

        return True

    def doStartSession_cm(self):
        values =  {'width': self.conf["geometry"][0],'height': self.conf["geometry"][1], "lead": 1}
        # url = self.cm_url+"/startsession.php"
        url = "%s/startsession.php?%s"%(self.cm_url, urllib.urlencode(values))
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
        values =  {'session': self.ssid}
        url = "%s/webservices/session_status.php?%s"%(self.cm_url, urllib.urlencode(values))

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

        return int(url.read())

    def do_getAppletParameters(self):
        url = self.cm_url+"/applet.php"
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

        self.infos = parse_applet_div(url.read())
        if self.infos == None:
            return False

        return True

    def check_print(self):
        print "Begin check print"

        values =  {'timestamp'  : 0, 'lead': 1}
        url = "%s/webservices/print.php?%s"%(self.cm_url, urllib.urlencode(values))

        request = urllib2.Request(url)
        
        while 1==1:
            try:
                url = self.urlOpener.open(request)
            except urllib2.HTTPError, exc:
                if exc.code != 404:
                    print "HTTP request return code %d (%s)" % (exc.code, exc.msg)
                    print exc.read()
                    return False
                
            except urllib2.URLError, exc:
                print "Echec Cause:", exc.reason
                
            time.sleep(2)


    def launch(self):
        self.infos["ssh_port"] = self.infos["ssh_port"].split(",")[0]
        local_port = random.randrange(1024, 65536)

        pid,pid2 = launch_ssh(self.infos["host"], self.infos["ssh_login"], self.infos["ssh_pass"], 
                         ["-N", "-o", "StrictHostKeyChecking=no",
                          "-L", "%d:localhost:%s"%(local_port, self.infos["vnc_port"]),
                          '-p', self.infos["ssh_port"]])
        if pid<=0 or pid2<=0:
            print "Error: ssh pid is %d"%(pid)
            return False

        print "sleeping to be sure ssh is ok"
        time.sleep(2)

        t = threading.Thread(target=self.check_print)
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

        print "end"
        return True


def usage():
    print "Usage: %s [-l|--login=username] [-h|--help] [-g|--geometry=WIDTHxHEIGHT] sm_url"%(sys.argv[0])
    print


conf = {}
conf["geometry"] = "800x600"
conf["login"] = os.environ["USER"]

try:
    opts, args = getopt.getopt(sys.argv[1:], 'l:g:h', ['login=', 'geometry=','help'])
    
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

print "Connect to '%s' with user '%s'"%(conf["url"], conf["login"])
conf["password"] = getpass.getpass("Password please: ")


d = Dialog(conf)

if not d.doLogin():
    print "Unable to login"
    sys.exit(1)


if not d.doStartSession():
    print "Unable to startsession"
    sys.exit(2)

print "sessid '%s'"%(d.ssid)

status = -1
while status!=0:
    status = d.doSessionStatus()
    print "status ", status

    if type(0) == type(False):
        print "Error in get status"
        sys.exit(5)
    
    time.sleep(0.5)
    if not status in [-1, 0]:
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




