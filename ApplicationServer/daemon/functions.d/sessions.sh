# Copyright (C) 2008 Ulteo SAS
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

sessions_get_active() {
    find $SPOOL/sessions -maxdepth 1 -mindepth 1 -type d -exec basename {} \;
}

sessions_get_to_create() {
    find $SPOOL/sessions2create -maxdepth 1 -mindepth 1 -type f -exec basename {} \;
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
    let i=0
    while [ -f $SPOOL/id/$i ]; do
	[ $i -ge 1000 ] && return 1
	let i=$(( $i + 1 ))
    done
    touch $SPOOL/id/$i

    log_INFO "session_init: '$SESSID' => $i"
    local RFB_PORT=$((5900+$i))
    local SSH_USER="SSH$i"
    local VNC_USER="VNC$i"

    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if [ `grep -e "$SSH_USER\:x" /etc/passwd` ]; then
	log_ERROR "session_init: user '$SSH_USER' already in /etc/passwd"
	rm $SPOOL/id/$i
	return 1
    fi
    log_INFO "useradd $SSH_USER with uid : $uid"
    useradd -K UID_MIN=2000 --shell /bin/false $SSH_USER 


    log_DEBUG "seeking VNC group $VNC_USER in /etc/group"
    if [ `grep -e "$VNC_USER\:x" /etc/group` ]; then
	log_ERROR "session_init: user '$VNC_USER' already in /etc/group"
	rm $SPOOL/id/$i
	return 1
    fi
    log_INFO "groupadd -K GID_MAX=70000 $VNC_USER"
    groupadd -K GID_MAX=70000 $VNC_USER


    log_DEBUG "seeking VNC user $VNC_USER in /etc/passwd"
    if [ `grep -e "$VNC_USER\:x" /etc/passwd` ]; then
	log_ERROR "session_init: user '$VNC_USER' already in /etc/passwd"
	rm $SPOOL/id/$i
	return 1
    fi
    log_INFO "useradd $VNC_USER with uid : $uid"
    useradd -K UID_MIN=2000 --shell /bin/false -g $VNC_USER $VNC_USER


    UUID=`id -u $VNC_USER`
    UGID=`id -g $VNC_USER`

    if rsbac_is_active && [ ! -d /tmpdir/tmp$UUID ]; then
	mkdir /tmpdir/tmp$UUID
	chown $UUID:$UGID /tmpdir/tmp$UUID
    fi

    # create new session dir
    install -d -g www-data -m 750 $SESSID_DIR
    install -d -g www-data -m 770 $SESSID_DIR/parameters
    install -d -g www-data -m 770 $SESSID_DIR/infos
    install -d             -m 700 $SESSID_DIR/private
    install -d -g www-data -m 750 $SESSID_DIR/clients

    install -d -m 700 $SPOOL_USERS/$SESSID

    # Initialize status file
    echo "0" >     $SESSID_DIR/infos/status
    chgrp www-data $SESSID_DIR/infos/status
    chmod 660      $SESSID_DIR/infos/status

    ## VNC password
    #
    VNC_PASS=`echo $RANDOM\`date +%s\` | md5sum | awk '{ print substr($1, 0, 9) }'`
    # on hardy we have tightvncpasswd, vncpasswd on dapper
    if $(which tightvncpasswd >/dev/null 2>&1); then
      TIGHTVNCPASSWD="tightvncpasswd"
    else
      TIGHTVNCPASSWD="vncpasswd"
    fi
    # have to cut the pass to 8 characters
    # for realvncpasswd
    echo $VNC_PASS | $TIGHTVNCPASSWD -f > $SESSID_DIR/private/encvncpasswd
    HEXA_VNC_PASS=`cat $SESSID_DIR/private/encvncpasswd | str2hex`

    ## SSH password
    #
    # Seems the applet doesn't like too long password ...
    SSH_PASS=`echo $RANDOM\`date +%s\` | md5sum | awk '{ print substr($1, 0, 9) }'`
    # we set new shadow pass for this session
    # just be paranoid by default
    echo "$SSH_USER:$SSH_PASS" | chpasswd

    #
    # we encode the encrypted pass in hexa because the sshvnc 
    # applet wants it
    HEXA_SSH_PASS=`echo $SSH_PASS | str2hex`
   
    ##echo $SSH_PASS >$SESSID_DIR/sshpasswd # <- just for test !!! remove it in production !!!
    echo $i > $SESSID_DIR/private/id
    echo $HEXA_VNC_PASS > $SESSID_DIR/private/hexavncpasswd
    echo $HEXA_SSH_PASS > $SESSID_DIR/private/hexasshpasswd
    echo $RFB_PORT > $SESSID_DIR/private/rfbport
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
    local i=`cat $SESSID_DIR/private/id`

    #session_switch_status $SESSID 3

    local rfb_port=`cat $SESSID_DIR/private/rfbport`
    local SSH_USER=`cat $SESSID_DIR/private/ssh_user`
    local VNC_USER=`cat $SESSID_DIR/private/vnc_user`
    
    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if [ `grep -e "$SSH_USER\:x" /etc/passwd` ]; then
	log_INFO "userdel $SSH_USER"
	userdel $SSH_USER
    fi

    log_DEBUG "seeking VNC user $VNC_USER in /etc/passwd"
    if [ `grep -e "$VNC_USER\:x" /etc/passwd` ]; then
        local VNC_UID=`id -u $VNC_USER`
	if rsbac_is_active && [ -d /tmpdir/tmp$VNC_UID ]; then
            # Delete the tmp directory
	    rm -rf /tmpdir/tmp$VNC_UID
	fi
	log_INFO "userdel $VNC_USER"
	userdel $VNC_USER
    fi

    log_DEBUG "seeking VNC group $VNC_USER in /etc/group"
    if [ `grep -e "$VNC_USER\:x" /etc/group` ]; then
	log_INFO "groupedel $VNC_USER"
	groupdel $VNC_USER
    fi

    log_INFO "session_remove: removing '$SESSID_DIR' ($i)"

    rm -rf $SPOOL_USERS/$SESSID
    rm -rf $SESSID_DIR
    rm $SPOOL/id/$i

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
    local i=`cat $SESSID_DIR/private/id`

    log_INFO "Purging ${SESSID}"
    local NICK=`cat ${SESSID_DIR}/parameters/user_displayname`
    local USER_LOGIN=`cat ${SESSID_DIR}/parameters/user_login`
    local USER_UID=$(id -u $USER_LOGIN)
    local HOME_DIR_TYPE=`cat ${SESSID_DIR}/parameters/module_fs/type`

    windows_logoff $SESSID_DIR $USER_LOGIN

    killall -u $USER_LOGIN
    sleep 0.5
    killall -9 -u $USER_LOGIN

    killall -u SSH$i
    killall -u VNC$i
    sleep 0.5
    killall -9 -u SSH$i
    killall -9 -u VNC$i

    if rsbac_is_active && [ -d /tmpdir/tmp$USER_UID ]; then
        # clean tmp dirs
	rm -rf /tmpdir/tmp$USER_UID
    fi

    SESSID=$SESSID SESSID_DIR=$SESSID_DIR \
	HOME_DIR_TYPE=$HOME_DIR_TYPE \
	USER_LOGIN=$USER_LOGIN USER_UID=$USER_UID \
	NICK=$NICK uumount.sh
    
    if [ -d /home/$NICK ]; then
	do_clean_home $NICK
    fi
}

