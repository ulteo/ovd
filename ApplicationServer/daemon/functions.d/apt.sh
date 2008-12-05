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

apt_do() {
    local file=$1
    local request=`head -n 1 $file`

    rm $file || return 2
    mkdir $file || return 2

    local status=$file/status
    local stdout=$file/stdout
    local stderr=$file/stderr
    touch $status $stdout $stderr

    log_INFO "apt-do $p => '$request'"

    apt-get update >>$stdout 2>>$stderr
    local ret=$?
    if [ $ret -ne 0 ]; then
	echo -n $ret >$status
	log_INFO "apt-do: apt-get update return $ret"
	return 1
    fi

    # avoid user interaction
    export DEBIAN_FRONTEND=noninteractive
    export DEBIAN_PRIORITY=critical
    export DEBCONF_NONINTERACTIVE_SEEN=true

    apt-get --yes --force-yes $request >>$stdout 2>>$stderr
    local ret=$?
    echo -n $ret >$status
    if [ $ret -ne 0 ]; then
	log_INFO "apt-do: apt-get $request return $ret"
	return 1
    fi

    log_INFO "apt-do $p success"
}

apt_daemon() {
    local directory=$SPOOL/apt

    while [ -d $directory ]; do
	local files=`find $directory -maxdepth 1 -mindepth 1 -type f`

	if [ "$files" = "" ]; then
	    inotifywait -t 10 -q -e close_write $directory
	else
	    for file in $files; do
		[ -f $SPOOL/files/sources.list ] && mv $SPOOL/files/sources.list /etc/apt/sources.list
		apt_do $file
		[ -f $file ] && rm $file
	    done
	fi
    done
}
