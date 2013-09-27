# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
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

conf_DATA = apache.conf config.inc.php

confdir = $(sysconfdir)/ulteo/wnos

EXTRA_DIST = apache.conf.in config.inc.php

apache.conf: apache.conf.in
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/$< > $@

clean-local:
	rm -f apache.conf

.PHONY: $(conf_DATA)
