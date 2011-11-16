# Copyright (C) 2010-2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2010-2011
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

Name: ovd-subsystem
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - Subsystem
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.1

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildroot: %{buildroot}

%description
This package provides the subsystem for the Ulteo Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-subsystem
###########################################

Summary: Ulteo Open Virtual Desktop - Session Manager
Group: Applications/System
Requires: curl
Conflicts: ulteo-ovd-slaveserver, samba, xrdp

%description -n ulteo-ovd-subsystem
This package provides the subsystem for the Ulteo Open Virtual Desktop.

%prep -n ulteo-ovd-subsystem
%setup -q

%install -n ulteo-ovd-subsystem
install -D conf/default.conf %buildroot/etc/default/ulteo-ovd-subsystem
install -D conf/subsystem.conf %buildroot/etc/ulteo/subsystem.conf
install -D init/suse/ulteo-ovd-subsystem %buildroot/etc/init.d/ulteo-ovd-subsystem
install -D script/ovd-subsystem-config %buildroot/usr/sbin/ovd-subsystem-config
install -D script/uchroot %buildroot/usr/sbin/uchroot

%preun -n ulteo-ovd-subsystem
if [ "$1" = "0" ]; then
    service ulteo-ovd-subsystem stop
    chkconfig --del ulteo-ovd-subsystem > /dev/null
fi

%postun -n ulteo-ovd-subsystem
if [ "$1" = "0" ]; then
    [ -f /etc/default/ulteo-ovd-subsystem ] && . /etc/default/ulteo-ovd-subsystem
    [ -f /etc/ulteo/subsystem.conf ] && . /etc/ulteo/subsystem.conf
    rm -rf $CHROOTDIR
fi

%clean -n ulteo-ovd-subsystem
rm -rf %buildroot

%files -n ulteo-ovd-subsystem
%defattr(-,root,root)
%config /etc/default/ulteo-ovd-subsystem
%config /etc/ulteo/subsystem.conf
%defattr(744,root,root)
/etc/init.d/ulteo-ovd-subsystem
/usr/sbin/*

%changelog -n ulteo-ovd-subsystem
 * Wed Sep 01 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4362
- Initial release
