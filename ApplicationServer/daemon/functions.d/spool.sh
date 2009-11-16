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


spool_init() {
    [ -d $SPOOL ] || mkdir -p $SPOOL
    [ -d $SPOOL/apt ] || mkdir $SPOOL/apt
    [ -d $SPOOL/cache ] || mkdir $SPOOL/cache
    [ -d $SPOOL/files ] || mkdir $SPOOL/files
    [ -d $SPOOL/id ] || mkdir $SPOOL/id
    [ -d $SPOOL/sessions ] || mkdir $SPOOL/sessions
    [ -d $SPOOL/sessions2create ] || mkdir $SPOOL/sessions2create

    [ -d $SPOOL_USERS ] || mkdir -p $SPOOL_USERS

    chown www-data:www-data $SPOOL/apt $SPOOL/files $SPOOL/sessions2create
    chmod 770 $SPOOL/apt $SPOOL/files $SPOOL/sessions2create
}

spool_clean() {
    if [ -n "$SPOOL" ]; then
        for d in apt files id sessions sessions2create; do
            rm -rf $SPOOL/$d/*
        done
    fi

    if [ -n "$SPOOL_USERS" ]; then
        rm -rf $SPOOL_USERS/*
    fi
}

spool_get_id() {
    local buf=0
    while [ -f $SPOOL/id/id_$buf ]; do
        [ $buf -ge 1000 ] && return 1
        buf=$(( $buf + 1 ))
    done
    echo $SESSID> $SPOOL/id/id_$buf
    echo $buf
}

spool_get_rfbport() {
    local buf=5900
    while [ -f $SPOOL/id/vnc_$buf ]; do
        [ $buf -ge 6900 ] && return 1
        buf=$(( $buf + 1 ))
    done
    echo $SESSID> $SPOOL/id/vnc_$buf
    echo $buf
}

spool_free_id() {
    local buf=$1

    [ -n "$buf" ] || return 1
    [ -f $SPOOL/id/id_$buf ] || return 2
    rm $SPOOL/id/id_$buf
}

spool_free_rfbport() {
    local buf=$1

    [ -n "$buf" ] || return 1
    [ -f $SPOOL/id/vnc_$buf ] || return 2
    rm $SPOOL/id/vnc_$buf
}
