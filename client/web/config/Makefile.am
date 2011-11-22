# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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

sbin_SCRIPTS = ovd-webclient-config

default_DATA = config.inc.php
defaultdir = $(datadir)/ulteo/webclient/default

conf_DATA = config.inc.php apache2.conf config.client.ini
confdir = $(sysconfdir)/ulteo/webclient

ovd-webclient-config: ovd-webclient-config.in
	sed "s/@PKG_VERSION@/$(PACKAGE_VERSION)/g" $< > $@

apache2.conf: apache2.conf.in
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/$< > $@

EXTRA_DIST = $(default_DATA) apache2.conf.in ovd-webclient-config.in config.client.ini

clean-local:
	rm -f apache2.conf ovd-webclient-config

.PHONY: $(conf_DATA)
