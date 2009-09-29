scripts_DATA = startovd-xfce startovdapp
scriptsdir = $(bindir)

sysconfig_DATA = xinitrc
sysconfigdir = $(sysconfdir)/xdg/ulteo

EXTRA_DIST = startovd-xfce.in startovd-kde startovdapp xinitrc

startovd-xfce: Makefile
	sed "s,@SYSCONFDIR@,$(sysconfdir)," $(srcdir)/startovd-xfce.in >$@

install-exec-hook:
	mkdir -p $(DESTDIR)$(bindir)
	ln -sf $(STARTOVD) $(DESTDIR)$(bindir)/startovd

clean-local:
	rm -f startovd-xfce
