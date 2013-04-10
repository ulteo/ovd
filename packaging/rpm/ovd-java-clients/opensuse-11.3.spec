# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010
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

Name: ovd-java-clients
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - applets
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.3

Source: %{name}-%{version}.tar.gz
Buildrequires: ulteo-ovd-cert, java-1.6.0-openjdk-devel, ant, ant-nodeps, mingw32-cross-gcc

%description
This applet is used in the Open Virtual Desktop to display the user session in
a browser

%install
OVD_CERT_DIR=/usr/share/ulteo/ovd-cert
[ -z "$JKS_PATH" ] && JKS_PATH=$OVD_CERT_DIR/keystore
[ -z "$JKS_PASSWD" ] && JKS_PASSWD=$OVD_CERT_DIR/password
[ -z "$JKS_ALIAS" ] && JKS_ALIAS=ulteo

ant applet.install ovdExternalAppsClient.install -Dbuild.type=stripped -Dprefix=/usr -Ddestdir=$RPM_BUILD_ROOT -Dmingw32.prefix=i686-pc-mingw32- \
	-Dkeystore.path=$JKS_PATH -Dkeystore.password="$(cat $JKS_PASSWD)" -Dkeystore.alias=$JKS_ALIAS

cd libXClientArea ; make clean ; make JAVAHOME=%{_libdir}/jvm/java ; make install DESTDIR=$RPM_BUILD_ROOT

if [ "%{_libdir}" != "/usr/lib" ]; then
	[ ! -d $RPM_BUILD_ROOT/%{_libdir} ] && mkdir -p $RPM_BUILD_ROOT/%{_libdir}
	mv $RPM_BUILD_ROOT/usr/lib/libXClientArea.so $RPM_BUILD_ROOT/%{_libdir}/libXClientArea.so
	rmdir $RPM_BUILD_ROOT/usr/lib
fi

%prep
%setup -q

%clean -n ulteo-ovd-applets
rm -rf $RPM_BUILD_ROOT

%changelog -n ulteo-ovd-applets
* Fri Aug 13 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4145
- Initial release

###########################################
%package -n ulteo-ovd-applets
###########################################

Summary: Ulteo Open Virtual Desktop - desktop applet
BuildArch: noarch

%description -n ulteo-ovd-applets
This applet is used in the Open Virtual Desktop to display the user session in
a browser

%files -n ulteo-ovd-applets
%defattr(-,root,root)
/usr/share/ulteo/applets/*

###########################################
%package -n ulteo-ovd-externalapps-client
###########################################

Summary: Ulteo Open Virtual Desktop - desktop applet
Group: Applications/System
BuildArch: noarch
Requires: java-1_6_0-sun, cups-client, libovd-xclient-area, desktop-file-utils, ulteo-ovd-integrated-launcher
# Java can also be provided by packages: java-1_6_0-openjdk

%description -n ulteo-ovd-externalapps-client
This application is used in the Open Virtual Desktop to display the user session and launch applications via an all integrated client.

%files -n ulteo-ovd-externalapps-client
%defattr(0755,root,root)
%{_bindir}/OVDExternalAppsClient
%defattr(0644,root,root)
/usr/share/java/OVDExternalAppsClient.jar

###########################################
%package -n libovd-xclient-area
###########################################

Summary: X Client area library
Group: Applications/System

%description -n libovd-xclient-area
Detect docks / taskbars and calculate the available size for X window applications area

%files -n libovd-xclient-area
%defattr(-,root,root)
%{_libdir}/libXClientArea.so
