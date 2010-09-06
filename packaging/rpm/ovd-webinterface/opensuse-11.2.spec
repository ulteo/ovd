Name: ovd-webinterface
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - web interface
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
This interface is a web client for Ulteo OVD.

###########################################
%package -n ulteo-ovd-webinterface
###########################################

Summary: Ulteo Open Virtual Desktop - web interface
Requires: apache2-mod_php5, php5, php5-curl, ulteo-ovd-applets

%description -n ulteo-ovd-webinterface
This interface is a web client for Ulteo OVD.

%prep -n ulteo-ovd-webinterface
%setup -q

%build -n ulteo-ovd-webinterface
./configure --prefix=/usr --sysconfdir=/etc --without-ulteo-applets
make

%install -n ulteo-ovd-webinterface
make DESTDIR=$RPM_BUILD_ROOT install

%post -n ulteo-ovd-webinterface
A2CONFDIR=/etc/apache2/conf.d
CONFDIR=/etc/ulteo/webinterface

a2enmod php5 > /dev/null

if [ ! -e $A2CONFDIR/webinterface.conf ]; then
    ln -sf $CONFDIR/apache2.conf $A2CONFDIR/webinterface.conf
    if apache2ctl configtest 2>/dev/null; then
        service apache2 reload || true
    else
        echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
    fi
fi

%postun -n ulteo-ovd-webinterface
A2CONFDIR=/etc/apache2/conf.d
if [ -e /etc/apache2/conf.d/webinterface ]; then
    rm -f $A2CONFDIR/webinterface
    if apache2ctl configtest 2>/dev/null; then
        service apache2 reload || true
    else
        echo << EOF
"Your apache configuration is broken!
Correct it and restart apache."
EOF
    fi
fi

%clean -n ulteo-ovd-webinterface
rm -rf $RPM_BUILD_ROOT

%files -n ulteo-ovd-webinterface
%defattr(-,root,root)
/usr/*
/etc/*
%config /etc/ulteo/webinterface/apache2.conf
%config /etc/ulteo/webinterface/config.inc.php

%changelog -n ulteo-ovd-webinterface
* Fri Aug 13 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4145
- Initial release