session_switch_status() {
    local SESSID=$1
    local RUNASAP=$2
    local SESSID_DIR=$SPOOL/sessions/$SESSID

    log_INFO "session_switch_status: ${SESSID} => $RUNASAP"
    echo $RUNASAP> ${SESSID_DIR}/infos/status
    webservices_session_request $SESSID $RUNASAP
}


session_install_client() {
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID

    # create new session dir
    install -o www-data $SESSID_DIR/private/hexavncpasswd $SESSID_DIR/clients/
    install -o www-data $SESSID_DIR/private/hexasshpasswd $SESSID_DIR/clients/
    install -o www-data $SESSID_DIR/private/ssh_user      $SESSID_DIR/clients/
    install -o www-data $SESSID_DIR/private/rfbport       $SESSID_DIR/clients/
}

session_load() {
    SESSID=$1
    SESSID_DIR=$SPOOL/sessions/$SESSID

    RUNASAP=`cat ${SESSID_DIR}/infos/status`

    # Private informations
    i=`cat $SESSID_DIR/private/id` || return 1
    RFB_PORT=`cat $SESSID_DIR/private/rfbport` || return 1
    SSH_USER=`cat $SESSID_DIR/private/ssh_user` || return 1
    VNC_USER=`cat $SESSID_DIR/private/vnc_user` || return 1

    # Parameters informations
    NICK=`cat ${SESSID_DIR}/parameters/user_displayname`  || return 1
    USER_LOGIN=`cat ${SESSID_DIR}/parameters/user_login` || return 1
    if [ -f ${SESSID_DIR}/parameters/user_id ]; then
	USER_ID=`cat ${SESSID_DIR}/parameters/user_id`
    else
	USER_ID=`id -u $USER_LOGIN`
    fi

    # Autodetection informations
    VNC_UID=`id -u $VNC_USER` || return 1
    SSH_UID=`id -u $SSH_USER` || return 1
}

