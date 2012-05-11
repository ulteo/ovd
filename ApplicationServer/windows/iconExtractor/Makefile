# Copyright (C) 2009-2012 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010, 2012
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

ifeq (${OS}, Windows_NT)
RM=del
else
PREFIX?=i586-mingw32msvc-
RM=rm -rf
endif

CC=$(PREFIX)gcc
CFLAGS= -Wall
LDFLAGS=-lgdi32 -lshlwapi



all: exeIcon2png.exe extract_icon.exe

exeIcon2png.exe: exeIcon2png.o
	$(CC) -o $@ $< $(LDFLAGS) -lpng -lz

%.exe: %.o
	$(CC) -o $@ $< $(LDFLAGS)


clean:
	$(RM) exeIcon2png.exe
	$(RM) extract_icon.exe
	$(RM) *.o
	$(RM) *~
