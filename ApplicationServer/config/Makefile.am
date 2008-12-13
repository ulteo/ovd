EXTRA_DIST = apache2.conf.in

conf_DATA = apache2.conf
confdir = $(sysconfdir)/ulteo/applicationserver

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

install-exec-hook:
	mkdir -p $(DESTDIR)$(localstatedir)/log/ulteo/applicationserver
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/applicationserver

clean-local:
	rm -f $(conf_DATA)
