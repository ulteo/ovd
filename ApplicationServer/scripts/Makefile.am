EXTRA_DIST = as-chroot-install.in

bin_SCRIPTS = as-chroot-install
dist_man_MANS = as-chroot-install.1

as-chroot-install:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALSTATEDIR@,$(localstatedir)," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/as-chroot-install.in > $@

clean-local:
	rm -f $(bin_SCRIPTS)
