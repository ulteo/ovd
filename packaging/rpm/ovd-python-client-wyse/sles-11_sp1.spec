# Copyright (C) 2013 Ulteo SAS
# http://www.ulteo.com
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

Name: ovd-python-client-wyse
Version: @VERSION@
Release: @RELEASE@

Summary: Ulteo Open Virtual Desktop - python client for wyse
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: David PHAM-VAN <d.pham-van@ulteo.com>
Distribution: SLES 11 SP1

Source: %{name}-%{version}.tar.gz
BuildArch: i586
Buildroot: %{buildroot}

%description
This application is used in the Open Virtual Desktop to display the user
session and launch applications via a native client on wyse thin clients.

%prep
%setup -q
mkdir -p etc/addons.d/%{name}
mkdir -p usr/bin
mkdir -p usr/lib
cp ovd.py etc/addons.d/%{name}
cp ovd-client-gtk.py etc/addons.d/%{name}
chmod +x etc/addons.d/%{name}/ovd-client-gtk.py
ln -s /etc/addons.d/%{name}/ovd-client-gtk.py usr/bin/ovd-client

svn co https://svn.ulteo.com/ovd/trunk/i18n/uovdclient
cd uovdclient
make install DESTDIR=..
cd ..

git clone git://github.com/FreeRDP/FreeRDP.git
cd FreeRDP
git checkout 1.0.2
sed -i s@\${FREERDP_PLUGIN_PATH}@/etc/addons.d/%{name}/freerdp@g libfreerdp-utils/CMakeLists.txt
cmake . -DWITH_CUPS=ON -DWITH_FFMPEG=OFF -DCMAKE_INSTALL_PREFIX=../etc/addons.d/%{name} -DCMAKE_INSTALL_BINDIR=. -DCMAKE_INSTALL_LIBDIR=. -DCMAKE_INSTALL_DATAROOTDIR=.
make -j 4
make install
cd ..
ln -s /etc/addons.d/%{name}/xfreerdp usr/bin/xfreerdp
for i in etc/addons.d/%{name}/*.so*; do ln -s /$i usr/lib/$(basename $i); done
rm -rf etc/addons.d/%{name}/include etc/addons.d/%{name}/pkgconfig

%install
mkdir -p %{buildroot}/var/lib/addons
mksquashfs etc usr %{buildroot}/var/lib/addons/%{name}.squash

%post
squash-merge -m %{name} || true

%preun
squash-merge -u %{name} || true

%files
%defattr(-,root,root)
/var/lib/addons/%{name}.squash

%clean
rm -rf %{buildroot}

%changelog
* Wed Jun 15 2013 David PHAM-VAN <d.pham-van@ulteo.com> 99.99
- Initial release
