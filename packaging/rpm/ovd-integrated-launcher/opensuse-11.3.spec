# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author Julien LANGLOIS <julien@ulteo.com> 2013
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

Name: ovd-integrated-launcher
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - integrated laucher
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Julien LANGLOIS <julien@ulteo.com>
Distribution: OpenSUSE 11.3

Source: %{name}-%{version}.tar.gz

%description
Launch hook for seamless applications

###########################################
%package -n ulteo-ovd-integrated-launcher
###########################################

Summary: Ulteo Open Virtual Desktop - integrated laucher
Group: Applications/System

%description -n ulteo-ovd-integrated-launcher
Launch hook for seamless applications

%prep -n ulteo-ovd-integrated-launcher
%setup -q

%build -n ulteo-ovd-integrated-launcher
./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var
make

%install -n ulteo-ovd-integrated-launcher
make DESTDIR=$RPM_BUILD_ROOT install

%clean ulteo-ovd-integrated-launcher
rm -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-integrated-launcher
%defattr(-,root,root)
%{_bindir}/UlteoOVDIntegratedLauncher

%changelog
* Wed Apr 10 2013 Julien LANGLOIS <julien@ulteo.com> 99.99.svn8677
- Initial release
