Name: ovd-session-manager
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - Session Manager
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.3

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: intltool

%description
This source package provides the Session Manager web services for the Ulteo
Open Virtual Desktop.

###########################################
%package -n ulteo-ovd-session-manager
###########################################

Summary: Ulteo Open Virtual Desktop - Session Manager
Requires: apache2, apache2-mod_php5, php5, php5-ldap, php5-curl, php5-mysql, php5-mbstring, php5-gettext, php5-pear, php5-ldap, php5-libchart, php5-imagick, curl, openssl

%description -n ulteo-ovd-session-manager
This package provides the Session Manager web services for the Ulteo
Open Virtual Desktop.

%prep -n ulteo-ovd-session-manager
%setup -q

%build -n ulteo-ovd-session-manager
./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var
make

%install -n ulteo-ovd-session-manager
make DESTDIR=$RPM_BUILD_ROOT install
# install the logrotate example
mkdir -p $RPM_BUILD_ROOT/etc/logrotate.d
install -m 0644 examples/ulteo-sm.logrotate $RPM_BUILD_ROOT/etc/logrotate.d/sessionmanager
# hack to not provide /usr/bin/php (zypper)
sed -i -e 's,^#!/usr/bin/php$,#!/usr/bin/php5,' $(find $RPM_BUILD_ROOT -name *.php*)

%clean -n ulteo-ovd-session-manager
rm -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-session-manager
%defattr(-,root,root)
/usr/*
/etc/*
%config /etc/ulteo/sessionmanager/apache2.conf
%config /etc/ulteo/sessionmanager/cron.php
%config /etc/logrotate.d/sessionmanager
%defattr(0660,wwwrun,root)
%config /etc/ulteo/sessionmanager/config.inc.php
%defattr(2770,wwwrun,root)
/var/log/ulteo/*
/var/spool/ulteo/*

%changelog -n ulteo-ovd-session-manager
* Fri Jan 02 2009 Gauvain Pocentek <gauvain@ulteo.com> 1.0~svn00130-1
- Initial release
