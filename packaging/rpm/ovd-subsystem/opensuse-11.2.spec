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

%description
This package provides the subsystem for the Ulteo Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-subsystem
###########################################

Summary: Ulteo Open Virtual Desktop - Session Manager
Requires: curl
Conflicts: ulteo-ovd-slaveserver, samba, xrdp

%description -n ulteo-ovd-subsystem
This package provides the subsystem for the Ulteo Open Virtual Desktop.

%prep -n ulteo-ovd-subsystem
%setup -q

%install -n ulteo-ovd-subsystem
install -D ovd-subsystem-config %buildroot/usr/sbin/ovd-subsystem-config
install -D init/suse/ulteo-ovd-subsystem %buildroot/etc/init.d/ulteo-ovd-subsystem

%preun -n ulteo-ovd-subsystem
if [ "$1" = "0" ]; then
    service ulteo-ovd-subsystem stop
    chkconfig --del ulteo-ovd-subsystem
fi

%postun -n ulteo-ovd-subsystem
if [ "$1" = "0" ]; then
    SUBCONF=/etc/ulteo/subsystem.conf
    CHROOTDIR=/opt/ulteo
    [ -f $SUBCONF ] && . $SUBCONF
    rm -rf $CHROOTDIR
    rm -f $SUBCONF
fi

%clean -n ulteo-ovd-subsystem
rm -rf %buildroot

%files -n ulteo-ovd-subsystem
%defattr(744,root,root)
/etc/init.d/ulteo-ovd-subsystem
/usr/sbin/ovd-subsystem-config

%changelog -n ulteo-ovd-subsystem
* Tue Aug 17 2010 Samuel Bovée <gauvain@ulteo.com> svn4180
- Initial release
