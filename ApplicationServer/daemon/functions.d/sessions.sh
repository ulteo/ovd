# Copyright (C) 2008-2010 Ulteo SAS
# http://www.ulteo.com
# Author Laurent CLOUET <laurent@ulteo.com> 2009
# Author Julien LANGLOIS <julien@ulteo.com> 2008, 2009, 2010
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

sessions_get_active() {
    find $SPOOL/sessions -maxdepth 1 -mindepth 1 -type d -printf "%f\n"
}

sessions_get_to_create() {
    find $SPOOL/sessions2create -maxdepth 1 -mindepth 1 -type f ! -name "*-lock*"  -printf "%f\n"
}


#
#  0: session created
#  1: session to initialize
# 22: session intializing
#  2: session ready
#  3: session to destroy
#
#  9: session to suspend
# 10: session suspend
# 11: session to restore
#
session_valid_runasap() {
    local i=0
    
    if [ -z "$1" ]; then
        log_WARN "session_valid_runasap $SESSID: empty session status"
        return 1
    fi

    for i in 0 1 22 2 3 9 10 11; do
        [ $i -eq $1 ] && return 0
    done

    return 1
}

## Session Initialise
# - create session directory and content
# - add SSH and VNC users and group
#
# $1 : session id
#
session_init() {
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID

    # Choose a number for this session
    local i=$(spool_get_id)
    if [ -z "$i" ]; then
	log_ERROR "Unable to get free ID for session $SESSID"
	return 1
    fi

    log_INFO "session_init: '$SESSID' => $i"
    [ $? -eq 0 ] || return 1

    local SSH_USER="SSH$i"
    local VNC_USER="VNC$i"

    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if grep -q -e "$SSH_USER\:x" /etc/passwd; then
        log_ERROR "session_init: user '$SSH_USER' already in /etc/passwd"
        spool_free_id $i
        return 1
    fi
    log_INFO "useradd $SSH_USER"
    useradd -K UID_MIN=2000 --shell /bin/false $SSH_USER 
    if [ $? -eq 1 ] || [ $? -eq 10 ]; then
        log_WARN "Session $SESSID init: unable to lock passwd file, switch to random procedure"

        retry_with_random "useradd -K UID_MIN=2000 --shell /bin/false $SSH_USER"
        [ $? -ne 0 ] && return 1
    fi

    log_DEBUG "seeking VNC group $VNC_USER in /etc/group"
    if grep -q -e "$VNC_USER\:x" /etc/group; then
        log_ERROR "session_init: user '$VNC_USER' already in /etc/group"
        spool_free_id $i
        return 1
    fi
    log_INFO "groupadd -K GID_MAX=70000 $VNC_USER"
    groupadd -K GID_MAX=70000 $VNC_USER
    if [ $? -eq 10 ]; then
        log_WARN "Session $SESSID init: unable to lock passwd file, switch to random procedure"

        retry_with_random "groupadd -K GID_MAX=70000 $VNC_USER"
        [ $? -ne 0 ] && return 1
    fi

    log_DEBUG "seeking VNC user $VNC_USER in /etc/passwd"
    if grep -q -e "$VNC_USER\:x" /etc/passwd; then
        log_ERROR "session_init: user '$VNC_USER' already in /etc/passwd"
        spool_free_id $i
        return 1
    fi
    log_INFO "useradd $VNC_USER"
    useradd -K UID_MIN=2000 --shell /bin/false -g $VNC_USER $VNC_USER
    if [ $? -eq 1 ]; then
        log_WARN "Session $SESSID init: unable to lock passwd file, switch to random procedure"

        retry_with_random "useradd -K UID_MIN=2000 --shell /bin/false -g $VNC_USER $VNC_USER"
        [ $? -ne 0 ] && return 1
    fi

    display_clean $VNC_USER

    UUID=$(id -u $VNC_USER)
    UGID=$(id -g $VNC_USER)

    # create new session dir
    install -d -g www-data             -m 750 $SESSID_DIR
    install -d -o www-data -g www-data -m 770 $SESSID_DIR/parameters
    install -d -o www-data -g www-data -m 770 $SESSID_DIR/infos
    install -d                         -m 700 $SESSID_DIR/private
    install -d -g www-data             -m 750 $SESSID_DIR/clients
    install -d -o www-data -g www-data -m 770 $SESSID_DIR/sessions

    install -d -m 700 $SPOOL_USERS/$SESSID

    # Initialize status file
    echo "0" >     $SESSID_DIR/infos/status
    chown www-data $SESSID_DIR/infos/status
    chmod 660      $SESSID_DIR/infos/status

    ## VNC password
    #
    VNC_PASS=$(echo $RANDOM$(date +%s) | md5sum | mawk '{ print substr($1, 0, 9) }')
    # on hardy we have tightvncpasswd, vncpasswd on dapper
    if $(which tightvncpasswd >/dev/null 2>&1); then
        TIGHTVNCPASSWD="tightvncpasswd"
    else
        TIGHTVNCPASSWD="vncpasswd"
    fi
    # have to cut the pass to 8 characters
    # for realvncpasswd
    echo $VNC_PASS | $TIGHTVNCPASSWD -f > $SESSID_DIR/private/encvncpasswd
    HEXA_VNC_PASS=$(cat $SESSID_DIR/private/encvncpasswd | str2hex)
    install -o $VNC_USER -m 700 $SESSID_DIR/private/encvncpasswd /tmp/.tmp${UUID}encvncpasswd

    ## SSH password
    #
    # Seems the applet doesn't like too long password ...
    SSH_PASS=$(echo $RANDOM$(date +%s) | md5sum | mawk '{ print substr($1, 0, 9) }')
    # we set new shadow pass for this session
    # just be paranoid by default
    echo "$SSH_USER:$SSH_PASS" | chpasswd
    if [ $? -ne 0 ]; then
        log_WARN "Session $SESSID init: unable to lock passwd file, switch to random procedure"

        retry_with_random "echo \"$SSH_USER:$SSH_PASS\" | chpasswd"
        [ $? -ne 0 ] && return 1
    fi

    #
    # we encode the encrypted pass in hexa because the sshvnc 
    # applet wants it
    HEXA_SSH_PASS=$(echo -n $SSH_PASS | str2hex)

    ##echo $SSH_PASS >$SESSID_DIR/sshpasswd # <- just for test !!! remove it in production !!!
    echo $i > $SESSID_DIR/private/id
    echo $HEXA_VNC_PASS > $SESSID_DIR/private/hexavncpasswd
    echo $HEXA_SSH_PASS > $SESSID_DIR/private/hexasshpasswd
    echo $SSH_USER > $SESSID_DIR/private/ssh_user
    echo $VNC_USER > $SESSID_DIR/private/vnc_user

    session_switch_status $SESSID 0
}


