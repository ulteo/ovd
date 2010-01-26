EXTRA_DIST = apache2.conf.in config.inc.php.in

conf_DATA = apache2.conf config.inc.php

confdir = $(sysconfdir)/ulteo/webinterface

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

clean-local:
	rm -f $(conf_DATA)

.PHONY: $(conf_DATA)
