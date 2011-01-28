# Copyright (C) 2008-2010 Ulteo SAS
# http://www.ulteo.com
# Author Gauvain POCENTEK <gauvain@linutop.com> 2008-2009
# Author Jeremy DESVAGES <jeremy@ulteo.com> 2009-2010
# Author Laurent CLOUET <laurent@ulteo.com> 2009-2010
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


SUBDIRS = ApplicationDB \
          ApplicationsGroupDB \
          AuthMethod \
          ProfileDB \
          SessionManagement \
          SharedFolderDB \
          UserDB \
          UserGroupDB \
          UserGroupDBDynamic \
          UserGroupDBDynamicCached

scriptsdir = $(datadir)/ulteo/sessionmanager/modules

scripts_DATA = ApplicationDB.php \
               ApplicationsGroupDB.php \
               AuthMethod.php \
               ProfileDB.php \
               SessionManagement.php \
               SharedFolderDB.php \
               UserDB.php \
               UserGroupDB.php \
               UserGroupDBDynamic.php \
               UserGroupDBDynamicCached.php

if HA
scripts_DATA += HA.php

hadir=$(datadir)/ulteo/sessionmanager/modules/HA
ha_DATA = HA/high_availability.php
endif

EXTRA_DIST = $(scripts_DATA) $(ha_DATA)
