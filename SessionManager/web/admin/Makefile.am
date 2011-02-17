# Copyright (C) 2008-2010 Ulteo SAS
# http://www.ulteo.com
# Author Gauvain POCENTEK <gauvain@linutop.com> 2008-2009
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2009-2010
# Author Julien LANGLOIS <julien@ulteo.com> 2009
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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

SUBDIRS = ajax media includes init classes

if HA
SUBDIRS += ha
endif

scripts_DATA = actions.php \
               applications.php \
               applications_static.php \
               appsgroup.php \
               checkup.php \
               configuration.php \
               configuration-partial.php \
               configuration-profile.php \
               configuration-sumup.php \
               index.php \
               login.php \
               logout.php \
               logs.php \
               mimetypes.php \
               news.php \
               password.php \
               profiles.php \
               publications.php \
               reporting.php \
               servers.php \
               sharedfolders.php \
               session-reporting.php \
               sessions.php \
               summary.php \
               sumup.php \
               tasks.php \
               users.php \
               usersgroup.php \
               wizard.php

scriptsdir = $(datadir)/ulteo/sessionmanager/admin

DIST_SUBDIRS=$(SUBDIRS)
EXTRA_DIST = $(scripts_DATA)
