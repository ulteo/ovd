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

cifs_no_sfu_daemon() {
    log_INFO "Start cifs synchronisation daemon"
    echo $$ > $SESSID_DIR/cifs_no_sfu_daemon_pid

    events="-e create -e modify -e close_write -e moved_to -e moved_from -e move -e delete -e delete_self"
    inotifywait $events -mrq --format "%e/%w%f" $CIFS_NO_SFU_TMP_BR | \
    while read buf; do
        event=$(echo "$buf" | cut -d/ -f1)
        file=${buf#$event/$CIFS_NO_SFU_TMP_BR/}
        log_DEBUG "$buf"

        case $event in
            CLOSE_WRITE,CLOSE|MOVED_TO*)
                log_DEBUG "Copy file from $CIFS_NO_SFU_TMP_BR/$file to $CIFS_MOUNT_POINT/$file"
                if [ -e "$CIFS_NO_SFU_TMP_BR/$file" ]; then
                    log_DEBUG "Copy file from $CIFS_NO_SFU_TMP_BR/$file to $CIFS_MOUNT_POINT/$file"
                    cp -r "$CIFS_NO_SFU_TMP_BR/$file" "$CIFS_MOUNT_POINT/$file"
                fi
                ;;
            CREATE,ISDIR)
                log_DEBUG "Creating directory $CIFS_MOUNT_POINT/$file"
                mkdir -p "$CIFS_MOUNT_POINT/$file"
                ;;
            DELETE|MOVED_FROM*)
                log_DEBUG "Delete file $CIFS_MOUNT_POINT/$file"
                [ -e "$CIFS_MOUNT_POINT/$file" ] && rm -rf "$CIFS_MOUNT_POINT/$file"
                ;;
            DELETE,ISDIR)
                directory=`dirname $file`
                file=`basename $file`
                file=${file#.wh..wh.}
                file=${file%.*}
                file=$directory/$file
                log_DEBUG "Delete directory $CIFS_MOUNT_POINT/$file"
                rm -rf "$CIFS_MOUNT_POINT/$file"
                ;;
        esac
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

    cifs_no_sfu_daemon &
}

cifs_no_sfu_do_umount() {
    log_DEBUG "cifs_no_sfu_do_umount"

    kill_processus $SESSID_DIR/cifs_no_sfu_daemon_pid

    # we don't have the pid of the inotify process, try to guess it
    INOTIFY_PID=$(ps axu | grep inotify.*$CIFS_NO_SFU_TMP_BR$ | mawk '{print $2}')
    [ -n "$INOTIFY_PID" ] && kill $INOTIFY_PID

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
