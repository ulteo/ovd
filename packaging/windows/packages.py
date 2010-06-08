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
import sys
import tempfile
import pysvn

import util

def make_source_archive(context, config, destfile):
	d = os.path.join(context["SPOOL"], "src-%s"%(context["VERSION"]))
	if os.path.exists(d):
		if not util.DeleteR(d):
			return False
	
	c = pysvn.Client()
	c.export(os.path.join(context["SVN_ROOT"], config.getSVNRoot()), d)
	
	if config.__dict__.has_key("pre_sources_commads"):
		os.chdir(d)
		for cmd in config.pre_sources_commads():
			for key in context.keys():
				if "@%s@"%(key) in cmd:
					cmd = cmd.replace("@%s@"%(key), context[key])
			
			#print cmd
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

	util.zip(destfile, d, os.path.basename(destfile)[:-4])
	if not util.DeleteR(d):
		return False
	
	return True



def make_binary_archive(context, config, src_archive, bin_archive):
	d = os.path.join(context["SPOOL"], "bin-%s"%(context["VERSION"]))
	if os.path.exists(d):
		if not util.DeleteR(d):
			return False
	
	print "unzip"
	util.unzip(src_archive, d, 1)
	
	print "compil"
	os.chdir(d)
	for cmd in config.compile_commands():
		r = os.system(cmd)
		if r is not 0:
			return False
	os.chdir(context["PWD"])
	
	
	if config.__dict__.has_key("binary_dir"):
		dirname = os.path.join(d, config.binary_dir())
	elif config.__dict__.has_key("binary_files"):
		bdir = os.path.join(context["SPOOL"], "buf")
		if os.path.exists(bdir):
			util.DeleteR(bdir)
		
		os.mkdir(bdir)
		for f in config.binary_files():
			for f2 in glob.glob(os.path.join(d, f)):
				os.rename(os.path.join(d, f2), os.path.join(bdir, os.path.basename(f2)))
		
		dirname = bdir
	
	util.zip(bin_archive, dirname, os.path.basename(bin_archive)[:-4])
	if not util.DeleteR(d):
		return False
	if config.__dict__.has_key("binary_files"):
		if not util.DeleteR(bdir):
			return False
		
	return True



def perform_target(target, context):
	print "target: ",target
	
	try:
		Config = __import__("%s.config"%(target), {}, {}, "config")
	
	except ImportError:
		print "Config error"
		return False
	
	
	if not context.has_key("VERSION"):
		c = pysvn.Client()
		
		r1 = c.info(os.path.join(context["PWD"], target))["commit_revision"].number
		r2 = c.info(os.path.join(context["SVN_ROOT"], Config.getSVNRoot()))["commit_revision"].number
		revision = max(r1, r2)
		
		context["VERSION"] = "99.99~+svn%05d"%(revision)
		os.environ["OVD_VERSION"] = context["VERSION"]
	
	
	context["TARGET"] = target
	context["SPOOL"]  = os.path.join(context["PWD"], target)
	
	src_archive = os.path.join(context["PWD"], "%s-src-%s.zip"%(target, context["VERSION"]))
	if os.path.exists(src_archive):
		if context["FORCE"]:
			print "Remove existing ",src_archive
			os.remove(src_archive)
		else:
			print "nothing to do"
			return True
	
	if not make_source_archive(context, Config, src_archive):
		return False
	
	
	bin_archive = os.path.join(context["PWD"], "%s-%s.zip"%(target, context["VERSION"]))
	if os.path.exists(bin_archive):
		if context["FORCE"]:
			print "Remove existing ",bin_archive
			os.remove(bin_archive)
		else:
			print "nothing to do"
			return True
	
	if not make_binary_archive(context, Config, src_archive, bin_archive):
		return False
	
	return True


def usage():
	print "Usage: %s [-h|--help] [-t|--targets= target1[target2[,target3...]]] [--set-version=VERSION]"%(sys.argv[0])
	print "\t-f|--force-build: Force the rebuild of the package even if already exists"
	print "\t-h|--help: print this help"
	print "\t-t|--targets targets1[,...]: Perform only target listed, default is all"
	print "\t--set-version=VERSION: Set the version instead of detect from SVN"
	print


if __name__ == "__main__":
	base_path = os.path.abspath(os.path.dirname(sys.argv[0])) # svn/packaging/windows
	
	svn_path = os.path.dirname(base_path) # svn/packaging
	svn_path = os.path.dirname(svn_path) # svn
	
	targets = None
	
	context = {}
	context["PWD"] = base_path
	context["SVN_ROOT"] = svn_path
	context["FORCE"] = False
	
	try:
		opts, args = getopt.getopt(sys.argv[1:], "fht:", ["force-build", "help", "targets=", "set-version="])
	
	except getopt.GetoptError, err:
		print >> sys.stderr, str(err)
		usage()
		sys.exit(1)
	
	for o, a in opts:
		if o in ("-h", "--help"):
			usage()
			sys.exit()
		
		elif o in ("-f", "--force-build"):
			context["FORCE"] = True
		
		elif o in ("-t", "--targets"):
			targets = []
			for elem in a.split(","):
				elem = elem.strip()
				if len(elem) == 0:
					continue
				
				targets.append(elem)
		
		elif o in ("--set-version"):
			context["VERSION"] = a.strip()
			os.environ["OVD_VERSION"] = context["VERSION"]
	
	
	if len(args) > 0:
		print >> sys.stderr, "Invalid argument '%s'"%(args[0])
		usage()
		sys.exit(2)
	
	
	if targets is None:
		targets = []
		for f in glob.glob(os.path.join(base_path, "*")):
			name = os.path.basename(f)
			if name.startswith("setup-"):
				continue
			
			if os.path.exists(os.path.join(f, "config.py")):
				targets.append(name)
	
	
	for target in targets:
		perform_target(target, context.copy())