## Session Remove
# - erase session directory
# - del SSH and VNC users and group
#
# $1 : session id
#
session_remove() {
    log_INFO "SESSION REMOVE"
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID
    local i=$(cat $SESSID_DIR/private/id)

    local SSH_USER=$(cat $SESSID_DIR/private/ssh_user)
    local VNC_USER=$(cat $SESSID_DIR/private/vnc_user)

    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if grep -q -e "$SSH_USER\:x" /etc/passwd; then
        log_INFO "userdel $SSH_USER"
        userdel $SSH_USER
        if [ $? -eq 1 ] || [ $? -eq 10 ]; then
            log_WARN "Session $SESSID remove: unable to lock passwd file, switch to random procedure"

            retry_with_random "userdel $SSH_USER"
        fi
    fi

    log_DEBUG "seeking VNC user $VNC_USER in /etc/passwd"
    if grep -q -e "$VNC_USER\:x" /etc/passwd; then
        local VNC_UID=$(id -u $VNC_USER)
        log_INFO "userdel $VNC_USER"
        userdel $VNC_USER
        if [ $? -eq 1 ] || [ $? -eq 10 ]; then
            log_WARN "Session $SESSID remove: unable to lock passwd file, switch to random procedure"

            retry_with_random "userdel $VNC_USER"
        fi
    fi

    log_DEBUG "seeking VNC group $VNC_USER in /etc/group"
    if grep -e "$VNC_USER\:x" /etc/group; then
        log_INFO "groupedel $VNC_USER"
        groupdel $VNC_USER
        if [ $? -eq 10 ]; then
            log_WARN "Session $SESSID remove: unable to lock passwd file, switch to random procedure"

            retry_with_random "groupdel $VNC_USER"
        fi
    fi

    log_INFO "session_remove: removing '$SESSID_DIR' ($i)"

    if [ -f $SESSID_DIR/private/failed_rfbports ]; then
        while read rfb_port; do
            log_INFO "remove failed rfb_port $rfb_port from session $SESSID"
            spool_free_rfbport $rfb_port
        done < $SESSID_DIR/private/failed_rfbports
    fi

    rm -rf $SPOOL_USERS/$SESSID
    rm -rf $SESSID_DIR
    spool_free_id $i

    webservices_session_request $SESSID 4
}


