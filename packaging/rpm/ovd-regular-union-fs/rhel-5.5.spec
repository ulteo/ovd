# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
# Author David PHAM-VAN <d.pham-van@ulteo.com>, 2013
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

Name: ovd-regular-union-fs
Version: @VERSION@
Release: @RELEASE@

Summary: Provide a regular expression based union filesystem
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: David PHAM-VAN <d.pham-van@ulteo.com>

Source: %{name}-%{version}.tar.gz
BuildArch: i586 x86_64
Buildrequires: libtool, gcc, cmake, fuse-devel, pam-devel
Buildroot: %{buildroot}

%description
The OVD Regular Union File System provides an expression based
union filesystem

###########################################
%package -n ulteo-ovd-regular-union-fs
###########################################

Summary: Provide a regular expression based union filesystem
Group: Applications/System
Requires: libfuse2, libpam

%description -n ulteo-ovd-regular-union-fs
The OVD Regular Union File System provides an expression based
union filesystem

%prep -n ulteo-ovd-regular-union-fs
%setup -q
cmake . -DCMAKE_BUILD_TYPE=Release -DCMAKE_INSTALL_PREFIX="/usr" -DCMAKE_C_FLAGS="-g -O2 -Wall" -DCMAKE_CXX_FLAGS="-g -O2 -Wall" -DCMAKE_SKIP_RPATH=ON -DCMAKE_VERBOSE_MAKEFILE=ON

%install -n ulteo-ovd-regular-union-fs
make install DESTDIR=$RPM_BUILD_ROOT

%post -n ulteo-ovd-regular-union-fs

%preun -n ulteo-ovd-regular-union-fs

%files -n ulteo-ovd-regular-union-fs
%defattr(-,root,root)
/etc/*
/usr/*

%clean -n ulteo-ovd-regular-union-fs
rm -rf %{buildroot}
