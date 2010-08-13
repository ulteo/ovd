default_DATA = config.inc.php
defaultdir = $(datadir)/ulteo/webinterface/default

conf_DATA = config.inc.php apache2.conf
confdir = $(sysconfdir)/ulteo/webinterface

apache2.conf: apache2.conf.in
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

install-data-hook:
	CONFFILE=$(DESTDIR)$(confdir)/config.inc.php ;\
	sed -i "s/@SESSION_MANAGER@/127.0.0.1/" $$CONFFILE

EXTRA_DIST = $(default_DATA) apache2.conf.in

clean-local:
	rm -f apache2.conf

.PHONY: $(conf_DATA)
