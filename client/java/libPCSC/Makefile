# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2012
# Author Yann HODIQUE <y.hodique@ulteo.com> 2012
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

OS?=$(shell uname -s)
OS:=$(OS:MINGW%=MINGW)
DEBUG?=FALSE

CC=gcc
JDK_HOME?=$(realpath $(dir $(realpath $(shell which javah)))/..)
DESTDIR?=.
LIBDIR=$(DESTDIR)/usr/lib/

ifeq "$(OS)" "Linux"
ARCH?=$(shell getconf LONG_BIT)
CFLAGS=-fPIC -I"${JDK_HOME}"/include -I"${JDK_HOME}"/include/linux -Isrc -Isrc/MUSCLE -I/usr/include/PCSC
LDFLAGS=
LIB=libPCSC.so
else
ARCH?=32
ifeq ($(ARCH), 64)
CC=x86_64-w64-mingw32-gcc
else
CC=i686-w64-mingw32-gcc
endif
CFLAGS=-m${ARCH} -I"${JDK_HOME}"/include -I"${JDK_HOME}"/include/win32 -Isrc -D_JNI_IMPLEMENTATION_
LDFLAGS=-m${ARCH} -lwinscard -Wl,--export-all,--kill-at
#-Wl,-enable-auto-import -Wl,-enable-runtime-pseudo-reloc -Wl,--add-stdcall-alias -shared -enable-stdcall-fixup
LIB=libPCSC.dll
endif

ifeq ($(ARCH), 64)
LDFLAGS+=$(if $(STATIC), -Llib_pic)
endif

ifeq "$(DEBUG)" "FALSE"
CFLAGS+=-Os
else
CFLAGS+=-g -O0 -DDEBUG
endif

all: pre $(LIB)

pre:
	mkdir -p $(ARCH)

$(LIB): org_ulteo_pcsc_PlatformPCSC.o org_ulteo_pcsc_PCSC.o
ifndef STATIC
	$(CC) -o $(ARCH)/$@ -shared -Wl,-soname,$@ $? $(LDFLAGS)
else
	$(LD) -o $(ARCH)/$@ -share -soname $(LIB) $^ --strip-all \
		-static $(LDFLAGS) -Bdynamic -lc
endif

%.o: src/%.c src/%.h
	$(CC) -Wall $(CFLAGS) -o $@ -c $<

clean:
	$(RM) *~
	$(RM) -rf org *.o $(LIB) $(ARCH)

install: all
	mkdir -p $(LIBDIR)
	install $(ARCH)/$(LIB) $(LIBDIR)

uninstall:
	$(RM) $(LIBDIR)/$(LIB)

dist: clean
	DISTDIR=ovd-libpcsc-@VERSION@ ; \
	mkdir -p $$DISTDIR/src ; \
	cp Makefile $$DISTDIR ; \
	cp src/org_ulteo_pcsc_PlatformPCSC.c src/org_ulteo_pcsc_PlatformPCSC.h $$DISTDIR/src ; \
	tar -zcf ovd-libpcsc.tar.gz $$DISTDIR ; \
	rm -rf $$DISTDIR

distcheck: dist
