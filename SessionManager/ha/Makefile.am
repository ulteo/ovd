# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
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

hadir = $(datadir)/ulteo/ovd/ha
ha_DATA = install.sh utils.sh su_cmd.sh ulteo_ha README

confdir = $(hadir)/conf
conf_DATA = conf/authkeys \
			conf/crm.conf \
			conf/ha.cf \
			conf/resources.conf \
			conf/sm0.res

ocfdir = $(libdir)/ocf/resource.d/heartbeat
ocf_DATA= conf/mysql-ovd

HAshell = shell/Makefile \
		  shell/main.cpp \
		  shell/Action.h \
		  shell/Action.cpp \
		  shell/Utils.h \
		  shell/Utils.cpp

EXTRA_DIST = $(ha_DATA) $(conf_DATA) $(ocf_DATA) $(HAshell)

install-data-hook:
	chmod 4755 $(DESTDIR)$(datadir)/ulteo/ovd/ha/su_cmd.sh
	chmod  755 $(DESTDIR)$(libdir)/ocf/resource.d/heartbeat/mysql-ovd
