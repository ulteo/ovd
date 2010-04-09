# Copyright (C) 2009-2010 Ulteo SAS
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
    [ -d $SPOOL/files ] || mkdir $SPOOL/files
    [ -d $SPOOL/id ] || mkdir $SPOOL/id
    [ -d $SPOOL/id/locks ] || mkdir $SPOOL/id/locks
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

    rm -rf $SPOOL/cache*

    if [ -n "$SPOOL_USERS" ]; then
        rm -rf $SPOOL_USERS/*
    fi
}

spool_get_id() {
    local buf=0
    while [ 1 -eq 1 ]; do
        [ $buf -ge 1000 ] && return 1

	local nb=$(find $SPOOL/id/locks -name "id_${buf}_*" |wc -l)
	if [ $nb -eq 0 ] && [ ! -f $SPOOL/id/id_$buf ]; then
	    touch $SPOOL/id/locks/id_${buf}_$PID
	    
	    local nb=$(find $SPOOL/id/locks -name "id_${buf}_*" |wc -l)
	    if [ $nb -eq 1 ] && [ ! -f $SPOOL/id/id_$buf ]; then
		break;
	    fi
	    
	    rm $SPOOL/id/locks/id_${buf}_$PID
	fi

        buf=$(( $buf + 1 ))
    done

    echo $SESSID> $SPOOL/id/id_$buf
    rm $SPOOL/id/locks/id_${buf}_$PID
    echo $buf
}

spool_get_rfbport() {
    local buf=5900

    while [ 1 -eq 1 ]; do
        [ $buf -ge 8900 ] && return 1

	local nb=$(find $SPOOL/id/locks -name "vnc_${buf}_*" |wc -l)
	if [ $nb -eq 0 ] && [ ! -f $SPOOL/id/vnc_${buf} ]; then
	    touch $SPOOL/id/locks/vnc_${buf}_$PID
		
	    local nb=$(find $SPOOL/id/locks -name "vnc_${buf}_*" |wc -l)
	    if [ $nb -eq 1 ] && [ ! -f $SPOOL/id/vnc_${buf} ]; then
		break;
	    fi

	    rm $SPOOL/id/locks/vnc_${buf}_$PID
	fi

        buf=$(( $buf + 1 ))
    done

    echo $SESSID> $SPOOL/id/vnc_$buf
    rm $SPOOL/id/locks/vnc_${buf}_$PID
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
