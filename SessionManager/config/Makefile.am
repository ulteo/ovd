# Copyright (C) 2008-2010 Ulteo SAS
# http://www.ulteo.com
# Author Gauvain POCENTEK <gauvain@linutop.com> 2008-2009
# Author Samuel BOVEE <samuel@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2010
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2010
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

EXTRA_DIST = apache2.conf.in \
			 apache2-admin.conf.in \
			 apache2-vhost-server.conf.in \
			 apache2-vhost-ssl.conf \
			 config.inc.php.in \
			 sessionmanager.cron.in

generate_conf = apache2.conf \
				apache2-admin.conf \
				apache2-vhost-server.conf \
				config.inc.php

conf_SCRIPTS = sessionmanager.cron
conf_DATA = $(generate_conf) apache2-vhost-ssl.conf

confdir = $(sysconfdir)/ulteo/sessionmanager

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

apache2-admin.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2-admin.conf.in > $@


apache2-vhost-server.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2-vhost-server.conf.in > $@

config.inc.php:
	sed "s,@LOCALSTATEDIR@,$(localstatedir)," < $(srcdir)/config.inc.php.in > $@

sessionmanager.cron:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/sessionmanager.cron.in > $@

install-exec-hook:
	mkdir -p $(DESTDIR)$(localstatedir)/log/ulteo/sessionmanager
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/sessionmanager
	mkdir -p $(DESTDIR)$(localstatedir)/cache/ulteo/sessionmanager

clean-local:
	rm -f $(generate_conf) $(conf_SCRIPTS)

.PHONY: $(conf_DATA) $(conf_SCRIPTS)
