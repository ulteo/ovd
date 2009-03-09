scripts_DATA = core.inc.php \
               functions.inc.php \
               session_path.inc.php

scriptsdir = $(datadir)/ulteo/applicationserver/includes

EXTRA_DIST = core.inc.php \
             functions.inc.php \
             session_path.inc.php.in

session_path.inc.php:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
		< $(srcdir)/session_path.inc.php.in > $@

clean-local:
	rm -f session_path.inc.php

.PHONY: session_path.inc.php
