# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2010
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


shares_cifs_prepare() {
    local id=$1
    local file=$2

    local nb=$(wc -l <$file)
    if [ $nb -lt 4 ]; then
        log_ERROR "Missing arguments for share $id"
        return 1
    fi
    
    share_cifs_url=$(head -n 3 $file| tail -n 1)
    local auth=$(head -n 4 $file| tail -n 1)
    
    case "$auth" in 
        "none")
            share_cifs_auth_args="guest"
            ;;
        "password")
            if [ $nb -lt 6 ]; then
                log_WARN "Missing arguments for share $id (auth)"
                return 1
            fi

            local auth_login=$(head -n 5 $file| tail -n 1)
            local auth_password=$(head -n 6 $file| tail -n 1)
            if [ -z "$auth_login" ] || [ -z "$auth_password" ]; then
                log_ERROR "Missing arguments for share $id (auth params empty)"
                return 1
            fi

            share_cifs_auth_args="username=$auth_login,password=$auth_password"
            ;;
        *)
            log_WARN "Unsupported auth method '$auth'"
            return 1
    esac
}

shares_cifs_mount() {
    local dist="$1"

    mount -t cifs -o uid=$USER_ID,umask=077,$share_cifs_auth_args "$share_cifs_url" "$dist"
}
