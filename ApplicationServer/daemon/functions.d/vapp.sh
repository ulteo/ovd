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


vapp_repo=$SPOOL'/virtual_apps'


vapp_init() {
    [ ! -d $vapp_repo ] && mkdir -p $vapp_repo
}

vapp_exist() {
    local id=$1
    local pixmap="/usr/share/pixmaps/vapp-"$id'.png'

    [ -f $vapp_repo/$id.desktop ] || return 1
    [ -f $pixmap ]                || return 1
}

vapp_get() {
    local id=$1
    local buffer="/tmp/test.xml"

    vapp_init

    webservices_get_application $id $buffer
    if [ $? -ne 0 ]; then
        log_WARN "Cannot get application from $id"
        [ -f $buffer ] && rm $buffer
        return 1
    fi

    local desktop=$vapp_repo/$id.desktop
    local pixmap="/usr/share/pixmaps/vapp-"$id'.png'

    webservices_get_application_icon $id $pixmap
    if [ $? -ne 0 ]; then
        log_WARN "Unable to catch application icon"
    fi

    xml2desktopfile $buffer $desktop
    if [ $? -ne 0 ] || [ ! -f $desktop ]; then
        log_WARN "Catch of $id failed"
        [ -f $buffer ] && rm $buffer
        [ -f $pixmap ] && rm $pixmap
        return 1
    fi

    [ -f $buffer ] && rm $buffer
    return 0
}
