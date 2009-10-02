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
    local bg=1
    [ -n "$3" ] && local bg=0

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
    [ $bg -eq 1 ] && local cmd="$cmd &"
    touch $sessid_dir/private/windows_connected
    su -s "/bin/bash" - ${USER_LOGIN} -c ". $ENV_FILE; DISPLAY=:$display $cmd" 
    rm $sessid_dir/private/windows_connected
}

windows_logoff() {
    local sessid_dir=$1
    local user_login=$2

    windows_connected $sessid_dir || return 0

    su -s "/bin/bash" $user_login -c "rdesktop -l logoff"
    sleep 0.5
}

windows_connected() {
    local sessid_dir=$1

    [ -f $sessid_dir/private/windows_connected ] || return 1
}

windows_is_application() {
    local id=$1

    vapp_exist $id || return 1

    grep -q "^Exec=rdesktop" $vapp_repo/$id.desktop
}

windows_purge_app() {
    local display=":$1"
    local cmd="rdesktop --destroy-by-display $display"

    log_INFO "windows_purge_app '${USER_LOGIN}' $display => $cmd"
    su -s "/bin/bash" - ${USER_LOGIN} -c "$cmd"
    log_INFO "windows_purge end $?"
}

windows_set_focus() {
    local display=":$1"
    local mode="$2"

    if [ $mode == "on" ]; then
        local c="uniconify"
    else
        local c="iconify"
    fi

    local cmd="rdesktop --$c-by-display $display"
    log_INFO "windows_set_focus '${USER_LOGIN}' $display => $cmd"
    su -s "/bin/bash" - ${USER_LOGIN} -c "$cmd"
    log_INFO "windows_set_focus end $?"
}    
