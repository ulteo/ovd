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

Name: ovd-java-clients
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - desktop applet
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.1

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: ulteo-ovd-cert, java-1_6_0-openjdk-devel, ant, ant-nodeps, mingw32-cross-gcc
Buildroot: %{buildroot}

%description
This applet is used in the Open Virtual Desktop to display the user session in
a browser

###########################################
%package -n ulteo-ovd-applets
###########################################

Summary: Ulteo Open Virtual Desktop - desktop applet
Group: Applications/System

%description -n ulteo-ovd-applets
This applet is used in the Open Virtual Desktop to display the user session in
a browser

%prep -n ulteo-ovd-applets
%setup -q

%install -n ulteo-ovd-applets
OVD_CERT_DIR=/usr/share/ulteo/ovd-cert
[ -z "$JKS_PATH" ] && JKS_PATH=$OVD_CERT_DIR/keystore
[ -z "$JKS_PASSWD" ] && JKS_PASSWD=$OVD_CERT_DIR/password
[ -z "$JKS_ALIAS" ] && JKS_ALIAS=ulteo

ant applet.install -Dbuild.type=stripped -Dprefix=/usr -Ddestdir=$RPM_BUILD_ROOT -Dmingw32.prefix=i686-pc-mingw32- \
	-Dkeystore.path=$JKS_PATH -Dkeystore.password="$(cat $JKS_PASSWD)" -Dkeystore.alias=$JKS_ALIAS

%files -n ulteo-ovd-applets
%defattr(-,root,root)
/usr/share/ulteo/applets/*

%clean -n ulteo-ovd-applets
rm -rf %{buildroot}

%changelog -n ulteo-ovd-applets
* Wed Sep 01 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4362
- Initial release
