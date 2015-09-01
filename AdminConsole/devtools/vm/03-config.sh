#!/bin/bash -x

CONFDIR=/etc/ulteo/sessionmanager
CACHEDIR=/var/cache/ulteo/sessionmanager
SPOOLDIR=/var/spool/ulteo/sessionmanager
LOGDIR=/var/log/ulteo/sessionmanager
INSTALLDIR=/usr/share/ulteo/sessionmanager

CONFFILE=$CONFDIR/config.inc.php

############################# APACHE CONFIGURATION #############################

A2SITESDIR=/etc/apache2/sites-available

if [ -d /etc/apache2/conf-available ]; then
    A2CONFDIR=/etc/apache2/conf-available
else
    A2CONFDIR=/etc/apache2/conf.d
fi

A2USER=www-data

# VHost server config
if [ ! -e $A2SITESDIR/sessionmanager-vhost-server ]; then
    ln -sfT $CONFDIR/apache2-vhost-server.conf \
        $A2SITESDIR/sessionmanager-vhost-server.conf
    a2ensite sessionmanager-vhost-server.conf > /dev/null
    a2enmod rewrite >/dev/null
fi

# Alias admin
if [ ! -e $A2CONFDIR/ovd-admin.conf ]; then
    ln -sfT $CONFDIR/apache2-admin.conf $A2CONFDIR/ovd-admin.conf
    a2enconf ovd-admin.conf > /dev/null
fi

# VHost SSL config
if [ ! -e $A2SITESDIR/sessionmanager-vhost-ssl ]; then
    serverName=$(hostname -f 2>/dev/null || true)
    [ -z "$serverName" ] && serverName=$(hostname) #Bad /etc/hosts configuration
    sed -i -r "s/^( *ServerName).*$/\1 ${serverName}/" \
        $CONFDIR/apache2-vhost-ssl.conf
    ln -sfT $CONFDIR/apache2-vhost-ssl.conf $A2SITESDIR/sessionmanager-vhost-ssl.conf
    a2ensite sessionmanager-vhost-ssl.conf > /dev/null
    a2enmod ssl > /dev/null
fi

# SSL self-signed key generation
if [ ! -f $CONFDIR/ovd.key -o ! -f $CONFDIR/ovd.csr -o ! -f $CONFDIR/ovd.crt ]
then
    echo "Auto-generate SSL configuration for Apache2 with self-signed certificate."
    openssl genrsa -out $CONFDIR/ovd.key 1024 2> /dev/null
    openssl req -new -subj /CN=$(hostname)/ -batch \
        -key $CONFDIR/ovd.key -out $CONFDIR/ovd.csr
    openssl x509 -req -days 3650 -in $CONFDIR/ovd.csr \
        -signkey $CONFDIR/ovd.key -out $CONFDIR/ovd.crt 2> /dev/null
    chown root:root $CONFDIR/ovd.key $CONFDIR/ovd.csr $CONFDIR/ovd.crt
    chmod 600       $CONFDIR/ovd.key $CONFDIR/ovd.csr $CONFDIR/ovd.crt
fi

# create folders and change permissions
mkdir -p $SPOOLDIR/reporting $LOGDIR
chown $A2USER:$A2USER $SPOOLDIR $SPOOLDIR/reporting $LOGDIR $CONFFILE

############################### FIRST INSTALL #################################

LOGIN="admin"
PASSWD=$(echo -n admin | md5sum | cut -d " " -f 1)

# set the configuration
sed -r -i "s,^(.*SESSIONMANAGER_ADMIN_LOGIN.*').*('.*)$,\1$LOGIN\2," $CONFFILE
sed -r -i "s,^(.*SESSIONMANAGER_ADMIN_PASSWORD.*').*('.*)$,\1$PASSWD\2," $CONFFILE

# Update database
if [ -f $INSTALLDIR/tools/update_database.php ]
then
   echo "Updating database."
   su $A2USER -c "php $INSTALLDIR/tools/update_database.php" 2>/dev/null
   [ $? -ne 0 ] && exit 1
fi

# Update wsdl
if [ -f $INSTALLDIR/tools/update_wsdl_cache.php ]
then
   echo "Purging wsdl cache files."
   su $A2USER -c "php $INSTALLDIR/tools/update_wsdl_cache.php" 2>/dev/null
   [ $? -ne 0 ] && exit 1
fi

#=======================================  Administration console
CONFDIR=/etc/ulteo/administration_console
CONFFILE=$CONFDIR/config.inc.php
SPOOLDIR=/var/spool/ulteo/administration_console
INSTALLDIR=/usr/share/ulteo/administration_console

############################# APACHE CONFIGURATION #############################

if [ -d /etc/apache2/conf-available ]; then
    A2CONFDIR=/etc/apache2/conf-available
else
    A2CONFDIR=/etc/apache2/conf.d
fi

A2USER=www-data

# Alias admin
if [ ! -e $A2CONFDIR/ovd-administration-console.conf ]; then
    ln -sfT $CONFDIR/apache2.conf $A2CONFDIR/ovd-administration-console.conf
    which a2enconf && a2enconf ovd-administration-console
fi

# Update wsdl
if [ -f $INSTALLDIR/tools/update_wsdl_cache.php ]
then
   echo "Purging wsdl cache files."
   su -s /usr/bin/php $A2USER "$INSTALLDIR/tools/update_wsdl_cache.php"
   [ $? -ne 0 ] && exit 1
fi

# restart apache server
if apache2ctl configtest 2>/dev/null; then
    invoke-rc.d apache2 reload || true
else
    echo << EOF
Your apache configuration is broken!
Correct it and restart apache.
EOF
fi

# create folders and change permissions
mkdir -p $SPOOLDIR
chown $A2USER:$A2USER $SPOOLDIR $CONFFILE

############################### FIRST INSTALL #################################

SM_HOST=127.0.0.1

# set the configuration
sed -i "/SESSIONMANAGER_HOST/ s/, *'.*' *);/, '${SM_HOST}');/" $CONFFILE

echo "define('DEBUG_MODE', true);" >> $CONFFILE
