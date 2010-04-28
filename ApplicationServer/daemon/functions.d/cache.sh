# Copyright (C) 2006-2010 Ulteo SAS
# http://www.ulteo.com
# Author Gaël DUVAL <gduval@ulteo.com> 2006
# Author Julien LANGLOIS <julien@ulteo.com> 2008, 2009, 2010
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

## Cache function
#
cache_build() {
    local old=$(find $SPOOL -maxdepth 1 -mindepth 1 -type d -name "cache.*"  | sort | head -n -1)
    local dest="cache.$(date +%s)$RANDOM"

    mkdir -p $SPOOL/$dest

    netstat -n --tcp > $SPOOL/$dest/netstat
    ps aux |tail -n +2 > $SPOOL/$dest/ps
    grep "cpulimit" $SPOOL/$dest/ps | mawk '{ print $13 }' > $SPOOL/$dest/ps_cpulimit
    ps axo ruser,pid,pcpu |tail -n +2 |grep -v "^root" > $SPOOL/$dest/ps_chroot

    if [ -n "$old" ]; then
	rm -rf $old
    fi
}

cache_get_latest() {
    find $SPOOL -maxdepth 1 -mindepth 1 -type d -name "cache.*" | sort | tail -n 2 | head -n 1 2>/dev/null
}

cache_net_display() {
    cat $(cache_get_latest)/netstat
}

cache_ps_display() {
    cat $(cache_get_latest)/ps 2>/dev/null
}

cache_ps_chroot_display() {
    cat $(cache_get_latest)/ps_chroot
}

cache_ps_pid_for_user() {
    grep "^$1" $(cache_get_latest)/ps | mawk '{ print $2 }'
}

cache_is_cpulimited() {
    grep -q "$1" $(cache_get_latest)/ps_cpulimit
}

cache_set_monitoring() {
    local file=$1

    local cpu_model=$(grep "model name" /proc/cpuinfo |head -n 1| sed -e 's/.*: //') || return 1
    local cpu_nb=$(grep "^processor" /proc/cpuinfo |tail -n 1| mawk '{ print $3 }') || return 1
    local cpu_nb=$(( $cpu_nb + 1 ))
    local cpu_load=$(cpu_load.py)

    local ram=$(grep ^MemTotal: /proc/meminfo |tr -s ' '|cut -d ' ' -f2) || return 1
    local ram_Free=$(grep ^MemFree: /proc/meminfo |tr -s ' '|cut -d ' ' -f2) || return 1
    local ram_Buffers=$(grep ^Buffers: /proc/meminfo |tr -s ' '|cut -d ' ' -f2) || return 1
    local ram_Cached=$(grep ^Cached: /proc/meminfo |tr -s ' '|cut -d ' ' -f2) || return 1
    ram_used=$(( $ram - $ram_Free - $ram_Buffers - $ram_Cached))

    if [ ! -f $file ]; then
        touch $file
        chown root:www-data $file
        chmod 640 $file
    fi

    echo '<?xml version="1.0" encoding="utf-8"?>'          > $file || return 1
    echo '<monitoring>'                                    >>$file
    echo ' <cpu nb_cores="'$cpu_nb'" load="'$cpu_load'">'  >>$file
    echo $cpu_model                                        >>$file
    echo '  </cpu>'                                        >>$file
    echo '<ram total="'$ram'" used="'$ram_used'" />'       >>$file

    echo '<sessions>'                                      >>$file
    for s in $(sessions_get_active); do
        session_load $s 2>/dev/null
        if [ $? -ne 0 ]; then
            log_WARN "Session $s does not exist anymore or status is not 1"
            continue
        fi
 
        echo '<session id="'$s'" i="'$i'">'                >>$file

        echo '<vnc login="'$VNC_USER'">'                   >>$file
        for pid in $(cache_ps_pid_for_user $VNC_USER); do
            echo '<pid id="'$pid'" />'                     >>$file
        done
        echo '</vnc>'                                      >>$file

        echo '<ssh login="'$SSH_USER'">'                   >>$file
        for pid in $(cache_ps_pid_for_user $SSH_USER); do
            echo '<pid id="'$pid'" />'                     >>$file
        done
        echo '</ssh>'                                      >>$file

        echo '<user login="'$USER_LOGIN'">'                >>$file
        if [ -f $SPOOL_USERS/$SESSID/apps ]; then
            while read pid app_id; do
                [ -z "$app_id" ] && continue
                echo '<application pid="'$pid'" app_id="'$app_id'" />' >>$file
            done < $SPOOL_USERS/$SESSID/apps
        fi

        for app in $(application_list $SESSID); do
            local app=$(basename $app)
            local app_id=$(application_get_appId $app $SESSID)
            echo '<session id="'$app'" app_id="'$app_id'" />' >>$file
        done

        echo '</user>'                                     >>$file
        echo '</session>'                                  >>$file
        session_unload
    done

    echo '</sessions>'                                     >>$file
    echo '</monitoring>'                                   >>$file
}
