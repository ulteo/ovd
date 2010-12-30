# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud LEGRAND <arnaud@ulteo.com> 2010
# Author Samuel BOVEE <samuel@ulteo.com> 2010
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

unset LANG

HA_CONF_DIR=/etc/ulteo/ovd/ha
HA_DATA_DIR=/usr/share/ulteo/ovd/ha
HA_VARLIB_DIR=/var/lib/ulteo/ovd

DRBD_RESOURCE="sm0"
DRBD_CONF=/etc/drbd.d/$DRBD_RESOURCE.res
HEARTBEAT_CONF_DIR=/etc/ha.d
SM_LOG_DIR=/var/log/ulteo/sessionmanager

. $HA_DATA_DIR/utils.sh

make_default_conf() {
    local AUTHKEY=$1

    NIC_NAME=$(grep -E "^NIC_NAME" $HA_CONF_DIR/resources.conf | sed -r "s/^NIC_NAME *= *([a-zA-Z0-9]*) ?.*$/\1/")
    GATEWAY=$(route -n | grep '^0\.0\.\0\.0[ \t]\+[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]\+[ \t]\+0\.0\.0\.0[ \t]\+[^ \t]*G[^ \t]*[ \t]' | awk '{print $2}')
    NIC_ADDR=$(ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $4}')

    # Heartbeat authkeys conf
    sed "s/%AUTHKEY%/$AUTHKEY/" $HA_DATA_DIR/conf/authkeys > $HEARTBEAT_CONF_DIR/authkeys
    chmod 600 $HEARTBEAT_CONF_DIR/authkeys

    # Heartbeat conf
    sed -e "s/%GATEWAY%/$GATEWAY/" -e "s/%NIC_NAME%/$NIC_NAME/" \
        -e "s/%NIC_ADDR%/$NIC_ADDR/" -e "s/%HOSTNAME%/$HOSTNAME/" \
        -e "s,%LOGDIR%,$SM_LOG_DIR," $HA_DATA_DIR/conf/ha.cf > $HEARTBEAT_CONF_DIR/ha.cf

    # DRBD conf
    local device=/dev/drbd0;
    local disk=$(losetup -f);
    sed -e "s/%RESOURCE%/$DRBD_RESOURCE/" -e "s,%DEVICE%,$device," \
        -e "s,%LOOP%,$disk," -e "s/%AUTH_KEY%/$AUTHKEY/" \
        -e "s/%HOSTNAME%/$HOSTNAME/" -e "s/%NIC_ADDR%/$NIC_ADDR/" \
            $HA_DATA_DIR/conf/$DRBD_RESOURCE.res > $DRBD_CONF
}

add_node() {
    OTHER_IP=$1
    OTHER_HOSTNAME=$2

    sed -i "/^}/i \\
    on $OTHER_HOSTNAME {\n\
        address $OTHER_IP:7788;\n\
    }" $DRBD_CONF
    sed -e "/ucast/ a\ucast $NIC_NAME $OTHER_IP" \
        -e "/node/ a\node $OTHER_HOSTNAME" -i $HEARTBEAT_CONF_DIR/ha.cf
}

case $1 in

    reload_master)
        if [ -n "$2" ] && [ -n "$3" ] && [ -n "$4" ]; then
            service heartbeat stop
            make_default_conf $4
            add_node $2 $3
            service heartbeat start
        else
            echo "$1 <IP> <HOSTNAME> <AUTHKEY>"
            exit 2
        fi
    ;;

    reload_master_excl)
        if [ -n "$4" ]; then
            service heartbeat stop
            make_default_conf $4
            service heartbeat start
        else
            echo "$1 <AUTHKEY>"
            exit 2
        fi
    ;;

    register)
        if  [ -n "$2" ] && [ -n "$3" ] && [ -n "$4" ]; then
            service mysql stop || true
            service apache2 stop
            service heartbeat stop
            rm -f /var/lib/heartbeat/crm/*
            make_default_conf $4
            add_node $2 $3
            service heartbeat start
        else
            echo "$1 <IP> <HOSTNAME> <AUTHKEY>"
            exit 2
        fi
    ;;

    reload_vip)
        VIP="$2"
        if [ -n $VIP ]; then
            valid_ip $VIP && \
                crm_resource --resource vip --set-parameter ip --parameter-value $VIP
        else
            echo "reload_vip <VIP>"
            exit 2
        fi
    ;;

esac

sleep 120
exit 0
