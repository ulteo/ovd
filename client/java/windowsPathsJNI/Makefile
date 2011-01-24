# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Thomas MOUTON <thomas@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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

BINPREFIX?=i586-mingw32msvc-
CC=$(BINPREFIX)gcc
JAVAHOME?=/usr/lib/jvm/java-1.6.0-openjdk
CFLAGS=-Wall -I${JAVAHOME}/include -I${JAVAHOME}/include/linux
LDFLAGS=
EXEC=libWindowsPaths.dll


all: $(EXEC)

$(EXEC): org_ulteo_ovd_integrated_WindowsPaths.o org_ulteo_ovd_integrated_WindowsPaths.def
	$(CC) $(LDFLAGS) -shared -o $@ $^

org_ulteo_ovd_integrated_WindowsPaths.h:
	javac -classpath ../src:../required_libraries/registry.jar -d . ../src/org/ulteo/ovd/integrated/WindowsPaths.java
	javah org.ulteo.ovd.integrated.WindowsPaths

org_ulteo_utils_jni_WindowsTweaks.h:
	javac -classpath ../src:../required_libraries/registry.jar -d . ../src/org/ulteo/utils/jni/WindowsTweaks.java
	javah org.ulteo.utils.jni.WindowsTweaks

%.o: %.c %.h org_ulteo_utils_jni_WindowsTweaks.h
	$(CC) $(CFLAGS) -c -o $@ $<

clean:
	$(RM) *.o $(EXEC)
	$(RM) *~
	$(RM) org_ulteo_ovd_integrated_WindowsPaths.h
	$(RM) org_ulteo_utils_jni_WindowsTweaks.h

install: all
	install $(EXEC) $(DIST)

uninstall:
	$(RM) $(DIST)/$(EXEC)
