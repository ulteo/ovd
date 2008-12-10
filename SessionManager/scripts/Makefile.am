EXTRA_DIST = sm-admin.in

bin_SCRIPTS = sm-admin

sm-admin:
	sed -e "s,@CONFFILE@,$(sysconfdir)/ulteo/sessionmanager/config.inc.php," \
	    -e "s,@CONFDIR@,$(sysconfdir)/ulteo/sessionmanager," \
		-e "s,@WEBDIR@,$(datadir)/ulteo/sessionmanager," \
			< $(srcdir)/sm-admin.in > $@

clean-local:
	rm -f $(bin_SCRIPTS)
