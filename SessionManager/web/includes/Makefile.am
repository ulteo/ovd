SUBDIRS = functions.inc.d

scripts_DATA = defaults.inc.php \
               functions.inc.php \
			   core.inc.php \
			   core-minimal.inc.php \
			   load_balancing.inc.php

scriptsdir = $(datadir)/ulteo/sessionmanager/includes

EXTRA_DIST = defaults.inc.php.in \
             functions.inc.php \
		     core.inc.php \
		     core-minimal.inc.php \
			 load_balancing.inc.php

defaults.inc.php:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALE_DIR@,$(datadir)/locale," \
	    -e "s,@OVD_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/defaults.inc.php.in > $@

clean-local:
	rm -f defaults.inc.php

.PHONY: defaults.inc.php
