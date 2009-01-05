#!/usr/bin/python
# -*- coding: utf-8 -*-

# Copyright (C) 2007-2008 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com>
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

import time

def cpu_load():
	fStat = open('/proc/stat')
	line = fStat.readline()
	fStat.close()
	infos = line.split(' ')
	data_str_old = infos[2:-1]
	
	time.sleep(1) # minumum one second
	
	fStat = open('/proc/stat')
	line = fStat.readline()
	fStat.close()
	infos = line.split(' ')
	data_str = infos[2:-1]
	
	data = [int(i) for i in data_str]
	data_old = [int(i) for i in data_str_old]
	
	used = data[0] + data[1] + data[2]  # user + nice + system
	total =  used + data[3] #used + idle
	used_old = data_old[0] + data_old[1] + data_old[2] # user + nice + system
	total_old =  used_old + data_old[3] #used + idle
	
	if total == total_old:
		return 0.0
	
	return float(used - used_old)  / float(total - total_old)
	
print cpu_load()
