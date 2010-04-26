scriptsdir = $(sysconfdir)/ulteo/webinterface
scripts_DATA = config.inc.php

defaultdir = $(datadir)/ulteo/webinterface/default
default_DATA = config.inc.php

confdir = $(sysconfdir)/ulteo/webinterface
conf_DATA = apache2.conf

EXTRA_DIST = $(scripts_DATA) apache2.conf.in

apache2.conf:
	sed "s,@DATADIR@,$(datadir)," < $(srcdir)/apache2.conf.in > $@

clean-local:
	rm -f $(conf_DATA)

.PHONY: $(conf_DATA)
