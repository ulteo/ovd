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

user_create() {
    if [ -f ${SESSID_DIR}/parameters/allow_shell ]; then
        USER_SHELL=/bin/bash
    else
        USER_SHELL=/bin/false
    fi

    if [ -f ${SESSID_DIR}/parameters/module_fs/user_id ]; then
        USER_ID=$(cat ${SESSID_DIR}/parameters/module_fs/user_id)
        USERADD_ARG='-u '${USER_ID}
    else
        USERADD_ARG='-K UID_MIN=2000'
    fi

    useradd --shell ${USER_SHELL} --home $USER_HOME -m -k /dev/null ${USERADD_ARG} ${USER_LOGIN}

    USER_ID=$(id -u $USER_LOGIN)
    chown $USER_ID $SPOOL_USERS/$SESSID
    chmod 770      $SPOOL_USERS/$SESSID
}

user_delete() {
    userdel $USER_LOGIN
}

user_set_env() {
    LC_ALL=$LOC
    LANG=$LOC
    LANGUAGE=$LOC

    XAUTHORITY=$SPOOL_USERS/$SESSID/.Xauthority

    OVD_SESSID_DIR=$SPOOL_USERS/$SESSID
    XDG_DATA_DIRS=$OVD_SESSID_DIR/xdg
    OVD_APPS_DIR=$XDG_DATA_DIRS/applications

    if [ -f ${SESSID_DIR}/parameters/timezone ]; then
        tz=$(cat ${SESSID_DIR}/parameters/timezone)
        if [ -f /usr/share/zoneinfo/$tz ]; then
            log_INFO "set TZ to $tz"
            TZ="/usr/share/zoneinfo/$tz"
        else
            log_WARN "invalid TZ to '/usr/share/zoneinfo/$tz'"
        fi
    fi

    session_create_env_file

    menu_spool $XDG_DATA_DIRS ${SESSID_DIR}
    # windows_init_connection ${SESSID_DIR}
}

user_exec() {
    local app_id=$1
    local app=$2
    local rfb_port=$3
    if [ -n "$4" ]; then
        local doc=$4
    fi

    # Start autocutsel
    su -s "/bin/bash" - ${USER_LOGIN} -c ". $ENV_FILE; DISPLAY=:$rfb_port /usr/bin/autocutsel" >/dev/null 2>&1 &

    local env="DISPLAY=:$rfb_port"
    if [ $app_id != "desktop" ]; then
        local env="$env NODESKTOP=1 APP=\"$app\" APP_ID=$app_id"
        if [ -n "$4" ]; then
            local env="$env DOC=\"$4\""
        fi
    fi
    log_INFO "env: $env"

    # Start the desktop session
    su -s "/bin/bash" - ${USER_LOGIN} -c ". $ENV_FILE; $env startovd" >/dev/null 2>&1
}

user_exec_cmd() {
    local command="$1"
    local display=$2
    [ $# -gt 2 ] && local env="$3"

    local cmd="$env DISPLAY=:$display $command"

    log_INFO "user_exec: $cmd"
    su -s "/bin/bash" - ${USER_LOGIN} -c ". $ENV_FILE; $cmd" &> /dev/null
}
