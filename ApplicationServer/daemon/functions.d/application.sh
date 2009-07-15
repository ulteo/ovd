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


application_check_status() {
    local sessid=$1
    local job_id=$2

    local dir=$SPOOL/sessions/$sessid/sessions/$job_id
    local status=$(cat $dir/status)
    local app_id=$(cat $dir/app_id)
    local rfb_port=$(cat $dir/rfb_port)


    #
    # SWITCH CASE status
    #

    # if the application is required to stop
    if [ $status -eq 3 ]; then
        application_purge $job_id $rfb_port
        if [ "$app_id" == "desktop" ]; then
            session_switch_status $sessid 3
            return 2
        fi

    elif [ $status -eq 2 ]; then
        # if the owner_exit file exist, kill the application
        if [ -f $dir/owner_exit ]; then
            log_INFO "session $sessid kill application $job_id"
            application_switch_status $sessid $job_id 3
            application_check_status $sessid $job_id
            return $?
        fi

        # if application owner has vanished, kill session
        if [ -e $dir/keepmealive ]; then
            local t0=$(stat -c "%Z" $dir/keepmealive)
            local t1=$(date +%s)
            local diff=$(( $t1 - $t0 ))

            if [ $diff -gt 20 ]; then
	        log_WARN "sesion $sessid KEEPMEALIVE expired application $job_id"
                application_switch_status $sessid $job_id 3
                application_check_status $sessid $job_id
                return $?
	    fi
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
    local rfb_port=$2

    display_stop $rfb_port

    spool_free_rfbport $rfb_port
    rm -rf $dir
}
