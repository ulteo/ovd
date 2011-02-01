#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2008-2011 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2010-2011
# Author Julien LANGLOIS <julien@ulteo.com> 2008-2011
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

import cookielib
import getopt
import os
import sys
import time
import urllib
import urllib2
from xml.dom import minidom
from xml.dom.minidom import Document
from xml.parsers.expat import ExpatError


def launch_client(user, password, host, withxterm=False):
    if withxterm:
        cmd_args = ["/usr/bin/xterm", "-e", "./ovd-client.py --verbose -l %s -p \"%s\" %s; sleep 1h"%(user, password, host)]
    else:
        cmd_args = ["./ovd-client.py",  "--verbose",  "-l",  user, "-p",  password,  host]

    # Fork a child process, using a new pseudo-terminal as the child's controlling terminal.
    pid =  os.fork()
    # If Child; execute external process
    if pid == 0:
        os.execv(cmd_args[0], cmd_args)
        # Should not appear
        sys.exit(0)

    return pid


def getUserList(host):
        url = "https://%s/ovd/client/userlist.php"%(host)
        cookiejar = cookielib.CookieJar()
        urlOpener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookiejar))

        request = urllib2.Request(url)


              
        try:
            url = urlOpener.open(request)

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


        headers = url.info()
        if not headers['Content-Type'].startswith('text/xml'):
            Logger.warn("Invalid response format")
            return False

        data = url.read()
        users = []
        try:
            dom = minidom.parseString(data)
        except ExpatError:
            Logger.warn("Invalid XML result")
            return False

        nodes = dom.getElementsByTagName('user')
        for node in nodes:
            if not node.hasAttribute('login'):
                Logger.warn("Bad xml result")
                return False

            users.append(node.getAttribute('login'))
        
        return users


def usage():
    print "Usage: %s [-h|--help] sm_host NumberOfSession [TimeToWaitBetweenSession]"%(sys.argv[0])
    print "\t   --auto-users: do not request for SM users list"
    print "\t   --with-xterm: launch each client on a separate xterm"
    print


conf = {}

try:
    opts, args = getopt.getopt(sys.argv[1:], 'hs:', ["help", "auto-users", "start", "with-xterm"])
    
except getopt.GetoptError, err:
    print >> sys.stderr, str(err)
    usage()
    sys.exit(2)

if not len(args)>1:
    print >> sys.stderr, "Missing sm_host"
    usage()
    sys.exit(2)

host = args[0]
number = args[1]
timetowait = None
if len(args) > 2:
    timetowait = float(args[2])
start = 0
auto_users = False
withxterm = False

for o, a in opts:
    if o in ("-h", "--help"):
        usage()
        sys.exit()
    elif o in ("--auto-users"):
        auto_users = True
    elif o in ("--with-xterm"):
        withxterm = True
    elif o in ("-s", "--start"):
        if not a.isdigit():
            print >> sys.stderr, a," is not a digit"
            usage()
            sys.exit(2)
        start = int(a)

if not number.isdigit():
    print >> sys.stderr, "Number is not a digit"
    usage()
    sys.exit(2)

number=int(number)

if auto_users is False:
    p = getUserList(host)
    if len(p) < number+start:
        number = len(p) - start
        print "Not enough users, reducing to %d sessions"%(number)

for j in xrange(number):
    if auto_users:
        i = "user_%d"%(j)
    else:
        i = p[j+start]

    print "launch session %d for %s"%(j, i)
#    launch_client(i, "", url)
    launch_client(i, i, host, withxterm)
    if timetowait is not None:
        time.sleep(timetowait)

print "end"




