#!/bin/bash

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

#########################################################################################################
# INIT
##
unset LANG

INIT_SCRIPT_DIR=/etc/init.d
ROOT_PATH=`echo $PWD`
SOURCES_PATH=`echo $ROOT_PATH/sources`
SERVICE_BIN=`which service`
IFCONFIG=`which ifconfig`
APT_GET=`which apt-get`
LOSETUP=`which losetup`
DRBDADM="" # Do not edit because not installed yet

DRBD_RESOURCE="sm0"
DRBD_CONF="/etc/drbd.d/$DRBD_RESOURCE.res"
DRBD_DEVICE=/dev/drbd0
DRBD_LOOP=`losetup -f`
DRBD_MOUNT_SM0=/data
DRBD_MOUNT_SM0_DATABASES=$DRBD_MOUNT_SM0/mysql
DRBD_MOUNT_SM0_SPOOL_SESSIONMANAGER=$DRBD_MOUNT_SM0/sessionmanager
ULTEO_SM_SPOOL_LOCATION=/var/spool/ulteo/sessionmanager
MYSQL_DATABASES_LOCATION=/var/lib/mysql
HEARTBEAT_HA_CONF=/etc/ha.d/ha.cf
HEARTBEAT_AUTHKEYS_CONF=/etc/ha.d/authkeys
HEARTBEAT_OCF_ROOT=/usr/lib/ocf/resource.d/heartbeat
HEARTBEAT_CIB_LOCATION=/var/lib/heartbeat/crm

AUTH_KEY=`date '+%m%d%y%H%M%S'`

GATEWAY=`route -n | grep '^0\.0\.\0\.0[ \t]\+[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]*\.[1-9][0-9]\+[ \t]\+0\.0\.0\.0[ \t]\+[^ \t]*G[^ \t]*[ \t]' | awk '{print $2}'`

# A vérifier selon distrib
MYSQL_DB=/var/lib/mysql

MODULES=/etc/modules
NIC_NAME=0
NIC_ADDR=0
NIC_MASK=0
NIC_BCAST=0

ULTEO_ROOT=/etc/ulteo
ULTEO_OVD_ROOT=$ULTEO_ROOT/ovd
ULTEO_HA_ROOT=$ULTEO_OVD_ROOT/ha
ULTEO_HA_INIT_SCRIPT_NAME=ulteo_ha
ULTEO_HA_INIT_SCRIPT_FILE=$ULTEO_HA_ROOT/$ULTEO_HA_INIT_SCRIPT_NAME
ULTEO_VBD_BIN_FILE=$ULTEO_HA_ROOT/vbd0.bin

ULTEO_LOG_DIR=/var/log/ulteo/sessionmanager
ULTEO_LOG_HA_DEBUG=$ULTEO_LOG_DIR/ha-debug-hb.log
ULTEO_LOG_HB=$ULTEO_LOG_DIR/ha-hb.log
ULTEO_LOG_HA=$ULTEO_LOG_DIR/ha.log
ULTEO_HASHELL_BIN=/usr/bin/HAshell
ULTEO_WEB_ADMIN_ROOT=/usr/share/ulteo/sessionmanager

ULTEO_HA_CONF=$ULTEO_HA_ROOT/resources.conf

VIP=0
MASTER=0
MYSQL=0
APACHE=0
SLAVE_NAME="smx"
SLAVE_IP=0
MASTER_IP=0




