# Copyright (C) 2006-2008 Ulteo SAS
# http://www.ulteo.com
# Author Gaël DUVAL <gduval@ulteo.com>
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

#
## NFS mount
#

. $MODULES_FSD/local.sh

nfs_set_fs() {
    NFS_SERVER=`cat ${SESSID_DIR}/parameters/module_fs/fileserver`
    NFS_ROOT=`cat ${SESSID_DIR}/parameters/module_fs/base`
    check_variables NFS_SERVER NFS_ROOT || return 1

    local default_opts="nfsvers=3,nolock,tcp,soft,timeo=80,retry=5,intr,rsize=8192,wsize=8192,noexec"
    NFS_MOUNT_CMD="mount -t nfs -o $default_opts"
    NFS_USER_HOME=$USER_LOGIN
}


nfs_get_status () {
    grep "/mnt/nfs/$NFS_SERVER nfs" /proc/mounts > /dev/null
}


nfs_get_bindstatus () {
    is_mount_point $USER_HOME
}


nfs_do_mount() {
    #1) mount the whole homes branch if it is not already
    nfs_get_status
    log_INFO "nfs:prestart NFS status: $?"

    [ ! -d /mnt/nfs/$NFS_SERVER ] && mkdir -p /mnt/nfs/$NFS_SERVER

    if ! nfs_get_status; then
	retry "$NFS_MOUNT_CMD  $NFS_SERVER:$NFS_ROOT /mnt/nfs/$NFS_SERVER" $MOUNT_RETRIES 21 2> $MOUNT_LOG
    fi

#    [ $? -eq 0 ] && nfs_get_status || return 1 
#                    ^-- ToDo when retry ok, nfs_get_status return != 0 :/

    # 2) bind real local user homedir to /home/nickname
    mount --bind /mnt/nfs/$NFS_SERVER/$NFS_USER_HOME $USER_HOME
}


nfs_do_umount() {
    local_do_umount

    # Only mountbind is unmounted <-- have to umount all nfs sub mounts
    #if nfs_get_bindstatus ; then 
    local to_umount=/mnt/nfs/$NFS_SERVER/$NFS_USER_HOME
	retry "umount $to_umount" $MOUNT_RETRIES 1 2>> $MOUNT_LOG

	if [ $? -ne 0 ]; then
	    # Forced and lazy umounts as a fallback
	    log_WARN "nfs: Attempting to force unmount. of $to_umount"
	    retry "umount -f $to_umount || umount -l -t bind $to_umount" $MOUNT_RETRIES 2>> $MOUNT_LOG
	fi

	#rmdir $USER_HOME
	#return $?
	
    #else
	#log_WARN "nfs: ${USER_HOME} bind is already unnmounted"
	#return 0
    #fi    
}


nfs_do_clean() {
    # clean the mountbinds
    local_do_clean
    [ -d /mnt/nfs ] || return 0

    # We remove the global mount too.
    local dirt_mounts=`find /mnt/nfs -maxdepth 1 -mindepth 1`
    for mount_point in $dirt_mounts; do
       log_WARN "nfs: Cleaning $mount_point"

       if is_mount_point $mount_point; then
           retry "umount $mount_point" $MOUNT_RETRIES 1 2>>$MOUNT_LOG
	   if [ $? != 0 ]; then
	       log_ERROR "Unable to umount '$mount_point', exiting"
	       return
	   fi
       fi

       rmdir $mount_point
    done

    rmdir /mnt/nfs
    if [ $? != 0 ]; then
	log_WARN "cifs_no_sfu: Cleaning '/mnt/nfs' not empty, erasing"
	rm -rf /mnt/nfs
    fi
}
