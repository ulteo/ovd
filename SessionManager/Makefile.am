SUBDIRS = web po config scripts examples

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
	find web -name "*.php" | grep -v 'defaults\.inc\.php' > po/POTFILES.in
