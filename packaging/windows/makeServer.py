# -*- coding: utf-8 -*-

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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

import glob
import os
import shutil
import sys
import tempfile
import zipfile


def main(basepath, distpath):
	os.chdir(os.path.join(basepath, "client", "java"))
	s = my_exec("ant integrated")
	
	os.chdir(os.path.join(basepath, "client", "java", "windowsPathsJNI"))
	s = my_exec("make -f Makefile.WIN32")

	os.chdir(os.path.join(basepath, "client", "java", "OVDIntegratedLauncher"))
	s = my_exec("make -f Makefile.WIN32")
	
	
	os.chdir(os.path.join(basepath, "ApplicationServer", "OvdShells"))
	s = my_exec("autogen.py")
	s = my_exec("python setup.py")
	
	os.chdir(os.path.join(basepath, "OvdServer"))
	s = my_exec("autogen.py")
	s = my_exec("python setup.py")
	
	os.chdir(os.path.join(basepath, "ApplicationServer", "windows", "iconExtractor"))
	s = my_exec("make")


def clean(basepath, distpath):
	f = os.path.join(distpath, "ovd.zip")
	if os.path.exists(f):
		os.remove(f)
	
	dirs = []
	dirs.append(os.path.join(distpath, "dist"))
	
	for path in [os.path.join(basepath, "ApplicationServer", "OvdShells"), os.path.join(basepath, "OvdServer")]:
		dirs.append(os.path.join(path, "build"))
		dirs.append(os.path.join(path, "dist"))
	
	for d in dirs:
		if os.path.isdir(d):
			shutil.rmtree(d)
	
	
	os.chdir(os.path.join(basepath, "client", "java"))
	s = my_exec("ant clean")
	
	os.chdir(os.path.join(basepath, "client", "java", "windowsPathsJNI"))
	s = my_exec("make -f Makefile.WIN32 clean")
	
	os.chdir(os.path.join(basepath, "client", "java", "OVDIntegratedLauncher"))
	s = my_exec("make -f Makefile.WIN32 clean")
	
	os.chdir(os.path.join(basepath, "ApplicationServer", "windows", "iconExtractor"))
	s = my_exec("make clean")


def install(basepath, distpath):
	install_dir = os.path.join(distpath, "dist")
	os.mkdir(install_dir)
	
	CopyContentIntoDir(os.path.join(basepath, "OvdServer", "dist"), install_dir)
	
	plus_dir = os.path.join(install_dir, "plus")
	os.mkdir(plus_dir)
	
	CopyContentIntoDir(os.path.join(basepath, "client", "java", "libs"), plus_dir, "*.jar")
	CopyContentIntoDir(os.path.join(basepath, "client", "java", "windowsPathsJNI"), plus_dir, "*.dll")
	CopyContentIntoDir(os.path.join(basepath, "client", "java", "OVDIntegratedLauncher"), plus_dir, "*.exe")
	
	CopyContentIntoDir(os.path.join(basepath, "ApplicationServer", "windows", "iconExtractor"), plus_dir, "*.exe")
	
	libs = [
		"gnu-getopt.jar",
		"image4j.jar",
		"jshortcut.jar",
		"registry.jar",
		"ICE_JNIRegistry.dll",
		"jshortcut.dll",
		"log4j-1.2.jar"
		]
	for lib in libs:
		src = os.path.join(basepath, "client", "java", "required_libraries", lib)
		dst = os.path.join(plus_dir, lib)
		
		if not os.path.exists(src):
			print "No file %s"%(src)
			sys.exit(1)
		
		shutil.copyfile(src, dst)
	
	
	dirs = []
	dirs.append(os.path.join(distpath, "bmp2png"))
	dirs.append(os.path.join(distpath, "seamlessrdpshell"))
	dirs.append(os.path.join(basepath, "ApplicationServer", "OvdShells", "dist"))
	
	for d in dirs:
		CopyContentIntoDir(d, plus_dir)

def dist(basepath, distpath):
	filename = os.path.join(distpath, "ovd.zip")
	if os.path.exists(filename):
		os.remove(filename)
	
	f = zipfile.ZipFile(filename, "w")
	makeRZip(f, os.path.join(distpath, "dist"), "ovd")
	f.close()


def makeRZip(f, d, base):
	for name in os.listdir(d):
		path = os.path.join(d, name)
		
		print " push",path
		if os.path.isfile(path):
			f.write(path, os.path.join(base, name), zipfile.ZIP_DEFLATED)
		else:
			makeRZip(f, path, os.path.join(base, name))
			
			

def CopyContentIntoDir(srcDir, dstDir, pattern="*"):
	if not os.path.isdir(dstDir):
		os.makedirs(dstDir)
	
	files = glob.glob(os.path.join(srcDir, pattern))
	for file in files:
		dst = os.path.join(dstDir, os.path.basename(file))
		
		if os.path.isfile(file):
			shutil.copyfile(file, dst)
		else:
			shutil.copytree(file, dst)


def my_exec(cmd, debug = False):
	if not debug:
		out = tempfile.mktemp()
		err = tempfile.mktemp()
		cmd = cmd+" >"+out+ " 2>"+err
	
	s = os.system(cmd)
	if s != 0:
		print >> sys.stderr, "Following command %s return status %d in directory '%s'"%(cmd, s, os.path.abspath(os.curdir))
		if not debug:
			if os.path.isfile(err):
				f = file(err, "r")
				print >> sys.stderr, f.read()
				f.close()
				os.remove(err)
			if os.path.isfile(out):
				os.remove(out)
		
		sys.exit(1)
	
	if not debug:
		if os.path.isfile(out):
			os.remove(out)
		if os.path.isfile(err):
			os.remove(err)


if __name__ == "__main__":
	base_path = os.path.dirname(sys.argv[0]) # svn/packaging/windows
	
	svn_path = os.path.dirname(base_path) # svn/packaging
	svn_path = os.path.dirname(svn_path) # svn

	
	args = sys.argv[1:]
	
	for arg in args:
		if arg not in ["all", "clean", "install", "dist"]:
			print >> sys.stderr, "Not supported target '%s'"%(arg)
			sys.exist(1)
	
	if len(args)==0:
		args.append("all")
	
	for arg in args:
		if arg == "clean":
			clean(svn_path, base_path)
		elif arg == "all":
			main(svn_path, base_path)
		elif arg == "dist":
			dist(svn_path, base_path)
		elif arg == "install":
			install(svn_path, base_path)
	
	print "success"
