# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
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

CCFLAGS ?= -Wall -DSYSCONFDIR=\"/etc\"
CXXFLAGS = $(CCFLAGS)

SRC = main.cpp Utils.cpp Action.cpp
OBJ = $(SRC:.cpp=.o)
EXE = HAshell

all: $(OBJ)
	$(CXX) -o $(EXE) $(OBJ) $(LDFLAGS)

$(EXE): all

install: $(EXE)
	mkdir -p $(DESTDIR)/usr/bin
	cp $(EXE) $(DESTDIR)/usr/bin
	chmod 4755 $(DESTDIR)/usr/bin/$(EXE)

clean:
	$(RM) *~
	$(RM) $(OBJ)
	$(RM) $(EXE)
