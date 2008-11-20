# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Jocelyn DELALANDE <jocelyn.delalande@no-log.org>
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

. functions.sh

export MOUNT_LOG=/var/log/mount_problems
export MOUNT_RETRIES=3
export FS=$HOME_DIR_TYPE
export MODULES_FSD=`dirname $0`/modules_fs.d

#
## Module_FS functions
#
# Below are standard module_fs functions, that *must* be defined for
# any module in modules_fs.d.

# Initialize some values, must be done before any mount/umount
set_fs() {
    if ! [ -d $MODULES_FSD  ] ; then
	log_ERROR "MODULES_FS: $MODULES_FS dir doesn't exists"
	return 1
    fi

    if ! [ -f $MODULES_FSD/${HOME_DIR_TYPE}.sh ] ; then
	log_ERROR "MODULES_FS: ${HOME_DIR_TYPE} is not an existing fs module."
	return 1
    fi

    . ${MODULES_FSD}/${HOME_DIR_TYPE}.sh || return 1

    ${HOME_DIR_TYPE}_set_fs
}

# Get mount status: mounted or not.
get_status() {
    ${HOME_DIR_TYPE}_get_status
}

# Creates dirs ant do effective mount in ${USER_HOME}
do_mount() {
    ${HOME_DIR_TYPE}_do_mount
}

# Do umount and removes empty dirs.
do_umount() {
    ${HOME_DIR_TYPE}_do_umount
}

do_clean() {
    local homes=`find /home -maxdepth 1 -mindepth 1`
    local fail=0
    for home in $homes; do
	do_clean_home ${home#/home/} || local fail=1
    done

    local types=`find /mnt -maxdepth 1 -mindepth 1`
    for t in $types; do
	do_clean_module ${t#/mnt/} || local fail=1
    done

    return $fail
}

do_clean_home() {
    local home=/home/$1
    log_INFO "do_clean_home: job '$home'"

    while is_mount_point $home; do
	umount $home
	if [ $? != 0 ]; then
	    log_ERROR "do_clean_home: umount of '$home' fail"
	    return 1
	fi
    done
	
    rmdir $home
    if [ $? != 0 ]; then
	log_WARN "do_clean_home: '$home' not empty -> erasing"
	rm -rf $home
    fi
}

do_clean_module() {
    local module=$1
    local dir=/mnt/$1

    . ${MODULES_FSD}/${module}.sh
    if [ $? != 0 ]; then
	log_ERROR "do_clean_module: existing directory '$dir' but load of module '${module}' fail"
	return 1
    fi

    ${module}_do_clean
    if [ -d $dir ]; then
	log_ERROR "do_clean: still have '$t' after ${module}_do_clean"
	return 1
    fi
}
