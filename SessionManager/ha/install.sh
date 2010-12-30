#!/bin/bash

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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#

set -e
unset LANG

DRBD_RESOURCE="sm0"
DRBD_CONF=/etc/drbd.d/$DRBD_RESOURCE.res
DRBD_DEVICE=/dev/drbd0
DRBD_MOUNT_DIR=/var/cache/ulteo/ha/drbd

HEARTBEAT_CONF_DIR=/etc/ha.d
HEARTBEAT_CRM_DIR=/var/lib/heartbeat/crm

HA_CONF_DIR=/etc/ulteo/ovd/ha
HA_VARLIB_DIR=/var/lib/ulteo/ovd
HA_LOG=/var/log/ha_install.log

SM_LOG_DIR=/var/log/ulteo/sessionmanager
SM_SPOOL_DIR=/var/spool/ulteo/sessionmanager

MYSQL_DB=/var/lib/mysql

AUTH_KEY=`date '+%m%d%y%H%M%S'`

# load util functions
. ./utils.sh

rm -rf $HA_CONF_DIR $HA_VARLIB_DIR
mkdir -p $HA_CONF_DIR $HA_VARLIB_DIR

set_netlink()
{
	NICS=(`ifconfig -s | tr -s ' ' | cut -d' ' -f1 | grep -E "eth[0-9]"`)
	len=${#NICS[*]}

	if [ $len != 0 ]; then
		echo -e "NIC detected :";
	else
		die "no nic detected, please configure your network";
	fi

	for i in $(seq 1 $len); do
		nic=$(ifconfig ${NICS[$i-1]} | grep "inet addr" | tr -s ' '| cut -d' ' -f3-)
		[ -n "$nic" ] && echo -e "\t\a[$i] ${NICS[$i-1]} (${nic})"
	done

	unset NIC_INFOS
	while [ -z "$NIC_INFOS" ]; do
		echo -n "Choose nic number: " && read var_nic
		[ -n "$var_nic" ] \
			&& [[ $var_nic == [0-9] ]] \
			&& [ $var_nic -ge 0 -a $var_nic -le $len ] \
			&& NIC_INFOS="${NICS[$var_nic-1]}"
	done

	NIC_NAME=${NICS[$NIC_INFOS]}
	NIC_ADDR=`ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $4}'`
	NIC_MASK=`ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $8}'`

	sed "s/%NIC_NAME%/$NIC_NAME/" conf/resources.conf > $HA_CONF_DIR/resources.conf
	info "NIC $NIC_NAME selected"
}


set_virtual_ip()
{
	while [ -z "$VIP" ]; do
		echo -n "Give the virtual IP: " && read vip
		[ -n "$vip" ] && valid_vip $NIC_MASK $NIC_ADDR $vip && VIP=$vip
	done
	sed -i "s/^.*VIP.*$/VIP=${VIP}/" $HA_CONF_DIR/resources.conf
	info "Virtual IP: $VIP"
}


function set_master_ip()
{
	while [ -z "$MIP" ]; do
		echo -n "Give the master host IP: " && read mip
		[ -n "$mip" ] && valid_ip "$mip" && MIP=$mip
	done
	info "Master IP: $mip"
}


function drbd_install()
{
	modprobe drbd

	info "stop all services"
	service mysql stop >> $HA_LOG 2>&1 || true
	service apache2 stop >> $HA_LOG
	service heartbeat stop >> $HA_LOG

	info "Create a virtual block device of 250 MBytes"
	dd if=/dev/zero of=$HA_VARLIB_DIR/vbd0.bin count=500k 2>> $HA_LOG

	local drbd_loop=$(losetup -f)
	losetup $drbd_loop $HA_VARLIB_DIR/vbd0.bin
	info "Connect $HA_VARLIB_DIR/vbd0.bin to $drbd_loop"

	# Create conf /etc/drbd.d/sm0.res
	info "Create conf $DRBD_CONF"
	sed -e "s/%RESOURCE%/$DRBD_RESOURCE/" -e "s,%DEVICE%,$DRBD_DEVICE," \
		-e "s,%LOOP%,$drbd_loop," -e "s/%AUTH_KEY%/$AUTH_KEY/"  \
		-e "s/%HOSTNAME%/$HOSTNAME/" -e "s/%NIC_ADDR%/$NIC_ADDR/" \
		conf/$DRBD_RESOURCE.res > $DRBD_CONF

	# prepare and clean drbd
	umount $DRBD_DEVICE 2>> $HA_LOG || true
	execute "drbdadm down $DRBD_RESOURCE"

	# create drbd resource
	execute "drbdadm create-md $DRBD_RESOURCE"
	execute "drbdadm up $DRBD_RESOURCE" || true

	if [ $1 == "M" ]; then
		# Check if overwrite of peer is necessary
		execute "drbdadm -- --overwrite-data-of-peer primary $DRBD_RESOURCE"

		# Create ext3 FS
		execute "mkfs.ext3 $DRBD_DEVICE"

		# Copy MySQL DB to VDB0
		mkdir -p $DRBD_MOUNT_DIR
		mount $DRBD_DEVICE $DRBD_MOUNT_DIR
		cp -a $MYSQL_DB $SM_SPOOL_DIR $DRBD_MOUNT_DIR
		umount $DRBD_MOUNT_DIR

	elif [ $1 == "S" ]; then
		execute "drbdadm adjust $DRBD_RESOURCE" || true

		# Synchronize data
		var_role=`drbdadm role $DRBD_RESOURCE | grep -E 'Secondary/Primary|Secondary/Secondary' || true`
		if [ -n "$var_role" ]; then
			info "Master connected, synchronizing $DRBD_RESOURCE data..."
			execute "drbdadm invalidate-remote $DRBD_RESOURCE"
			for i in $(seq 0 60); do
				[ $(drbdadm dstate $DRBD_RESOURCE) -eq "UpToDate/UpToDate" ] && break
				sleep 1
			done
		fi
	fi
	execute "drbdadm down $DRBD_RESOURCE"
}


function heartbeat_install()
{
	# create logs files
	mkdir -p $SM_LOG_DIR
	touch $SM_LOG_DIR/ha.log $SM_LOG_DIR/ha-hb.log $SM_LOG_DIR/ha-debug-hb.log
	chown www-data:www-data  $SM_LOG_DIR/ha.log
	chown hacluster:haclient $SM_LOG_DIR/ha-hb.log $SM_LOG_DIR/ha-debug-hb.log

	info "generate ha.cf file"
	sed -e "s/%GATEWAY%/$GATEWAY/" -e "s/%NIC_NAME%/$NIC_NAME/" \
		-e "s/%NIC_ADDR%/$NIC_ADDR/" -e "s/%HOSTNAME%/$HOSTNAME/" \
		-e "s,%LOGDIR%,$SM_LOG_DIR," conf/ha.cf > $HEARTBEAT_CONF_DIR/ha.cf

	info "generate authkeys file"
	echo -e "auth 1\n1 sha1 $AUTH_KEY" > $HEARTBEAT_CONF_DIR/authkeys
	chmod 600 $HEARTBEAT_CONF_DIR/authkeys

	# Copy resource for OCF manager
	cp conf/mysql-ovd /usr/lib/ocf/resource.d/heartbeat/

	# Delete old cibs
	[ -e $HEARTBEAT_CRM_DIR/cib.xml ] && rm -f $HEARTBEAT_CRM_DIR/*

	service heartbeat start >> $HA_LOG 2>&1
}


function heartbeat_cib_install()
{
	echo -ne "\033[36;1m[INFO] \033[0m Waiting connection to CRM. It may take some time"
	for i in $(seq 1 60); do
		[ $i -eq 60 ] && die "Connection timeout to the cluster"
		echo -n "." && sleep 5
		crm_mon -1 | grep -E "[1-9] Nodes configured" && break || true
	done
	info "Connection to CRM done."

	execute "crm_attribute --type nodes --node $HOSTNAME --name standby --update on"
	info "submit resource configurations"
	sed -e "s,%MOUNT_DIR%,$DRBD_MOUNT_DIR," -e "s,%MYSQL_DB%,$MYSQL_DB," \
		-e "s,%SM_SPOOL_DIR%,$SM_SPOOL_DIR," -e "s/%DRBD_RESOURCE%/$DRBD_RESOURCE/" \
		-e "s/%VIP%/$VIP/" conf/crm.conf | crm configure 2>> $HA_LOG
	execute "crm_attribute --type nodes --node $HOSTNAME --name standby --update off"
}


function hashell_install()
{
	info "install HAshell"
	make install -C shell >> $HA_LOG
}


# update-rc.d -f remove mysql/apache2 necessary
function set_init_script()
{
	info "install init script"
	cp ulteo_ha /etc/init.d/
	update-rc.d ulteo_ha defaults >> $HA_LOG
}


# Slave only : register to Master host
function set_ha_register_to_master()
{
	info "register server to the SM"
	response=$(wget --no-check-certificate --post-data="action=register&hostname=$HOSTNAME" \
		https://$MIP/ovd/admin/ha/registration.php -O - 2>> $HA_LOG || true)
	[ "$response" = "ok" ] || die "request to master failed"
}

###############################################################################
# BEGINING
##

dpkg -l ulteo-ovd-session-manager > $HA_LOG
[ $? -eq 0 ] || die "package ulteo-ovd-session-manager is required"
[ -x $(which mysql) ] || die "mysql is required"
[ -x $(which drbdadm) ] || die "drbd is required"
[ -e /etc/init.d/heartbeat ] || die "hearbeat is required"

# TODO: demander les infos manquantes !
GATEWAY=`route -n | grep '^0\.0\.\0\.0[ \t]\+[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]\+[ \t]\+0\.0\.0\.0[ \t]\+[^ \t]*G[^ \t]*[ \t]' | awk '{print $2}'`
[ -z "$GATEWAY" ] && die "No gateway found"
[ -z "$HOSTNAME" ] && die "No Hostname found"

# choose MASTER/SLAVE
while :; do
	echo -n "Install this session manager as master or slave [m/s]: " && read CHOICE
	CHOICE=$(echo $CHOICE | tr 'A-Z' 'a-z')

	case $CHOICE in
		master | m)
			info "the host will become the master"

			set_netlink
			set_virtual_ip
			drbd_install "M"
			heartbeat_install
			heartbeat_cib_install
			hashell_install
			set_init_script

			echo -e "\n\033[37;1mYou You must enable the HA module in configuration before !\033[0m"
			echo -e "\033[37;1mThen you can get web interface at: https://$VIP/ovd/admin/ha/status.php\033[0m"
		;;

		slave | s)
			info "the host will become a slave"

			set_netlink
			set_master_ip
			drbd_install "S"
			heartbeat_install
			set_ha_register_to_master
			hashell_install
			set_init_script
			service mysql start || true
			service apache2 start
		;;

		*)
			echo -e "\033[31;1mYour response is not valid\033[0m"
			continue
		;;
	esac
	break
done
echo -e "\n\033[31;1mINSTALLATION SUCCESSFULL\033[0m\n"
