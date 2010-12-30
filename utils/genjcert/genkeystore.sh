#!/bin/sh

# Copyright (C) 2009-2010 Ulteo SAS
# http://www.ulteo.com
# Author Gauvain POCENTEK <gauvain@linutop.com> 2009
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

PASSWD=$(pwgen -1)
DNAME="@DNAME@"
ALIAS="ulteo"
KEYSTORE=keystore
VALIDITY=180

usage () {
    echo "Options :"
    echo " -p PASSWD"
    echo " -d DNAME"
    echo " -a ALIAS"
    echo " -k KEYSTORE"
    echo " -v VALISITY"
    echo " -h"
}

while getopts "p:d:a:k:v:h" options; do
    case "$options" in
        p)
            PASSWD=$OPTARG;;
        d)
            DNAME=$OPTARG;;
        a)
            ALIAS=$OPTARG;;
        k)
            KEYSTORE=$OPTARG;;
        v)
            VALIDITY=$OPTARG;;
        h)
            usage
            exit 0
            ;;
        *)
            usage
            exit 1
            ;;
    esac
done

# gen the keystore
keytool -genkeypair -dname "$DNAME" -alias "$ALIAS" \
    -keypass $PASSWD -storepass $PASSWD \
    -keystore $KEYSTORE -validity $VALIDITY

keytool -selfcert -alias "$ALIAS" -keystore $KEYSTORE \
    -keypass $PASSWD -storepass $PASSWD

echo $PASSWD
exit 0

