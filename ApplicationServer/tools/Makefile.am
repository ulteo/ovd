if PORTAL
SUBDIRS = portal
endif

EXTRA_DIST = aps-chroot-install.in aps-chroot.in

bin_SCRIPTS = aps-chroot-install aps-chroot
dist_man_MANS = aps-chroot-install.8 aps-chroot.8

aps-chroot-install:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALSTATEDIR@,$(localstatedir)," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/aps-chroot-install.in > $@

aps-chroot:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
		< $(srcdir)/aps-chroot.in > $@

clean-local:
	rm -f $(bin_SCRIPTS)

.PHONY: aps-chroot-install
