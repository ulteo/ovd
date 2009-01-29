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

    if [ ! -f $sessid_dir/parameters/windows_server ] || \
	[ ! -f $sessid_dir/parameters/windows_login ] || \
	[ ! -f $sessid_dir/parameters/windows_password ]; then
	return 1
    fi

    grep -q ".lnk$" $sessid_dir/parameters/menu
    [ $? -ne 0 ] && return 1

    return 0
}

windows_init_connection() {
    local sessid_dir=$1

    windows_use_seamlessrdp $sessid_dir
    if [ $? -ne 0 ]; then
	log_INFO "No Windows parameters for this session"
	return 0
    fi
    # log_INFO "menu_windows_init_connection 0"
    local server=`cat ${sessid_dir}/parameters/windows_server`
    local login=`cat ${sessid_dir}/parameters/windows_login`
    local password=`cat ${sessid_dir}/parameters/windows_password`
    # log_INFO "menu_windows_init_connection 1"
    local cmd='rdesktop -u "'$login'" -p "'$password'" -A -s "c:\seamlessrdp\seamlessrdpshell.exe c:\seamlessrdp\donothing.exe" '$server
    # log_INFO "menu_windows_init_connection 2 launch cmd '$cmd'"
    su -s "/bin/bash" ${USER_LOGIN} -c "$cmd &" 
    # log_INFO "==============================="
    #&> ${SESSID_DIR}/log_rdp.log
}

windows_catch_application() {
    local desktop=$1
    local buffer="/tmp/test.xml"

    webservices_get_application "$desktop" $buffer
    if [ $? -ne 0 ]; then
	log_WARN "Cannot get application from $basename"
	[ -f $buffer ] && rm $buffer
	return 1
    fi

    xml2desktopfile $buffer $windows_app_cache
    if [ $? -ne 0 ] || [ ! -f "$uri" ]; then
	log_WARN "Catch of $basename failed"
	[ -f $buffer ] && rm $buffer
	return 1
    fi

    echo "avant buffer '$buffer'"
    [ -f $buffer ] && rm $buffer
    echo "apres buffer '$buffer'"
    return 0
}
