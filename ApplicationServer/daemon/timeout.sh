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

USER_TMP=${SPOOL_USERS}/${SESSID}/

[ ! -f ${SESSID_DIR}/parameters/timeout_message ] && exit 1

i=$(( $i + 5900 ))
MESSAGE=$(cat ${SESSID_DIR}/parameters/timeout_message)

# CMD="kdialog --sorry \"$message\" --caption \"Session is about to end\""
if $(which lmessage > /dev/null); then
    CMD='lmessage --title "Session is about to end" --type warn "'$MESSAGE'"'
elif $(which Xdialog > /dev/null); then
    CMD='Xdialog -title "Session is about to end" -msgbox "'$MESSAGE'" 20 80'
fi

if [ -n "$CMD" ]; then
    export DISPLAY=:$i XAUTHORITY=${USER_TMP}.Xauthority
    su -s "/bin/bash" $USER_LOGIN -c "${CMD}" &
fi
