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

    for app in `cat $sessid_dir/parameters/menu`; do
	menu_put $app $menu_dir
    done
}

menu_put() {
    local desktop=$1
    local menu_dir=$2

    [ -f $desktop ] || return 1
    local basename=`basename $desktop`
    local dest=$menu_dir/$basename

    ln -sf $desktop $dest
}

menu_clean() {
    local user_id=$1
    local menu_dir=/var/spool/menus/$user_id

    [ ! -d $menu_dir ] || return 0

    rm -f $menu_dir/*
    rmdir $menu_dir

}
