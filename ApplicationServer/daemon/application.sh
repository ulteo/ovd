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

nb_line=$(wc -l $file | cut -d' ' -f1)
if [ $nb_line -lt 2 ] || [ $nb_line -gt 3 ]; then
    log_WARN "Unable to perform job ($job): missing arguments ($nb_line lines)"
    rm $file
    exit 1
fi

app_id=$(head -n 1 $file)
if [ -z "$app_id" ] || ! menu_has_application $SPOOL_USERS/$SESSID/xdg $app_id; then
    log_WARN "Unable to perform job ($job): unknown application '$app_id'"
    rm $file
    exit 1
fi

geometry=$(head -n 2 $file |tail -n 1)
if [ -z "$geometry" ]; then
    log_WARN "Unable to perform job ($job): missing geometry"
    rm $file
    exit 1
fi
[ $nb_line -eq 3 ] && doc=$(head -n 3 $file |tail -n 1)

install -d -g www-data -m 770 $dir
application_switch_status $SESSID $job 1
echo $app_id > $dir/app_id
[ -n "$doc" ] && echo "$doc" > $dir/doc
echo $geometry > $dir/geometry
rm $file


application_startdisplay $SESSID $job $geometry
if [ $? -ne 0 ]; then
    log_WARN "Session $SESSID: Unable to continue"
    exit 1
fi
rfb_port=$(cat $dir/rfb_port)

application_switch_status $SESSID $job 2
user_exec $app_id $rfb_port "$doc"
# If application already killed
[ -d $dir ] || exit 0
application_switch_status $SESSID $job 3

