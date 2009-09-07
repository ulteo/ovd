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

USER_TMP=/tmp/.tmp${USER_ID}
VNC_TMP=/tmp/.tmp${VNC_USER_ID}

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

# Start the VNC server
/bin/su -s "/bin/bash" $VNC_USER -c "XAUTHORITY=${VNC_TMP}.Xauthority /usr/bin/Xtightvnc $VNCCACHINGOPTS :$i -desktop X$i -nolock -once -interface 127.0.0.1 -localhost -lf 1024 -geometry $GEOMETRY -depth 24 -rfbwait 240000 -rfbauth ${VNC_TMP}encvncpasswd -rfbport $RFB_PORT -fp /usr/share/fonts/X11/Type1/,/usr/share/fonts/X11/misc/,/usr/share/fonts/X11/75dpi/,/usr/share/fonts/X11/100dpi/ -co /etc/X11/rgb -ac -auth ${VNC_TMP}.Xauthority" &> /dev/null &

session_install_client $SESSID

# give some time to Xtightvnc to start
sleep 1

log_INFO "startsession : session state 2"
session_switch_status $SESSID 2

# Xvnc accept connexion only from MIT_MAGIC_COOKIEs
su -s "/bin/bash" $VNC_USER -c "DISPLAY=:$i XAUTHORITY=${VNC_TMP}.Xauthority /usr/bin/xhost -";

LC_ALL=$LOC
LANG=$LOC
LANGUAGE=$LOC

DISPLAY=:$i
XAUTHORITY=$SPOOL_USERS/$SESSID/.Xauthority

OVD_SESSID_DIR=$SPOOL_USERS/$SESSID
XDG_DATA_DIRS=$OVD_SESSID_DIR/xdg
OVD_APPS_DIR=$XDG_DATA_DIRS/applications

[ -f ${SESSID_DIR}/parameters/start_app ] && APP=`cat ${SESSID_DIR}/parameters/start_app`
[ -f ${SESSID_DIR}/parameters/start_app_id ] && APP_ID=`cat ${SESSID_DIR}/parameters/start_app_id`
[ -f ${SESSID_DIR}/parameters/open_doc ] && DOC=`cat ${SESSID_DIR}/parameters/open_doc`

[ -f ${SESSID_DIR}/parameters/module_fs/user_homedir ] && CIFS_HOME_DIR=`cat ${SESSID_DIR}/parameters/module_fs/user_homedir`

if [ "r$DOC" != "r" ] || [ "r$APP" != "r" ]; then
    [ -f ${SESSID_DIR}/parameters/app_with_desktop ] || NODESKTOP=1
fi

if [ -f ${SESSID_DIR}/parameters/timezone ]; then
    tz=`cat ${SESSID_DIR}/parameters/timezone`
    if [ -f /usr/share/zoneinfo/$tz ]; then
	log_INFO "set TZ to $tz"
	TZ="/usr/share/zoneinfo/$tz"
    else
	log_WARN "invalid TZ to '/usr/share/zoneinfo/$tz'"
    fi
fi

session_create_env_file

menu_spool $XDG_DATA_DIRS ${SESSID_DIR}
windows_init_connection ${SESSID_DIR}

# Start autocutsel
su -s "/bin/bash" - ${USER_LOGIN} -c ". $ENV_FILE; /usr/bin/autocutsel" &> /dev/null &

# Start the desktop session
su -s "/bin/bash" - ${USER_LOGIN} -c ". $ENV_FILE; cd ~; startovd" &> /dev/null

# force session to end
session_switch_status $SESSID 3
