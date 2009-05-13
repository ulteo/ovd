scripts_DATA = startovd-xfce startovd-kde startovdapp
scriptsdir = $(bindir)

sysconfig_DATA = xinitrc
sysconfigdir = $(sysconfdir)/xdg/ulteo

EXTRA_DIST = startovd-xfce.in startovd-kde startovdapp xinitrc

startovd-xfce: Makefile
	sed "s,@SYSCONFDIR@,$(sysconfdir)," $(srcdir)/startovd-xfce.in >$@

install-exec-hook:
	$(MKDIR_P) $(DESTDIR)$(bindir)
	ln -sf $(STARTOVD) $(DESTDIR)$(bindir)/startovd

clean-local:
	rm -f startovd-xfce
