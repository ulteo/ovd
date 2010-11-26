Name: libchart
Version: 1.2.2
Release: 1

Summary: Simple PHP chart drawing library
License: GPL2
Group: Applications/web
Vendor: Ulteo SAS
Packager: Samuel Bovée <samuel@ulteo.com>
URL: http://www.ulteo.com
Distribution: RHEL 5.5

Source: %{name}-%{version}.tar.gz
Patch1: 01_no-image.diff
BuildArch: noarch
Buildroot: %{buildroot}

%description
Libchart is a free chart creation PHP library, that is easy to use.

###############################
%package -n php-libchart
###############################

Summary: Simple PHP chart drawing library
Group: Applications/web
Requires: php, php-gd

%description -n php-libchart
Libchart is a free chart creation PHP library, that is easy to use.

%prep -n php-libchart
%setup -q -n libchart
%patch1 -p1

%install -n php-libchart
PHPDIR=%{buildroot}/usr/share/php
LIBCHARTDIR=$PHPDIR/libchart
mkdir -p $PHPDIR
cp -r libchart $PHPDIR
cp -r demo $LIBCHARTDIR

rmdir $LIBCHARTDIR/demo/generated
rm -rf $LIBCHARTDIR/images
rm $LIBCHARTDIR/COPYING $LIBCHARTDIR/ChangeLog $LIBCHARTDIR/README

%clean -n php-libchart
rm -rf %{buildroot}

%files -n php-libchart
%defattr(-,root,root)
%doc libchart/COPYING
%doc libchart/ChangeLog
%doc libchart/README
/usr

%changelog -n php-libchart
* Wed Nov 10 2010 Samuel Bovée <samuel@ulteo.com> 1.2.1
- Initial release
