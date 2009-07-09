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

display_vnc_opts="-caching -caching_ent 1500 -caching_malg LRU -caching_minsize 5000000"

display_init() {
    local sessid=$1
    local rfb_port=$2
    local xauth_file=/tmp/.tmp${VNC_UID}.Xauthority

    # Install the MIT_MAGIC_COOKIE
    echo "$VNC_USER -c xauth -f $xauth_file add :$rfb_port"
    su -s "/bin/bash" $VNC_USER -c "xauth -f $xauth_file add :$rfb_port . `/usr/bin/mcookie`"

    # Install the MIT_MAGIC_COOKIE into the real user env
    cp $xauth_file $SPOOL_USERS/$sessid/.Xauthority
    chown ${USER_LOGIN}      $SPOOL_USERS/$sessid/.Xauthority

}


display_start() {
    local rfb_port=$1
    local geometry=$2

    local vnc_tmp=/tmp/.tmp${VNC_UID} 

    # Start the VNC server
    /bin/su -s "/bin/bash" $VNC_USER -c "XAUTHORITY=${vnc_tmp}.Xauthority /usr/bin/Xtightvnc ${multei_session_vnc_opts} :${rfb_port} -desktop X${rfb_port} -nolock -once -interface 127.0.0.1 -localhost -lf 1024 -geometry ${geometry} -depth 24 -rfbwait 240000 -rfbauth ${vnc_tmp}encvncpasswd -rfbport ${rfb_port} -fp /usr/share/X11/fonts/Type1/,/usr/share/X11/fonts/misc/,/usr/share/X11/fonts/75dpi/,/usr/share/X11/fonts/100dpi/ -co /etc/X11/rgb -ac -auth ${vnc_tmp}.Xauthority" &> /dev/null &

    sleep 1

    # Xvnc accept connexion only from MIT_MAGIC_COOKIEs
    su -s "/bin/bash" $VNC_USER -c "DISPLAY=:$rfb_port XAUTHORITY=${vnc_tmp}.Xauthority /usr/bin/xhost +";
#     su -s "/bin/bash" $VNC_USER -c "DISPLAY=:$rfb_port XAUTHORITY=${vnc_tmp}.Xauthority /usr/bin/xhost -";
}

display_stop() {
    local rfb_port=$1

    tightvncserver -kill :$rfb_port 2>/dev/null
    [ $? -eq 0 ] && return 0

    local pid=$(ps ax |grep Xtightvnc |grep ":200" |cut -d ' ' -f1)
    [ -n "$pid" ] || return 1

    kill $pid
}
