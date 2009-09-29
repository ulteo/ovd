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

application_switch_status() {
    local sessid=$1
    local job=$2
    local status=$3
    local directory=$SPOOL/sessions/$sessid/sessions/$job

    log_INFO "session $sessid application_switch_status $status"
    echo $status >$dir/status
}


application_is_valid_status() {
    local status=$1

    [ $PERSISTENT -eq 1 ] && \
        [ $status -ge 8 ] && \
        [ $status -le 11 ] && \
        return 0

    [ $status -ge 1 ] && \
        [ $status -le 3 ] && \
        return 0

    return 1
}


application_check_status() {
    local sessid=$1
    local job_id=$2

    local dir=$SPOOL/sessions/$sessid/sessions/$job_id
    local status=$(cat $dir/status)
    local rfb_port=$(cat $dir/rfb_port)
    if [ $PERSISTENT -eq 1 ]; then
        local next_status=8
    else
        local next_status=3
    fi

    if ! application_is_valid_status $status; then
        log_INFO "session $sessid wrong application status, killing"
        application_switch_status $sessid $job_id 3
        local status=3
    fi

    #
    # SWITCH CASE status
    #

    # if the application is required to stop
    if [ $status -eq 3 ]; then
        application_purge $job_id $dir
        if [ "$job_id" = "desktop" ]; then
            session_switch_status $sessid 3
            return 2
        fi

    elif [ $status -eq 2 ]; then
        # if the owner_exit file exist, kill the application
        if [ -f $dir/owner_exit ]; then
            log_INFO "session $sessid kill application $job_id"
            application_switch_status $sessid $job_id $next_status
            application_check_status $sessid $job_id
            return $?
        fi

        # if application owner has vanished, kill session
        if [ -f $dir/keepmealive ]; then
            local t0=$(stat -c "%Z" $dir/keepmealive)
            local t1=$(date +%s)
            local diff=$(( $t1 - $t0 ))

            if [ $diff -gt 20 ]; then
                log_WARN "session $sessid KEEPMEALIVE expired application $job_id"
                application_switch_status $sessid $job_id $next_status
                application_check_status $sessid $job_id
                return $?
            fi
        else
            # Create a kma to use as timeout
            install -g www-data -m 660 $dir/status $dir/keepmealive
        fi

    # Suspend/resume management
    elif [ $PERSISTENT -eq 1 ]; then
        if [ $status -eq 8 ]; then
            application_switch_status $sessid $job_id 9
            log_INFO "session $sessid suspend $job_id"
            [ -f $dir/owner_exit ] && rm $dir/owner_exit
            [ -f $dir/keepmealive ] && rm $dir/keepmealive
            application_switch_status $sessid $job_id 10
            return

        # If application is in suspend mode ... Nothing to do
        elif [ $status -eq 10 ]; then
            if [ -f $dir/owner_is_back ]; then
                log_INFO "session $sessid application $job_id owner_is_back"
                rm $dir/owner_is_back
                application_switch_status $sessid $job_id 11
                application_check_status $sessid $job_id
                return $?
            fi
            return

        # If application need to be restored ...
        elif [ $status -eq 11 ]; then
            log_INFO "session $sessid resume $job_id"
            [ -f $dir/owner_exit ] && rm $dir/owner_exit
            application_switch_status $sessid $job_id 2
            return
        fi
    fi
}


application_loop() {
    local sessid=$1
    local files=$(find $SESSID_DIR/sessions/ -maxdepth 1 -mindepth 1)

    for file in $files; do
        if [ -f $file ] && echo $file | grep -q ".txt$"; then
            local job=$(basename $file .txt)
            application.sh $SESSID $job &
            sleep 0.5
        elif [ -d $file ]; then
            local job=$(basename $file)

            application_check_status $sessid $job
        else
            log_WARN "application_loop: unknown pattern '$file' ... erasing"
            rm -rf $file
        fi
    done
}


application_purge() {
    local job=$1
    local dir=$2

    local rfb_port=$(cat $dir/rfb_port)

    log_INFO "purging application '$job'"
    display_stop $rfb_port $dir/vnc.pid

    spool_free_rfbport $rfb_port
    rm -rf $dir
}
