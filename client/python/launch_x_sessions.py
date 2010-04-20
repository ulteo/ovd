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

import cookielib
import getopt
import os
import sys
import urllib
import urllib2


def launch_client(user, password, url):
    cmd_args = ["xterm", "-e", "./ovd-client.py -l %s -p \"%s\" %s; sleep 1h"%(user, password, url)]

    # Fork a child process, using a new pseudo-terminal as the child's controlling terminal.
    pid =  os.fork()
    # If Child; execute external process
    if pid == 0:
        os.execvp(cmd_args[0], cmd_args)
        # Should not appear
        sys.exit(0)

    return pid


def getUserList(url):
        url = url+"/webservices/userlist.php"
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

        return [i.strip() for i in url.readlines()]


def usage():
    print "Usage: %s [-h|--help] sm_url Number"%(sys.argv[0])
    print


conf = {}

try:
    opts, args = getopt.getopt(sys.argv[1:], 'hs:', ['help','start'])
    
except getopt.GetoptError, err:
    print >> sys.stderr, str(err)
    usage()
    sys.exit(2)

if not len(args)>1:
    print >> sys.stderr, "Missing sm_url"
    usage()
    sys.exit(2)

url = args[0]
number = args[1]
start = 0

for o, a in opts:
    if o in ("-h", "--help"):
        usage()
        sys.exit()
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

p = getUserList(url)

j = 1
for i in p[start:]:
    if j>number:
        break
    j+=1

    print "launch session %d a session for %s"%(j, i)
#    launch_client(i, "", url)
    launch_client(i, i, url)

print "end"




