# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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

Name: ovd-l10n
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - localization
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: RHEL 6.0

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: gettext, make

%description
Localization package for Ulteo Open Virtual Desktop

###########################################
%package -n ulteo-ovd-l10n
###########################################

Summary: Ulteo Open Virtual Desktop - localization
Group: Applications/System

%description -n ulteo-ovd-l10n
Localization package for Ulteo Open Virtual Desktop

%prep -n ulteo-ovd-l10n
%setup -q

%build -n ulteo-ovd-l10n
make

%install -n ulteo-ovd-session-manager
make DESTDIR=$RPM_BUILD_ROOT install

%clean -n ulteo-ovd-l10n
make clean
rm -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-l10n
%defattr(-,root,root)
/usr/share/*

%changelog -n ulteo-ovd-l10n
* Wed Sep 20 2011 Samuel Bovée <samuel@ulteo.com> 99.99.svn7521
- Initial release
