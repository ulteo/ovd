# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Gauvain POCENTEK <gauvain@ulteo.com>
# Author Julien LANGLOIS <julien@ulteo.com>
# Author Jocelyn DELALANDE <jocelyn.delalande@no-log.org>
# Author Jonathan LESTRELIN <jonathan@ulteo.com>
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

# do we have an active RSBAC server?
rsbac_is_active () {
    [ -d /proc/rsbac-info ] && return 0
    return 1
}

retry() {
    #args: command, number of attempts, delay
    local COUNTER=1
    local SUCCESS=1

    local COMMAND=$1
    local ATTEMPTS=$2
    local DELAY=$3

    while [ $SUCCESS -ne 0 ] && [ $COUNTER -lt $ATTEMPTS ] ; do
        $COMMAND
        SUCCESS=$?
        log_INFO "retry $COUNTER.."	
	if [ $SUCCESS -ne 0 ]; then
	    let COUNTER++
	    sleep $DELAY
	fi
    done

    if [ $SUCCESS -eq 0 ]; then 
	log_INFO "retry SUCCESS"
    else
	log_INFO "retry FAILED"
    fi
    return $SUCCESS
}

check_variables() {
    for key in $@; do
	eval content=\$$key
	if ! [ $content ]; then
	    log_ERROR "variable '$key' is empty"
	    return 1
	fi
    done
    return 0
}

is_mount_point() {
    mount | grep -q " $1 "
}

kill_processus() {
    pid_file=$1
    [ -e $pid_file ] || return 0    
    pid=`cat $pid_file`

    kill $pid > /dev/null

    if [ $? != 0 ]; then
            kill -9 $pid > /dev/null
    fi
    rm -f $pid_file
}

str2hex() {
    perl -pe 's/(.)/sprintf("%02lx", ord $1)/eg'
}

unbase64() {
    echo $1 | perl -MMIME::Base64 -ne 'print decode_base64($_)'
    #echo $1 | base64 -d
}

get_real_user() {
    awk '{ split($1, buf, ":"); if (buf[3]>1000) print buf[1] }' < /etc/passwd
}

# Delete vars from export but keep value set
unexport() {
    for var in $@; do
	local buf=$(eval "echo $"$var)
	unset $var
	eval "$var=\"$buf\""
    done
}
