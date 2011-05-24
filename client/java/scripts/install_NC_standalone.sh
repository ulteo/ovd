#! /bin/sh
#
# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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

expr $1 : '^--\w\+' >/dev/null && CMD=$1 && shift
[ -z "$CMD" ] && CMD=--install
DESTDIR=$1

case $CMD in
    --install)
	    find usr -type f -exec install -D {} $DESTDIR/{} \;
    ;;

    --remove)
	    find usr -type f -exec rm -f $DESTDIR/{} \;
	    rm -rf $DESTDIR/usr/lib/libXClientArea
    ;;

    --version)
        echo "version: $(cat VERSION)"
    ;;

    --help)
        echo "./install.sh <install|uninstall> [DESTDIR]"
    ;;

    --*)
        echo "unknown '$CMD' argument"
        exit 127
    ;;
esac
