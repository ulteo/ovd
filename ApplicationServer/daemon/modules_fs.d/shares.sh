# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2009, 2010
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

. $MODULES_FSD/local.sh
. $MODULES_FSD/shares.d/cifs.sh
. $MODULES_FSD/shares.d/webdav.sh

shares_set_fs() {
    log_DEBUG "shares_set_fs"
    local_set_fs

    SHARES_MOUNT_POINT=/mnt/shares/$USER_LOGIN
}

shares_get_status() {
    local_get_status && return 0

    [ -d $SHARES_MOUNT_POINT ] && return 0

    return 1
}


shares_do_mount() {
    local_do_mount || return 1

    mkdir -p $SHARES_MOUNT_POINT

    for share in $(find ${SESSID_DIR}/parameters/module_fs/ -name 'shares_*'); do
        local id=$(basename $share |mawk '{ print substr($1, 8) }')

        local name=$(head -n 1 $share| tail -n 1)
        local type=$(head -n 2 $share| tail -n 1)

        if [ "$type" != "cifs" ] && [ "$type" != "webdav" ]; then
            log_WARN "Unkonw type '$type' for share $share"
            continue
        fi
        
        shares_${type}_prepare $id $share
        if [ $? != 0 ]; then
            continue
        fi
        
        mkdir -p $SHARES_MOUNT_POINT/$id
        shares_${type}_mount $SHARES_MOUNT_POINT/$id
        if [ $? != 0 ]; then
            log_WARN "Unable to mount!"
            continue
        fi

        mkdir -p "$USER_HOME/$name"
        mount --bind $SHARES_MOUNT_POINT/$id  "$USER_HOME/$name"
        if [ $? != 0 ]; then
            log_ERROR "shares: mount bind failed on $USER_HOME/$name"
        fi

     done
}

shares_do_umount() {
    for share in $(find ${SESSID_DIR}/parameters/module_fs/ -name 'shares_*'); do
        local id=$(basename $share |mawk '{ print substr($1, 8) }')
        local name=$(head -n 1 $share)
        
        local dir="$USER_HOME/$name"
        if is_mount_point "$dir"; then
            log_INFO "shares: umounting bind $dir"
            umount "$dir" || log_ERROR "shares: Failed to umount $dir"

            rmdir "$dir"
        fi

        local dir="$SHARES_MOUNT_POINT/$id"
        if is_mount_point "$dir"; then
            log_INFO "shares: umounting $dir"
            umount "$dir" || log_ERROR "shares: Failed to umount $dir"

            rmdir "$dir"
        fi
     done

    local_do_umount
}

shares_do_clean() {
    # clean the mountbinds
    local_do_clean
    [ -d /mnt/shares ] || return 0

    for d in $(find /mnt/shares -maxdepth 1 -mindepth 1 -type d); do
        for mount_point in $(find $d -maxdepth 1 -mindepth 1 -type d); do
            log_WARN "shares: Cleaning dirt mount $mount_point"

            is_mount_point "$dir" && umount $mount_point
            # do it twice because bind + real mount
            is_mount_point "$dir" && umount $mount_point
            
            if is_mount_point "$dir"; then
                log_ERROR "still mount $dir after 2 umount !!!"
            fi

            rmdir "$dir"
        done

        rmdir $d
    done

    # FIXME: what's the point of testing first if we rm -rf anyway?
    if [ -d /mnt/shares ]; then
        rmdir /mnt/shares 2>/dev/null
        if [ $? != 0 ]; then
            log_WARN "shares: Cleaning '/mnt/shares' not empty, erasing"
            rm -rf /mnt/shares
        fi
    fi
}
