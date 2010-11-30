%define php_ver %((echo %{default_apiver}; php -i 2>/dev/null | sed -n 's/^PHP Version => //p') | tail -1)
%{!?php_extdir: %{expand: %%global php_extdir %(php-config --extension-dir)}}

%define pecl_name imagick
%define pecl_xmldir /usr/share/doc/packages/
# maybe not the good folder ! or remove it ?

Summary:       Extension to create and modify images using ImageMagick
Name:          php5-%{pecl_name}
Version:       3.0.1
Release:       2
License:       PHP
Group:         Development/Languages
Vendor:        Ulteo SAS
Packager:      Samuel Bovée <samuel@ulteo.com>
Distribution:  SLES 11 SP1

Source:        http://pecl.php.net/get/%{pecl_name}-%{version}.tgz
BuildRoot:     %{_tmppath}/%{name}-%{version}-%{release}-root
BuildRequires: php5-devel >= 5.1.3, php5-pear, ImageMagick-devel >= 6.2.4

%if %{?php_zend_api}0
Requires:      php(zend-abi) = %{php_zend_api}
Requires:      php(api) = %{php_core_api}
%else
Requires:      php = %{php_ver}
%endif
Provides:      php-pecl(%{pecl_name}) = %{version}

%description
Imagick is a native php extension to create and modify images
using the ImageMagick API.

%prep
%setup -q -c
cd %{pecl_name}-%{version}

%build
cd %{pecl_name}-%{version}
%{_bindir}/phpize
%configure --with-imagick=%{prefix}
%{__make} %{?_smp_mflags}


%install
pushd %{pecl_name}-%{version}
%{__rm} -rf %{buildroot}
%{__make} install INSTALL_ROOT=%{buildroot}

# Drop in the bit of configuration
%{__mkdir_p} %{buildroot}%{_sysconfdir}/php5/conf.d
%{__cat} > %{buildroot}%{_sysconfdir}/php5/conf.d/%{name}.ini << 'EOF'
; Enable %{pecl_name} extension module
extension = %{pecl_name}.so

; Option not documented
imagick.locale_fix=0
EOF

popd
# Install XML package description
mkdir -p $RPM_BUILD_ROOT/%{pecl_xmldir}
install -pm 644 package.xml $RPM_BUILD_ROOT/%{pecl_xmldir}/%{name}.xml


%if 0%{?pecl_install:1}
%post
%{pecl_install} %{pecl_xmldir}/%{name}.xml >/dev/null || :
%endif


%if 0%{?pecl_uninstall:1}
%postun
if [ $1 -eq 0 ] ; then
    %{pecl_uninstall} %{pecl_name} >/dev/null || :
fi
%endif


%clean
%{__rm} -rf %{buildroot}


%files
%defattr(-, root, root, 0755)
%doc %{pecl_name}-%{version}/CREDITS %{pecl_name}-%{version}/TODO
%doc %{pecl_name}-%{version}/examples
%config(noreplace) %{_sysconfdir}/php5/conf.d/%{name}.ini
%{php_extdir}/%{pecl_name}.so
%{pecl_xmldir}/%{name}.xml
/usr/include/php5/ext/imagick/*.h

%changelog
* Thu Nov 25 2010 Samuel Bovee <samuel@ulteo.com> 3.0.1-1
* Tue Sep 02 2010 Samuel Bovee <samuel@ulteo.com> 3.0.0-1
* Mon Aug 09 2010 Samuel Bovée <samuel@ulteo.com> 2.3.0-1
* Sat Dec 13 2008 Remi Collet <rpms@famillecollet.com> 2.2.1-1.fc#.remi.1
- rebuild with php 5.3.0-dev
- add imagick-2.2.1-php53.patch

* Sat Dec 13 2008 Remi Collet <rpms@famillecollet.com> 2.2.1-1
- update to 2.2.1

* Sat Jul 19 2008 Remi Collet <rpms@famillecollet.com> 2.2.0-1.fc9.remi.1
- rebuild with php 5.3.0-dev

* Sat Jul 19 2008 Remi Collet <rpms@famillecollet.com> 2.2.0-1
- update to 2.2.0

* Thu Apr 24 2008 Remi Collet <rpms@famillecollet.com> 2.1.1-1
- Initial package

