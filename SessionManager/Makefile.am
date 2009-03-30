SUBDIRS = web po config scripts extra examples

noinst_DATA = ovd-session-manager.spec
ovd-session-manager.spec:
	sed -e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/ovd-session-manager.spec.in > $@

clean-local:
	rm -f ovd-session-manager.spec

EXTRA_DIST = ovd-session-manager.spec.in

.PHONY: ovd-session-manager.spec

# maintainer rules
update-potfiles-in:
	find web -name "*.php" > po/POTFILES.in
	sed -i '/defaults\.inc\.php/d' po/POTFILES.in
	sed -i '/web\/admin\/init\/setup.php/d' po/POTFILES.in
