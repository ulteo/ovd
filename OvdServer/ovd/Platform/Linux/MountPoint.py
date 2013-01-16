# -*- coding: utf-8 -*-

# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2013
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


class MountPoint:
	@classmethod
	def get_list(cls, base_dir = None):
		"""
		Equivalent of: cat /proc/self/mountinfo |cut -d " " -f5 |grep "^%base_dir"
		"""
		
		ret = cls.parse_proc_mount("/proc/self/mountinfo", 4, base_dir)
		if ret is None:
			return cls.get_list_alt(base_dir)
		
		ret.sort(reverse=True)
		return ret
	
	
	@classmethod
	def get_list_alt(cls, base_dir = None):
		"""
		Equivalent of: cat /proc/mounts |cut -d " " -f2 |grep "%base_dir"
		"""
		
		dirs = cls.parse_proc_mount("/proc/mounts", 2, base_dir)
		if dirs is None:
			return None
		
		ret = []
		for d in dirs:
			if not os.path.exists(d):
				continue
			
			ret.append(d)
		
		ret.sort(reverse=True)
		return ret
	
	
	@classmethod
	def parse_proc_mount(cls, filename, index, base_dir = None):
		try:
			f = file(filename, "r")
			lines = f.readlines()
			f.close()
		except IOError, err:
			return None
		
		if base_dir is not None:
			base_dir_escaped = cls.mtab_escape(base_dir)
		
		dirs = []
		for line in lines:
			line = line.strip()
			b = line.split()
			if len(b) < index+1:
				# weird line
				continue
			
			p = b[index]
			if base_dir is not None and not p.startswith(base_dir_escaped):
				continue
			
			dirs.append(cls.mtab_unescape(p))
		
		return dirs
	
	
	@classmethod
	def mtab_escape(cls, src):
		dst = src.replace(" ", "\\040")
		dst = dst.replace("\t", "\\011")
		dst = dst.replace("\n", "\\012")
		dst = dst.replace("\\", "\\134")
		
		return dst
	
	
	@classmethod
	def mtab_unescape(cls, src):
		dst = src.replace("\\040", " ")
		dst = dst.replace("\\011", "\t")
		dst = dst.replace("\\012", "\n")
		dst = dst.replace("\\134", "\\")
		
		return dst
