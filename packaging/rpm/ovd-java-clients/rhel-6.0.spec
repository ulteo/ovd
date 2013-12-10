# Copyright (C) 2010-2013 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010
# Author Julien LANGLOIS <julien@ulteo.com> 2013
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

Name: ovd-java-clients
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - applets
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: David PHAM-VAN <d.pham-van@ulteo.com>

Source: %{name}-%{version}.tar.gz
Buildrequires: ulteo-ovd-cert, java-1.6.0-openjdk-devel, ant, ant-nodeps
Buildroot: %{buildroot}

%description
This applet is used in the Open Virtual Desktop to display the user session in
a browser

%install
OVD_CERT_DIR=/usr/share/ulteo/ovd-cert
[ -z "$JKS_PATH" ] && JKS_PATH=$OVD_CERT_DIR/keystore
[ -z "$JKS_PASSWD" ] && JKS_PASSWD=$OVD_CERT_DIR/password
[ -z "$JKS_ALIAS" ] && JKS_ALIAS=ulteo

ant applet.install ovdExternalAppsClient.install -Dbuild.type=stripped -Dprefix=/usr -Ddestdir=%{buildroot} -Dmingw32.prefix=i686-pc-mingw32- \
	-Dkeystore.path=$JKS_PATH -Dkeystore.password="$(cat $JKS_PASSWD)" -Dkeystore.alias=$JKS_ALIAS

if [ -d %{_libdir}/jvm/java ]; then
export JAVA_HOME=%{_libdir}/jvm/java
else
export JAVA_HOME=/usr/lib/jvm/java
fi

pushd libXClientArea ; make clean ; make JAVAHOME=$JAVA_HOME ; make install DESTDIR=%{buildroot} ; popd
pushd librdp ; cmake . ; make install/strip DESTDIR=%{buildroot} ; popd

if [ "%{_libdir}" != "/usr/lib" ]; then
	[ ! -d %{buildroot}/%{_libdir} ] && mkdir -p %{buildroot}/%{_libdir}
	mv %{buildroot}/usr/lib/libXClientArea.so %{buildroot}/%{_libdir}/libXClientArea.so
	mv %{buildroot}/usr/lib/librdp.so %{buildroot}/%{_libdir}/librdp.so
	rmdir %{buildroot}/usr/lib
fi

%prep
%setup -q

%clean
rm -rf %{buildroot}


###########################################
%package -n ulteo-ovd-applets
###########################################

Summary: Ulteo Open Virtual Desktop - desktop applet
Group: Applications/System
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
Requires: java-1.6.0-openjdk, cups, libovd-xclient-area, desktop-file-utils, ulteo-ovd-integrated-launcher
# Java can also be provided by packages: java-1.7.0-openjdk  jre-7-linux jre-6-linux java-1_6_0-sun

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


###########################################
%package -n libovd-accelerator
###########################################

Summary: Ovd Client optimization library
Group: Applications/System

%description -n libovd-accelerator
Ovd Client optimization library Compression algorithm in jni library

%files -n libovd-accelerator
%defattr(-,root,root)
%{_libdir}/librdp.so
