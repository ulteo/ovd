Summary: Ulteo Open Virtual Desktop - Session Manager
Name: ovd-session-manager
Version: @VERSION@
Release: 1
License: GPL2
Group: Applications/System
Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Distribution: OpenSUSE 11.3
Requires: apache2, apache2-mod_php5, curl, php5, php5-ldap, php5-mysql, php5-mbstring, php5-xmlwriter, php5-gettext, php5-curl, ovd-applets
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>

%description
This package provides the Session Manager web services for the Ulteo
Open Virtual Desktop.

%prep
%setup -q

%build
./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var
make

%install
make DESTDIR=$RPM_BUILD_ROOT install
# install the logrotate example
mkdir -p $RPM_BUILD_ROOT/etc/logrotate.d
install -m 0644 examples/ulteo-sm.logrotate $RPM_BUILD_ROOT/etc/logrotate.d/sessionmanager

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
/usr/*
%config /etc/ulteo/sessionmanager/apache2.conf
%config /etc/ulteo/sessionmanager/cron.php
%config /etc/logrotate.d/sessionmanager
%defattr(0660,wwwrun,root)
%config /etc/ulteo/sessionmanager/config.inc.php
%defattr(2770,wwwrun,root)
/var/log/ulteo/*
/var/spool/ulteo/*

%changelog
* Fri Jan 02 2009 Gauvain Pocentek <gauvain@ulteo.com> 1.0~svn00130-1
- Initial release
