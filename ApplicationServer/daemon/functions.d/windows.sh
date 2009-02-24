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

    windows_use_seamlessrdp $sessid_dir || return 0
    log_INFO "There are Windows applications parameters for this session"

    local server=`cat ${sessid_dir}/parameters/windows_server`
    local login=`cat ${sessid_dir}/parameters/windows_login`
    local password=`cat ${sessid_dir}/parameters/windows_password`

    local cmd='rdesktop -u "'$login'" -p "'$password'" -A -s "seamlessrdpshell.exe donothing.exe" '$server
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

    local app_id=`grep id $buffer | sed -e 's/.*id="//' -e 's/".*//'`
    local pixmap="/usr/share/pixmaps/windows-"$app_id'.png'

    webservices_get_application_icon $app_id $pixmap
    if [ $? -ne 0 ]; then
	log_WARN "Unable to catch application icon"
    fi

    xml2desktopfile $buffer $windows_app_cache
    if [ $? -ne 0 ] || [ ! -f "$uri" ]; then
	log_WARN "Catch of $basename failed"
	[ -f $buffer ] && rm $buffer
	[ -f $pixmap ] && rm $pixmap
	return 1
    fi

    [ -f $buffer ] && rm $buffer
    return 0
}

windows_logoff() {
    local sessid_dir=$1
    local user_login=$2

    windows_use_seamlessrdp $sessid_dir || return 0

    su -s "/bin/bash" $user_login -c "rdesktop -l logoff"
}
