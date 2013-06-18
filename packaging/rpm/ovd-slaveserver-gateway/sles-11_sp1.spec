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

Name: ovd-slaveserver-gateway
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - gateway role for slave server
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
Ulteo Open Virtual Desktop gateway role for daemon server

###########################################
%package -n ulteo-ovd-slaveserver-role-gateway
###########################################

Summary: Ulteo Open Virtual Desktop - gateway role for slave server
Group: Applications/System
Requires: python, ulteo-ovd-slaveserver, openssl, python-openssl

%description -n ulteo-ovd-slaveserver-role-gateway
Gateway role for the Ulteo OVD slave server.

%prep -n ulteo-ovd-slaveserver-role-gateway
%setup -q

%build -n ulteo-ovd-slaveserver-role-gateway
%{__python} setup.py build

%install -n ulteo-ovd-slaveserver-role-gateway
%{__python} setup.py install --root $RPM_BUILD_ROOT --prefix %{_prefix}

%{__rm} -r $RPM_BUILD_ROOT{%{_datadir},%{_sysconfdir},%{_sbindir}}
%{__rm} -r $RPM_BUILD_ROOT%{_libdir}/python*/site-packages/ovd/{Communication,Platform,*.py*}
%{__rm} -r $RPM_BUILD_ROOT%{_libdir}/python*/site-packages/ovd/Role/{*.py*,ApplicationServer,FileServer}

%post -n ulteo-ovd-slaveserver-role-gateway
CONFDIR=%{_sysconfdir}/ulteo/ovd
if [ ! -e $CONFDIR/gateway.pem ]; then
    openssl genrsa 1024 > $CONFDIR/gateway.pem 2> /dev/null
    openssl req -new -x509 -days 3650 -key $CONFDIR/gateway.pem -batch >> $CONFDIR/gateway.pem
    chmod 400 $CONFDIR/gateway.pem
fi

[ "$1" = "1" ] && %{_sbindir}/ovd-slaveserver-role add Gateway

%postun -n ulteo-ovd-slaveserver-role-gateway
CONFDIR=%{_sysconfdir}/ulteo/ovd
if [ "$1" = "0" ]; then
    %{__rm} -f $CONFDIR/gateway.pem
    %{_sbindir}/ovd-slaveserver-role del Gateway
fi

%clean -n ulteo-ovd-slaveserver-role-gateway
%{__rm} -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-slaveserver-role-gateway
%defattr(-,root,root)
%{_libdir}/python*/site-packages/ovd/Role/Gateway/*.py*
%{_libdir}/python*/site-packages/ovd_slaveserver_gateway-*.egg-info

%changelog -n ulteo-ovd-slaveserver-role-gateway
* Thu Sep 21 2011 Samuel Bovée <samuel@ulteo.com> 99.99.svn7524
- Initial release
