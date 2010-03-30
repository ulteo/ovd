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


shares_webdav_prepare() {
    local id=$1
    local file=$2

    local nb=$(wc -l <$file)
    if [ $nb -lt 4 ]; then
        log_ERROR "Missing arguments for share $id"
        return 1
    fi
 
    share_webdav_url=$(head -n 3 $file| tail -n 1)
    local auth=$(head -n 4 $file| tail -n 1)


    local id=$1
    local file=$2

    local nb=$(wc -l <$file)
    if [ $nb -lt 4 ]; then
        log_ERROR "Missing arguments for share $id"
        return 1
    fi
    
    share_webdav_url=$(head -n 3 $file| tail -n 1)
    local auth=$(head -n 4 $file| tail -n 1)
    
    shares_webdav_config=/mnt/shares/$USER_LOGIN/$id.conf
    echo "ask_auth 0"      >$shares_webdav_config
    echo "use_locks 0"    >>$shares_webdav_config

    case "$auth" in 
        "none")
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

            local shares_webdav_secret=/mnt/shares/$USER_LOGIN/$id.secret
            echo "$share_webdav_url $auth_login $auth_password" >>$shares_webdav_secret
            chmod 600 $shares_webdav_secret
       
            echo "secrets $shares_webdav_secret">>$shares_webdav_config
            ;;
        "cookie")
            if [ $nb -lt 5 ]; then
                log_WARN "Missing arguments for share $id (auth)"
                return 1
            fi

            local auth_cookie=$(head -n 5 $file| tail -n 1)
            if [ -z "$auth_cookie" ]; then
                log_ERROR "Missing arguments for share $id (auth params empty)"
                return 1
            fi

            echo "allow_cookie 1" >>$shares_webdav_config
            echo "pre_request $auth_cookie" >>$shares_webdav_config
            ;;
        *)
            log_WARN "Unsupported auth method '$auth'"
            return 1
    esac
}


shares_webdav_mount() {
    local dist="$1"

    mount -t davfs -o conf=$shares_webdav_config,uid=$USER_ID,dir_mode=700,file_mode=600 "$share_webdav_url" "$dist"
}
