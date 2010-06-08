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

import getopt
import glob
import os
import shutil
import sys
import tempfile
import zipfile
import pysvn

import util

def make_source_archive(target, context, config, destfile):
	d = os.path.join(context["PWD"], target, "%s-src-%s"%(target, context["VERSION"]))
	
	if os.path.exists(d):
		if not util.DeleteR(d):
			return False
	
	c = pysvn.Client()
	c.export(os.path.join(context["SVN_ROOT"], config.getSVNRoot()), d)
	
	if config.__dict__.has_key("pre_sources_commads"):
		os.chdir(os.path.join(context["PWD"], target, d))
		for cmd in config.pre_sources_commads():
			for key in context.keys():
				if "@%s@"%(key) in cmd:
					cmd = cmd.replace("@%s@"%(key), context[key])
			
			print cmd
			r = os.system(cmd)
			if r is not 0:
				return False
		os.chdir(context["PWD"])
	
	if config.__dict__.has_key("source_remove_files"):
		for f in config.source_remove_files():
			for f2 in glob.glob(os.path.join(d, f)):
				util.DeleteR(f2)
	
	if os.path.exists(destfile):
		util.DeleteR(destfile)

	util.zip(destfile, d, os.path.basename(d))
	if not util.DeleteR(d):
		return False
	
	return True



def perform_target(target, context):
	print "target: ",target
	
	try:
		Config = __import__("%s.config"%(target), {}, {}, "config")
	
	except ImportError:
		print "Config error"
		return False
	
	
	src_archive = os.path.join(context["PWD"], "%s-src-%s.zip"%(target, context["VERSION"]))
	if os.path.exists(src_archive):
		os.remove(src_archive)
	
	if not make_source_archive(target, context, Config, src_archive):
		return False
	
	
	d = os.path.join(context["PWD"], target, "%s-%s"%(target, context["VERSION"]))
	
	print "unzip"
	util.unzip(src_archive, d, 1)
	
	print "compil"
	os.chdir(d)
	for cmd in Config.compile_commands():
		r = os.system(cmd)
		if r is not 0:
			return False
	os.chdir(context["PWD"])
	
	
	
	
	if Config.__dict__.has_key("binary_dir"):
		dirname = os.path.join(d, Config.binary_dir())
	elif Config.__dict__.has_key("binary_files"):
		bdir = os.path.join(context["PWD"], target, "buf")
		if os.path.exists(bdir):
			util.DeleteR(bdir)
		
		os.mkdir(bdir)
		for f in Config.binary_files():
			for f2 in glob.glob(os.path.join(d, f)):
				os.rename(os.path.join(d, f2), os.path.join(bdir, os.path.basename(f2)))
		
		dirname = bdir
	
	bin_archive = os.path.join(context["PWD"], "%s-%s.zip"%(target, context["VERSION"]))
	util.zip(bin_archive, dirname, "%s-%s"%(target, context["VERSION"]))
	if not util.DeleteR(d):
		return False
	if Config.__dict__.has_key("binary_files"):
		if not util.DeleteR(bdir):
			return False
		
	return True



def usage():
	print "Usage: %s [-h|--help] [-t|--targets= target1[target2[,target3...]]]"%(sys.argv[0])
	print "\t-h|--help: print this help"
	print "\t-t|--targets targets1[,...]: Perform only target listed, default is all"
	print


if __name__ == "__main__":
	base_path = os.path.abspath(os.path.dirname(sys.argv[0])) # svn/packaging/windows
	
	svn_path = os.path.dirname(base_path) # svn/packaging
	svn_path = os.path.dirname(svn_path) # svn
	
	targets = None
	
	
	try:
		opts, args = getopt.getopt(sys.argv[1:], 'ht:', ['help', 'targets='])
	
	except getopt.GetoptError, err:
		print >> sys.stderr, str(err)
		usage()
		sys.exit(1)
	
	for o, a in opts:
		if o in ("-h", "--help"):
			usage()
			sys.exit()
		elif o in ("-t", "--targets"):
			targets = []
			for elem in a.split(","):
				elem = elem.strip()
				if len(elem) == 0:
					continue
				
				targets.append(elem)
	
	
	if len(args) > 0:
		print >> sys.stderr, "Invalid argument '%s'"%(args[0])
		usage()
		sys.exit(2)
	
	
	if targets is None:
		targets = []
		for f in glob.glob(os.path.join(base_path, "*")):
			if os.path.exists(os.path.join(f, "config.py")):
				targets.append(os.path.basename(f))
	
	
	c = pysvn.Client()
	revision = c.info(base_path)["revision"].number
	version = "99.99~trunk+svn%05d"%(revision)
	os.environ["OVD_VERSION"] = version
	
	context = {}
	context["PWD"] = base_path
	context["SVN_ROOT"] = svn_path
	context["VERSION"] = version	
	
	for target in targets:
		perform_target(target, context)
