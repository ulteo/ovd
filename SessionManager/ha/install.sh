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
echo -e "\033[31;1m[REQUIRE] \033[0m Package ulteo-ovd-session-manager must be installed before executing this script.";

unset LANG

SOURCES_PATH=$PWD/sources

DRBD_RESOURCE="sm0"
DRBD_CONF=/etc/drbd.d/$DRBD_RESOURCE.res
DRBD_DEVICE=/dev/drbd0
DRBD_MOUNT_SM0=/var/cache/ulteo/ha/drbd

SM_SPOOL_DIR=/var/spool/ulteo/sessionmanager
MYSQL_DATABASES_LOCATION=/var/lib/mysql

HEARTBEAT_HA_CONF=/etc/ha.d/ha.cf
HEARTBEAT_AUTHKEYS_CONF=/etc/ha.d/authkeys
HEARTBEAT_OCF_ROOT=/usr/lib/ocf/resource.d/heartbeat
HEARTBEAT_CIB_LOCATION=/var/lib/heartbeat/crm

AUTH_KEY=`date '+%m%d%y%H%M%S'`
GATEWAY=`route -n | grep '^0\.0\.\0\.0[ \t]\+[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]\+[ \t]\+0\.0\.0\.0[ \t]\+[^ \t]*G[^ \t]*[ \t]' | awk '{print $2}'`

MYSQL_DB=/var/lib/mysql

ULTEO_HA_ROOT=/etc/ulteo/ovd/ha
ULTEO_VBD_BIN_FILE=$ULTEO_HA_ROOT/vbd0.bin
ULTEO_HA_CONF=$ULTEO_HA_ROOT/resources.conf

SM_LOG_DIR=/var/log/ulteo/sessionmanager
ULTEO_WEB_ADMIN_ROOT=/usr/share/ulteo/sessionmanager

###############################################################################
# UTILS FUNCTIONS
##

