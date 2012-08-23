#!/usr/bin/env python

# Copyright (C) 2012 Ulteo SAS
# http://www.ulteo.com
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
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

import os
import re
from subprocess import Popen, PIPE

def jscompress(outfile, filename):
	print " * compress %s" % filename
	output = Popen(("yui-compressor", "--type", "js", "--charset", "utf-8", filename), stdout=PIPE).stdout.read()
	outfile.write("\n\n/* %s */\n" % filename)
	outfile.write(output)


def csscompress(outfile, filename):
	print " * compress %s" % filename
	output = Popen(("yui-compressor", "--type", "css", "--charset", "utf-8", filename), stdout=PIPE).stdout.read()
	outfile.write("\n\n/* %s */\n" % filename)
	outfile.write(output)


copyright = """/**
* Copyright (C) 2012 Ulteo SAS
* http://www.ulteo.com
* this is the minified version of multiple files
* see the original files for individual copyright information
**/"""

if __name__ == "__main__":
	f = open(os.path.join("web", "index.php"))
	content = f.read()
	f.close()

	outfilename = os.path.join("web", "media", "script", "uovd.js")
	outfile = open(outfilename, "w")
	outfile.write(copyright)

	for match in re.findall("<script type=\"text/javascript\" src=\"media/(.*).js[^\"]*\" charset=\"utf-8\"></script>", content, re.IGNORECASE):
		filename = os.path.join("web", "media", match + ".js")
		if not os.path.basename(filename) in ("uovd.js", "uovd_int_client.js", "uovd_ext_client.js"):
			jscompress(outfile, filename)

	outfile.close()

	outfilename = os.path.join("web", "media", "style", "uovd.css")
	outfile = open(outfilename, "w")
	outfile.write(copyright)

	for match in re.findall("<link rel=\"stylesheet\" type=\"text/css\" href=\"media/(.*).css\" />", content, re.IGNORECASE):
		filename = os.path.join("web", "media", match + ".css")
		if not os.path.basename(filename) in ("uovd.css", ):
			csscompress(outfile, filename)

	outfile.close()
