scripts_DATA = config.inc.php

scriptsdir = $(sysconfdir)/ulteo/webinterface

EXTRA_DIST = $(scripts_DATA) apache2.conf.in

conf_DATA = apache2.conf

confdir = $(sysconfdir)/ulteo/webinterface

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

clean-local:
	rm -f $(conf_DATA)

.PHONY: $(conf_DATA)
