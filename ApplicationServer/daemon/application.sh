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

if [ -z "$1" ] || [ -z "$2" ]; then
    log_ERROR "$0 missing arguments"
    exit 1
fi

SESSID=$1
job=$2

session_load $SESSID
ENV_FILE=$SPOOL_USERS/$SESSID/env.sh

file=$SESSID_DIR/sessions/$job.txt
dir=$SESSID_DIR/sessions/$job
log_INFO "Session $SESSID detect job $job"

mkdir -p $dir
echo 1 > $dir/status

app_id=$(head -n 1 $file)
geometry=$(head -n 2 $file |tail -n 1)
app=$(head -n 3 $file |tail -n 1)
if [ -z "$app_id" ] || [ -z "$geometry" ] || \
    [ -z "$app" ] && [ "$app_id" != "desktop" ]; then
    log_WARN "Unable to perform job: missing arguments"
    exit 1
fi

echo $app_id > $dir/app_id
echo $app > $dir/app
echo $geometry > $dir/geometry
rm $file

rfb_port=$(spool_get_rfbport)
echo $rfb_port > $dir/rfb_port

display_init $SESSID $rfb_port
if [ $? -ne 0 ]; then
    log_WARN "Job error !"
    exit 1
fi

display_start $rfb_port $geometry
if [ $? -ne 0 ]; then
    log_WARN "Job error ! 2"
    exit 1
fi

# Todo: DOC, desktop
echo 2 > $dir/status
user_exec $app_id "$app" $rfb_port 1
echo 3 > $dir/status

display_stop $rfb_port

spool_get_rfbport $rfb_port

echo 3 > $dir/status
rm -rf $dir

if [ "$app_id" == "desktop" ]; then
    session_switch_status $SESSID 3
fi
