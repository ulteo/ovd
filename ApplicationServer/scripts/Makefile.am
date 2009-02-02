EXTRA_DIST = aps-chroot-install.in

bin_SCRIPTS = aps-chroot-install
dist_man_MANS = aps-chroot-install.1

aps-chroot-install:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALSTATEDIR@,$(localstatedir)," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/aps-chroot-install.in > $@

clean-local:
	rm -f $(bin_SCRIPTS)

.PHONY: aps-chroot-install