session_unload() {
    unset SESSID
    unset SESSID_DIR

    unset RUNASAP

    # Private informations
    unset i
    unset RFB_PORT
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
    local i=`cat $SESSID_DIR/private/id`

    session_switch_status $SESSID 9

    local SSH_USER=`cat $SESSID_DIR/private/ssh_user`
    
    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if [ ! `grep -e "$SSH_USER\:x" /etc/passwd` ]; then
	log_ERROR "No ssh user in /etc/passwd"
	return 1
    fi

    # Kill all ssh process about this session
    killall -u $SSH_USER
    sleep 0.5
    killall -9 -u $SSH_USER

    userdel $SSH_USER
    rm $SESSID_DIR/clients/*
    rm ${SESSID_DIR}/private/luck
    rm ${SESSID_DIR}/infos/keepmealive

    log_INFO "session_suspend: $i"
    session_switch_status $SESSID 10
}

session_restore() {
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID
    local i=`cat $SESSID_DIR/private/id`

    local SSH_USER=`cat $SESSID_DIR/private/ssh_user`
    session_switch_status $SESSID 11

    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if [ `grep -e "$SSH_USER\:x" /etc/passwd` ]; then
	log_ERROR "session_restore: user '$SSH_USER' already in /etc/passwd"
	return 1
    fi

    useradd -K UID_MIN=2000 --shell /bin/false $SSH_USER 
    if [ $? -ne 0 ]; then
	log_ERROR "session_restore: unable to useradd ssh user"
	return 1
    fi

    ## SSH password
    #
    # Seems the applet doesn't like too long password ...
    SSH_PASS=`echo $RANDOM\`date +%s\` | md5sum | awk '{ print substr($1, 0, 9) }'`
    # we set new shadow pass for this session
    # just be paranoid by default
    echo "$SSH_USER:$SSH_PASS" | chpasswd

    #
    # we encode the encrypted pass in hexa because the sshvnc 
    # applet wants it
    HEXA_SSH_PASS=`echo $SSH_PASS | str2hex`
    echo $HEXA_SSH_PASS > $SESSID_DIR/private/hexasshpasswd

    session_install_client $SESSID
    log_INFO "session_restore: $i"
    session_switch_status $SESSID 2
}
