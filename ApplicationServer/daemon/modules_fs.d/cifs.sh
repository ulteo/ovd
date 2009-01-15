# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Jocelyn DELALANDE <jocelyn.delalande@no-log.org>
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


#
## CIFS MOUNT
#
# It's the clean CIFS module, to use with Services For Unix.
#

. $MODULES_FSD/local.sh

cifs_set_fs() {
    log_DEBUG "cifs_set_fs"
    
    CIFS_HOME_DIR=`cat ${SESSID_DIR}/parameters/module_fs/user_homedir`
    CIFS_LOGIN=`cat ${SESSID_DIR}/parameters/module_fs/login`
    CIFS_PASSWORD=`cat ${SESSID_DIR}/parameters/module_fs/password`

    check_variables CIFS_LOGIN CIFS_PASSWORD CIFS_HOME_DIR || return 1

    CIFS_MOUNT_POINT=/mnt/cifs/$USER_LOGIN
}

cifs_get_status() {
    # grep returns 1 if no match
    is_mount_point  $CIFS_MOUNT_POINT
}

cifs_do_mount() {
    # HACK: waiting for a working krb. sends plain-text credentials.
    # MOUNT_CMD="username=$KRB_PRINCIPAL,sec=krb5i,guest,sfu"
    local default_opts="username=$CIFS_LOGIN,password=$CIFS_PASSWORD,uid=$USER_ID,umask=077"
    local mount_cmd="mount -t cifs -o $default_opts $CIFS_HOME_DIR $CIFS_MOUNT_POINT"

    mkdir -p $CIFS_MOUNT_POINT

    log_INFO "cifs: mounting $CIFS_MOUNT_POINT, share: $CIFS_HOME_DIR"
    retry "$mount_cmd" $MOUNT_RETRIES 2 2>> $MOUNT_LOG
    local ret=$?
    [ $ret = 0 ] || return $ret

    mount --bind $CIFS_MOUNT_POINT $USER_HOME 2>> $MOUNT_LOG
}

cifs_do_umount() {
    cifs_do_umount_bind && cifs_do_umount_real
}


cifs_do_umount_real() {
    if  is_mount_point $CIFS_MOUNT_POINT; then
	log_INFO "cifs: umounting cifs mount $CIFS_MOUNT_POINT"
	retry "umount $CIFS_MOUNT_POINT" $MOUNT_RETRIES 2 2>> $MOUNT_LOG
    else
	log_WARN "cifs: ${CIFS_MOUNT_POINT} is already unnmounted"
    fi
    rmdir $CIFS_MOUNT_POINT || return 1
}


cifs_do_umount_bind() {
    if  is_mount_point $USER_HOME; then
	log_INFO "cifs: umounting bind $USER_HOME"
	retry "umount $USER_HOME" $MOUNT_RETRIES 1 2>> $MOUNT_LOG
    else
	log_WARN "cifs ${USER_HOME} bind is already unnmounted"
    fi
    rmdir $USER_HOME || return 1
}


cifs_do_clean() {
    # clean the mountbinds
    local_do_clean
    [ -d /mnt/cifs ] || return 0

    # Clean all the CIFS mounts, for all servers.
    local dirt_mounts=`find /mnt/cifs -maxdepth 1 -mindepth 1`
    for mount_point in $dirt_mounts; do
	log_WARN "cifs: Cleaning dirt mount $mount_point"
	CIFS_MOUNT_POINT=$mount_point cifs_do_umount_real
    done

    rmdir /mnt/cifs
    if [ $? != 0 ]; then
	log_WARN "cifs: Cleaning '/mnt/cifs' not empty, erasing"
	rm -rf /mnt/cifs
    fi
}
