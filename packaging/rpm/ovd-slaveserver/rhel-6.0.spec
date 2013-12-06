# Copyright (C) 2011-2013 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
# Author Julien LANGLOIS <julien@ulteo.com> 2013
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

%define python_sitelib %(%{__python} -c "from distutils.sysconfig import get_python_lib; print get_python_lib()")

Name: ovd-slaveserver
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - slave server
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: David PHAM-VAN <d.pham-van@ulteo.com>

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: python
Buildroot: %{buildroot}


%description
This daemon manages the Open Virtual Desktop servers.


%prep
%setup -q


%build
%{__python} setup.py build


%install
%{__python} setup.py install --root %{buildroot} --prefix %{_prefix}
%{__mkdir_p} %{buildroot}%{_var}/log/ulteo/ovd
%{__ln_s} /usr/share/ulteo/ovd/slaveserver/ovd-slaveserver-role.py %{buildroot}%{_sbindir}/ovd-slaveserver-role
%{__ln_s} /usr/share/ulteo/ovd/slaveserver/ulteo-ovd-slaveserver.py %{buildroot}%{_sbindir}/ulteo-ovd-slaveserver
%{__install} -T -D examples/ulteo-ovd-slaveserver.rhel.init %{buildroot}/%{_sysconfdir}/init.d/ulteo-ovd-slaveserver


%clean
%{__rm} -rf %{buildroot}


###########################################
%package -n ulteo-ovd-slaveserver
###########################################

Summary: Ulteo Open Virtual Desktop - slave server
Group: Applications/System
Requires: python


%description -n ulteo-ovd-slaveserver
This daemon manages the Open Virtual Desktop servers.


%post -n ulteo-ovd-slaveserver
pymodule=/usr/lib64/python/site-packages/ovd
if [ "$(getconf LONG_BIT)" = "64" -a ! -e $pymodule ]; then
    %{__ln_s} -T /usr/lib/python2.6/site-packages/ovd $pymodule
fi

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
%{python_sitelib}/ovd_slaveserver-*.egg-info
%{python_sitelib}/ovd/*.py*
%{python_sitelib}/ovd/Communication/*.py*
%{python_sitelib}/ovd/Platform/*.py*
%{python_sitelib}/ovd/Platform/Linux/*.py*
%{python_sitelib}/ovd/Role/*.py*
%config %{_sysconfdir}/init.d/ulteo-ovd-slaveserver
%config %{_sysconfdir}/ulteo/ovd/slaveserver.conf
%{_datadir}/ulteo/ovd/slaveserver/*.py
%{_sbindir}/ulteo-ovd-slaveserver
%{_sbindir}/ovd-slaveserver-role
%{_sbindir}/ovd-slaveserver-config
%{_var}/log/ulteo/ovd


%changelog -n ulteo-ovd-slaveserver
* Fri Jun 21 2013 David PHAM-VAN <d.pham-van@ulteo.com> 4.0
- Corrections
* Thu Sep 21 2011 Samuel Bovée <samuel@ulteo.com> 99.99.svn7524
- Initial release


###########################################
%package -n ulteo-ovd-slaveserver-role-aps
###########################################

Summary: Ulteo Open Virtual Desktop - application server role for slave server
Group: Applications/System
%if %{defined rhel}
Requires: python, ulteo-ovd-slaveserver, ulteo-ovd-shells, ulteo-ovd-externalapps-client, xrdp-python, xrdp-seamrdp, xrdp-rdpdr, xrdp-printer, xrdp-sound, xrdp-clipboard, ImageMagick, passwd, rsync, cifs-utils, pyxdg
%else
Requires: python, ulteo-ovd-slaveserver, ulteo-ovd-shells, ulteo-ovd-externalapps-client, xrdp-python, xrdp-seamrdp, xrdp-rdpdr, xrdp-printer, xrdp-sound, xrdp-clipboard, ImageMagick, pwdutils, rsync, cifs-utils, python-xdg
%endif


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
%{python_sitelib}/ovd/Role/ApplicationServer/*.py*
%{python_sitelib}/ovd/Role/ApplicationServer/Platform/*.py*
%{python_sitelib}/ovd/Role/ApplicationServer/Platform/Linux/*.py*
%config %{_sysconfdir}/ulteo/ovd/profiles_filter.conf


###########################################
%package -n ulteo-ovd-slaveserver-role-fs
###########################################

Summary: Ulteo Open Virtual Desktop - file server role for slave server
Group: Applications/System
%if %{defined rhel}
Requires: python, python-inotify, ulteo-ovd-slaveserver, samba, httpd, ulteo-ovd-regular-union-fs
%else
Requires: python, python-inotify, ulteo-ovd-slaveserver, samba, apache2, ulteo-ovd-regular-union-fs
# There is no python-inotify available package (or equivalent) on OpenSuse. Will become useless with RUFS
%endif

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
%{python_sitelib}/ovd/Role/FileServer/*.py*
%config %{_datadir}/ulteo/ovd/slaveserver/apache2.conf
%config %{_datadir}/ulteo/ovd/slaveserver/samba.conf


###########################################
%package -n ulteo-ovd-slaveserver-role-web
###########################################

Summary: Ulteo Open Virtual Desktop - web gateway role for slave server
Group: Applications/System
%if %{defined rhel}
Requires: python, ulteo-ovd-slaveserver, openssl, pyOpenSSL, python-ntlm, python-mechanize, python-pycurl
%else
Requires: python, ulteo-ovd-slaveserver, openssl, python-openssl, python-ntlm, python-mechanize, python-curl
%endif


%description -n ulteo-ovd-slaveserver-role-web
Web gateway role for slave server


%post -n ulteo-ovd-slaveserver-role-web
if [ "$1" = "1" ]; then
    %{_sbindir}/ovd-slaveserver-role add WebApps
fi
CONFDIR=%{_sysconfdir}/ulteo/ovd
if [ ! -e $CONFDIR/WebApps.pem ]; then
    openssl genrsa 1024 > $CONFDIR/WebApps.pem 2> /dev/null
    openssl req -new -x509 -days 3650 -key $CONFDIR/WebApps.pem -batch >> $CONFDIR/WebApps.pem
    chmod 400 $CONFDIR/WebApps.pem
fi


%postun -n ulteo-ovd-slaveserver-role-web
CONFDIR=%{_sysconfdir}/ulteo/ovd
if [ "$1" = "0" ]; then
    %{__rm} -f $CONFDIR/WebApps.pem
    %{_sbindir}/ovd-slaveserver-role del WebApps
    service ulteo-ovd-slaveserver restart
fi


%files -n ulteo-ovd-slaveserver-role-web
%defattr(-,root,root)
%{python_sitelib}/ovd/Role/WebApps/*.py*
