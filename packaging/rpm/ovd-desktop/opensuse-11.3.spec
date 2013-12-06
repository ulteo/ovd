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

Name: ovd-desktop
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - default desktop
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Julien LANGLOIS <julien@ulteo.com>
Distribution: RHEL 6.0

Source: %{name}-%{version}.tar.gz
BuildRequires: gcc, autoconf, automake, make, intltool, libtool
BuildRequires: xfce4-dev-tools, libxfce4util-devel, libxfcegui4-devel, xfce4-panel-devel, gtk2-devel
%if %{defined suse_version}
BuildRequires: gconf2-devel, thunar-devel
BuildRequires: rsvg-view
%else
BuildRequires: GConf2-devel, Thunar-devel
%endif


%description
default OVD desktop

%prep
%setup -q
# ./autogen
# %setup macro is not working good with index.theme

%build
%configure --prefix=%{_prefix} --libdir=%{_libdir} -sysconfdir=%{_sysconfdir} --localstatedir=%{_localstatedir} --enable-png-icons
make

%install
make DESTDIR=%{buildroot} install

rm -rf %{buildroot}/etc/xdg
rm -rf %{buildroot}/usr/bin/xfsm-compat
rm -rf %{buildroot}/usr/lib*/plymouth
rm -rf %{buildroot}/var/spool/menus-common/disconnect.desktop
rm -rf %{buildroot}/var/spool/menus-common/quit-legacy.desktop

%clean
rm -rf %{buildroot}


%changelog
* Thu Sep 19 2013 Julien LANGLOIS <julien@ulteo.com> a2ad72a78
- Initial release


###########################################
%package -n ulteo-ovd-desktop
###########################################

Summary: Ulteo Open Virtual Desktop
Group: Applications/System
Requires: apparmor-profiles
Requires: xfce4-session, xfce4-settings, xfce4-notifyd, xfce4-panel, xfce4-taskmanager
Requires: ulteo-ovd-desktop-gtk-theme, ulteo-xfce4-restricted-menu-plugin, ulteo-ovd-slaveserver-role-aps
BuildArch: noarch

%description -n ulteo-ovd-desktop
A desktop based on Xfce 4.4 for Ulteo OVD solution

%files -n ulteo-ovd-desktop
%defattr(-,root,root)
/etc/ulteo/xdg/*
%config /etc/restricted-menu.cfg
/var/spool/menus-common/quit.desktop
/usr/share/pixmaps/*
/usr/share/wallpapers/*


###########################################
%package -n ulteo-ovd-desktop-gtk-theme
###########################################

Summary: Ulteo Open Virtual Desktop GTK Theme
Group: Applications/System
Requires: gtk-xfce-engine
BuildArch: noarch

%description -n ulteo-ovd-desktop-gtk-theme
A GTK theme icons for Ulteo OVD solution

%files -n ulteo-ovd-desktop-gtk-theme
%defattr(-,root,root)
/usr/share/icons/*
/usr/share/themes/*

###########################################
%package -n ulteo-xfce4-restricted-menu-plugin
###########################################

Summary: Ulteo Open Virtual Desktop - Restricted menu for the xfce4 panel
Group: Applications/System
Requires: xfce4-panel

%description -n ulteo-xfce4-restricted-menu-plugin
 This plugin for the Xfce panel allows the administrateur to restrict the
 access to the available applications.

%files -n ulteo-xfce4-restricted-menu-plugin
%defattr(-,root,root)
%{_libdir}/xfce4/*
/usr/share/xfce4/*
/usr/share/locale/*

###########################################
%package -n ulteo-ovd-desktop-apparmor
###########################################
BuildArch: noarch
Group: Applications/System
Summary: Ulteo Open Virtual Desktop

%description -n ulteo-ovd-desktop-apparmor
 Ulteo Open Virtual Desktop

%files -n ulteo-ovd-desktop-apparmor
%defattr(-,root,root)
%config /etc/apparmor.d/*

###########################################
%package -n ulteo-ovd-logout-dialog
###########################################
BuildArch: noarch
Requires: python-gtk2
Summary: Ulteo Open Virtual Desktop - Logout dialog

%description -n ulteo-ovd-logout-dialog
 Ulteo Open Virtual Desktop

%files -n ulteo-ovd-logout-dialog
%defattr(-,root,root)
/usr/bin/ulteo-logout-dialog
