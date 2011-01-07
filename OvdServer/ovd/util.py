# -*- coding: utf-8 -*-

# Copyright (C) 2008-2009 Ulteo SAS
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

import platform
import socket


def isIP(address, format_='packed'):
	if format_ == 'packed':
		try:
			socket.inet_ntoa(address)
		except Exception:
			return False
	elif format_ == 'dotted':
		try:
			socket.inet_aton(address)
		except Exception:
			return False
	else:
		Logger.error("util:: isIP: unknown value '%s' for argument format" % format_)
		return False
	return True


def array_flush(array):
	array.reverse()
	for _ in xrange(len(array)):
		yield array.pop()


def get_platform():
	platform_ = platform.system().lower()
	for key in ["windows","microsoft","microsoft windows"]:
		if key in platform_:
			return "windows"
		
	return "linux"