function valid_ip()
{
    local ip=$1
    local _ifs=$IFS

    if [[ $ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        IFS='.'
        ip=($ip)
        IFS=$_ifs
        [[ ${ip[0]} -le 255 && ${ip[1]} -le 255 \
            && ${ip[2]} -le 255 && ${ip[3]} -le 255 ]]
        return $?
    fi
    return 1
}

function execute()
{
    $1 2> /dev/null
    ret=$?
    if $ret; then
        echo -e "\033[34;1m[OK] \033[0m $1";
    else
        echo -e "\033[31;1m[FAILED] \033[0m $1";
    fi
    return $?
}

function set_master_ip()
{
	unset MASTER_IP
	while [ -z "$MASTER_IP" ]; do
		echo -n "Please, give the master host IP: "
		read MASTER_IP
		[ -z "$MASTER_IP" ] && continue
		valid_ip "$MASTER_IP" && echo "You have selected "$MASTER_IP" as master ip !"
	done
}

# LIST NETWORK INTERFACES & CHOOSE ONE
function set_netlink()
{
	NICS=(`ifconfig -s | tr -s ' ' | cut -d' ' -f1 | grep -E "eth[0-9]"`)
	len=${#NICS[*]}

	if [ $len != 0 ]; then
	    echo -e "\nNIC detected :";
    else
		echo -e "\033[31;1m[FAILED] \033[0m No nic detected, please configure your network";
		exit 2;
	fi

	for i in {0..$len}; do
		tmp=`ifconfig ${NICS[$i]} | grep "inet addr" | tr -s ' '| cut -d' ' -f3-`
		echo -e "\t\a[$i] ${NICS[$i]} ("$tmp")"
	done

    unset NIC_INFOS
	while [ -z "$NIC_INFOS" ]; do
		echo -n "Choose nic number: "
		read var_nic
		[ -z "$var_nic" ] && continue
		[[ $var_nic != [0-9] ]] && \
            [ $var_nic -ge 0 -a $var_nic -le $len ] && \
			NIC_INFOS=${NICS[$var_nic]}
	done

	NIC_NAME=${NICS[$NIC_INFOS]}
	NIC_ADDR=`ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $4}'`
	NIC_BCAST=`ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $6}'`
	NIC_MASK=`ifconfig $NIC_NAME | awk -F":| +" '/inet addr/{print $8}'`

	echo -e "\033[36;1m[INFO] \033[0m You have selected NIC $NIC_NAME"
	echo "Addr:" $NIC_ADDR
	echo "Mask:" $NIC_MASK
	echo "Broadcast:" $NIC_BCAST
}

function set_virtual_ip()
{

	var_mask=`echo "$NIC_MASK" | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`
	var_addr=`echo "$NIC_ADDR" | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`

	i=0
	for n in $var_addr; do
		tab_ip[$i]=$n
		let i++
	done

	# Determine Address class of VIP
	i=0
	for n in $var_mask; do
		if [ $n == "0" ]; then
			tab_ip[$i]=-1
			break;
		fi
		let i++
	done

	# Select a VIP
	while [ 1 ]; do
		echo -n "Please, give the virtual IP you want: "
		read var_vip
		if [ -z "$var_vip" ]; then
			continue;
		fi
		valid_ip $var_vip
		if [[ $? == "0" ]]; then
			var_vip2=`echo "$var_vip" | awk '{for(i=1;i<=NF;i++) printf " " $i}' FS=.`
			i=0
			for n in $var_vip2; do
				if [ "${tab_ip[$i]} " -eq "-1" ]; then
					tab_vip[$i]=$n
				elif [ "${tab_ip[$i]}" -eq "$n" ]; then
					tab_vip[$i]=$n
				else
					break;
				fi
				let i++
			done
			let i--
			if [ "$i" == "3" ]; then
				VIP=`echo ${tab_vip[0]}"."${tab_vip[1]}"."${tab_vip[2]}"."${tab_vip[3]}`
				[ "$VIP" != "$NIC_ADDR" ] && break
			fi
		fi
		echo "The ip address you have submit is forbidden !"
	done

	echo "You have selected" $VIP "as virtual IP"
}

###############################################################################
# DRBD
##
function drbd_install()
{
	echo -e "\033[36;1m[INFO] \033[0m Installing depends..."
	apt-get install heartbeat ntpdate drbd8-utils make gcc

	########################
	# [OLD VERSION OF LUCID]
	# drbd8-source drbd8-module-source build-essential module-assistant
	# Performed kernel module drbd
	#echo -e "\033[36;1m[INFO] \033[0m module-assistant auto-install drbd8"
	#module-assistant auto-install drbd8
	#echo -e "\033[36;1m[INFO] \033[0m m-a -f get drbd8-module-source."
	#m-a -f get drbd8-module-source
	########################

	modprobe drbd

	# Stopping all services
	service mysql stop &> /dev/null
	service apache2 stop &> /dev/null
	service heartbeat stop &> /dev/null

	########################
	# [OLD VERSION OF LUCID]
	# verify install modprobe drbd in /etc/modprobes.d/drbd.conf
	# insert in /etc/module drbd
    #NIC_NAME=0
	#if [ -e $MODULES ] && [ -w $MODULES ]; then
	#	cat $MODULES | awk ' NF > 0 {if ( $1 != "drbd" ) {print}}' > /tmp/tmp_etc-modules.txt
	#	echo "drbd" >> /tmp/tmp_etc-modules.txt
	#	mv /tmp/tmp_etc-modules.txt $MODULES
	#fi
	########################

	# Create a virtual block device of 250M
	echo -e "\033[36;1m[INFO] \033[0m Create a virtual block device of 250 MBytes"
	mkdir -p $ULTEO_HA_ROOT

	# Create virtual block device
	dd if=/dev/zero of=$ULTEO_VBD_BIN_FILE count=500k
    DRBD_LOOP=`losetup -f`
	losetup $DRBD_LOOP $ULTEO_VBD_BIN_FILE

	# Create conf /etc/drbd.d/sm0.res
	echo -e "\033[36;1m[INFO] \033[0m Create conf $DRBD_CONF"
    cat > $DRBD_CONF << EOF
resource $DRBD_RESOURCE {
  device    $DRBD_DEVICE;
  disk      $DRBD_LOOP;
  meta-disk internal;

  disk {
on-io-error   detach;
  }

  startup {
wfc-timeout  10;
degr-wfc-timeout 5;
  }
  syncer {
# rate after al-extents use-rle cpu-mask verify-alg csums-alg
rate 10M;
verify-alg sha1;
  }
  net {
cram-hmac-alg sha1;
shared-secret "$AUTH_KEY";
after-sb-0pri discard-older-primary;
after-sb-1pri call-pri-lost-after-sb;
after-sb-2pri call-pri-lost-after-sb;
#allow-two-primaries;
  }
  on $HOSTNAME {
	address   $NIC_ADDR:7788;
  }

}
EOF

	sleep 10
	mkdir -p $DRBD_MOUNT_SM0
	if [ $1 == "M" ]; then
		# Initialize vbd0 with drbd
		execute "drbdadm down $DRBD_RESOURCE"
		execute "drbdadm create-md $DRBD_RESOURCE"
		execute "drbdadm up $DRBD_RESOURCE"

		# Check if overwrite of peer is necessary
		execute "drbdadm -- --overwrite-data-of-peer primary $DRBD_RESOURCE"

		#Format resource
		execute "mkfs.ext3 $DRBD_DEVICE"

		# Copy MySQL DB to VDB0
		execute "mount $DRBD_DEVICE $DRBD_MOUNT_SM0"
		cp -prf $MYSQL_DB $SM_SPOOL_DIR $DRBD_MOUNT_SM0
		umount $DRBD_MOUNT_SM0

		execute "drbdadm down $DRBD_RESOURCE"
	else
		execute "drbdadm create-md $DRBD_RESOURCE"
		execute "drbdadm up $DRBD_RESOURCE"
		execute "drbdadm adjust $DRBD_RESOURCE"

		#Synchronize data
		var_role=`drbdadm role $DRBD_RESOURCE | grep -E 'Secondary/Primary|Secondary/Secondary'`
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
		execute "drbdadm down $DRBD_RESOURCE"
	fi
}

###############################################################################
# HEARTBEAT
##

function heartbeat_install()
{
	# Create logs files
	echo -e "\033[36;1m[INFO] \033[0m Creating logs..."
	mkdir -p $SM_LOG_DIR
	touch $SM_LOG_DIR/ha.log $SM_LOG_DIR/ha-hb.log $SM_LOG_DIR/ha-debug-hb.log
	chown www-data:www-data $ULTEO_LOG_HA
	chown hacluster:haclient $SM_LOG_DIR/ha-hb.log $SM_LOG_DIR/ha-debug-hb.log

	# Copy of ha.cf [HEARTBEAT]
	echo -e "\033[36;1m[INFO] \033[0m Copy of ha.cf [HEARTBEAT]"
    cat > $HEARTBEAT_HA_CONF << EOF
autojoin any
use_logd off
logfile /var/log/ulteo/sessionmanager/ha-hb.log
debugfile /var/log/ulteo/sessionmanager/ha-debug-hb.log
logfacility local0
udpport 694
keepalive 1
warntime 15
deadtime 3
initdead 30
ping $GATEWAY

ucast $NIC_NAME $NIC_ADDR
node $HOSTNAME
crm yes
EOF

	# Copy of authkeys [HEARTBEAT]
	echo -e "\033[36;1m[INFO] \033[0m Copy of authkeys [HEARTBEAT]."
    echo -e "auth 1\n1 sha1 $AUTH_KEY" > $HEARTBEAT_AUTHKEYS_CONF
	chown root:root $HEARTBEAT_AUTHKEYS_CONF
	chmod 600 $HEARTBEAT_AUTHKEYS_CONF

	# Copy of new resource OCF manager [OCF]
	echo -e "\033[36;1m[INFO] \033[0m Copy of new resource OCF manager [OCF]."
	cp $SOURCES_PATH/ocf/mysql-ulteo $HEARTBEAT_OCF_ROOT;
	chown root:root $HEARTBEAT_OCF_ROOT/mysql-ulteo;

	# Delete old cibs [HEARTBEAT]
	[ -e $HEARTBEAT_CIB_LOCATION/cib.xml ] && rm -f $HEARTBEAT_CIB_LOCATION/*

	# RESTART heartbeat
	service heartbeat start # start or restart ???
}


function heartbeat_cib_install()
{
	sleep 10
	echo -e "\033[36;1m[INFO] \033[0m Preparing resources. It may takes few minutes..."
	echo -ne "\033[33;1m[INFO] \033[0m Waiting connection to CRM."
	local t=0

	while [ -n "$var_cib_node" ]; do
		var_cib_node=`crm_mon -1 | grep -E "[1-9] Nodes configured"`
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

	crm configure << EOF
property \$id="cib-bootstrap-options" no-quorum-policy="ignore" stonith-enabled="false" cluster-infrastructure="Heartbeat" symmetric-cluster="true"
rsc_defaults \$id="rsc-options" resource-stickiness="0"
primitive mount_sm0 ocf:heartbeat:Filesystem params device="/dev/drbd0" directory="'$DRBD_MOUNT_SM0'" fstype="ext3"
primitive mount_sm0_databases ocf:heartbeat:Filesystem params device="'$DRBD_MOUNT_SM0/mysql'" directory="'$MYSQL_DATABASES_LOCATION'" fstype="ext3" options="bind"
primitive mount_sm0_spool ocf:heartbeat:Filesystem params device="'$DRBD_MOUNT_SM0/sessionmanager'" directory="'$SM_SPOOL_DIR'" fstype="ext3" options="bind"
primitive r-sql ocf:heartbeat:mysql-ulteo params binary=/usr/sbin/mysqld datadir=/var/lib/mysql enable_creation=0 group=mysql user=mysql log=/var/log/mysql.log pid=/var/run/mysqld/mysqld.pid socket=/var/run/mysqld/mysqld.sock user=mysql op monitor interval="10s" timeout="20s"
primitive r-web ocf:heartbeat:apache params httpd="/usr/sbin/apache2" configfile="/etc/apache2/apache2.conf" op monitor interval="20s" meta is-managed="true"
primitive sm0 ocf:linbit:drbd params drbd_resource="'$DRBD_RESOURCE'" op monitor interval="29s" role="Master" op monitor interval="30s" role="Slave"
primitive vip ocf:heartbeat:IPaddr2 params ip="'$VIP'" cidr_netmask="32" op monitor interval="10s"
group group_applis mount_sm0 mount_sm0_databases mount_sm0_spool r-sql r-web vip params colocated="true" ordered="true" meta target-role="Started"
ms ms_sm0 sm0 meta master-max="1" clone-max="2" clone-node-max="1" master-clone-max="1" notify="true" target-role="Master"
colocation coloc_ms_gr_applis inf: group_applis ms_sm0:Master
order order_ms_fs inf: ms_sm0:promote mount_sm0:start
commit
EOF
	echo -e "\033[36;1m[INFO] \033[0m Resource configurations submitted !"
}


###############################################################################
# OTHERS
##

function set_conf_and_script()
{
	echo -e "\033[36;1m[INFO] \033[0m INIT SCRIPT not installed !";
	echo "WWW_USER=www-data" > $ULTEO_HA_CONF;
	echo "NIC_NAME=$NIC_NAME" >> $ULTEO_HA_CONF;
	[ $1 == "M" ] && echo "VIP=$VIP" >> $ULTEO_HA_CONF;
}

function hashell_install()
{
	make -C  $SOURCES_PATH/HAshell/
	make install -C $SOURCES_PATH/HAshell/
	echo -e "\033[36;1m[INFO] \033[0m Install HAshell done !"
}

# Copy the web admin/ha directory
function set_ha_web_interface()
{
	echo -e "\033[36;1m[INFO] \033[0m Adding HA web interface at $ULTEO_WEB_ADMIN_ROOT/admin"
	cp -a $SOURCES_PATH/web/admin $ULTEO_WEB_ADMIN_ROOT/
	cp -a $SOURCES_PATH/web/modules $ULTEO_WEB_ADMIN_ROOT/
}

# update-rc.d -f remove mysql/apache2 necessary
function set_init_script2()
{
	cp $SOURCES_PATH/conf/ulteo_ha /etc/init.d/
	update-rc.d ulteo_ha defaults 91
}

# Slave only : register to Master host
function set_ha_register_to_master()
{
	response=$(wget --no-check-certificate https://$MASTER_IP/ovd/admin/ha/registration.php \
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

[ -z "$HOSTNAME" ] && echo -e "\033[31;1m[FAILED] \033[0m No Hostname found !" && exit 2
[ -z "$GATEWAY" ] && echo -e "\033[31;1m[FAILED] \033[0m No gateway found !" && exit 2

# choose MASTER/SLAVE
while [ 1 ]; do
	echo -n "Install this session manager as master or slave [m/s]: "
	read var_master
	case $var_master in
		master | m | M)
			echo -e "\033[36;1m[INFO] \033[0m Your host will become the master"

	        set_netlink
	        set_virtual_ip
	        drbd_install "M"
	        heartbeat_install
	        heartbeat_cib_install
	        set_conf_and_script "M"
	        hashell_install
	        service mysql stop
	        service apache2 stop
	        set_ha_web_interface
	        set_init_script2
	        crm_attribute --type nodes --node $HOSTNAME --name standby --update off

	        echo -e "\n\033[34;1m###############################################"
	        echo -e "#\033[31;1m INSTALLATION SUCCESSFULL [MASTER]"
	        echo -e "\033[37;1mYou You must enable the HA module in configuration before !\033[0m"
	        echo -e "\033[37;1mThen you can get web interface at: https://$VIP/ovd/admin/ha/status.php\033[0m\n"
			break;
		;;

		S | s | slave)
			echo -e "\033[36;1m[INFO] \033[0m Your host will become a slave"
	        set_netlink
	        set_master_ip
	        drbd_install "S"
	        heartbeat_install
	        set_conf_and_script "S"
	        hashell_install
	        set_ha_web_interface
	        set_ha_register_to_master
	        set_init_script2

		    service mysql start ;
		    service apache2 start ;

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
