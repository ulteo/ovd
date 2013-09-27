# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
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
%else
%define httpd apache2
%define apachectl apache2ctl
%define apache_user wwwrun
%define apache_group www
%endif

Name: ovd-wnos
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - wnos client
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: David PHAM-VAN <d.pham-van@ulteo.com>

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: intltool
Buildroot: %{buildroot}

%description
This is a wnos based client for Ulteo OVD.

###########################################
%package -n ulteo-ovd-wnos-client
###########################################

Summary: Ulteo Open Virtual Desktop - wnos client
Group: Applications/System
Requires: %{php_bin}, %{php_bin}-curl

%description -n ulteo-ovd-wnos-client
This is a wnos based client for Ulteo OVD.

%prep -n ulteo-ovd-wnos-client
%setup -q

%build -n ulteo-ovd-wnos-client
./configure --prefix=/usr --sysconfdir=/etc

%install -n ulteo-ovd-wnos-client
make DESTDIR=%{buildroot} install

%post -n ulteo-ovd-wnos-client
A2CONFDIR=/etc/%{httpd}/conf.d
CONFDIR=/etc/ulteo/wnos

if [ ! -e $A2CONFDIR/wnos.conf ]; then
    ln -sf $CONFDIR/apache2.conf $A2CONFDIR/wnos.conf
    if %{apachectl} configtest 2>/dev/null; then
        service %{httpd} reload || true
    else
        echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
    fi
fi

%postun -n ulteo-ovd-wnos-client
A2CONFDIR=/etc/httpd/conf.d

if [ "$1" = "0" ]; then
    if [ -L $A2CONFDIR/wnos.conf ]; then
        # remove apache2 link
        rm -f $A2CONFDIR/wnos.conf

        # reload apache
        if %{apachectl} configtest 2>/dev/null; then
            service %{httpd} reload || true
        else
            echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
        fi
    fi
fi

%files -n ulteo-ovd-wnos-client
%defattr(-,root,root)
/usr/*
%config /etc/*

%clean -n ulteo-ovd-wnos-client
rm -rf %{buildroot}
