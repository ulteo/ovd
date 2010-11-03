Name: ovd-l10n
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - localization
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>
Distribution: OpenSUSE 11.1

Source: %{name}-%{version}.tar.gz
BuildArch: noarch
Buildrequires: gettext, make
Buildroot: %{buildroot}

%description
Localization package for Ulteo Open Virtual Desktop

###########################################
%package -n ulteo-ovd-l10n
###########################################

Summary: Ulteo Open Virtual Desktop - localization
Group: Applications/System

%description -n ulteo-ovd-l10n
Localization package for Ulteo Open Virtual Desktop

%prep -n ulteo-ovd-l10n
%setup -q

%build -n ulteo-ovd-l10n
make

%install -n ulteo-ovd-session-manager
make DESTDIR=%{buildroot} install

%clean -n ulteo-ovd-l10n
make clean
rm -rf %{buildroot}

%files -n ulteo-ovd-l10n
%defattr(-,root,root)
/usr/share/*

%changelog -n ulteo-ovd-l10n
* Thu Nov 02 2010 Samuel Bovée <samuel@ulteo.com> 3.0+svn5123-1
- Initial release
