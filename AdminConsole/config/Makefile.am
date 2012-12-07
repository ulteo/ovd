# Copyright (C) 2008-2012 Ulteo SAS
# http://www.ulteo.com
# Author Gauvain POCENTEK <gauvain@linutop.com> 2008-2009
# Author Samuel BOVEE <samuel@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2012
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

sbin_SCRIPTS = ovd-administration-console-config

EXTRA_DIST = apache2.conf.in \
			 config.inc.php.in \
			 ovd-administration-console-config.in

generate_conf = apache2.conf \
				config.inc.php

conf_DATA = $(generate_conf)

confdir = $(sysconfdir)/ulteo/administration_console

ovd-administration-console-config:
	sed "s/@PKG_VERSION@/$(PACKAGE_VERSION)/g" < $(srcdir)/ovd-administration-console-config.in > $@

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

config.inc.php:
	sed "s,@LOCALSTATEDIR@,$(localstatedir)," < $(srcdir)/config.inc.php.in > $@

install-exec-hook:
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/administration_console

clean-local:
	rm -f $(generate_conf) ovd-administration-console-config

.PHONY: $(conf_DATA)
