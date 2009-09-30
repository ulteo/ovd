if PORTAL
SUBDIRS = portal
endif

EXTRA_DIST = \
	aps-chroot-install.in \
	aps-chroot.in \
	aps-chroot-install.8.in \
	aps-chroot.8.in

bin_SCRIPTS = aps-chroot-install aps-chroot
dist_man_MANS = aps-chroot-install.8 aps-chroot.8

%.8:
	sed -e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/$@.in > $@

aps-chroot-install:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALSTATEDIR@,$(localstatedir)," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/aps-chroot-install.in > $@

aps-chroot:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
		< $(srcdir)/aps-chroot.in > $@

clean-local:
	rm -f $(bin_SCRIPTS) $(dist_man_MANS)

.PHONY: aps-chroot-install
