# Copyright (C) 2009-2011 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010, 2011
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

IS_WIN=$(findstring Win,$(OS))
ifneq ($(IS_WIN),)
	RM=del
else
	PREFIX?=i586-mingw32msvc-
endif

CC=$(PREFIX)gcc
RESC=$(PREFIX)windres

CFLAGS= -Wall
LDFLAGS= -lshlwapi

SRC1=main.c
RES1=ressources.rc
OBJ1=$(SRC1:.c=.o) $(RES1:.rc=.o)
EXE1=OvdNativeClient.exe
EXE2=OvdNativeClientw.exe


.SUFFIXES: .c .rc

all: $(EXE1) $(EXE2)

$(EXE1): $(OBJ1)
	$(CC) -o $(EXE1) $(OBJ1) $(LDFLAGS)
$(EXE2): $(OBJ1)
	$(CC) -o $(EXE2) $(OBJ1) $(LDFLAGS) -mwindows

.rc.o :
	$(RESC) -o $@ $<

clean:
	$(RM) $(EXE1) $(EXE2) $(OBJ1)
	$(RM) *~


install: all
	install $(EXE1) $(DIST)
	install $(EXE2) $(DIST)

uninstall:
	$(RM) $(DIST)/$(EXE1) 
	$(RM) $(DIST)/$(EXE2)
