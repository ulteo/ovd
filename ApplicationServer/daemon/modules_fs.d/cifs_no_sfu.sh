# Copyright (C) 2008 Ulteo SAS
# http://www.ulteo.com
# Author Jocelyn DELALANDE <jocelyn.delalande@no-log.org>
# Author Julien LANGLOIS <julien@ulteo.com>
# Author Jonathan LESTRELIN <julien@ulteo.com>
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
## Cifs-No-Sfu
#
# Handles CIFS without sfu support.
# It uses aufs to handle transparently special files and symlinks ; 
# Data are synced to cifs at session closing time.
# Cifs-No-Sfu "inherits" procedures from cifs
#

. $MODULES_FSD/cifs.sh

parse_dir_name(){
    file=$1

    echo $file
}

cifs_no_sfu_daemon() {
    echo $$ > $SESSID_DIR/cifs_no_sfu_daemon_pid

    log_INFO "Start cifs synchronisation daemon"
    
    touch $SESSID_DIR/cifs_no_sfu_buf
    inotifywait -mrq --format "%w!%e!%f" $CIFS_NO_SFU_TMP_BR >> $SESSID_DIR/cifs_no_sfu_buf &
    echo $$ > $SESSID_DIR/cifs_no_sfu_daemon_inotify_pid
    
    i=0
    while [ -d $SESSID_DIR ]
    do
        if [[ $i != `cat $SESSID_DIR/cifs_no_sfu_buf | wc -l` ]]
        then
            i=$(($i + 1))
            
            buf=`head -n $i $SESSID_DIR/cifs_no_sfu_buf | tail -n 1`

            file=`echo "$buf" | cut -d '!' -f1``echo "$buf" | cut -d '!' -f3` #FIXME problem when there is ! in names
            file=${file#$CIFS_NO_SFU_TMP_BR/}

            event=`echo "$buf" | cut -d '!' -f2`

            if [ "$event" = "CLOSE_WRITE,CLOSE" ] && [ -f "$CIFS_NO_SFU_TMP_BR/$file" ] || [ "$event" = "CREATE,ISDIR" ] || [ "$event" = "MOVED_TO" ]
            then
                log_DEBUG "Copy file from $CIFS_NO_SFU_TMP_BR/$file to $CIFS_MOUNT_POINT/$file"
                cp -r "$CIFS_NO_SFU_TMP_BR/$file" "$CIFS_MOUNT_POINT/$file" 2>/dev/null
            fi

            if [ "$event" = "DELETE" ] || [ "$event" = "MOVED_FROM" ]
            then
                log_DEBUG "Delete file $CIFS_MOUNT_POINT/$file"
                rm "$CIFS_MOUNT_POINT/$file" 2>/dev/null
            fi

            if [ "$event" = "DELETE,ISDIR" ]
            then
                directory=`dirname $file`
                file=`basename $file`
                file=${file#.wh..wh.}
                file=${file%.*}
                file=$directory/$file
                log_DEBUG "Delete directory $CIFS_MOUNT_POINT/$file"
                rm -r "$CIFS_MOUNT_POINT/$file" 2>/dev/null
            fi
        fi
        
		kill_processus $SESSID_DIR/cifs_no_sfu_daemon_inotify_pid
    done
}

cifs_no_sfu_set_fs() {
    log_DEBUG "cifs_no_sfu_set_fs"
    cifs_set_fs || return 1

    CIFS_NO_SFU_TMP_BR=/mnt/cifs_no_sfu/${USER_LOGIN}
}

cifs_no_sfu_get_status() {
    cifs_get_status
}

cifs_no_sfu_do_mount() {
    log_DEBUG "cifs_no_sfu_do_mount"
    cifs_do_mount || return 1

    # Unmount the mountbind from regular cifs module.
    umount -t bind $USER_HOME 2>> $MOUNT_LOG
    if [ $? != 0 ]; then
	log_ERROR "unable to umount bind '$USER_HOME'"
	return 1
    fi

    # This branch will be able to receive special files, symlinks...
    mkdir -p $CIFS_NO_SFU_TMP_BR
    chown -R ${USER_ID}:${USER_ID} $CIFS_NO_SFU_TMP_BR

    log_INFO "cifs_no_sfu: aufs $CIFS_NO_SFU_TMP_BR:rw, $CIFS_MOUNT_POINT:ro into $USER_HOME"
    local mount_cmd="mount -t aufs -o br:$CIFS_NO_SFU_TMP_BR=rw:$CIFS_MOUNT_POINT=ro none $USER_HOME"
    retry "$mount_cmd" $MOUNT_RETRIES 1 2>> $MOUNT_LOG
    [ $? == 0 ] || return 1

    #cifs_no_sfu_daemon &
}

cifs_no_sfu_do_umount() {
    log_DEBUG "cifs_no_sfu_do_umount"
    
    kill_processus $SESSID_DIR/cifs_no_sfu_daemon_pid
    kill_processus $SESSID_DIR/cifs_no_sfu_daemon_inotify_pid
    
    cifs_do_umount_bind || return 1

    # --exclude is to prevent aufs inode tables copies.
    rsync -ru --exclude='*.wh.*' $CIFS_NO_SFU_TMP_BR/ $CIFS_MOUNT_POINT
    if [ $? -ne 0 ]; then
	log_WARN "cifs_no_sfu: unable to rsync $USER_HOME --> $CIFS_BRANCH, some data may have been lost."
    fi

    cifs_do_umount_real

    rm -rf $CIFS_NO_SFU_TMP_BR
}


cifs_no_sfu_do_clean() {
    log_DEBUG "cifs_no_sfu_do_clean"
    cifs_do_clean
    [ -d /mnt/cifs_no_sfu ] || return 0

    local dirt_mounts=`find /mnt/cifs_no_sfu -maxdepth 1 -mindepth 1`
    for mount_point in $dirt_mounts; do
	log_WARN "cifs_no_sfu: Cleaning dirt $mount_point"
	rm -rf $mount_point
    done

    rmdir /mnt/cifs_no_sfu
    if [ $? != 0 ]; then
	log_WARN "cifs_no_sfu: Cleaning '/mnt/cifs_no_sfu' not empty, erasing"
	rm -rf /mnt/cifs_no_sfu
    fi
}
