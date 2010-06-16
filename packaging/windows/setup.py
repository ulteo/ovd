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
import pysvn
import sys
import tempfile

import dir2nsh
import util

def getArchive(name, context):
	if context.has_key("VERSION"):
		z_arch = "%s-%s.zip"%(name, context["VERSION"])
		path = os.path.join(context["PWD"], z_arch)
		if not os.path.exists(path):
			return None
		
		return (path, context["VERSION"])
	
	for f in glob.glob(os.path.join(context["PWD"], "%s-*.zip"%(name))):
		extra = os.path.basename(f)[len("%s-"%(name)):-len(".zip")]
		if extra.startswith("src-"):
			continue
		
		return (f, extra)
	
	return None


def perform_target(target, context):
	print "target: ",target
	
	try:
		Config = __import__("setup-%s.config"%(target), {}, {}, "config")
	
	except ImportError:
		print "Config error"
		return False
	
	context["TARGET"] = target
	context["SPOOL"]  = os.path.join(context["PWD"], "setup-%s"%(target))
	
	distDir = os.path.join(context["SPOOL"], "dist")
	if os.path.exists(distDir):
		if not util.DeleteR(distDir):
			return False
	
	
	versions = []
	if not context.has_key("VERSION"):
		c = pysvn.Client()
		r = c.info(context["SPOOL"])["commit_revision"].number
		versions.append("99.99~+svn%05d"%(r))
	
	
	r = Config.getArch()
	r[0] = distDir
	values = [r]
	while len(values)>0:
		obj = values.pop()
		
		os.mkdir(obj[0])
		
		for archive in obj[1]:
			r = getArchive(archive, context)
			if r is None:
				print "Missing package ",archive
				return False
			
			versions.append(r[1])
			util.unzip(r[0], obj[0], 1)
		
		for d in obj[2]:
			path = os.path.join(context["PWD"], d)
			if not os.path.isdir(path):
				print "Missing external data",d
				return False
			
			util.copytree(path, obj[0])
		
		for d in obj[3]:
			d[0] = os.path.join(obj[0], d[0])
			values.insert(0, d)
	
	name = "setup-%s-dist-%s"%(target, max(versions))
	path = os.path.join(context["PWD"], name+".zip")
	util.zip(path, distDir, name)
	
	
	dir2nsh.doSectionFile(distDir)
	
	v = max(versions)
	if v.startswith("99.99~+svn"):
		v = v.replace("~+svn", ".0.")
	
	name = "setup-%s-%s"%(target, v)
	cmd = "makensis -NOCD -DOUT_DIR=%s -DSETUP_NAME=%s -DPRODUCT_VERSION=%s main.nsi"%(context["PWD"], name, v)
	
	os.chdir(context["SPOOL"])
	r = os.system(cmd)
	os.chdir(context["PWD"])
	
	if r is not 0:
		print "FAILED",cmd
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
			if not name.startswith("setup-"):
				continue
			
			if not os.path.exists(os.path.join(f, "config.py")):
				continue
			
			targets.append(name[len("setup-"):])
	
	
	for target in targets:
		perform_target(target, context.copy())
