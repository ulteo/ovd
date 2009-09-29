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

log_INFO "setting user environment"

if [ -z "$1" ]; then
    log_ERROR "$0 missing argument"
    exit 1
fi

SESSID=$1
session_load $SESSID
if [ $? -ne 0 ]; then
    log_ERROR "$0: unable to load session $SESSID"
    exit 1
fi

LOC=$(cat ${SESSID_DIR}/parameters/locale)
GEOMETRY=$(cat ${SESSID_DIR}/parameters/geometry)
HOME_DIR_TYPE=$(cat ${SESSID_DIR}/parameters/module_fs/type)
USER_HOME=/home/$NICK

check_variables GEOMETRY \
    HOME_DIR_TYPE \
    LOC \
    NICK \
    USER_LOGIN
if [ $? -ne 0 ]; then
    log_ERROR "$0 missing variable"
    exit 1
fi

. modules_fs.sh || exit 1

user_create
if [  $? -ne 0 ]; then
    log_ERROR "$0: unable to create user"
    exit 1
fi

set_fs
if [  $? -ne 0 ]; then
    log_ERROR "$0: set_fs FAILED."
    exit 1
fi

do_mount
if [  $? -ne 0 ]; then
    log_ERROR "$0: do_mount of $USER_HOME FAILED."
    exit 1
fi

user_set_env
if [  $? -ne 0 ]; then
    log_ERROR "$0: user_set_env FAILED."
    exit 1
fi

session_install_client $SESSID
