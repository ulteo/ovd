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

Name: ovd-web-client
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - web client
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.2

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: intltool

%description
This is a web based client for Ulteo OVD.

###########################################
%package -n ulteo-ovd-web-client
###########################################

Summary: Ulteo Open Virtual Desktop - web client
Requires: apache2-mod_php5, php5, php5-curl, php5-dom, php5-gettext, php5-pear, ulteo-ovd-applets, ulteo-ovd-l10n

%description -n ulteo-ovd-web-client
This is a web based client for Ulteo OVD.

%prep -n ulteo-ovd-web-client
%setup -q

%build -n ulteo-ovd-web-client
./configure --prefix=/usr --sysconfdir=/etc --without-ulteo-applets

%install -n ulteo-ovd-web-client
make DESTDIR=$RPM_BUILD_ROOT install
cp -a ajaxplorer $RPM_BUILD_ROOT/usr/share/ulteo/webclient

%post -n ulteo-ovd-web-client
A2CONFDIR=/etc/apache2/conf.d
CONFDIR=/etc/ulteo/webclient

a2enmod php5 > /dev/null

if [ ! -e $A2CONFDIR/webclient.conf ]; then
    ln -sf $CONFDIR/apache2.conf $A2CONFDIR/webclient.conf
    if apache2ctl configtest 2>/dev/null; then
        service apache2 reload || true
    else
        echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
    fi
fi

%postun -n ulteo-ovd-web-client
A2CONFDIR=/etc/apache2/conf.d

if [ "$1" = "0" ]; then
    if [ -L $A2CONFDIR/webclient.conf ]; then
        # remove apache2 link
        rm -f $A2CONFDIR/webclient.conf

        # reload apache
        if apache2ctl configtest 2>/dev/null; then
            service apache2 reload || true
        else
            echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
        fi
    fi
fi

%clean -n ulteo-ovd-web-client
rm -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-web-client
%defattr(-,root,root)
/usr/*
/etc/*
%config /etc/ulteo/webclient/apache2.conf
%config /etc/ulteo/webclient/config.client.ini
%config /etc/ulteo/webclient/config.inc.php

%changelog -n ulteo-ovd-web-client
* Fri Aug 13 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4145
- Initial release

##############################################
%package -n ulteo-ovd-web-client-ajaxplorer
##############################################

Summary: Ulteo Open Virtual Desktop - Ajaxplorer portal
Group: Applications/System
Requires: ulteo-ovd-web-client

%description -n ulteo-ovd-web-client-ajaxplorer
This is a web based client for Ulteo OVD.

%files -n ulteo-ovd-web-client-ajaxplorer
%defattr(-,root,root)
/usr/share/ulteo/webclient/ajaxplorer
%defattr(-,wwwrun,www)
/usr/share/ulteo/webclient/ajaxplorer/server/logs
