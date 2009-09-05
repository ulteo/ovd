if PORTAL
SUBDIRS = portal
endif

EXTRA_DIST = aps-chroot-install.in ovdchroot.in

bin_SCRIPTS = aps-chroot-install ovdchroot
dist_man_MANS = aps-chroot-install.8 ovdchroot.8

aps-chroot-install:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALSTATEDIR@,$(localstatedir)," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/aps-chroot-install.in > $@

ovdchroot:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
		< $(srcdir)/ovdchroot.in > $@

clean-local:
	rm -f $(bin_SCRIPTS)

.PHONY: aps-chroot-install
