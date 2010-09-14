#!/bin/bash
set -e

# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Arnaud LEGRAND <arnaud@ulteo.com>
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

unset LANG

DRBD_RESOURCE="sm0"
DRBD_CONF=/etc/drbd.d/$DRBD_RESOURCE.res
DRBD_DEVICE=/dev/drbd0
DRBD_MOUNT_DIR=/var/cache/ulteo/ha/drbd

HEARTBEAT_CONF_DIR=/etc/ha.d
HEARTBEAT_CRM_DIR=/var/lib/heartbeat/crm

HA_CONF_DIR=/etc/ulteo/ovd/ha
HA_VARLIB_DIR=/var/lib/ulteo/ovd

SM_LOG_DIR=/var/log/ulteo/sessionmanager
SM_SPOOL_DIR=/var/spool/ulteo/sessionmanager
SM_DATA_DIR=/usr/share/ulteo/sessionmanager

MYSQL_DB=/var/lib/mysql

AUTH_KEY=`date '+%m%d%y%H%M%S'`
GATEWAY=`route -n | grep '^0\.0\.\0\.0[ \t]\+[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]\+[ \t]\+0\.0\.0\.0[ \t]\+[^ \t]*G[^ \t]*[ \t]' | awk '{print $2}'`

# load util functions
. ./utils.sh

rm -rf $HA_CONF_DIR $HA_VARLIB_DIR
mkdir -p $HA_CONF_DIR $HA_VARLIB_DIR

function drbd_install()
{
	modprobe drbd
	service mysql stop || true

	# Create a virtual block device of 250M
	echo -e "\033[36;1m[INFO] \033[0m Create a virtual block device of 250 MBytes"
	dd if=/dev/zero of=$HA_VARLIB_DIR/vbd0.bin count=500k # 260MB
	DRBD_LOOP=$(losetup -f)
	losetup $DRBD_LOOP $HA_VARLIB_DIR/vbd0.bin

	# Create conf /etc/drbd.d/sm0.res
	echo -e "\033[36;1m[INFO] \033[0m Create conf $DRBD_CONF"
	sed "s/%RESOURCE%/$DRBD_RESOURCE/" conf/$DRBD_RESOURCE.res | \
		sed "s,%DEVICE%,$DRBD_DEVICE," | sed "s,%LOOP%,$DRBD_LOOP," | \
		sed "s/%AUTH_KEY%/$AUTH_KEY/"  | sed "s/%HOSTNAME%/$HOSTNAME/" | \
		sed "s/%NIC_ADDR%/$NIC_ADDR/" > $DRBD_CONF

	mkdir -p $DRBD_MOUNT_DIR
	if [ $1 == "M" ]; then
		# Initialize vbd0 with drbd
		execute "drbdadm create-md $DRBD_RESOURCE"
		drbdadm up $DRBD_RESOURCE || true

		# Check if overwrite of peer is necessary
		execute "drbdadm -- --overwrite-data-of-peer primary $DRBD_RESOURCE"

		#Format resource
		execute "mkfs.ext3 $DRBD_DEVICE"

		# Copy MySQL DB to VDB0
		mount $DRBD_DEVICE $DRBD_MOUNT_DIR
		cp -a $MYSQL_DB $SM_SPOOL_DIR $DRBD_MOUNT_DIR
		umount $DRBD_MOUNT_DIR

	elif [ $1 == "S" ]; then
		execute "drbdadm create-md $DRBD_RESOURCE"
		execute "drbdadm up $DRBD_RESOURCE"
		execute "drbdadm adjust $DRBD_RESOURCE"

		#Synchronize data
		var_role=`drbdadm role $DRBD_RESOURCE | grep -E 'Secondary/Primary|Secondary/Secondary' || true`
		if [ -n "$var_role" ]; then
			echo -e "\033[36;1m[INFO] \033[0m  Master connected, synchronizing $DRBD_RESOURCE data..."
			execute "drbdadm invalidate-remote $DRBD_RESOURCE"
			local t=0
			while [ $t -lt 120 ]; do
				var_isfinish=`drbdadm dstate $DRBD_RESOURCE`
				[ "$var_isfinish" -eq "UpToDate/UpToDate" ] && break
				let t++
				sleep 2
			done
		fi
	fi
	execute "drbdadm down $DRBD_RESOURCE"
	service mysql start
}


