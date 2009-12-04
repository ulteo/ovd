#!/bin/sh
# Copyright (C) 2009 Ulteo SAS
# http://www.ulteo.com
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
. log.sh

if [ -z "$1" ]; then
    log_ERROR "$0 missing arguments"
    exit 1
fi

SESSID=$1
job=windows

session_load $SESSID

if ! windows_use_seamlessrdp $SESSID_DIR; then
    log_INFO "Don't have to init windows"
    exit 0
fi

ENV_FILE=$SPOOL_USERS/$SESSID/env.sh

dir=$SESSID_DIR/sessions/$job
log_INFO "Session $SESSID detect job $job"

install -d -o www-data -g www-data -m 770 $dir

geometry=$(cat $SESSID_DIR/parameters/geometry)

if [ -z "$geometry" ]; then
    log_WARN "Unable to perform job: missing arguments"
    exit 1
fi

echo "desktop" > $dir/app_id

application_startdisplay $SESSID $job $geometry
if [ $? -ne 0 ]; then
    log_WARN "Session $SESSID: Unable to continue"
    exit 1
fi
rfb_port=$(cat $dir/rfb_port)

application_switch_status $SESSID $job 2
windows_init_connection ${SESSID_DIR} $rfb_port 1
sleep 2
[ -d $dir ] || exit 0
log_WARN "Session $SESSID Application $job still exist but should not !"
application_switch_status $SESSID $job 3
