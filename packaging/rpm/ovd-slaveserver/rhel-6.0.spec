# Copyright (C) 2011-2014 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com> 2011
# Author Julien LANGLOIS <julien@ulteo.com> 2013
# Author David PHAM-VAN <d.pham-van@ulteo.com> 2013, 2014
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

%if %{defined suse_version}
# Change some default configuration related to OpenSUSE distribution
sed -e "s/# linux_skel_directory = /linux_skel_directory = \/etc\/skel/" -i  %{buildroot}/%{_sysconfdir}/ulteo/ovd/slaveserver.conf
sed -e "s/# linux_fuse_group = /linux_fuse_group = trusted/" -i %{buildroot}/%{_sysconfdir}/ulteo/ovd/slaveserver.conf
%else
# Change some default configuration related to RHEL distribution
sed -e "s/# linux_set_password = /linux_set_password = passwd \"%s\"/" -i  %{buildroot}/%{_sysconfdir}/ulteo/ovd/slaveserver.conf
sed -e "s/# linux_unset_password = /linux_unset_password = passwd -d \"%s\"/" -i  %{buildroot}/%{_sysconfdir}/ulteo/ovd/slaveserver.conf
%endif

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
%if %{defined suse_version}
pymodule=/usr/lib64/python/site-packages/ovd
if [ "$(getconf LONG_BIT)" = "64" -a ! -e $pymodule ]; then
    %{__ln_s} -T /usr/lib/python2.6/site-packages/ovd $pymodule
fi
%endif

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


###########################################
%package -n ulteo-ovd-slaveserver-role-aps
###########################################

Summary: Ulteo Open Virtual Desktop - application server role for slave server
Group: Applications/System
Requires: python, ulteo-ovd-slaveserver, ulteo-ovd-shells, ulteo-ovd-externalapps-client, uxda-server-python, uxda-server-seamrdp, uxda-server-rdpdr, uxda-server-printer, uxda-server-sound, uxda-server-clipboard, ImageMagick, cifs-utils, rsync, xdg-utils, ulteo-ovd-regular-union-fs
%if %{defined rhel}
Requires: passwd, pyxdg
%else
Requires: pwdutils, python-xdg
%endif


%description -n ulteo-ovd-slaveserver-role-aps
Application server role for the Ulteo OVD slave server


%post -n ulteo-ovd-slaveserver-role-aps
if [ "$1" = "1" ]; then
    %{_sbindir}/ovd-slaveserver-role add ApplicationServer
    service ulteo-ovd-slaveserver restart
fi

[ -d /usr/share/ovd ] || mkdir /usr/share/ovd
for i in icons pixmaps mime themes glib-2.0; do
	[ -d /usr/share/$i -a ! -e /usr/share/ovd/$i ] && ln -s /usr/share/$i /usr/share/ovd/
done


%postun -n ulteo-ovd-slaveserver-role-aps
if [ "$1" = "0" ]; then
    %{_sbindir}/ovd-slaveserver-role del ApplicationServer
    service ulteo-ovd-slaveserver restart
fi

for i in icons pixmaps mime themes glib-2.0; do
	[ -L /usr/share/ovd/$i ] && rm /usr/share/ovd/$i
done
rmdir /usr/share/ovd || echo "Do not delete /usr/share/ovd"


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
Requires: python, ulteo-ovd-slaveserver, samba, ulteo-ovd-regular-union-fs
%if %{defined rhel}
Requires: httpd
%else
Requires: apache2
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
