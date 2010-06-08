# -*- coding: utf-8 -*-

## Copyright (C) 2007-2010 Ulteo SAS
## http://www.ulteo.com
## Author Julien LANGLOIS <julien@ulteo.com> 2008, 2010

## This program is free software; you can redistribute it and/or
## modify it under the terms of the GNU General Public License
## as published by the Free Software Foundation; version 2
## of the License

## This program is distributed in the hope that it will be useful,
## but WITHOUT ANY WARRANTY; without even the implied warranty of
## MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.

## You should have received a copy of the GNU General Public License
## along with this program; if not, write to the Free Software
## Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

import glob
import md5
import os
import sys

def doSectionFile(directory):
	datas = getStructure(directory)
	lines = structure2section(datas, directory)
	
	f = file(directory+".nsh", "w")
	f.write("\n".join(lines))
	f.write("\n")
	f.close()


def getStructure(firstdir):
	dirs = [firstdir]
	sections = []
	
	for d in dirs:
		files = glob.glob(d+"/*")
		cursec = []
	
		for f in files:
			if os.path.isdir(f):
				dirs.append(f)
			else:
				cursec.append(f)
		
		sections.append((d, cursec))
	
	return sections

def removeprefix(base, prefix):
	#print "base:  ",base
	#print "prefix:",prefix
	if base.startswith(prefix):
		base = base[len(prefix):]
	
	if base.startswith("\\"):
		base = base[1:]
	
	#print "result: ",base
	return base


def structure2section(datas, directory):
	lines = []
	
	for (d, files) in datas:
		name  = md5.md5(d).hexdigest()
		path = removeprefix(d, directory)
		
		lines.append('Section "Sec%s" Sec%s'%(name, name))
		lines.append('  SetOutPath "$INSTDIR\\%s"'%(path))
		lines.append('  SetOverwrite ifnewer')
		lines.append("")
		
		for f in files:
			lines.append('  File "%s"'%(os.path.abspath(f)))
		
		lines.append("SectionEnd")
		lines.append("")
	
	
	datas.reverse()
	for (d, files) in datas:
		name  = md5.md5(d).hexdigest()
		path = removeprefix(d, directory)
		
		lines.append('Section "un.Sec%s" SecUn%s'%(name, name))
		for f in files:
			f = removeprefix(f, directory)
			lines.append('  Delete "$INSTDIR\\%s"'%(f))
		
		lines.append("")
		lines.append('  RMDir "$INSTDIR\\%s"'%(path))
		lines.append("SectionEnd")
		lines.append("")
		
	return lines


if __name__ == "__main__":
	if len(sys.argv) == 1:
		print "Usage %s [-p1] directory [directories ...]"%(sys.argv[0])
		sys.exit(1)
	
	flag = False
	args = sys.argv[1:]
	
	if args[0] == "-p1":
		flag = True
		args = args[1:]
	
		if len(args) == 0:
			print "Usage %s -p1 directory [directories ...]"%(sys.argv[0])
			sys.exit(1)
	
	for d in args:
		if not os.path.isdir(d):
			print "No such directory '%s'"%(d)
			continue
		
		doSectionFile(d)