#########################################################################################################
# UTILS FUNCTIONS
##
function valid_ip()
{
	
    local  ip=$1
    local  stat=1

    if [[ $ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        OIFS=$IFS
        IFS='.'
        ip=($ip)
        IFS=$OIFS
        [[ ${ip[0]} -le 255 && ${ip[1]} -le 255 \
            && ${ip[2]} -le 255 && ${ip[3]} -le 255 ]]
        stat=$?
    fi
    return $stat
}

function execute()
{
    if [[ `$1 2> /dev/null` ]]; then
         echo -e "\033[34;1m[OK] \033[0m $1";
    else
        echo -e "\033[31;1m[FAILED] \033[0m $1";
		exit 2;
   fi
}

function set_master_ip()
{
	var_mip="";
	while [ 1 ]; do
		echo -n "Please, give the master host IP: "
		read var_mip
		if [ -z "$var_mip" ]; then 
			continue;
		fi
		valid_ip $var_mip
		if [[ $? == "0" ]]; then
			MASTER_IP=`echo $var_mip`
			echo "You have selected" $var_mip "as master ip !"
			break;
		fi
	done
}
#########################################################################################################
# LIST NETWORK INTERFACES & CHOOSE ONE
##
function set_netlink()
{
	
	NICS=(`$IFCONFIG -s | tr -s ' ' | cut -d' ' -f1 | grep -E "eth[0-9]"`)
	len=${#NICS[*]}

	if [ $len == 0 ]; then
		echo -e "\033[31;1m[FAILED] \033[0m No nic detected, please configure your network";
		exit 2;
	fi
	echo ""
	echo "NIC detected :";

	i=0
	while [ $i -lt $len ]; do
		tmp=`$IFCONFIG ${NICS[$i]} | grep "inet addr" | tr -s ' '| cut -d' ' -f3-`	
		echo -e "\t\a" "[$i] ${NICS[$i]} ("$tmp")"
		let i++
	done

	let i--
	while [ 1 ]; do
		echo -n "Choose nic number: "
		read var_nic
		if [ -z "$var_nic" ]; then 
			continue;
		fi
		if [[ $var_nic != [0-9] ]]; then 
			continue;
		elif [ $var_nic -ge 0 ] && [ $var_nic -le $i ]; then
			NIC_INFOS=${NICS[$var_nic]}
			break;
		fi
	done
	
	NIC_NAME=${NICS[$var_nic]}
	
	NIC_ADDR=`$IFCONFIG $NIC_NAME | awk -F":| +" '/inet addr/{print $4}'`
	NIC_BCAST=`$IFCONFIG $NIC_NAME | awk -F":| +" '/inet addr/{print $6}'`
	NIC_MASK=`$IFCONFIG $NIC_NAME | awk -F":| +" '/inet addr/{print $8}'`

	echo -e "\033[36;1m[INFO] \033[0m You have selected NIC $NIC_NAME"
	echo "Addr:" $NIC_ADDR
	echo "Mask:" $NIC_MASK
	echo "Broadcast:" $NIC_BCAST
	return 1
}

#########################################################################################################
# Virtual IP
##
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
				if [ "$VIP" != "$NIC_ADDR" ]; then
					break;
				fi
			fi
		fi
		echo "The ip address you have submit is forbidden !"
	done

	echo "You have selected" $VIP "as virtual IP"
	return 1
}

#########################################################################################################
# DRBD
##
function drbd_install()
{
	
	# Install packages
	echo -e "\033[36;1m[INFO] \033[0m Installing depends..."
	$APT_GET install heartbeat ntpdate drbd8-utils make gcc 
	
	########################
	# [OLD VERSION OF LUCID]
	# drbd8-source drbd8-module-source build-essential module-assistant
	# Performed kernel module drbd
	#echo -e "\033[36;1m[INFO] \033[0m module-assistant auto-install drbd8"
	#module-assistant auto-install drbd8
	#echo -e "\033[36;1m[INFO] \033[0m m-a -f get drbd8-module-source."
	#m-a -f get drbd8-module-source
	########################
	
	# Start kernel module drbd if not present (IMPORTANT)
	if [ -e /proc/drbd ]; then
		# Do nothing 
		echo "\033[36;1m[INFO] \033[0m DRBD kernel module is present !"
	else
		modprobe drbd;
	fi

	# Stopping all services
	$SERVICE_BIN mysql stop &> /dev/null
	$SERVICE_BIN apache2 stop &> /dev/null
	$SERVICE_BIN heartbeat stop &> /dev/null
	
	########################
	# [OLD VERSION OF LUCID]
	# verify install modprobe drbd in /etc/modprobes.d/drbd.conf
	# insert in /etc/module drbd
	#if [ -e $MODULES ] && [ -w $MODULES ]; then 
	#	cat $MODULES | awk ' NF > 0 {if ( $1 != "drbd" ) {print}}' > /tmp/tmp_etc-modules.txt
	#	echo "drbd" >> /tmp/tmp_etc-modules.txt
	#	mv /tmp/tmp_etc-modules.txt $MODULES
	#fi
	########################

	# Create a virtual block device of 250M
	echo -e "\033[36;1m[INFO] \033[0m Create a virtual block device of 250 MBytes"
	if [ ! -d $ULTEO_OVD_ROOT ]; then
		mkdir $ULTEO_OVD_ROOT
	fi
	mkdir $ULTEO_HA_ROOT

	# Create virtual block device
	dd if=/dev/zero of=$ULTEO_VBD_BIN_FILE count=500k
	$LOSETUP $DRBD_LOOP $ULTEO_VBD_BIN_FILE
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
	DRBDADM=`which drbdadm`

	if [ $MASTER -eq 1 ]; then
		# Initialize vbd0 with drbd
		echo -e "\033[36;1m[INFO] \033[0m  $DRBDADM down $DRBD_RESOURCE"
		$DRBDADM down $DRBD_RESOURCE
		echo -e "\033[36;1m[INFO] \033[0m  $DRBDADM create-md $DRBD_RESOURCE"
		$DRBDADM create-md $DRBD_RESOURCE
		echo -e "\033[36;1m[INFO] \033[0m  $DRBDADM up $DRBD_RESOURCE"
		$DRBDADM up $DRBD_RESOURCE

		# Check if overwrite of peer is necessary
		echo -e "\033[36;1m[INFO] \033[0m  $DRBDADM -- --overwrite-data-of-peer primary $DRBD_RESOURCE"
		$DRBDADM -- --overwrite-data-of-peer primary $DRBD_RESOURCE
		
		#Format resource
		echo -e "\033[36;1m[INFO] \033[0m  mkfs.ext3 $DRBD_DEVICE"
		mkfs.ext3 $DRBD_DEVICE
		
		# Copy MySQL DB to VDB0
		mkdir $DRBD_MOUNT_SM0


		echo -e "\033[36;1m[INFO] \033[0m  mount $DRBD_DEVICE $var_dir"
		mount $DRBD_DEVICE $DRBD_MOUNT_SM0
		
		mkdir $DRBD_MOUNT_SM0_DATABASES
		mkdir $DRBD_MOUNT_SM0_SPOOL_SESSIONMANAGER
		
		cp -rf $MYSQL_DB $DRBD_MOUNT_SM0
		chown -R mysql:mysql $DRBD_MOUNT_SM0_DATABASES/
		
		cp -rf $ULTEO_SM_SPOOL_LOCATION $DRBD_MOUNT_SM0
		chown -R www-data:www-data $DRBD_MOUNT_SM0_SPOOL_SESSIONMANAGER/
		
		umount $DRBD_MOUNT_SM0

		echo -e "\033[36;1m[INFO] \033[0m  $DRBDADM down $DRBD_RESOURCE"
		$DRBDADM down $DRBD_RESOURCE

		rm -rf $var_dir
	else
		echo -e "\033[36;1m[INFO] \033[0m  $DRBDADM up $DRBD_RESOURCE"
		$DRBDADM create-md $DRBD_RESOURCE
		$DRBDADM up $DRBD_RESOURCE
		$DRBDADM adjust $DRBD_RESOURCE

		mkdir $DRBD_MOUNT_SM0
		#Synchronize data
		var_role=`$DRBDADM role $DRBD_RESOURCE | grep -E 'Secondary/Primary|Secondary/Secondary'`
		if [ -n "$var_role" ]; then
				echo -e "\033[36;1m[INFO] \033[0m  Master connected, synchronizing $DRBD_RESOURCE data..."
				
				$DRBDADM invalidate-remote $DRBD_RESOURCE
				t=0
				while [ $t -lt 120 ]; do
					var_isfinish=`$DRBDADM dstate $DRBD_RESOURCE`
					if [ "$var_isfinish" -eq "UpToDate/UpToDate" ]; then
						break;
					fi
					let t++
					sleep 2
				done
		fi
		$DRBDADM down $DRBD_RESOURCE
	fi
	return 1
}

#########################################################################################################
# MYSQL
## NOTHING TO DO

#########################################################################################################
# HEARTBEAT
##
function heartbeat_install()
{

	# Create logs files
	echo -e "\033[36;1m[INFO] \033[0m Creating logs..."
	if [ -d $ULTEO_LOG_DIR ]; then
		touch $ULTEO_LOG_HA_DEBUG;
		chown hacluster:haclient $ULTEO_LOG_HA_DEBUG;
		touch $ULTEO_LOG_HB;
		chown hacluster:haclient $ULTEO_LOG_HB;
		touch $ULTEO_LOG_HA;
		chown www-data:www-data $ULTEO_LOG_HA;
	fi

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
cat > $HEARTBEAT_AUTHKEYS_CONF << EOF
auth 1
1 sha1 $AUTH_KEY
EOF
	chown root:root $HEARTBEAT_AUTHKEYS_CONF
	chmod 600 $HEARTBEAT_AUTHKEYS_CONF

	# Copy of new resource OCF manager [OCF]
	echo -e "\033[36;1m[INFO] \033[0m Copy of new resource OCF manager [OCF]."
	if [ -e $HEARTBEAT_OCF_ROOT ]; then
		cp $SOURCES_PATH/ocf/mysql-ulteo $HEARTBEAT_OCF_ROOT;
		chown root:root $HEARTBEAT_OCF_ROOT/mysql-ulteo;
	else
		echo -e "\033[31;1m[FAILED] \033[0m Heartbeat / Pacemaker for lucid seams not be installed. Please check OCF resources !"
		exit 2;
	fi

	# Delete old cibs [HEARTBEAT]
	if [ -e $HEARTBEAT_CIB_LOCATION/cib.xml ]; then 
		rm $HEARTBEAT_CIB_LOCATION/* ;
	fi

	# RESTART heartbeat 
	$SERVICE_BIN heartbeat start
	return 1
}


function heartbeat_cib_install()
{

	sleep 10
	echo -e "\033[36;1m[INFO] \033[0m Preparing resources. It may takes few minutes..."
	echo -ne "\033[33;1m[INFO] \033[0m Waiting connection to CRM."
	t=0
	while [ 1 ]; do
		var_cib_node=`crm_mon -1 | grep -E "[1-9] Nodes configured"`
		if [ -n "$var_cib_node" ]; then
			break;
		elif [ $t -gt 360 ]; then
			echo -e "\n\033[31;1m[FAILED] \033[0m Connection timeout to the cluster. Please verify Hearbeat Installation";
			exit 2 ;
		fi
		echo -n "." 
		let t+=5
		sleep 5
	done
	echo -e "\n\033[34;1m[OK] \033[0m Connection to CRM done."

	##########################################################################################
	# CIB (ATTENTION tout n'est pas dynamique ici)
	echo -e "\033[36;1m[INFO] \033[0m Submitting resource configurations to CRM. It may takes few seconds..."
	crm node standby
	sleep 10 

	echo 'property \$id="cib-bootstrap-options" no-quorum-policy="ignore" stonith-enabled="false" cluster-infrastructure="Heartbeat"' > /tmp/_cib.xml
	echo 'rsc_defaults \$id="rsc-options" resource-stickiness="0"' >> /tmp/_cib.xml
	
	
	echo 'primitive mount_sm0 ocf:heartbeat:Filesystem params device="/dev/drbd0" directory="'$DRBD_MOUNT_SM0'" fstype="ext3"' >> /tmp/_cib.xml
	echo 'primitive mount_sm0_databases ocf:heartbeat:Filesystem params device="'$DRBD_MOUNT_SM0_DATABASES'" directory="'$MYSQL_DATABASES_LOCATION'" fstype="ext3" options="bind"' >> /tmp/_cib.xml
	echo 'primitive mount_sm0_spool ocf:heartbeat:Filesystem params device="'$DRBD_MOUNT_SM0_SPOOL_SESSIONMANAGER'" directory="'$ULTEO_SM_SPOOL_LOCATION'" fstype="ext3" options="bind"' >> /tmp/_cib.xml
	
	
	echo 'primitive r-sql ocf:heartbeat:mysql-ulteo params binary=/usr/sbin/mysqld datadir=/var/lib/mysql enable_creation=0 group=mysql user=mysql log=/var/log/mysql.log pid=/var/run/mysqld/mysqld.pid socket=/var/run/mysqld/mysqld.sock user=mysql op monitor interval="10s" timeout="20s"' >> /tmp/_cib.xml
	echo 'primitive r-web ocf:heartbeat:apache params httpd="/usr/sbin/apache2" configfile="/etc/apache2/apache2.conf" op monitor interval="20s" meta is-managed="true"' >> /tmp/_cib.xml
	echo 'primitive sm0 ocf:linbit:drbd params drbd_resource="'$DRBD_RESOURCE'" op monitor interval="29s" role="Master" op monitor interval="30s" role="Slave"' >> /tmp/_cib.xml
	echo 'primitive vip ocf:heartbeat:IPaddr2 params ip="'$VIP'" cidr_netmask="32" op monitor interval="10s"' >> /tmp/_cib.xml
	echo 'group group_applis mount_sm0 mount_sm0_databases mount_sm0_spool r-sql r-web vip params colocated="true" ordered="true" meta target-role="Started"' >> /tmp/_cib.xml
	echo 'ms ms_sm0 sm0 meta master-max="1" clone-max="2" clone-node-max="1" master-clone-max="1" notify="true" target-role="Master"' >> /tmp/_cib.xml
	echo 'colocation coloc_ms_gr_applis inf: ms_sm0:Master group_applis' >> /tmp/_cib.xml
	echo 'order order_ms_fs inf: ms_sm0:promote mount_sm0:start' >> /tmp/_cib.xml
	echo 'commit' >> /tmp/_cib.xml

	crm configure < /tmp/_cib.xml
	sleep 1
	rm /tmp/_cib.xml
	echo -e "\033[36;1m[INFO] \033[0m Resource configurations submitted !"
	return 1
}
#########################################################################################################
# OTHERS
##
function set_conf_and_script()
{
	echo -e "\033[36;1m[INFO] \033[0m INIT SCRIPT not installed !";
	echo "WWW_USER=www-data" > $ULTEO_HA_CONF;
	echo "NIC_NAME=$NIC_NAME" >> $ULTEO_HA_CONF;
	if [ $MASTER -eq 1 ]; then
		echo "VIP=$VIP" >> $ULTEO_HA_CONF;
	fi
	return 1;
}

function hashell_install()
{
	make -C  $SOURCES_PATH/HAshell/
	make install -C $SOURCES_PATH/HAshell/
	echo -e "\033[36;1m[INFO] \033[0m Install HAshell done !"
	return 1
}

##########################################################################################
# Copy the web admin/ha directory
##
function set_ha_web_interface()
{
	echo -e "\033[36;1m[INFO] \033[0m Adding HA web interface at $ULTEO_WEB_ADMIN_ROOT/admin"
	cp -rf $SOURCES_PATH/web/admin $ULTEO_WEB_ADMIN_ROOT/
	chown -R root:root  $ULTEO_WEB_ADMIN_ROOT/admin
	cp -rf $SOURCES_PATH/web/modules $ULTEO_WEB_ADMIN_ROOT/
	chown -R root:root  $ULTEO_WEB_ADMIN_ROOT/modules/HA
	chown  root:root  $ULTEO_WEB_ADMIN_ROOT/modules/HA.php
	
	return 1
}
##########################################################################################
# update-rc.d -f remove mysql/apache2 necessary
##
function set_init_script2()
{
	
	cp $SOURCES_PATH/conf/ulteo_ha  /etc/init.d/;
	update-rc.d $ULTEO_HA_INIT_SCRIPT_NAME defaults 91
	return 1
}
##########################################################################################
# Slave only : register to Master host
##
function set_ha_register_to_master()
{
	(wget --no-check-certificate https://$MASTER_IP/ovd/admin/ha/registration.php --post-data="action=register&hostname=$HOSTNAME" -O /tmp/ulteo-slave-register)
	response=`cat /tmp/ulteo-slave-register`
	rm -f /tmp/ulteo-slave-register &> /dev/null
	if [ $? -eq 2 ]; then 
		echo -e "\033[31;1m[FAILED] \033[0m Connection to $MASTER_IP refused !";
		return 2;
	else
		if [ "$response" -eq "2" ]; then
			echo -e "\033[31;1m[FAILED] \033[0m Request is corrupted !";
		else
			echo -e "\033[36;1m[INFO] \033[0m Server registration has been done successfully !";
			$SERVICE_BIN mysql start ;
			$SERVICE_BIN apache2 start ;
		fi
	fi
	return 1
}
#########################################################################################################
#########################################################################################################
#########################################################################################################
# BEGINING
#########################################################################################################
#########################################################################################################
# PRIMARY TESTS
##
if [ -z "$HOSTNAME" ]; then
	echo -e "\033[31;1m[FAILED] \033[0m No Hostname found !";
	exit 2;
fi
if [ -z "$GATEWAY" ]; then
	echo -e "\033[31;1m[FAILED] \033[0m No gateway found !";
	exit 2;
fi

#apt-get update > /dev/null

#########################################################################################################
# MASTER/SLAVE
##

while [ 1 ]; do
	echo -n "Install this session manager as master or slave [m/s]: "
	read var_master
	case $var_master in
		master | m | M) 
			MASTER=1; 
			break;
			echo -e "\033[36;1m[INFO] \033[0m Your host will become the master"
		;;
		S | s | slave) 
			MASTER=0;
			break;
			echo -e "\033[36;1m[INFO] \033[0m Your host will become a slave"
		;;
		*)
			echo -e "\033[31;1m Your response is not valid !\033[0m";
			continue;
		;;
	esac
done

if [ $MASTER -eq 1 ]; then
	set_netlink
	set_virtual_ip
	drbd_install
	heartbeat_install
	heartbeat_cib_install	
	set_conf_and_script
	hashell_install
	$SERVICE_BIN mysql stop
	$SERVICE_BIN apache2 stop
	set_ha_web_interface
	set_init_script2
	crm_attribute --type nodes --node $HOSTNAME --name standby --update off
else
	set_netlink
	set_master_ip
	drbd_install
	heartbeat_install
	set_conf_and_script
	hashell_install
	set_ha_web_interface
	set_ha_register_to_master
	set_init_script2
fi

echo ""
if [ $MASTER -eq 1 ]; then
	echo -e "\033[34;1m###############################################";
	echo -e "#\033[31;1m INSTALLATION SUCCESSFULL [MASTER]";
	echo -e "\033[37;1mYou You must enable the HA module in configuration before !\033[0m"
	echo -e "\033[37;1mThen you can get web interface at: https://$VIP/ovd/admin/ha/status.php\033[0m"
else
	echo -e "\033[34;1m###############################################";
	echo -e "#\033[31;1m INSTALLATION SUCCESSFULL [SLAVE]\033[0m";
fi
echo ""

