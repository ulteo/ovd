# -*- coding: utf-8 -*-
#
# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License.
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


class FileTailer:
	""" Tail a file as GNU Tail """
	
	def __init__(self, filename):
		self.filename = filename
		self.fd = None
		self.buffer_line = ""
		self.buffer_lines = []
		
		self.pos = None;
		self.bufsize = 2048
		self.end = False
	
	
	def __del__(self):
		if self.fd is not None:
			self.fd.close()
			self.fd = None
	
	
	def init(self):
		try:
			self.fd = file(self.filename, "r")
		except IOError,e:
			return False
		
		self.fd.seek(-1, os.SEEK_END)
		self.size = self.fd.tell() + 1

		last = self.fd.read(1)
		if last == os.linesep:
			self.size-= 1

		if self.size < self.bufsize:
			self.pos = 0
			self.bufsize = self.size
		else:
			self.pos = self.size - self.bufsize
		
		return True
	
	
	def hasLines(self):
		if self.fd is None:
			ret = self.init()
			if ret is False:
				return False
		
		return len(self.buffer_lines) > 0 or len(self.buffer_line)>0 or self.end is not True
	
	
	def tail(self, nb_lines_ = 10):
		if self.fd is None:
			ret = self.init()
			if ret is False:
				return []
		
		ret = []
		while nb_lines_ > 0 and len(self.buffer_lines)>0:
			buf = self.buffer_lines.pop()
			ret.insert(0, buf)
			nb_lines_-= 1
		
		while nb_lines_ > 0 and self.end is not True:
			self.fd.seek(self.pos)
			buf = self.fd.read(self.bufsize) + self.buffer_line
			
			lines = buf.splitlines()
			self.buffer_line = lines.pop(0)
			
			while nb_lines_ > 0 and len(lines)>0:
				buf = lines.pop()
				ret.insert(0, buf)
				nb_lines_-= 1
			
			self.buffer_lines = lines
			
			if self.pos == 0:
				self.end = True
			elif self.pos < self.bufsize:
				self.bufsize = self.pos
				self.pos = 0
			else:
				self.pos-= self.bufsize
		
		if nb_lines_ > 0 and self.end:
			ret.insert(0, self.buffer_line)
			self.buffer_line = ""
		
		return ret
	
	
	def tail_str(self, nb_lines_):
		return "\n".join(self.tail(nb_lines_))


if __name__ == "__main__":
	import sys
	
	if len(sys.argv) < 2:
		print >> sys.stderr, "Usage: %s filename"%(sys.argv[0])
		sys.exit(1)
	
	tailer = FileTailer(sys.argv[1])
	ret = tailer.tail(4)
	for i in ret:
		print i
	
	print "==="
	ret = tailer.tail(10)
	for i in ret:
		print i