## Session Purge
# - kill process from the user of the session
# - kill SSH and VNC process
#
# $1 : session id
#
session_purge() {
    log_INFO "SESSION PURGE "
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID
    local i=$(cat $SESSID_DIR/private/id)

    log_INFO "Purging ${SESSID}"
    local NICK=$(cat ${SESSID_DIR}/parameters/user_displayname 2>/dev/null)
    local USER_LOGIN=$(cat ${SESSID_DIR}/parameters/user_login)
    local USER_UID=$(id -u $USER_LOGIN)
    local HOME_DIR_TYPE=$(cat ${SESSID_DIR}/parameters/module_fs/type)
    local SSH_USER=$(cat $SESSID_DIR/private/ssh_user)
    local VNC_USER=$(cat $SESSID_DIR/private/vnc_user)
    UUID=$(id -u $VNC_USER)

    killall -u $USER_LOGIN
    sleep 0.5
    killall -s 9 -u $USER_LOGIN

    killall -u SSH$i
    killall -u VNC$i
    sleep 0.5
    killall -s 9 -u SSH$i
    killall -s 9 -u VNC$i

    log_DEBUG "removing user's files from /tmp"
    find /tmp/ -user $USER_LOGIN -exec rm -rf {} \; 2>/dev/null

    display_clean $VNC_USER

    SESSID=$SESSID SESSID_DIR=$SESSID_DIR \
    HOME_DIR_TYPE=$HOME_DIR_TYPE \
    USER_LOGIN=$USER_LOGIN USER_UID=$USER_UID \
    NICK=$NICK del_user.sh

    if [ -z "$NICK" ]; then
        log_WARN "Session ${SESSID} has no NICK defined; purging HOME and mount points"
        return 0
    fi

    [ -d /home/$NICK ] && do_clean_home $NICK
}

session_switch_status() {
    local SESSID=$1
    local RUNASAP=$2
    local SESSID_DIR=$SPOOL/sessions/$SESSID

    log_INFO "session_switch_status: ${SESSID} => $RUNASAP"
    echo $RUNASAP> ${SESSID_DIR}/infos/status
    if [ $RUNASAP -eq 2 ]; then
        echo $(date +%s) > ${SESSID_DIR}/private/ready_since
    fi

    webservices_session_request $SESSID $RUNASAP
}


session_install_client() {
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID

    # create new session dir
    install -o www-data $SESSID_DIR/private/hexavncpasswd $SESSID_DIR/clients/
    install -o www-data $SESSID_DIR/private/hexasshpasswd $SESSID_DIR/clients/
    install -o www-data $SESSID_DIR/private/ssh_user      $SESSID_DIR/clients/
}

