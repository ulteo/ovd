EXTRA_DIST = as-chroot-install.in

bin_SCRIPTS = as-chroot-install

as-chroot-install:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALSTATEDIR@,$(localstatedir)," \
		< $(srcdir)/as-chroot-install.in > $@

clean-local:
	rm -f $(bin_SCRIPTS)
