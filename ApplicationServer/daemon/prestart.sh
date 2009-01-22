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

if [ -f ${SESSID_DIR}/parameters/module_fs/user_id ]; then
    export USER_ID=`cat ${SESSID_DIR}/parameters/module_fs/user_id`
    groupadd -g ${USER_ID} ${USER_LOGIN} 
    useradd --shell /bin/false --home $USER_HOME -m -k /dev/null -u ${USER_ID} -g ${USER_LOGIN} ${USER_LOGIN}
else
    groupadd ${USER_LOGIN} 
    useradd --shell /bin/false --home $USER_HOME -m -k /dev/null -K UID_MIN=2000 -g ${USER_LOGIN} ${USER_LOGIN}
    export USER_ID=$(id -u $USER_LOGIN)   
fi

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
if rsbac_is_active; then
    tmp_make ${USER_LOGIN}
    tmp_make $VNC_USER
    chown :${USER_LOGIN} /tmpdir/tmp${VNC_USER_ID}
    ln -sf /tmpdir/tmp${VNC_USER_ID}/.X11-unix /tmpdir/tmp${USER_ID}/
fi

if rsbac_is_active ; then
    JAIL="/sbin/run-jail startsession"
fi

export GEOMETRY LOC
$JAIL startsession.sh &
