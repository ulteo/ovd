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

install -d -g www-data -m 770 $dir

geometry=$(cat $SESSID_DIR/parameters/geometry)

if [ -z "$geometry" ]; then
    log_WARN "Unable to perform job: missing arguments"
    exit 1
fi

rfb_port=$(spool_get_rfbport)
echo $rfb_port > $dir/rfb_port
echo "desktop" > $dir/app_id

display_init $SESSID $rfb_port
if [ $? -ne 0 ]; then
    log_WARN "Job error !"
    exit 1
fi

display_start $rfb_port $geometry $dir/vnc.pid
if [ $? -ne 0 ]; then
    log_WARN "Job error ! 2"
    exit 1
fi

application_switch_status $SESSID $job 2
windows_init_connection ${SESSID_DIR} $rfb_port 1
application_switch_status $SESSID $job 3
