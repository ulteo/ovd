Name: ovd-web-client
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - web client
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.2

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: intltool

%description
This is a web based client for Ulteo OVD.

###########################################
%package -n ulteo-ovd-web-client
###########################################

Summary: Ulteo Open Virtual Desktop - web client
Requires: apache2-mod_php5, php5, php5-curl, ulteo-ovd-applets, ulteo-ovd-l10n

%description -n ulteo-ovd-web-client
This is a web based client for Ulteo OVD.

%prep -n ulteo-ovd-web-client
%setup -q

%build -n ulteo-ovd-web-client
./configure --prefix=/usr --sysconfdir=/etc --without-ulteo-applets

%install -n ulteo-ovd-web-client
make DESTDIR=$RPM_BUILD_ROOT install
cp -a ajaxplorer $RPM_BUILD_ROOT/usr/share/ulteo/webclient

%post -n ulteo-ovd-web-client
A2CONFDIR=/etc/apache2/conf.d
CONFDIR=/etc/ulteo/webclient

a2enmod php5 > /dev/null

if [ ! -e $A2CONFDIR/webclient.conf ]; then
    ln -sf $CONFDIR/apache2.conf $A2CONFDIR/webclient.conf
    if apache2ctl configtest 2>/dev/null; then
        service apache2 reload || true
    else
        echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
    fi
fi

%postun -n ulteo-ovd-web-client
A2CONFDIR=/etc/apache2/conf.d
if [ -e /etc/apache2/conf.d/webclient ]; then
    rm -f $A2CONFDIR/webclient
    if apache2ctl configtest 2>/dev/null; then
        service apache2 reload || true
    else
        echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
    fi
fi

%clean -n ulteo-ovd-web-client
rm -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-web-client
%defattr(-,root,root)
/usr/*
/etc/*
%config /etc/ulteo/webclient/apache2.conf
%config /etc/ulteo/webclient/config.inc.php

%changelog -n ulteo-ovd-web-client
* Fri Aug 13 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4145
- Initial release

##############################################
%package -n ulteo-ovd-web-client-ajaxplorer
##############################################

Summary: Ulteo Open Virtual Desktop - Ajaxplorer portal
Group: Applications/System
Requires: ulteo-ovd-web-client

%description -n ulteo-ovd-web-client-ajaxplorer
This is a web based client for Ulteo OVD.

%post -n ulteo-ovd-web-client-ajaxplorer
AJAXPLORERDIR=/usr/share/ulteo/webclient/ajaxplorer
chown wwwrun:wwwrun $AJAXPLORERDIR/server/logs

%files -n ulteo-ovd-web-client-ajaxplorer
%defattr(-,root,root)
/usr/share/ulteo/webclient/ajaxplorer
