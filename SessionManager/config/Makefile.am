EXTRA_DIST = apache2.conf.in config.inc.php.in

conf_DATA = apache2.conf config.inc.php
confdir = $(sysconfdir)/ulteo/sessionmanager

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

config.inc.php:
	sed "s,@LOCALSTATEDIR@,$(localstatedir)," < $(srcdir)/config.inc.php.in > $@

install-exec-hook:
	mkdir -p $(DESTDIR)$(localstatedir)/log/ulteo/sessionmanager
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/sessionmanager

clean-local:
	rm -f $(conf_DATA)

.PHONY: apache2.conf config.inc.php
