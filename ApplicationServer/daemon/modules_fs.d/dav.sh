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

    if [ -f ${SESSID_DIR}/parameters/module_fs/cookie_url ]; then
        DAV_AUTH=url
        DAV_INIT_URL=$(cat ${SESSID_DIR}/parameters/module_fs/cookie_url)

        check_variables DAV_INIT_URL || return 1

    elif [ -f ${SESSID_DIR}/parameters/module_fs/login ] && \
        [ -f ${SESSID_DIR}/parameters/module_fs/password ]; then
        DAV_AUTH=password
        DAV_LOGIN=$(cat ${SESSID_DIR}/parameters/module_fs/login)
        DAV_PASSWORD=$(cat ${SESSID_DIR}/parameters/module_fs/password)
        check_variables DAV_LOGIN DAV_PASSWORD || return 1
    else
        log_ERROR "dav: missing authentication method"
        return 1
    fi

    DAV_CONFIG=${SESSID_DIR}/private/davfs2.conf
    DAV_SECRET=${SESSID_DIR}/private/davfs2.secret
    DAV_MOUNT_POINT=/mnt/dav/$USER_LOGIN
}

dav_get_status() {
    local_get_status && return 0

    [ -d $DAV_MOUNT_POINT ] && return 0

    return 1
}

dav_do_init_config() {
    echo "ask_auth 0"      >$DAV_CONFIG
    echo "use_locks 0"    >>$DAV_CONFIG

    if [ $DAV_AUTH = 'url' ]; then
        echo "allow_cookie 1" >>$DAV_CONFIG
        echo "pre_request $DAV_INIT_URL" >>$DAV_CONFIG
    else
        echo "secrets $DAV_SECRET">>$DAV_CONFIG

        while read line; do
            local url=$(echo $line| cut -d '|' -f2)
            echo "$url $DAV_LOGIN $DAV_PASSWORD" >>$DAV_SECRET
        done < ${SESSID_DIR}/parameters/module_fs/dav_dirs
        chmod 600 $DAV_SECRET
    fi
}

dav_do_purge_config() {
    rm -f $DAV_CONFIG
    [ -f $DAV_SECRET ] && rm $DAV_SECRET
}

dav_do_mount() {
    local_do_mount || return 1
    dav_do_init_config || return 1

    local i=0
    while read line; do
        local name=$(echo $line| cut -d '|' -f1)
        local url=$(echo $line| cut -d '|' -f2)

        mkdir -p $DAV_MOUNT_POINT/$i
        mount -t davfs -o conf=$DAV_CONFIG,uid=$USER_ID,dir_mode=700,file_mode=600 "$url" $DAV_MOUNT_POINT/$i
        if [ $? != 0 ]; then
            log_ERROR "dav: mount failed from '$url'"
            return 1
        fi

        mkdir -p "$USER_HOME/$name"
        mount --bind $DAV_MOUNT_POINT/$i  "$USER_HOME/$name"
        if [ $? != 0 ]; then
            log_ERROR "dav: mount bind failed on $USER_HOME/$name"
        fi

        i=$(( $i + 1 ))
     done < ${SESSID_DIR}/parameters/module_fs/dav_dirs
}

dav_do_umount() {
    dav_do_purge_config
    dav_do_umount_bind
    dav_do_umount_real
    rmdir $DAV_MOUNT_POINT

    local_do_umount
}


dav_do_umount_real() {
    local nb=$(wc -l ${SESSID_DIR}/parameters/module_fs/dav_dirs |cut  -d ' ' -f1)

    for i in $(seq 0 $nb); do
        local dir=$DAV_MOUNT_POINT/$i
        is_mount_point "$dir" || continue

        umount "$dir" || log_ERROR "dav: Failed to umount $dir"
    done

    for i in $(seq 0 $nb); do
        [ -d $DAV_MOUNT_POINT/$i ] || continue
        rmdir $DAV_MOUNT_POINT/$i
    done
}


dav_do_umount_bind() {
    while read line; do
        local name=$(echo $line| cut -d '|' -f1)
        local url=$(echo $line| cut -d '|' -f2)

        local dir="$USER_HOME/$name"
        is_mount_point "$dir" || continue

        log_INFO "dav: umounting bind $dir"
        umount "$dir" || log_ERROR "dav: Failed to umount $dir"

        rmdir "$dir"
    done < ${SESSID_DIR}/parameters/module_fs/dav_dirs
}

dav_do_umount_critical() {
    local dirs=$(find $DAV_MOUNT_POINT -maxdepth 1 -mindepth 1 -type d)
    for dir in $dirs; do
        is_mount_point "$dir" || continue
        umount "$dir"
        rmdir "$dir"
    done
}

dav_do_clean() {
    # clean the mountbinds
    local_do_clean
    [ -d /mnt/dav ] || return 0

    local dirt_mounts=$(find /mnt/dav -maxdepth 1 -mindepth 1)
    for mount_point in $dirt_mounts; do
    log_WARN "dav: Cleaning dirt mount $mount_point"

    DAV_MOUNT_POINT=$mount_point dav_do_umount_critical
    done

    # FIXME: what's the point of testing first if we rm -rf anyway?
    if [ -d /mnt/dav ]; then
        rmdir /mnt/dav 2>/dev/null
        if [ $? != 0 ]; then
            log_WARN "dav: Cleaning '/mnt/dav' not empty, erasing"
            rm -rf /mnt/dav
        fi
    fi
}
