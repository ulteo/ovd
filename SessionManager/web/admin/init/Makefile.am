scripts_DATA =  demo.php setup.php
scriptsdir = $(datadir)/ulteo/sessionmanager/admin/init

%.php: %.php.in
	sed "s,@DATADIR@,$(datadir)," $(srcdir)/$@.in > $@

EXTRA_DIST = demo.php.in setup.php.in

clean-local:
	rm -f demo.php setup.php
