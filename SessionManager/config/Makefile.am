EXTRA_DIST = apache2.conf.in \
			 apache2-vhost-server.conf.in \
			 apache2-vhost-ssl.conf \
			 config.inc.php.in \
			 cron.php.in

conf_SCRIPTS = cron.php
conf_DATA = apache2.conf \
			apache2-vhost-server.conf \
			apache2-vhost-ssl.conf \
			config.inc.php

confdir = $(sysconfdir)/ulteo/sessionmanager

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

apache2-vhost-server.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2-vhost-server.conf.in > $@

config.inc.php:
	sed "s,@LOCALSTATEDIR@,$(localstatedir)," < $(srcdir)/config.inc.php.in > $@

cron.php:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/cron.php.in > $@

install-exec-hook:
	mkdir -p $(DESTDIR)$(localstatedir)/log/ulteo/sessionmanager
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/sessionmanager

clean-local:
	rm -f $(conf_DATA) $(conf_SCRIPTS)

.PHONY: $(conf_DATA) $(conf_SCRIPTS)
