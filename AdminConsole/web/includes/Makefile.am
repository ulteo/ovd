scripts_DATA = core.inc.php \
               core-minimal.inc.php \
               defaults.inc.php \
               functions.inc.php \
               misc.inc.php \
               ovd-admin.wsdl \
               page_template.php \
               page_template_static.php \
               template_server.inc.php

scriptsdir = $(datadir)/ulteo/administration_console/includes

EXTRA_DIST = core.inc.php \
               core-minimal.inc.php \
               defaults.inc.php.in \
               functions.inc.php \
               misc.inc.php \
               ovd-admin.wsdl \
               page_template.php \
               page_template_static.php \
               template_server.inc.php

defaults.inc.php:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir)," \
	    -e "s,@LOCALE_DIR@,$(datadir)/locale," \
	    -e "s,@OVD_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/defaults.inc.php.in > $@

clean-local:
	rm -f defaults.inc.php

.PHONY: defaults.inc.php
