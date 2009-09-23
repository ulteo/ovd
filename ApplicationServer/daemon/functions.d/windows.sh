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

windows_use_seamlessrdp() {
    local sessid_dir=$1

    for wf in windows_server windows_login windows_password windows_keymap; do
        [ ! -f $sessid_dir/parameters/$wf ] && return 1
    done

    return 0
}

windows_init_connection() {
    local sessid_dir=$1
    local display=$2

    windows_use_seamlessrdp $sessid_dir || return 0
    log_INFO "There are Windows applications parameters for this session"

    local server=$(cat ${sessid_dir}/parameters/windows_server)
    local login=$(cat ${sessid_dir}/parameters/windows_login)
    local password=$(cat ${sessid_dir}/parameters/windows_password)
    local keymap=$(cat ${sessid_dir}/parameters/windows_keymap)

    local have_printers=$(LANG= lpstat -p |wc -l)
    local printer_args=""
    if [ $have_printers -gt 0 ]; then
        local printers=$(LANG= lpstat -p |cut -d' ' -f 2)
        local default_printer=$(LANG= lpstat -d |cut -d':' -f 2 |cut -d' ' -f 2)
        [ d"$default_printer" != d"" ] && local printer_args="$printer_args -r printer:$default_printer"
        for printer in $printers; do
            [ "$printer" != "$default_printer" ] || continue
            local printer_args="$printer_args -r printer:$printer"
        done
    fi

    local cmd='rdesktop -k "'$keymap'" -u "'$login'" -p "'$password'" -A -s "seamlessrdpshell.exe" '$printer_args' '$server
    su -s "/bin/bash" - ${USER_LOGIN} -c ". $ENV_FILE; DISPLAY=:$display $cmd &" 
}

windows_logoff() {
    local sessid_dir=$1
    local user_login=$2

    windows_use_seamlessrdp $sessid_dir || return 0

    su -s "/bin/bash" $user_login -c "rdesktop -l logoff logoff"
}
