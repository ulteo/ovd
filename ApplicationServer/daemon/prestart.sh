#!/bin/sh
# Copyright (C) 2006-2009 Ulteo SAS
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
. log.sh

close_session() {
    log_WARN "Prestart close session (${SESSID})"
    session_switch_status $SESSID 3
    exit 1
}

NFSSTATUS=0
COUNTER=0

LOC=`cat ${SESSID_DIR}/parameters/locale`
GEOMETRY=`cat ${SESSID_DIR}/parameters/geometry`
check_variables USER_LOGIN USER_HOME LOC GEOMETRY RFB_PORT || close_session

HOME_DIR_TYPE=`cat ${SESSID_DIR}/parameters/module_fs/type`
. modules_fs.sh || close_session


if [ -f ${SESSID_DIR}/parameters/allow_shell ]; then
    USER_SHELL=/bin/bash
else
    USER_SHELL=/bin/false
fi

if [ -f ${SESSID_DIR}/parameters/module_fs/user_id ]; then
    USER_ID=`cat ${SESSID_DIR}/parameters/module_fs/user_id`
    #GROUPADD_ARG="-g ${USER_ID}"
    USERADD_ARG='-u '${USER_ID}
else
    USERADD_ARG='-K UID_MIN=2000'
fi

groupadd ${USER_LOGIN} 
useradd --shell ${USER_SHELL} --home $USER_HOME -m -k /dev/null $USERADD_ARG} -g ${USER_LOGIN} ${USER_LOGIN}
if [ $? -ne 0 ]; then
    log_ERROR "Unable to useradd '${USER_LOGIN}', that could say that's it already exist."
    session_switch_status $SESSID 3
    exit 1
fi

export USER_ID=$(id -u $USER_LOGIN)

chown ${USER_ID} $SPOOL_USERS/$SESSID
chmod 770        $SPOOL_USERS/$SESSID

# May return 1 if $HOME_DIR_TYPE doesn't exists.

set_fs || close_session

# Mount home directory
 
log_DEBUG "Prestart geometry: "$GEOMETRY

do_mount
if [  $? -gt 0 ]; then
    log_ERROR "prestart: Home dir mount of $USER_HOME FAILED."
    close_session
fi

# erase all previous session junk
su -s "/bin/bash" ${USER_LOGIN} -c "/bin/rm -rf ${USER_HOME}/.DCOP* ${USER_HOME}/.ICE* ${USER_HOME}/.kde/cache* ${USER_HOME}/.kde/socket* ${USER_HOME}/.kde/tmp*";

# create tmp dirs for VNC and the user, link unix sockets together
export VNC_USER_ID=$(id -u $VNC_USER)

export GEOMETRY LOC
startsession.sh &
