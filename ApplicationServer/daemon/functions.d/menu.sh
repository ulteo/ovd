# Copyright (C) 2008 Ulteo SAS
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

menu_spool() {
    local user_id=$1
    local sessid_dir=$2
    local menu_dir=/var/spool/menus/$user_id

    [ -f $sessid_dir/parameters/menu ] || return 1

    [ -d $menu_dir ] && menu_clean $user_id
    mkdir -p $menu_dir

    local file=$sessid_dir'/parameters/menu'
    local nblines=`wc -l $file |cut -d ' ' -f1`

    windows_use_seamlessrdp $sessid_dir
    local put_windows_app=$?

    for i in `seq $nblines`; do
	local app=`head -n $i $file | tail -n 1`
	local type=${app##*.}

	case $type in
	    'desktop')
		menu_put $app $menu_dir
		;;
	    'lnk')
		[ $put_windows_app -ne 0 ] && continue
		menu_windows_put "$app" $menu_dir
		[ $? -eq 0 ] && local nbwindows_app=$(( $nbwindows_app + 1 ))
		;;
	    *)
		log_WARN "Unrecognized application type '$name'"
	esac
    done

    if [ -f $sessid_dir/parameters/desktop_icons ]; then
	touch $menu_dir/.show_on_desktop
    fi
}

menu_put() {
    local desktop=$1
    local menu_dir=$2

    [ -f "$desktop" ] || return 1
    local basename=$(basename "$desktop")
    local dest=$menu_dir/$basename

    ln -sf "$desktop" "$dest"
}

menu_clean() {
    local user_id=$1
    local menu_dir=/var/spool/menus/$user_id

    [ ! -d $menu_dir ] && return 0

    rm -f $menu_dir/*
    [ -f $menu_dir/.show_on_desktop ] && rm $menu_dir/.show_on_desktop
    rmdir $menu_dir

}


menu_windows_put() {
    local desktop=$1
    local menu_dir=$2

    local windows_app_cache=$SPOOL'/windows'
    [ ! -d $windows_app_cache ] && mkdir -p $windows_app_cache

    local basename=$(echo "$desktop" | sed -e 's/\\/\//g')

    local basename=`basename "$basename" .lnk`'.desktop'
    local uri=$windows_app_cache'/'$basename

    log_INFO "menu_windows_put: get '$uri'"

    if [ ! -f "$uri" ]; then
	windows_catch_application "$desktop" || return 1
    fi

    menu_put "$uri" $menu_dir
}
