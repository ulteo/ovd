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

unexport i BIN_DIR CONF_FILE FS GEOMETRY LOG_FILE \
    LOG_FLAGS MAXLUCK MINLUCK MODULES_FSD \
    MOUNT_LOG MOUNT_RETRIES NICK RFB_PORT SERVERNAME \
    SESSID_DIR SESSION_MANAGER_URL \
    SPOOL USER_HOME USER_ID \
    USER_LOGIN VNC_USER VNC_USER_ID \
    SUDO_USER SUDO_GID SUDO_UID SUDO_COMMAND

if rsbac_is_active; then
    USER_TMP=/tmpdir/tmp${USER_ID}/
    VNC_TMP=/tmpdir/tmp${VNC_USER_ID}/
else
    USER_TMP=/tmp/.tmp${USER_ID}
    VNC_TMP=/tmp/.tmp${VNC_USER_ID}
fi

VNCCACHINGOPTS="-caching -caching_ent 1500 -caching_malg LRU -caching_minsize 5000000"

i=$(( $i + 5900 ))

# Install the MIT_MAGIC_COOKIE
/bin/su -s "/bin/bash" $VNC_USER -c "xauth -f ${VNC_TMP}.Xauthority add :$i . `/usr/bin/mcookie`"

# Install the MIT_MAGIC_COOKIE into the real user env
cp ${VNC_TMP}.Xauthority $SPOOL_USERS/$SESSID/.Xauthority
chown ${USER_LOGIN}      $SPOOL_USERS/$SESSID/.Xauthority

# Install the rfbauth file into a directory readable by the VNC user
cp ${SESSID_DIR}/private/encvncpasswd ${VNC_TMP}encvncpasswd
chown $VNC_USER:$VNC_USER ${VNC_TMP}encvncpasswd

out=/dev/null

# Start the VNC server
/bin/su -s "/bin/bash" $VNC_USER -c "XAUTHORITY=${VNC_TMP}.Xauthority /usr/bin/Xtightvnc $VNCCACHINGOPTS :$i -desktop X$i -nolock -once -interface 127.0.0.1 -localhost -lf 1024 -geometry $GEOMETRY -depth 24 -rfbwait 240000 -rfbauth ${VNC_TMP}encvncpasswd -rfbport $RFB_PORT -fp /usr/share/X11/fonts/Type1/,/usr/share/X11/fonts/misc/,/usr/share/X11/fonts/75dpi/,/usr/share/X11/fonts/100dpi/ -co /etc/X11/rgb -ac -auth ${VNC_TMP}.Xauthority" &> $out &

session_install_client $SESSID

# give some time to Xtightvnc to start
sleep 1

log_INFO "startsession : session state 2"
session_switch_status $SESSID 2

# Xvnc accept connexion only from MIT_MAGIC_COOKIEs
su -s "/bin/bash" $VNC_USER -c "DISPLAY=:$i XAUTHORITY=${VNC_TMP}.Xauthority /usr/bin/xhost -";

export LC_ALL=$LOC LANG=$LOC LANGUAGE=$LOC
export DISPLAY=:$i XAUTHORITY=$SPOOL_USERS/$SESSID/.Xauthority
export XDG_DATA_DIRS=$SPOOL_USERS/$SESSID/xdg
export OVD_APPS_DIR=$XDG_DATA_DIRS/applications
[ -f ${SESSID_DIR}/parameters/start_app ] && export APP=`cat ${SESSID_DIR}/parameters/start_app`
[ -f ${SESSID_DIR}/parameters/open_doc ] && export DOC=`cat ${SESSID_DIR}/parameters/open_doc`

menu_spool $XDG_DATA_DIRS ${SESSID_DIR}
windows_init_connection ${SESSID_DIR}

if [ -f ${SESSID_DIR}/parameters/timezone ]; then
    tz=`cat ${SESSID_DIR}/parameters/timezone`
    if [ -f /usr/share/zoneinfo/$tz ]; then
	log_INFO "set TZ to $tz"
	export TZ="/usr/share/zoneinfo/$tz"
    else
	log_WARN "invalid TZ to '/usr/share/zoneinfo/$tz'"
    fi
fi

# Start autocutsel
su -s "/bin/bash" ${USER_LOGIN} -c "/usr/bin/autocutsel" &> $out &

if [ "$AJAX" = "TRUE" ]; then
    # Start DCOP/etc services
    su -s "/bin/bash" ${USER_LOGIN} -c "LD_BIND_NOW=true /usr/bin/kdeinit" &> $out &

    # Start ulteowm
    su -s "/bin/bash" ${USER_LOGIN} -c "/usr/bin/ulteowm $((6900+$i))" &> $out
else
    # Start the KDE session
    su -s "/bin/bash" ${USER_LOGIN} -c "cd ~ && startovd" &> $out
fi

# force session to end
session_switch_status $SESSID 3
