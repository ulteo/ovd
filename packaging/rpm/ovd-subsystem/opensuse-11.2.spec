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

BuildArch: noarch

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
svn co https://svn.ulteo.com/ovd/trunk/utils/subsystem script
svn co https://svn.ulteo.com/ovd/trunk/packaging/debian/ovd-subsystem/lucid init
cp init/ulteo-ovd-subsystem.init %_builddir
cp script/sub-config %_builddir

%install -n ulteo-ovd-subsystem
BINDIR=%buildroot/usr/bin
INITDIR=%buildroot/etc/init.d
mkdir -p $BINDIR $INITDIR
cp sub-config $BINDIR/sub-config
cp ulteo-ovd-subsystem.init $INITDIR/ulteo-ovd-subsystem

%postun -n ulteo-ovd-subsystem
rm -f /etc/ulteo/subsystem.conf
rm -rf /opt/ulteo

%clean -n ulteo-ovd-subsystem
rm -rf %buildroot

%files -n ulteo-ovd-subsystem
%defattr(-,root,root)
/usr/*
/etc/*

%changelog -n ulteo-ovd-subsystem
* Tue Aug 17 2010 Samuel Bovée <gauvain@ulteo.com> svn4180
- Initial release
