# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
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

Name: ovd-shells
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - shells
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Julien LANGLOIS <julien@ulteo.com>
Distribution: OpenSUSE 11.3

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: python, subversion-python
Buildroot: %{buildroot}

%description
Ulteo Open Virtual Desktop - shell

###########################################
%package -n ulteo-ovd-shells
###########################################

Summary: Ulteo Open Virtual Desktop - shells
Group: Applications/System
Requires: python xrdp-python

%description -n ulteo-ovd-shells
These scripts handle the Open Virtual Desktop user sessions.

%prep -n ulteo-ovd-shells
%setup -q

%build -n ulteo-ovd-shells
%{__python} setup.py build

%install -n ulteo-ovd-shells
%{__python} setup.py install --root $RPM_BUILD_ROOT --prefix %{_prefix}

%clean -n ulteo-ovd-shells
%{__rm} -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-shells
%defattr(-,root,root)
/usr/lib/python*/site-packages/ovd_shells-*.egg-info
/usr/lib/python*/site-packages/ovd_shells/*.py*
/usr/lib/python*/site-packages/ovd_shells/Platform/*
%{_bindir}/OvdDesktop
%{_bindir}/OvdRemoteApps
%{_bindir}/startovdapp

%changelog -n ulteo-ovd-shells
* Thu Apr 10 2013 Julien LANGLOIS <julien@ulteo.com> 99.99.svn8667
- Initial release
