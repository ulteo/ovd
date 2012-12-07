# Copyright (C) 2011-2012 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
# Author Julien LANGLOIS <julien@ulteo.com> 2012
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

Name: ovd-administration-console
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - Administration Console
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: David PHAM-VAN <dpv@ulteo.com>
Distribution: RHEL 6.0

Source: %{name}-%{version}.tar.gz
BuildArch: noarch

%description
This source package provides the Administration Console web services for the Ulteo
Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-administration-console
###########################################

Summary: Ulteo Open Virtual Desktop - Administration Console
Group: Applications/System
Requires: ulteo-ovd-l10n, php, php-dom, php-mbstring, php-libchart, php-soap

%description -n ulteo-ovd-administration-console
This package provides the web Administration Console for the Ulteo
Open Virtual Desktop solution.

%prep -n ulteo-ovd-administration-console
%setup -q

%build -n ulteo-ovd-administration-console
./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var --without-libchart
make

%install -n ulteo-ovd-administration-console
make DESTDIR=$RPM_BUILD_ROOT install

%post -n ulteo-ovd-administration-console
A2CONFDIR=/etc/httpd/conf.d
CONFDIR=/etc/ulteo/administration_console

# Alias admin
if [ ! -e $A2CONFDIR/ovd-administration-console.conf ]; then
    ln -sfT $CONFDIR/apache2.conf $A2CONFDIR/ovd-administration-console.conf
fi

# restart apache server
if apachectl configtest 2>/dev/null; then
    /etc/init.d/httpd restart || true
else
    echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
fi

%postun -n ulteo-ovd-administration-console
if [ "$1" = "0" ]; then
    A2CONFDIR=/etc/httpd/conf.d
    CONFDIR=/etc/ulteo/administration_console
    rm -f $A2CONFDIR/ovd-administration-console.conf
   
    rm -rf /var/spool/ulteo/administration_console

    if apachectl configtest 2>/dev/null; then
        /etc/init.d/httpd restart || true
    else
        echo "Apache configuration broken: correct the issue and restart the apache2 server"
    fi
fi

%clean -n ulteo-ovd-administration-console
rm -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-administration-console
%defattr(-,root,root)
/usr/*
%dir /var/*
%dir /var/*/ulteo
%config /etc/ulteo/administration_console/*.conf
%defattr(0660,apache,apache)
%config /etc/ulteo/administration_console/config.inc.php
%defattr(2770,apache,apache)
%dir /var/spool/ulteo/administration_console

%changelog -n ulteo-ovd-administration-console
* Wed Dec 05 2012 David PHAM-VAN <dpv@ulteo.com> trunk
- Initial release
