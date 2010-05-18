# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Thomas MOUTON <thomas@ulteo.com> 2010
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

PREFIX?=i586-mingw32msvc-
CC=$(PREFIX)gcc
#JAVAHOME="C:\Program Files\Java\jdk1.6.0_18"
#CFLAGS=-Wall -I${JAVAHOME}\include -I${JAVAHOME}\include\win32
JAVAHOME?=/usr/lib/jvm/java-1.6.0-openjdk
CFLAGS=-Wall -I${JAVAHOME}/include -I${JAVAHOME}/include/linux
LDFLAGS=
EXEC=libWindowsPaths.dll


all: clean org_ulteo_ovd_integrated_WindowsPaths.h $(EXEC)

$(EXEC): org_ulteo_ovd_integrated_WindowsPaths.o org_ulteo_ovd_integrated_WindowsPaths.def org_ulteo_ovd_integrated_WindowsPaths.h
	$(CC) org_ulteo_ovd_integrated_WindowsPaths.o org_ulteo_ovd_integrated_WindowsPaths.def $(LDFLAGS) -shared -o ${EXEC}

org_ulteo_ovd_integrated_WindowsPaths.h:
	javah -classpath ../classes/ -d . org.ulteo.ovd.integrated.WindowsPaths

clean:
	$(RM) *.o $(EXEC)
	$(RM) *~
	$(RM) org_ulteo_ovd_integrated_WindowsPaths.h

.h:

install: all
	install $(EXEC) $(DIST)

uninstall:
	$(RM) $(DIST)/$(EXEC)