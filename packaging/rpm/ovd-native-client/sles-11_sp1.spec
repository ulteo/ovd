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

Name: ovd-native-client
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - native client
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: SLES 11 SP1

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: java-1_6_0-ibm-devel, ant, ant-nodeps, gettext
Buildroot: %{buildroot}

%description
This application is used in the Open Virtual Desktop to display the user
session and launch applications via a native client.

###########################################
%package -n ulteo-ovd-native-client
###########################################

Summary: Ulteo Open Virtual Desktop - native client
Group: Applications/System
Requires: java-1_6_0-ibm-devel, xdg-utils

%description -n ulteo-ovd-native-client
This application is used in the Open Virtual Desktop to display the user
session and launch applications via a native client.

%prep -n ulteo-ovd-native-client
%setup -q

%install -n ulteo-ovd-native-client
ant ovdNativeClient.install -Dbuild.type=stripped -Dprefix=/usr -Ddestdir=$RPM_BUILD_ROOT -Dlanguages=true

%post -n ulteo-ovd-native-client
ICONFILE=/usr/share/icons/ulteo-ovd-native-client.png
xdg-icon-resource install --size 128 --mode system $ICONFILE ulteo-ovd-native-client

%preun -n ulteo-ovd-native-client
xdg-icon-resource uninstall --size 128 --mode system icon-ulteo

%files -n ulteo-ovd-native-client
%defattr(-,root,root)
/usr/*

%clean -n ulteo-ovd-native-client
rm -rf %{buildroot}

%changelog -n ulteo-ovd-native-client
* Fri 25 Mar 2011 Samuel Bovée <samuel@ulteo.com> 99.99.svn6577
- Initial release
