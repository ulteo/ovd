# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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
JAVAHOME?=$(realpath $(dir $(realpath $(shell which javah)))/..)
EXEC=libXClientArea.so
DESTDIR?=.
LIBDIR=$(DESTDIR)/usr/lib/
ARCH=$(shell getconf LONG_BIT)

CFLAGS=-I${JAVAHOME}/include -I${JAVAHOME}/include/linux
LDFLAGS=-lX11 -lxcb -lXau -lXdmcp
# -lxcb-xlib for Hardy
ifeq ($(ARCH), 64)
LDFLAGS+=$(if $(STATIC), -Llib_pic)
endif

all: pre $(EXEC)

pre:
	mkdir -p $(ARCH)

$(EXEC): org_ulteo_utils_jni_WorkArea.o
ifndef STATIC
	$(CC) -o $(ARCH)/$@ -shared -Wl,-soname,$@ $? $(LDFLAGS)
else
	$(LD) -o $(ARCH)/$@ -share -soname $(EXEC) $^ --strip-all \
		-static $(LDFLAGS) -Bdynamic -lc
endif

src/org_ulteo_utils_jni_WorkArea.h:
	javac -classpath ../src -d . ../src/org/ulteo/utils/jni/WorkArea.java
	javah -d ./src/ org.ulteo.utils.jni.WorkArea

%.o: src/%.c src/%.h
	$(CC) -Os -fPIC -Wall $(CFLAGS) -o $@ -c $<

clean:
	$(RM) *~
	$(RM) src/org_ulteo_utils_jni_WorkArea.h
	$(RM) -rf org *.o $(EXEC) $(ARCH)

install: all
	mkdir -p $(LIBDIR)
	install $(ARCH)/$(EXEC) $(LIBDIR)

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
