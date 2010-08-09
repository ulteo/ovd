Name: libchart
Version: 1.2.1
Release: 1

Summary: Simple PHP chart drawing library
License: GPL2
Group: Applications/web
Vendor: Ulteo SAS
Packager: Samuel Bovée <samuel@ulteo.com>
URL: http://www.ulteo.com
Distribution: OpenSUSE 11.2

Source: %{name}-%{version}.tar.gz
Patch1: 01_no-image.diff
BuildArch: noarch

Requires: php5, php5-gd

%description
Libchart is a free chart creation PHP library, that is easy to use.

###############################
%package -n php5-libchart
###############################

Summary: Simple PHP chart drawing library

%description -n php5-libchart
Libchart is a free chart creation PHP library, that is easy to use.

%prep -n php5-libchart
%setup -q
%patch1 -p1

%install -n php5-libchart
PHP5DIR=$RPM_BUILD_ROOT/usr/share/php5
mkdir -p $PHP5DIR
cp -r libchart $PHP5DIR
cp -r demo $PHP5DIR/libchart
rmdir $PHP5DIR/libchart/demo/generated
rm -rf $PHP5DIR/libchart/images

%clean -n php5-libchart
rm -rf $RPM_BUILD_ROOT

%files -n php5-libchart
%defattr(-,root,root)
/usr

%changelog -n php5-libchart
* Fri Jul 08 2010 Samuel Bovée <samuel@ulteo.com> 1.2.1
- Initial release
