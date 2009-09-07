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

### Tmpdir Managments

tmp_make() {
    # $1 is user id or username
    # Create the corresponding /tmp directory in $TMPDIR

    local uid="$1"
    [ -z "$uid" ] && return 1

    if [[ "$1" != [0-9] ]]; then
        local uid=$(id -u $uid)
        [ -z "$uid" ] && return 1
    fi

    local tmpdir="/tmpdir/tmp$uid"
    [ -d $tmpdir ] && rm -rf $tmpdir
    mkdir $tmpdir && chmod 750 $tmpdir
    # is it a human user? if not, create the .X11-unix
    if ! [ $uid -gt 69999 ]; then
        mkdir $tmpdir/.X11-unix && chmod a+rwxt $tmpdir/.X11-unix
    fi
    mkdir $tmpdir/.ICE-unix && chmod a+rwxt $tmpdir/.ICE-unix
    chown -R $uid:$uid $tmpdir
}

## Futur functions
tmp_init() {
	local tmpdir=$tmp_base

	[ ! -d $tmpdir ] && mkdir $tmpdir
	chmod 777 $tmpdir
	
	[ ! -d $tmpdir/.X11-unix ] && mkdir $tmpdir/.X11-unix
	chmod a+rwxt $CHROOT/$tmp_base/.X11-unix
	
	[ ! -d $tmpdir/.ICE-unix ] && mkdir $tmpdir/.ICE-unix
	chmod a+rwxt $CHROOT/$tmp_base/.ICE-unix
}

tmp_create() {
    local uid=$1

	local tmpdir=$CHROOT/$tmp_base/$uid

    [ -d $tmpdir ] && rm -rf $tmpdir
    mkdir $tmpdir && chmod 750 $tmpdir
    
    chown -R $uid:$uid $tmpdir
}

tmp_destroy() {
    local uid=$1

	local tmpdir=$tmp_base/$uid
    
    [ -d $tmpdir ] && rm -rf $tmpdir
}
