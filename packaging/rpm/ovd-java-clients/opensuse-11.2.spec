Name: ovd-java-clients
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - desktop applet
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.2

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: ulteo-ovd-cert, java-1.6.0-openjdk-devel, ant, ant-nodeps, mingw32-cross-gcc

%description
This applet is used in the Open Virtual Desktop to display the user session in
a browser

###########################################
%package -n ulteo-ovd-applets
###########################################

Summary: Ulteo Open Virtual Desktop - desktop applet

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
rm -rf $RPM_BUILD_ROOT

%changelog -n ulteo-ovd-applets
* Fri Aug 13 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4145
- Initial release
