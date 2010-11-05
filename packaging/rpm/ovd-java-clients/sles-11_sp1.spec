Name: ovd-java-clients
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - desktop applet
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: SLES 11 SP1

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: ulteo-ovd-cert, java-1_6_0-ibm-devel, ant, ant-nodeps, mingw32-cross-gcc
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
OVD_CERT_DIR=/usr/share/ulteo/ovd-cert/
ant applet.install -Dbuild.type=stripped -Dprefix=/usr -Ddestdir=$RPM_BUILD_ROOT -Dbuild.cert=$OVD_CERT_DIR -Dkeystore.password=$(cat $OVD_CERT_DIR/password) -Dmingw32.prefix=i686-pc-mingw32-

%files -n ulteo-ovd-applets
%defattr(-,root,root)
/usr/share/ulteo/applets/*

%clean -n ulteo-ovd-applets
rm -rf %{buildroot}

%changelog -n ulteo-ovd-applets
* Wed Sep 01 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4395
- Initial release
