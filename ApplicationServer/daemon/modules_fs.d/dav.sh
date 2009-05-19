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


#
# Mount dav
#

. $MODULES_FSD/local.sh

dav_set_fs() {
    log_DEBUG "dav_set_fs"
    local_set_fs

    [ -f ${SESSID_DIR}/parameters/module_fs/dav_dirs ] || return 1

    [ -f ${SESSID_DIR}/parameters/module_fs/login ]    || return 1
    [ -f ${SESSID_DIR}/parameters/module_fs/password ] || return 1
    DAV_LOGIN=$(cat ${SESSID_DIR}/parameters/module_fs/login)
    DAV_PASSWORD=$(cat ${SESSID_DIR}/parameters/module_fs/password)
    check_variables DAV_LOGIN DAV_PASSWORD || return 1

    DAV_MOUNT_POINT=/mnt/dav/$USER_LOGIN
    CREDENTIALS=/etc/davfs2/secrets
}

dav_get_status() {
    local_get_status || return 1    

    local dirs=$(find $DAV_MOUNT_POINT -type d -maxdepth 1 -mindepth 1)
    for dir in $dirs; do
    is_mount_point  $dir || return 1
    done
}

dav_do_mount() {
    local_do_mount || return 1

    local i=0
    while read line; do
        local name=$(echo $line| cut -d '|' -f1)
        local url=$(echo $line| cut -d '|' -f2)

        echo "$url $DAV_LOGIN $DAV_PASSWORD" >>$CREDENTIALS

        mkdir -p $DAV_MOUNT_POINT/$i
        mount -t davfs -o uid=$USER_ID,dir_mode=700,file_mode=600 $url $DAV_MOUNT_POINT/$i

        mkdir -p "$USER_HOME/$name"
        mount --bind $DAV_MOUNT_POINT/$i  "$USER_HOME/$name"
        if [ $? != 0 ]; then
            log_ERROR "dav: mount bind failed on $USER_HOME/$name"
        fi

        i=$(( $i + 1 ))
     done < ${SESSID_DIR}/parameters/module_fs/dav_dirs
}

dav_do_umount() {
    dav_do_umount_bind && dav_do_umount_real

    rmdir $DAV_MOUNT_POINT

    local_do_umount
}


dav_do_umount_real() {
    local i=0
    while read line; do
        local name=$(echo $line| cut -d '|' -f1)
        local url=$(echo $line| cut -d '|' -f2)

        local dir=$DAV_MOUNT_POINT/$i
        umount "$dir" || log_ERROR "dav: Failed to umount $dir"
        sed -i "\%^$url %d" $CREDENTIALS
        rmdir $DAV_MOUNT_POINT/$i

        i=$(( $i + 1 ))
    done < ${SESSID_DIR}/parameters/module_fs/dav_dirs
}


dav_do_umount_bind() {
    while read line; do
        local name=$(echo $line| cut -d '|' -f1)
        local url=$(echo $line| cut -d '|' -f2)

        local dir="$USER_HOME/$name"
        log_INFO "dav: umounting bind $dir"
        umount "$dir" || log_ERROR "dav: Failed to umount $dir"

        rmdir "$dir"
    done < ${SESSID_DIR}/parameters/module_fs/dav_dirs
}


dav_do_clean() {
    # clean the mountbinds
    local_do_clean
    [ -d /mnt/dav ] || return 0

    local dirt_mounts=`find /mnt/dav -maxdepth 1 -mindepth 1`

    if [ -d /mnt/dav ]; then
        rmdir /mnt/dav 2>/dev/null
        if [ $? != 0 ]; then
            log_WARN "dav: Cleaning '/mnt/dav' not empty, erasing"
            rm -rf /mnt/dav
        fi
    fi
}
