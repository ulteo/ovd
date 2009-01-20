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
import os
from glob import glob
import random
import time
import md5
#import grp
#import pwd
import sys
import platform

def touch(filename):
    f = file(filename, "w")
    f.close()

def chroot_exec(cmd_, input=None):
    cmd = "chroot %s %s"%(os.environ['ULTEO_CHROOT'], cmd_)
    if input:
        cmd = "echo %s | "%(input)+cmd
        print "final cmd: '%s'"%(cmd)

    return commands.getstatusoutput(cmd)


def remover(path):
    if os.path.isfile(path):
        return os.remove(path)

    if not os.path.isdir(path):
        return True

    for p in glob(os.path.join(path, "*")):
        remover(p)
    os.rmdir(path)


def str2hex(str_):
    return str_.encode('hex')


def hex2str(hex_):
    return hex_.decode('hex')


def random_pass():
    return md5.md5(str(random.random())+str(time.time())).hexdigest()[:8]


#def change_perms(path, user=-1, group=-1, mode=None ):
    #if type(user) == type(""):
        #user = pwd.getpwnam(user)[2]

    #if type(group) == type(""):
        #group = grp.getgrnam(group)[2]

    #print "os.chown(%s, %s, %s)"%(path, str(user), str(group))
    #os.chown(path, user, group)

    #if mode != None:
        #os.chmod(path, mode)

#def get_pid_from_uids(uids):
    #uids = [str(e) for e in uids]
    #cmd = "pgrep -U %s"%(",".join(uids))

    #(status, out) = commands.getstatusoutput(cmd)
    #if not status==0:
        #return []

    #return out.splitlines()


#def kill_process_from_uids(uids, force=False):
    #uids = [str(e) for e in uids]
    #cmd = "pkill -U %s"
    #if force:
        #cmd = "pkill -9 -U %s"

    #status = commands.getstatus(cmd%(",".join(uids)))
    #return status==0

def myOS():
    if (len([b for b in ["windows","microsoft","microsoft windows"] if platform.system().lower() in b]) >0):
        return "windows"
    else:
        return platform.system().lower()