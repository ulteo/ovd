EXTRA_DIST = apache2.conf.in \
			 apache2-admin.conf.in \
			 apache2-vhost-server.conf.in \
			 apache2-vhost-ssl.conf \
			 config.inc.php.in \
			 sessionmanager.cron.in

generate_conf = apache2.conf \
				apache2-admin.conf \
				apache2-vhost-server.conf \
				config.inc.php

conf_SCRIPTS = sessionmanager.cron
conf_DATA = $(generate_conf) apache2-vhost-ssl.conf

confdir = $(sysconfdir)/ulteo/sessionmanager

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

apache2-admin.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2-admin.conf.in > $@


apache2-vhost-server.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2-vhost-server.conf.in > $@

config.inc.php:
	sed "s,@LOCALSTATEDIR@,$(localstatedir)," < $(srcdir)/config.inc.php.in > $@

sessionmanager.cron:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/sessionmanager.cron.in > $@

install-exec-hook:
	mkdir -p $(DESTDIR)$(localstatedir)/log/ulteo/sessionmanager
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/sessionmanager
	mkdir -p $(DESTDIR)$(localstatedir)/cache/ulteo/sessionmanager

clean-local:
	rm -f $(generate_conf) $(conf_SCRIPTS)

.PHONY: $(conf_DATA) $(conf_SCRIPTS)
