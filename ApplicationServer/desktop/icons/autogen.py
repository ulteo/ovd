#! /usr/bin/env python

# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2013
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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
import json
from glob import glob

theme_categories = [
	"actions",
	"apps",
	"categories",
	"devices",
	"emblems",
	"mimes",
	"places",
	"status",
	"stock",
]

png_sizes = [
	16,
	22,
	24,
	32,
	48,
	64,
	72,
	96,
	128
]


if __name__ == "__main__":
	str_scalable = []
	str_install_scalable = []
	str_install_png = []
	str_uninstall_scalable = []
	str_uninstall_png = []
	
	links = json.load(open("theme/links.json", "r"))
	
	if len(links) > 0:
		for src, dest in links.iteritems():
			str_install_scalable.append("\t$(LN_S) %s $(DESTDIR)$(scalabledir)/%s" % (dest, src))
			str_uninstall_scalable.append("\trm -f $(DESTDIR)$(scalabledir)/%s" % src)
			src = src.replace(".svg", ".png")
			dest = dest.replace(".svg", ".png")
			for s in png_sizes:
				pngsrc = "%dx%d/%s" % (s, s, src)
				str_install_png.append("\t$(LN_S) %s $(DESTDIR)$(themedir)/%s" % (dest, pngsrc))
				str_uninstall_png.append("\trm -f $(DESTDIR)$(themedir)/%s" % pngsrc)
	
	for t in theme_categories:
		infos = {}
		infos["dir"] = t
		infos["files"] = " ".join(glob(os.path.join("theme", infos["dir"], "*")))
		
		str_scalable.append("scalable%(dir)sdir = $(datarootdir)/icons/Ulteo/scalable/%(dir)s"%infos)
		str_scalable.append("scalable%(dir)s_DATA = %(files)s"%infos)
		str_scalable.append("EXTRA_DIST+= $(scalable%(dir)s_DATA)"%infos)
		str_scalable.append("")
		
	for s in png_sizes:
		infos["size"] = "%d"%(s)
		infos["%"] = "%"
		for t in theme_categories:
			infos["dir"] = os.path.join("theme", t)
			infos["theme"] = t
			for fic in glob(infos["dir"] + "/*"):
				if fic.endswith(".svg"):
					infos["fn_svg"] = os.path.join(t, os.path.basename(fic))
					infos["fn_png"] = infos["fn_svg"].replace(".svg", ".png")
					str_install_png.insert(0, "\trsvg-convert -f png -w %(size)s -h %(size)s -o $(DESTDIR)$(themedir)/%(size)sx%(size)s/%(fn_png)s $(DESTDIR)$(scalabledir)/%(fn_svg)s" % infos)
					str_uninstall_png.append("\trm -f $(DESTDIR)$(themedir)/%(size)sx%(size)s/%(fn_png)s" % infos)
				else:
					infos["fn"] = os.path.join(t, os.path.basename(fic))
					str_install_png.append("\t$(LN_S) ../../scalable/%(fn)s $(DESTDIR)$(themedir)/%(size)sx%(size)s/%(fn)s" % infos)
					str_uninstall_png.append("\trm -f $(DESTDIR)$(themedir)/%(size)sx%(size)s/%(fn)s" % infos)
			str_install_png.insert(0, "\t$(MKDIR_P) $(DESTDIR)$(themedir)/%(size)sx%(size)s/%(theme)s" % infos)
	
	f = file("Makefile.am.in", "r")
	c = f.read()
	f.close()
	
	c = c.replace("@CATGEORY_DIRS@", " ".join(theme_categories))
	c = c.replace("@PNG_SIZE@", " ".join([str(i) for i in png_sizes]))
	c = c.replace("@SCALABLE_PART@", "\n".join(str_scalable))
	c = c.replace("@INSTALL_SCALABLE@", "\n".join(str_install_scalable))
	c = c.replace("@INSTALL_PNG@", "\n".join(str_install_png))
	c = c.replace("@UNINSTALL_SCALABLE@", "\n".join(str_uninstall_scalable))
	c = c.replace("@UNINSTALL_PNG@", "\n".join(str_uninstall_png))
	
	f = file("Makefile.am", "w")
	f.write(c)
	f.close()
