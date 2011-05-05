# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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

import os
import subprocess

from ovd.Platform import Platform

__all__ = ["getstatusoutput"]


def getstatusoutput(args):
	if type(args) is type([]):
		shell = False
	elif type(args) in [type(""), type(u"")]:
		shell = True

	p = subprocess.Popen(args, preexec_fn=Platform.System.detachFatherProcess,
		stdout=subprocess.PIPE, stderr=subprocess.STDOUT, shell=shell)
	p.wait()
	output = p.communicate()[0]
	return  (p.returncode, output)
