# -*- coding: utf-8 -*-

# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2011
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

DB= "0123456789abcdefghijklmnopqrstuvwxyz"


"""
This function convert an integer in base 36
"""
def int2b36(i, l=0):
	k = len(DB)
	o = i%k
	p = i/k
	
	r = DB[o]
	if p!=0:
		r = int2b36(p) + r
	
	if l>0 and len(r)<l:
		r = DB[0]*(l-len(r))+r
	
	return r

"""
This function convert a base 36 string into an interger
"""
def b362int(s):
	if len(s)==0:
		return 0
	
	c = s[-1]
	pos = DB.find(c)
	
	g = b362int(s[:-1])
	
	return len(DB)*g +pos
    
    
if __name__ == "__main__":
	t = 36001400
	r = int2b36(t)
	print "int2b36(%d): %s"%(t, r)
	
	r = int2b36(t, 9)
	print "int2b36(%d): %s"%(t, r)
	
	h = b362int(r)
	print "b362int(%s): %d"%(r, h)
	
	print "b36int(000xdds): ", b362int("000xdds")
	print "b36int(xdds): ", b362int("xdds")
