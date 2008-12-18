scripts_DATA = defaults.inc.php \
               functions.inc.php \
			   core.inc.php \
			   core-minimal.inc.php

scriptsdir = $(datadir)/ulteo/sessionmanager/includes

EXTRA_DIST = functions.inc.php core.inc.php core-minimal.inc.php defaults.inc.php.in

defaults.inc.php:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
		< $(srcdir)/defaults.inc.php.in > $@

clean-local:
	rm -f defaults.inc.php

.PHONY: defaults.inc.php
