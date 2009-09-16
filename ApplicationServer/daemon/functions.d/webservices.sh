# Copyright (C) 2006-2008 Ulteo SAS
# http://www.ulteo.com
# Author Gaël DUVAL <gduval@ulteo.com>
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

webservices_get='curl --insecure --retry 5 --connect-timeout 5'

webservices_session_request() {
    local args="session=$1&status=$2&fqdn=${SERVERNAME}"
    local request="${SESSION_MANAGER_URL}/webservices/session_status.php?${args}"

    log_INFO "webservices_session_request: doing $request"
    $webservices_get "$request" >/dev/null 2>&1
}

webservices_server_request() {
    local args="status=$1&fqdn=${SERVERNAME}"
    local request="${SESSION_MANAGER_URL}/webservices/server_status.php?${args}"

    log_INFO "webservices_server_request: doing $request"
    $webservices_get "$request" >/dev/null 2>&1
}

webservices_server_ready() {
    webservices_server_request "ready"
}

webservices_server_down() {
    webservices_server_request "down"
}

webservices_server_broken() {
    webservices_server_request "broken"
}


webservices_available_application() {
    local url="${SESSION_MANAGER_URL}/webservices/admin/server.php"
    local file=/var/lib/ulteo/available-apps.xml
    local version="$(head -n 1 /etc/issue |sed -e 's/ \\\n.*//g')"

    if [ ! -f $file ]; then
        log_ERROR "No such file '$file'"
        return 1
    fi

    if ! [ "$version" ]; then
        log_ERROR "Invalid '/etc/issue' file"
        return 1
    fi

    curl --form xml=@$file --form action=register \
        --form fqdn=${SERVERNAME} --form type=linux \
        --form version="$version" --insecure $url &/dev/null
}

webservices_system_monitoring() {
    local url="${SESSION_MANAGER_URL}/webservices/server_monitoring.php"
    cache_set_monitoring /tmp/monitoring.xml || return 1
    curl --form xml=@/tmp/monitoring.xml --form fqdn=${SERVERNAME} --insecure $url >/dev/null 2>&1
}

webservices_get_application() {
    local id=$1
    local output=$2

    local args="fqdn=${SERVERNAME}&id=$id"
    local request="${SESSION_MANAGER_URL}/webservices/application.php?${args}"

    log_INFO "webservices_server_request: doing $request"
    $webservices_get "$request" >$output 2>/dev/null
}

webservices_get_application_icon() {
    local id=$1
    local output=$2

    local args="fqdn=${SERVERNAME}&id=$id"
    local request="${SESSION_MANAGER_URL}/webservices/icon.php?${args}"

    log_INFO "webservices_server_request: doing $request"
    $webservices_get "$request" >$output 2>/dev/null
}
