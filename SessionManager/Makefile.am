SUBDIRS = web po config scripts extra examples

noinst_DATA = ovd-session-manager.rhel.spec ovd-session-manager.suse.spec

%.spec:
	sed -e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/$@.in > $@

clean-local:
	rm -f $(noinst_DATA)

EXTRA_DIST = ovd-session-manager.rhel.spec.in ovd-session-manager.suse.spec.in

#.PHONY: $(noinst_DATA)

# maintainer rules
update-potfiles-in:
	find web -name "*.php" > po/POTFILES.in
	sed -i '/defaults\.inc\.php/d' po/POTFILES.in
	sed -i '/web\/admin\/init\/setup.php/d' po/POTFILES.in
