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

%install -n ulteo-ovd-session-manager-premium
make DESTDIR=%{buildroot}/tmp install
mkdir -p %{buildroot}/usr/share/ulteo/sessionmanager
mv %{buildroot}/tmp/usr/share/ulteo/sessionmanager/premium %{buildroot}/usr/share/ulteo/sessionmanager/

%clean -n ulteo-ovd-session-manager-premium
rm -rf %{buildroot}

%files -n ulteo-ovd-session-manager-premium
%defattr(-,root,root)
%exclude /tmp
/usr/share/ulteo/sessionmanager/premium/*
