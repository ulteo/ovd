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
    local uid=$(( 2000 + $i ))
    log_INFO "useradd $SSH_USER with uid : $uid"
    useradd --shell /bin/false -u $uid $SSH_USER 


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
    local uid=$(( 3000 + $i ))
    log_INFO "useradd $VNC_USER with uid : $uid"
    useradd --shell /bin/false -u $uid -g $VNC_USER $VNC_USER


    UUID=`id -u $VNC_USER`
    UGID=`id -g $VNC_USER`

    if rsbac_is_active && [ ! -d /tmpdir/tmp$UUID ]; then
	mkdir /tmpdir/tmp$UUID
	chown $UUID:$UGID /tmpdir/tmp$UUID
    fi

    # create new session dir
    mkdir $SESSID_DIR
    chgrp www-data $SESSID_DIR
    chmod 770 $SESSID_DIR
    # install -d -g www-data -m 770 $SESSID_DIR

    echo "0" > $SESSID_DIR/runasap
#    touch $SESSID_DIR/runasap
    chgrp www-data $SESSID_DIR/runasap
    chmod 660 $SESSID_DIR/runasap

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
    echo $VNC_PASS | $TIGHTVNCPASSWD -f > $SESSID_DIR/encvncpasswd
    HEXA_VNC_PASS=`cat $SESSID_DIR/encvncpasswd | str2hex`

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
    echo $i > $SESSID_DIR/id
    echo $HEXA_VNC_PASS > $SESSID_DIR/hexavncpasswd
    echo $HEXA_SSH_PASS > $SESSID_DIR/hexasshpasswd
    echo $RFB_PORT > $SESSID_DIR/rfbport
    echo $SSH_USER > $SESSID_DIR/sshuser

    session_switch_status $SESSID 0
}


## Session Remove
# - erase session directory
# - del SSH and VNC users and group
#
# $1 : session id
#
session_remove() {
    log_INFO "SESSION REMOVE "
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID
    local i=`cat $SESSID_DIR/id`

    session_switch_status $SESSID 3

    local rfb_port=$((5900+$i))
    local SSH_USER="SSH$i"
    local VNC_USER="VNC$i"
    
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

    rm -rf $SESSID_DIR
    rm $SPOOL/id/$i
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
    local i=`cat $SESSID_DIR/id`

    log_INFO "Purging ${SESSID}"
    local NICK=`cat ${SESSID_DIR}/nick`
    local USER_LOGIN=`cat ${SESSID_DIR}/user_login`
    local USER_UID=$(id -u $USER_LOGIN)
    local HOME_DIR_TYPE=`cat ${SESSID_DIR}/module_fs`

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
    echo $RUNASAP> ${SESSID_DIR}/runasap
    webservices_session_request $SESSID $RUNASAP
}

session_load() {
    SESSID=$1
    SESSID_DIR=$SPOOL/sessions/$SESSID

    i=`cat $SESSID_DIR/id` || return 1
    NICK=`cat ${SESSID_DIR}/nick`  || return 1
    USER_ID=`cat ${SESSID_DIR}/uu` || return 1
    USER_LOGIN=`cat ${SESSID_DIR}/user_login` || return 1
    
    VNC_USER="VNC$i"
    SSH_USER="SSH$i"

    VNC_UID=`id -u $VNC_USER` || return 1
    SSH_UID=`id -u $SSH_USER` || return 1
}

session_unload() {
    unset SESSID
    unset SESSID_DIR

    unset i
    unset NICK
    unset USER_ID
    unset USER_LOGIN
    
    unset VNC_USER
    unset SSH_USER

    unset VNC_UID
    unset SSH_UID
}

session_suspend() {
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID
    local i=`cat $SESSID_DIR/id`

    session_switch_status $SESSID 9

    local SSH_USER="SSH$i"
    
    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if [ ! `grep -e "$SSH_USER\:x" /etc/passwd` ]; then
	log_ERROR "No ssh user in /etc/passwd"
	return 1
    fi

    # Kill all ssh process about this session
    killall -u SSH$i
    sleep 0.5
    killall -9 -u SSH$i

    userdel $SSH_USER
    rm $SESSID_DIR/hexasshpasswd
    rm ${SESSID_DIR}/luck
    rm ${SESSID_DIR}/keepmealive

    log_INFO "session_suspend: $i"
    session_switch_status $SESSID 10
}

session_restore() {
    local SESSID=$1
    local SESSID_DIR=$SPOOL/sessions/$SESSID
    local i=`cat $SESSID_DIR/id`

    local SSH_USER="SSH$i"
    session_switch_status $SESSID 11

    log_DEBUG "seeking SSH user $SSH_USER in /etc/passwd"
    if [ `grep -e "$SSH_USER\:x" /etc/passwd` ]; then
	log_ERROR "session_restore: user '$SSH_USER' already in /etc/passwd"
	return 1
    fi
    local uid=$(( 2000 + $i ))

    useradd --shell /bin/false -u $uid $SSH_USER 
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
    echo $HEXA_SSH_PASS > $SESSID_DIR/hexasshpasswd


    log_INFO "session_restore: $i"
    session_switch_status $SESSID 2
}
