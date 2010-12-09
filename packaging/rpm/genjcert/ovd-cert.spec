# Copyright (C) 2010 Ulteo SAS
# http://www.ulteo.com
# Author Samuel BOVEE <samuel@ulteo.com>
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

Name: genjcert
Version: 0.1
Release: 1

Summary: Ulteo Open Virtual Desktop - signed certificate generator
License: GPL2
Group: Applications/System
Vendor: Ulteo SAS
URL: http://www.ulteo.com
Packager: Samuel Bovée <samuel@ulteo.com>

Source: genkeystore.sh
BuildArch: noarch
Buildrequires: pwgen, java-1_6_0-openjdk

%description
This package provides an Ulteo certificate used to signed the Open Virtual
Desktop java applet.

###########################################
%package -n ulteo-ovd-cert
###########################################

Summary: Ulteo Open Virtual Desktop - signed certificate generator

%description -n ulteo-ovd-cert
This package provides an Ulteo certificate used to signed the Open Virtual
Desktop java applet.

%prep -n ulteo-ovd-cert
pwd
cp ../SOURCES/genkeystore.sh .

%build -n ulteo-ovd-cert
rm -f password keystore
./genkeystore.sh -d "cn=Ulteo OVD,ou=Ulteo,o=Ulteo,c=FR" -a Ulteo > password

%install -n ulteo-ovd-cert
OVDCERTDIR=$RPM_BUILD_ROOT/usr/share/ulteo/ovd-cert/
mkdir -p $OVDCERTDIR
install -m 644 keystore $OVDCERTDIR
install -m 644 password $OVDCERTDIR

%files -n ulteo-ovd-cert
%defattr(-,root,root)
/

%changelog -n ulteo-ovd-cert
* Fri Aug 13 2010 Samuel Bovée <samuel@ulteo.com> 99.99.svn4145
- Initial release