session_load() {
    SESSID=$1
    SESSID_DIR=$SPOOL/sessions/$SESSID

    RUNASAP=$(cat ${SESSID_DIR}/infos/status)

    # Private informations
    i=$(cat $SESSID_DIR/private/id) || return 1
    SSH_USER=$(cat $SESSID_DIR/private/ssh_user) || return 1
    VNC_USER=$(cat $SESSID_DIR/private/vnc_user) || return 1

    # Parameters informations
    NICK=$(cat ${SESSID_DIR}/parameters/user_displayname 2>/dev/null)  || return 1
    USER_LOGIN=$(cat ${SESSID_DIR}/parameters/user_login) || return 1
    if [ -f ${SESSID_DIR}/parameters/user_id ]; then
        USER_ID=$(cat ${SESSID_DIR}/parameters/user_id)
    else
        USER_ID=$(id -u $USER_LOGIN 2>/dev/null)
        if [ $? -ne 0 ]; then
            log_INFO "User doesn't exist yet: tweak USER_ID to 0"
            USER_ID=0
        fi
    fi

    # Autodetection informations
    VNC_UID=$(id -u $VNC_USER) || return 1
    SSH_UID=$(id -u $SSH_USER) || return 1
}

session_unload() {
    unset SESSID
    unset SESSID_DIR

    unset RUNASAP

    # Private informations
    unset i
    unset SSH_USER
    unset VNC_USER

    # Parameters informations
    unset NICK
    unset USER_ID
    unset USER_LOGIN

    # Autodetection informations
    unset VNC_UID
    unset SSH_UID
}

session_suspend() {
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID
    local i=$(cat $SESSID_DIR/private/id)

    application_suspend_all $SESSID

    session_switch_status $SESSID 9

    local SSH_USER=$(cat $SESSID_DIR/private/ssh_user)

    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if ! grep -q -e "$SSH_USER\:x" /etc/passwd; then
        log_ERROR "No ssh user in /etc/passwd"
        return 1
    fi

    # Kill all ssh process about this session
    killall -u $SSH_USER
    sleep 0.5
    killall -s 9 -u $SSH_USER

    passwd -d $SSH_USER
    rm -f $SESSID_DIR/clients/*
    rm -f ${SESSID_DIR}/infos/keepmealive

    log_INFO "session_suspend: $i"
    session_switch_status $SESSID 10
}

session_restore() {
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID
    local i=$(cat $SESSID_DIR/private/id)

    local SSH_USER=$(cat $SESSID_DIR/private/ssh_user)
    session_switch_status $SESSID 11

    ## SSH password
    #
    # Seems the applet doesn't like too long password ...
    SSH_PASS=$(echo $RANDOM$(date +%s) | md5sum | mawk '{ print substr($1, 0, 9) }')
    # we set new shadow pass for this session
    # just be paranoid by default
    echo "$SSH_USER:$SSH_PASS" | chpasswd

    #
    # we encode the encrypted pass in hexa because the sshvnc 
    # applet wants it
    HEXA_SSH_PASS=$(echo -n $SSH_PASS | str2hex)
    echo $HEXA_SSH_PASS > $SESSID_DIR/private/hexasshpasswd

    session_install_client $SESSID
    [ -f ${SESSID_DIR}/infos/owner_exit ] && \
        rm ${SESSID_DIR}/infos/owner_exit
    log_INFO "session_restore: $i"

    local type=$(cat ${SESSID_DIR}/parameters/session_mode)
    if [ "$type" = "desktop" ]; then
        application_switch_status $SESSID "desktop" 11
    fi

    session_switch_status $SESSID 2
}

session_change_login_if_needed() {
    local login=$USER_LOGIN
    local pos=0

    grep -q "^$login:" /etc/passwd || return 0

    while grep -q "^$login$pos:" /etc/passwd; do
        pos=$(( $pos + 1 ))
    done

    echo "$login$pos" >${SESSID_DIR}/parameters/user_login 
    return 1
}

session_change_displayname_if_needed() {
    local displayname=$NICK
    local pos=0

    [ ! -d /home/$NICK ] && return 0

    while [ -d /home/$NICK$pos ]; do
        pos=$(( $pos + 1 ))
    done

    echo $NICK$pos >${SESSID_DIR}/parameters/user_displayname
    return 1
}

session_create_env_file() {
    ENV_FILE=$OVD_SESSID_DIR/env.sh
    for key in LC_ALL LANG LANGUAGE TZ \
               XAUTHORITY \
               OVD_SESSID_DIR XDG_DATA_DIRS OVD_APPS_DIR \
               CIFS_HOME_DIR; do
        eval value=\$$key
        echo "export $key=$value" >> $ENV_FILE
    done
}
