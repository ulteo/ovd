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

install -d -g www-data -m 770 $dir
application_switch_status $SESSID $job 1

nb_line=$(wc -l $file | cut -d' ' -f1)
if [ $nb_line -lt 2 ] || [ $nb_line -gt 3 ]; then
    log_WARN "Unable to perform job: missing arguments ($nb_line lines)"
    exit 1
fi

app_id=$(head -n 1 $file)
geometry=$(head -n 2 $file |tail -n 1)
if [ -z "$app_id" ] || [ -z "$geometry" ] || \
    [ "$app_id" = "desktop" ]; then
    log_WARN "Unable to perform job: missing arguments"
    exit 1
fi
[ $nb_line -eq 3 ] && doc=$(head -n 3 $file |tail -n 1)

echo $app_id > $dir/app_id
[ -n "$doc" ] && echo "$doc" > $dir/doc
echo $geometry > $dir/geometry
rm $file

rfb_port=$(spool_get_rfbport)
echo $rfb_port > $dir/rfb_port

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
user_exec $app_id $rfb_port "$doc"
# If application already killed
[ -d $dir ] || exit 0
application_switch_status $SESSID $job 3

