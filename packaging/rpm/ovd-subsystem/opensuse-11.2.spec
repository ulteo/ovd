Name: ovd-subsystem
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - Subsystem
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.2

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: subversion

%description
This package provides the subsystem for the Ulteo Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-subsystem
###########################################

Summary: Ulteo Open Virtual Desktop - Session Manager
Requires: curl

%description -n ulteo-ovd-subsystem
This package provides the subsystem for the Ulteo Open Virtual Desktop.

%prep -n ulteo-ovd-subsystem
%setup -q

%install -n ulteo-ovd-subsystem
SBINDIR=%buildroot/usr/sbin
INITDIR=%buildroot/etc/init.d
mkdir -p $SBINDIR $INITDIR
cp ovd-subsystem-config $SBINDIR
cp ulteo-ovd-subsystem.init.rpm $INITDIR/ulteo-ovd-subsystem

%preun
service ulteo-ovd-subsystem stop

%postun -n ulteo-ovd-subsystem
SUBCONF=/etc/ulteo/subsystem.conf
CHROOTDIR=/opt/ulteo
[ -f $SUBCONF ] && . $SUBCONF
rm -rf $CHROOTDIR
rm -f $SUBCONF

%clean -n ulteo-ovd-subsystem
rm -rf %buildroot

%files -n ulteo-ovd-subsystem
%defattr(744,root,root)
/usr/*
/etc/*

%changelog -n ulteo-ovd-subsystem
* Tue Aug 17 2010 Samuel Bovée <gauvain@ulteo.com> svn4180
- Initial release
