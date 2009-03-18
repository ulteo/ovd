scripts_DATA =  setup.php

scriptsdir = $(datadir)/ulteo/sessionmanager/admin/init

EXTRA_DIST = setup.php.in

setup.php:
	sed -e "s,@DATADIR@,$(datadir)," \
		< $(srcdir)/setup.php.in > $@

clean-local:
	rm -f setup.php

.PHONY: setup.php