function heartbeat_install()
{
	service heartbeat stop || true

	# Create logs files
	echo -e "\033[36;1m[INFO] \033[0m Creating logs..."
	mkdir -p $SM_LOG_DIR
	touch $SM_LOG_DIR/ha.log $SM_LOG_DIR/ha-hb.log $SM_LOG_DIR/ha-debug-hb.log
	chown www-data:www-data  $SM_LOG_DIR/ha.log
	chown hacluster:haclient $SM_LOG_DIR/ha-hb.log $SM_LOG_DIR/ha-debug-hb.log

	# create ha.cf
	echo -e "\033[36;1m[INFO] \033[0m Copy of ha.cf [HEARTBEAT]"
	sed "s/%GATEWAY%/$GATEWAY/" conf/ha.cf | \
		sed "s/%NIC_NAME%/$NIC_NAME/" | sed "s/%NIC_ADDR%/$NIC_ADDR/" | \
		sed "s/%HOSTNAME%/$HOSTNAME/" | sed "s,%LOGDIR%,$SM_LOG_DIR," \
		    > $HEARTBEAT_CONF_DIR/ha.cf

	# create authkeys
	echo -e "auth 1\n1 sha1 $AUTH_KEY" > $HEARTBEAT_CONF_DIR/authkeys
	chmod 600 $HEARTBEAT_CONF_DIR/authkeys

	# Copy resource for OCF manager
	cp conf/mysql-ocf /usr/lib/ocf/resource.d/heartbeat

	# Delete old cibs [HEARTBEAT]
	[ -e $HEARTBEAT_CRM_DIR/cib.xml ] && rm -f $HEARTBEAT_CRM_DIR/*

	service heartbeat start
}


function heartbeat_cib_install()
{
	sleep 10
	echo -e "\033[36;1m[INFO] \033[0m Preparing resources. It may takes few minutes..."
	echo -ne "\033[33;1m[INFO] \033[0m Waiting connection to CRM."
	local t=0

	while [ -z "$var_cib_node" ]; do
		var_cib_node=`crm_mon -1 | grep -E "[1-9] Nodes configured" || true`
		if [ $t -gt 360 ]; then
			echo -e "\n\033[31;1m[FAILED] \033[0m Connection timeout to the cluster. Please verify Hearbeat Installation";
			exit 2 ;
		fi
		echo -n "."
		let t+=5
		sleep 5
	done
	echo -e "\n\033[34;1m[OK] \033[0m Connection to CRM done."

	# CIB (ATTENTION tout n'est pas dynamique ici)
	echo -e "\033[36;1m[INFO] \033[0m Submitting resource configurations to CRM. It may takes few seconds..."
	crm node standby
	sleep 10

	sed "s/%MOUNT_DIR%/$DRBD_MOUNT_DIR/" conf/crm.conf | \
		sed "s/%MYSQL_DB%/$MYSQL_DB/" | sed "s/%SM_SPOOL_DIR%/$SM_SPOOL_DIR/" | \
		sed "s/%DRBD_RESOURCE%/$DRBD_RESOURCE/" | sed "s/%VIP%/$VIP/" |
	crm configure

	echo -e "\033[36;1m[INFO] \033[0m Resource configurations submitted !"
}


function set_conf_and_script()
{
	echo -e "\033[36;1m[INFO] \033[0m INIT SCRIPT not installed !";
	echo "WWW_USER=www-data" > $HA_CONF_DIR/resources.conf;
	echo "NIC_NAME=$NIC_NAME" >> $HA_CONF_DIR/resources.conf;
	[ $1 == "M" ] && echo "VIP=$VIP" >> $HA_CONF_DIR/resources.conf;
}


function hashell_install()
{
	make -C  shell
	make install -C shell
	echo -e "\033[36;1m[INFO] \033[0m Install HAshell done !"
}


# update-rc.d -f remove mysql/apache2 necessary
function set_init_script()
{
	cp ulteo_ha /etc/init.d/
	update-rc.d ulteo_ha defaults 91
}


# Slave only : register to Master host
function set_ha_register_to_master()
{
	response=$(wget --no-check-certificate https://$MASTER_IP/ovd/admin/ha/registration.php
         			--post-data="action=register&hostname=$HOSTNAME" -O -)
	if [ -z "$response" -o "$response" -eq 2 ]; then
		echo -e "\033[36;1m[INFO] \033[0m Server registration has been done successfully !";
	else
		echo -e "\033[31;1m[FAILED] \033[0m Request is corrupted !";
	fi
}

###############################################################################
# BEGINING
##

echo -e "\033[31;1m[REQUIRE] \033[0m Package ulteo-ovd-session-manager, mysql-server, drbd8-utils and heartbeat must be installed before executing this script.";

[ -z "$HOSTNAME" ] && echo -e "\033[31;1m[FAILED] \033[0m No Hostname found !" && exit 2
[ -z "$GATEWAY" ] && echo -e "\033[31;1m[FAILED] \033[0m No gateway found !" && exit 2

set_netlink
set_virtual_ip $NIC_MASK $NIC_ADDR

# choose MASTER/SLAVE
while true; do
	echo -n "Install this session manager as master or slave [m/s]: " && read CHOICE
	CHOICE=$(echo $CHOICE | tr 'A-Z' 'a-z')

	case $CHOICE in
		master | m)
			echo -e "\033[36;1m[INFO] \033[0m Your host will become the master"

			drbd_install "M"
			heartbeat_install
			heartbeat_cib_install
			set_conf_and_script "M"
			hashell_install
			set_init_script
			crm_attribute --type nodes --node $HOSTNAME --name standby --update off

			echo -e "\n\033[34;1m###############################################"
			echo -e "#\033[31;1m INSTALLATION SUCCESSFULL [MASTER]"
			echo -e "\033[37;1mYou You must enable the HA module in configuration before !\033[0m"
			echo -e "\033[37;1mThen you can get web interface at: https://$VIP/ovd/admin/ha/status.php\033[0m\n"
			break;
		;;

		slave | s)
			echo -e "\033[36;1m[INFO] \033[0m Your host will become a slave"

			drbd_install "S"
			heartbeat_install
			set_conf_and_script "S"
			hashell_install
			set_ha_register_to_master
			set_init_script

			echo -e "\n\033[34;1m###############################################"
			echo -e "#\033[31;1m INSTALLATION SUCCESSFULL [SLAVE]\033[0m\n"
			break;
		;;

		*)
			echo -e "\033[31;1m Your response is not valid !\033[0m"
			continue;
		;;
	esac
done
