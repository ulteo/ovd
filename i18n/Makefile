# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
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

GETTEXT_PACKAGES=$(wildcard uovd*)

CURDIR=$(shell pwd)

all:
	for package in $(GETTEXT_PACKAGES); do \
		cd $$package; \
		make; \
		cd $(CURDIR); \
	done

install:
	for package in $(GETTEXT_PACKAGES); do \
		cd $$package; \
		make install DESTDIR=$(DESTDIR); \
		cd $(CURDIR); \
	done

clean:
	for package in $(GETTEXT_PACKAGES); do \
		cd $$package; \
		make clean; \
		cd $(CURDIR); \
	done
