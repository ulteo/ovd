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

Name: ovd-slaveserver
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - slave server
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Julien LANGLOIS <julien@ulteo.com>
Distribution: RHEL 6.0

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: python, pysvn
Buildroot: %{buildroot}

%description
This daemon manages the Open Virtual Desktop servers.

%prep
%setup -q

%build
%{__python} setup.py build

%install
%{__python} setup.py install --root $RPM_BUILD_ROOT --prefix %{_prefix}
%{__mkdir_p} $RPM_BUILD_ROOT%{_var}/log/ulteo/ovd
%{__ln_s} /usr/share/ulteo/ovd/slaveserver/ovd-slaveserver-role.py $RPM_BUILD_ROOT%{_sbindir}/ovd-slaveserver-role
%{__ln_s} /usr/share/ulteo/ovd/slaveserver/ulteo-ovd-slaveserver.py $RPM_BUILD_ROOT%{_sbindir}/ulteo-ovd-slaveserver
%{__install} -T -D examples/ulteo-ovd-slaveserver.rhel.init $RPM_BUILD_ROOT/%{_sysconfdir}/init.d/ulteo-ovd-slaveserver

%clean
%{__rm} -rf $RPM_BUILD_ROOT

###########################################
%package -n ulteo-ovd-slaveserver
###########################################

Summary: Ulteo Open Virtual Desktop - slave server
Group: Applications/System
Requires: python

%description -n ulteo-ovd-slaveserver
This daemon manages the Open Virtual Desktop servers.

%post -n ulteo-ovd-slaveserver
if [ "$1" = "1" ]; then
    %{_sbindir}/ovd-slaveserver-config --sm-address "127.0.0.1"
    chkconfig ulteo-ovd-slaveserver on
fi

service ulteo-ovd-slaveserver restart

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

%files -n ulteo-ovd-slaveserver
%defattr(-,root,root)
/usr/lib/python*/site-packages/ovd_slaveserver-*.egg-info
/usr/lib/python*/site-packages/ovd/*.py*
/usr/lib/python*/site-packages/ovd/Communication/*.py*
/usr/lib/python*/site-packages/ovd/Platform/*.py*
/usr/lib/python*/site-packages/ovd/Platform/Linux/*.py*
/usr/lib/python*/site-packages/ovd/Role/*.py*
%config %{_sysconfdir}/init.d/ulteo-ovd-slaveserver
%config %{_sysconfdir}/ulteo/ovd/slaveserver.conf
%{_datadir}/ulteo/ovd/slaveserver/*.py
%{_sbindir}/ulteo-ovd-slaveserver
%{_sbindir}/ovd-slaveserver-role
%{_sbindir}/ovd-slaveserver-config
%{_var}/log/ulteo/ovd

%changelog -n ulteo-ovd-slaveserver
* Thu Sep 21 2011 Samuel Bovée <samuel@ulteo.com> 99.99.svn7524
- Initial release

###########################################
%package -n ulteo-ovd-slaveserver-role-aps
###########################################

Summary: Ulteo Open Virtual Desktop - application server role for slave server
Group: Applications/System
Requires: python, ulteo-ovd-slaveserver, ulteo-ovd-shells, ulteo-ovd-externalapps-client, xrdp-python, xrdp-seamrdp, xrdp-rdpdr, xrdp-printer, xrdp-sound, xrdp-clipboard, ImageMagick, passwd, rsync, cifs-utils, pyxdg

%description -n ulteo-ovd-slaveserver-role-aps
Application server role for the Ulteo OVD slave server

%post -n ulteo-ovd-slaveserver-role-aps
if [ "$1" = "1" ]; then
    %{_sbindir}/ovd-slaveserver-role add ApplicationServer
    service ulteo-ovd-slaveserver restart
fi

%postun -n ulteo-ovd-slaveserver-role-aps
if [ "$1" = "0" ]; then
    %{_sbindir}/ovd-slaveserver-role del ApplicationServer
    service ulteo-ovd-slaveserver restart
fi

%files -n ulteo-ovd-slaveserver-role-aps
%defattr(-,root,root)
/usr/lib/python*/site-packages/ovd/Role/ApplicationServer/*.py*
/usr/lib/python*/site-packages/ovd/Role/ApplicationServer/Platform/*.py*
/usr/lib/python*/site-packages/ovd/Role/ApplicationServer/Platform/Linux/*.py*
%config %{_sysconfdir}/ulteo/ovd/profiles_filter.conf

###########################################
%package -n ulteo-ovd-slaveserver-role-fs
###########################################

Summary: Ulteo Open Virtual Desktop - file server role for slave server
Group: Applications/System
Requires: python, python-inotify, ulteo-ovd-slaveserver, samba, httpd

%description -n ulteo-ovd-slaveserver-role-fs
File server role for the Ulteo OVD slave server

%post -n ulteo-ovd-slaveserver-role-fs
if [ "$1" = "1" ]; then
    %{_sbindir}/ovd-slaveserver-role add FileServer
fi

%postun -n ulteo-ovd-slaveserver-role-fs
if [ "$1" = "0" ]; then
    %{_sbindir}/ovd-slaveserver-role del FileServer
    service ulteo-ovd-slaveserver restart
fi

%files -n ulteo-ovd-slaveserver-role-fs
%defattr(-,root,root)
/usr/lib/python*/site-packages/ovd/Role/FileServer/*.py*
%config %{_datadir}/ulteo/ovd/slaveserver/apache2.conf
%config %{_datadir}/ulteo/ovd/slaveserver/samba.conf
