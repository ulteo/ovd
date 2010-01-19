# Copyright (C) 2008-2010 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com>
# Author Laurent CLOUET <laurent@ulteo.com> 2009
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

menu_init() {
    local menu_dir=$1
    [ -d $menu_dir ] && rm -rf $menu_dir

    mkdir -p                  $menu_dir/applications
    ln -sf /usr/share/icons   $menu_dir/icons
    ln -sf /usr/share/pixmaps $menu_dir/pixmaps
    ln -sf /usr/share/mime    $menu_dir/mime
    ln -sf /usr/share/themes  $menu_dir/themes
}

menu_spool() {
    local menu_dir=$1
    local sessid_dir=$2

    menu_init $menu_dir

    [ -f $sessid_dir/parameters/applications ] || return 1

    while read app ; do
        local id=$(echo $app| cut -d '|' -f1)
        local type=$(echo $app| cut -d '|' -f2)

        case $type in
        'local')
            local path=$(echo $app| cut -d '|' -f3)
            menu_put $id "$path" $menu_dir
            ;;
        'virtual')
            local mode=$(echo $app| cut -d '|' -f3)
            menu_virtual_put $id $mode $menu_dir
            ;;
        *)
            log_WARN "Unrecognized application type '$name'"
        esac
    done <$sessid_dir'/parameters/applications'

    update-desktop-database $menu_dir/applications

    if [ -f $sessid_dir/parameters/desktop_icons ]; then
        touch $menu_dir/applications/.show_on_desktop
    fi
}

menu_put() {
    local id=$1
    local desktop="$2"
    local menu_dir=$3

    [ -f "$desktop" ] || return 1
    local dest=$menu_dir/applications/$id.desktop

    sed -r "s#^Exec=(.*)#Exec=startovdapp $id \"$desktop\"#" <"$desktop" >"$dest"
}

menu_clean() {
    local menu_dir=$1

    [ -d $menu_dir ] && rm -rf $menu_dir
}

menu_virtual_put() {
    local id=$1
    local mode=$2
    local menu_dir=$3

    if ! vapp_exist $id || [ $mode = 'reload' ]; then
        vapp_get $id
        [ $? -eq 0 ] || return 1
    fi

    menu_put $id $vapp_repo/$id.desktop $menu_dir
}

menu_has_application() {
    local menu_dir=$1
    local app_id=$2

    test -f $menu_dir/applications/$app_id.desktop
}

