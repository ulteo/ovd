# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; version 2
# of the License
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Name: ovd-session-manager
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - Session Manager
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: RHEL 5.5

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: intltool
Buildroot: %{buildroot}

%description
This source package provides the Session Manager web services for the Ulteo
Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-session-manager
###########################################

Summary: Ulteo Open Virtual Desktop - Session Manager
Group: Applications/System
Requires: ulteo-ovd-l10n, php, php-ldap, php-mysql, php-mbstring, php-pear, php-xml, php-libchart, php-imagick, curl, openssl, mod_ssl

%description -n ulteo-ovd-session-manager
This package provides the Session Manager web services for the Ulteo
Open Virtual Desktop.

%prep -n ulteo-ovd-session-manager
%setup -q

%build -n ulteo-ovd-session-manager
./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var --without-libchart
make

%install -n ulteo-ovd-session-manager
make DESTDIR=%{buildroot} install

# install the logrotate example
mkdir -p %{buildroot}/etc/logrotate.d
install -m 0644 examples/ulteo-sm.logrotate %{buildroot}/etc/logrotate.d/sessionmanager

# put the correct Apache user in cron file
A2USER=apache
sed -i "s/@APACHE_USER@/${A2USER}/" %{buildroot}/etc/ulteo/sessionmanager/sessionmanager.cron


%post -n ulteo-ovd-session-manager
A2CONFDIR=/etc/httpd/conf.d
CONFDIR=/etc/ulteo/sessionmanager

# VHost server config
if [ ! -e $A2CONFDIR/sessionmanager-vhost-server.conf ]; then
    ln -sfT $CONFDIR/apache2-vhost-server.conf \
        $A2CONFDIR/sessionmanager-vhost-server.conf
fi

# Alias admin
if [ ! -e $A2CONFDIR/ovd-admin.conf ]; then
    ln -sfT $CONFDIR/apache2-admin.conf $A2CONFDIR/ovd-admin.conf
fi

# VHost SSL config
if [ ! -e $A2CONFDIR/sessionmanager-vhost-ssl.conf ]; then
    serverName=$(hostname -f 2>/dev/null || true)
    [ -z "$serverName" ] && serverName=$(hostname) # Bad /etc/hosts configuration
    sed -i -r "s/^( *ServerName).*$/\1 ${serverName}/" \
        $CONFDIR/apache2-vhost-ssl.conf
    ln -sfT $CONFDIR/apache2-vhost-ssl.conf \
        $A2CONFDIR/sessionmanager-vhost-ssl.conf
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

# restart apache server
if apachectl configtest 2>/dev/null; then
    /etc/init.d/httpd restart || true
else
    echo << EOF
"Apache configuration error after enable OVD virtual hosts. Please remove your
old SSL configuration or be sure that the following URL are valid:
https://hostname/ovd/admin, https://hostname/ovd/client.
If you don't change anything, you won't start OVD sessions."
EOF
fi

# link crons
ln -sfT $CONFDIR/sessionmanager.cron /etc/cron.d/sessionmanager

%postun -n ulteo-ovd-session-manager
if [ "$1" = "0" ]; then
    A2CONFDIR=/etc/httpd/conf.d
    CONFDIR=/etc/ulteo/sessionmanager
    rm -f $A2CONFDIR/sessionmanager-vhost-server.conf \
          $A2CONFDIR/sessionmanager-vhost-ssl.conf \
          $A2CONFDIR/ovd-admin.conf
    rm -f $CONFDIR/ovd.key $CONFDIR/ovd.csr $CONFDIR/ovd.crt
    rm -f /etc/cron.hourly/sessionmanager
    rm -rf /var/spool/ulteo/sessionmanager/* \
           /var/cache/ulteo/sessionmanager/* \
           /var/log/ulteo/sessionmanager/*

    if apachectl configtest 2>/dev/null; then
        /etc/init.d/httpd restart || true
    else
        echo "Apache configuration broken: correct the issue and restart the apache2 server"
    fi
fi

%clean -n ulteo-ovd-session-manager
rm -rf %{buildroot}

%files -n ulteo-ovd-session-manager
%defattr(-,root,root)
/usr/*
%dir /var/*
%dir /var/*/ulteo
%config /etc/ulteo/sessionmanager/*.conf
%config /etc/logrotate.d/sessionmanager
%defattr(0644,root,root)
%config /etc/ulteo/sessionmanager/sessionmanager.cron
%defattr(0660,apache,apache)
%config /etc/ulteo/sessionmanager/config.inc.php
%defattr(2770,apache,apache)
%dir /var/*/ulteo/sessionmanager

%changelog -n ulteo-ovd-session-manager
* Thu Sep 02 2010 Samuel Bovée <samuel@ulteo.com> 3.0+svn05193-1
- Initial release
