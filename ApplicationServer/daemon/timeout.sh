#!/bin/sh
# Copyright (C) 2006-2008 Ulteo SAS
# http://www.ulteo.com
# Author Gaël DUVAL <gduval@ulteo.com>
# Author Gauvain POCENTEK <gauvain@ulteo.com>
# Author Julien LANGLOIS <julien@ulteo.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License 
# as published by the Free Software Foundation, version 2
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

. functions.sh

if rsbac_is_active; then
    USER_TMP=/tmp/
else
    USER_TMP=/tmp/.tmp${USER_ID}
fi

if [ -e ${SESSID_DIR}/FREE ]; then
    CMD='kdialog --sorry "Dear user,\n you are enjoying a FREE Ulteo Online Desktop session. In order to provide this benefit to the largest number of people, we have to limit the time of your free Ulteo session. If you want to get rid of this limitation, you can purchase an Ulteo Premium Service at:\n\nhttp://store.ulteo.com\n\nPLEASE SAVE ALL YOUR DATA NOW.\nYour session is going to end in 3 minutes." --caption "Session is about to end."'
else
    CMD='kdialog --sorry "Dear user,\n your Ulteo Online Desktop session is going to end, due to time limitation for each session.\n\nPLEASE SAVE ALL YOUR DATA NOW.\nYour session is going to end in 3 minutes." --caption "Session is about to end."'
fi

su -s "/bin/bash" $USER_LOGIN -c "DISPLAY=:$i XAUTHORITY=${USER_TMP}.Xauthority ${CMD}" &

let newtimeout=$(( `date +%s` + 600 ))
echo $newtimeout > ${SESSID_DIR}/ex
sleep 300
session_switch_status $SESSID 3
