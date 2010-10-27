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

CC=gcc
JAVAHOME?=/usr/lib/jvm/java-1.6.0-openjdk
ARCH?=32
CFLAGS=-Wall -I${JAVAHOME}/include -I${JAVAHOME}/include/linux -fPIC
LDFLAGS=-lX11
EXEC=libXClientArea.so
DESTDIR?=.
LIBDIR=$(DESTDIR)/usr/lib/
CIBLE=$(ARCH)/$(EXEC)

ifeq ($(ARCH), 64)
  CIBLE+=32/$(EXEC)
endif

all: $(CIBLE)

%/pre:
	mkdir -p $(dir $@)

%/$(EXEC): %/pre %/org_ulteo_utils_jni_WorkArea.o
	$(CC) $(LDFLAGS) -m$(subst /,,$(dir $@)) -shared -o $@ $1

src/org_ulteo_utils_jni_WorkArea.h:
	javac -classpath ../src -d . ../src/org/ulteo/utils/jni/WorkArea.java
	javah -d ./src/ org.ulteo.utils.jni.WorkArea

32/%.o: src/%.c src/%.h
	$(CC) $(CFLAGS) -m32 -c -o $@ $<

64/%.o: src/%.c src/%.h
	$(CC) $(CFLAGS) -m64 -c -o $@ $<

clean:
	$(RM) *~
	$(RM) src/org_ulteo_utils_jni_WorkArea.h
	$(RM) -rf org 32 64

install: all
	mkdir -p $(LIBDIR)
	install $(EXEC) $(LIBDIR)

uninstall:
	$(RM) $(LIBDIR)/$(EXEC)

dist: clean
	DISTDIR=ovd-xclient-area-@VERSION@ ; \
	mkdir -p $$DISTDIR/src ; \
	cp Makefile $$DISTDIR ; \
	cp src/org_ulteo_utils_jni_WorkArea.c $$DISTDIR/src ; \
	tar -zcf ovd-xclient-area.tar.gz $$DISTDIR ; \
	rm -rf $$DISTDIR

distcheck: dist
