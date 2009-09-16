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
## Local Filesystem
#
# No retries, if it fails, it fails...

local_set_fs() {
    log_DEBUG "local:set_fs"

    LOCAL_HOME_BASE=/users
    . $CONF_FILE

    # Maybe there is no user homes.
    if [ ! -d $LOCAL_HOME_BASE ]; then
        mkdir -p $LOCAL_HOME_BASE
        chmod 0711 $LOCAL_HOME_BASE
    fi
}


local_get_status() {
    is_mount_point $USER_HOME
}


local_do_mount() {
    if [ ! -d $LOCAL_HOME_BASE/$USER_LOGIN ]; then
        mkdir $LOCAL_HOME_BASE/$USER_LOGIN
        chown $USER_LOGIN:$USER_LOGIN $LOCAL_HOME_BASE/$USER_LOGIN
    else
        chown -R $USER_LOGIN $LOCAL_HOME_BASE/$USER_LOGIN
    fi

    # Bind local user homedir to /home/nickname
    mount --bind $LOCAL_HOME_BASE/$USER_LOGIN $USER_HOME
}


local_do_umount() {
    if is_mount_point $USER_HOME ; then
        umount $USER_HOME
        if [ $? -ne 0 ]; then
            log_WARN "local: Attempting to force unmount of local $USER_HOME"
            umount  -f $USER_HOME || umount -l  $USER_HOME
        fi
    else
        log_INFO "local: $USER_HOME is present but not a mount point"
    fi

    rmdir $USER_HOME
}


# TODO: put this function outside local in module_fs because it's generic
local_do_clean() {
    local SOME_FAILED=0

    local dirt_homes=$(find /home -maxdepth 1 -mindepth 1)
    for home in $dirt_homes; do
        log_WARN "local: $home is dirty, cleaning..."
        USER_HOME=${home} local_do_umount # || local SOME_FAILED=1
        [ -d "$home" ] && rm -rf "$home"
    done

    return $SOME_FAILED
}
