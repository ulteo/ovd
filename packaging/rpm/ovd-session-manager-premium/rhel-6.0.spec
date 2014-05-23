# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2013
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

%define php_bin %(basename `php-config --php-binary`)
%if %{defined rhel}
%define httpd httpd
%define apachectl apachectl
%define apache_user apache
%define apache_group apache
%define apache_requires , mod_ssl, php-xml
%else
%define httpd apache2
%define apachectl apache2ctl
%define apache_user wwwrun
%define apache_group www
%define apache_requires php5-curl, php5-dom, apache2, apache2-mod_php5
%endif

Name: ovd-session-manager-premium
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - Session Manager Premium Edition
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: David PHAM-VAN <d.pham-van@ulteo.com>

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildroot: %{buildroot}

%description
This source package provides the Session Manager Premium Edition 
web services for the Ulteo Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-session-manager-premium
###########################################

Summary: Ulteo Open Virtual Desktop - Session Manager Premium Edition 
Group: Applications/System
Requires: ulteo-ovd-session-manager

%description -n ulteo-ovd-session-manager-premium
This source package provides the Session Manager Premium Edition 
web services for the Ulteo Open Virtual Desktop.

%prep -n ulteo-ovd-session-manager-premium
%setup -q

%build -n ulteo-ovd-session-manager-premium
./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var --without-libchart --enable-premium
make

%pre -n ulteo-ovd-session-manager-premium
INSTALLDIR=/usr/share/ulteo/sessionmanager
# Check if update is possible
if [ -f $INSTALLDIR/tools/can_update.php ]
then
   su %{apache_user} -s /usr/bin/php $INSTALLDIR/tools/can_update.php 2>/dev/null
   if [ $? -ne 0 ]
   then
      exit 1
   fi
fi

%install -n ulteo-ovd-session-manager-premium
make DESTDIR=%{buildroot}/tmp install
mkdir -p %{buildroot}/usr/share/ulteo/sessionmanager
mkdir -p %{buildroot}/usr/share/ulteo/sessionmanager/admin
mkdir -p %{buildroot}/usr/share/ulteo/sessionmanager/PEAR
mkdir -p %{buildroot}/usr/share/ulteo/sessionmanager/modules/AuthMethod
mkdir -p %{buildroot}/usr/share/ulteo/sessionmanager/client
mv %{buildroot}/tmp/usr/share/ulteo/sessionmanager/premium %{buildroot}/usr/share/ulteo/sessionmanager/
mv %{buildroot}/tmp/usr/share/ulteo/sessionmanager/PEAR/php-saml %{buildroot}/usr/share/ulteo/sessionmanager/PEAR/
mv %{buildroot}/tmp/usr/share/ulteo/sessionmanager/modules/AuthMethod/SAML2.php %{buildroot}/usr/share/ulteo/sessionmanager/modules/AuthMethod/
mv %{buildroot}/tmp/usr/share/ulteo/sessionmanager/admin/heartbeat.php %{buildroot}/usr/share/ulteo/sessionmanager/admin/
mv %{buildroot}/tmp/etc/ulteo/sessionmanager/sessionmanager-ha.cron %{buildroot}/etc/ulteo/sessionmanager/

%post -n ulteo-ovd-session-manager-premium
# Update database
INSTALLDIR=/usr/share/ulteo/sessionmanager
if [ -f $INSTALLDIR/tools/update_database.php ]
then
   echo "Updating database."
   su %{apache_user} -s /usr/bin/php $INSTALLDIR/tools/update_database.php 2>/dev/null
   if [ $? -ne 0 ]
   then
      exit 1
   fi
fi

# Update wsdl
if [ -f $INSTALLDIR/tools/update_wsdl_cache.php ]
then
   echo "Purging wsdl cache files."
   su %{apache_user} -s /usr/bin/php $INSTALLDIR/tools/update_wsdl_cache.php 2>/dev/null
   if [ $? -ne 0 ]
   then
      exit 1
   fi
fi

# link crons
CONFDIR=/etc/ulteo/sessionmanager
ln -sfT $CONFDIR/sessionmanager-ha.cron /etc/cron.d/sessionmanager-ha

%preun -n ulteo-ovd-session-manager-premium
if [ "$1" = "0" ]; then
  if [ -L /etc/cron.d/sessionmanager-ha ]; then
    unlink /etc/cron.d/sessionmanager-ha
  fi
fi

%clean -n ulteo-ovd-session-manager-premium
rm -rf %{buildroot}

%files -n ulteo-ovd-session-manager-premium
%defattr(-,root,root)
%exclude /tmp
/usr/share/ulteo/sessionmanager/premium/*
/usr/share/ulteo/sessionmanager/PEAR/php-saml
/usr/share/ulteo/sessionmanager/modules/AuthMethod/SAML2.php
/usr/share/ulteo/sessionmanager/admin/heartbeat.php
/etc/ulteo/sessionmanager/sessionmanager-ha.cron

