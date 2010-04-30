# Copyright (C) 2008-2010 Ulteo SAS
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
            COUNTER=$(( $COUNTER + 1 ))
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
    pid=$(cat $pid_file)

    kill $pid > /dev/null

    if [ $? != 0 ]; then
        kill -s 9 $pid > /dev/null
    fi
    rm -f $pid_file
}

str2hex() {
    python -c 'import sys; print sys.stdin.read().encode("hex")'
}

get_real_user() {
    mawk '{ split($1, buf, ":"); if (buf[3]>1000) print buf[1] }' < /etc/passwd
}

pid_alive() {
    local pid=$1
    ps ax | mawk '{ print $1 }' |grep -q $pid
}

get_pid() {
    python -c "import os; print os.getppid()"
}

array_del() {
    local array="$1"
    local item=$2

    for i in $array; do
        if [ "$i" != "$item" ]; then
            echo $i
        fi
    done
}

get_ram() {
    grep "MemTotal:" /proc/meminfo |tr -s " "| cut -d " " -f2
}

get_nb_core() {
    grep "processor" /proc/cpuinfo |wc -l
}
