# Copyright (C) 2011 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
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

Name: ovd-slaveserver
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - slave server
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: SLES 11 SP1

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: python
Buildroot: %{buildroot}

%description
Ulteo Open Virtual Desktop daemon server

###########################################
%package -n ulteo-ovd-slaveserver
###########################################

Summary: Ulteo Open Virtual Desktop - slave server
Group: Applications/System
Requires: python

%description -n ulteo-ovd-slaveserver
This daemon manages the Open Virtual Desktop servers.

%prep -n ulteo-ovd-slaveserver
%setup -q

%build -n ulteo-ovd-slaveserver
%{__python} setup.py build

%install -n ulteo-ovd-slaveserver
%{__python} setup.py install --root $RPM_BUILD_ROOT --prefix %{_prefix}
%{__mkdir_p} $RPM_BUILD_ROOT%{_var}/log/ulteo/ovd
%{__ln_s} /usr/share/ulteo/ovd/slaveserver/ulteo-ovd-slaveserver.py $RPM_BUILD_ROOT%{_sbindir}/ulteo-ovd-slaveserver
%{__ln_s} /usr/share/ulteo/ovd/slaveserver/ovd-slaveserver-role.py $RPM_BUILD_ROOT%{_sbindir}/ovd-slaveserver-role
%{__install} -T -D examples/ulteo-ovd-slaveserver.suse.init $RPM_BUILD_ROOT/%{_sysconfdir}/init.d/ulteo-ovd-slaveserver

# TODO: remove this part when other roles will be created
%{__rm} -r $RPM_BUILD_ROOT%{_libdir}/python*/site-packages/ovd/Role/{ApplicationServer,FileServer}
%{__rm} $RPM_BUILD_ROOT%{_datadir}/ulteo/ovd/slaveserver/{apache2.conf,samba.conf}

%post -n ulteo-ovd-slaveserver
pymodule=/usr/lib64/python/site-packages/ovd
if [ "$(getconf LONG_BIT)" = "64" -a ! -e $pymodule ]; then
    %{__ln_s} -T /usr/lib/python2.6/site-packages/ovd $pymodule
fi

if [ "$1" = "1" ]; then
    %{_sbindir}/ovd-slaveserver-config --sm-address "127.0.0.1"
fi

%preun -n ulteo-ovd-slaveserver
if [ "$1" = "0" ]; then
    service ulteo-ovd-slaveserver stop
    chkconfig --del ulteo-ovd-slaveserver > /dev/null
fi

%postun -n ulteo-ovd-slaveserver
LOGDIR=%{_var}/log/ulteo/ovd
if [ "$1" = "0" ]; then
    %{__rm} -f $LOGDIR/slaveserver.log
fi

%clean -n ulteo-ovd-slaveserver
%{__rm} -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-slaveserver
%defattr(-,root,root)
%{_sysconfdir}
%{_var}
%{_libdir}/python*/site-packages/ovd_slaveserver-*.egg-info
%{_libdir}/python*/site-packages/ovd/*.py*
%{_libdir}/python*/site-packages/ovd/Role/*.py*
%{_libdir}/python*/site-packages/ovd/Platform/*
%{_libdir}/python*/site-packages/ovd/Communication/*
%{_sbindir}
%{_datadir}/ulteo/ovd/slaveserver/*.py
%config %{_sysconfdir}/init.d/ulteo-ovd-slaveserver
%config %{_sysconfdir}/ulteo/ovd/slaveserver.conf

%changelog -n ulteo-ovd-slaveserver
* Thu Sep 21 2011 Samuel Bovée <samuel@ulteo.com> 99.99.svn7524
- Initial release

###########################################
#%package -n ulteo-ovd-slaveserver-role-aps
###########################################

###########################################
#%package -n ulteo-ovd-slaveserver-role-fs
###########################################

