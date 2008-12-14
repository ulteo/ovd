EXTRA_DIST = sm-admin.in sm-admin.1.in

bin_SCRIPTS = sm-admin
dist_man_MANS = sm-admin.1

sm-admin:
	sed -e "s,@CONFFILE@,$(sysconfdir)/ulteo/sessionmanager/config.inc.php," \
	    -e "s,@CONFDIR@,$(sysconfdir)/ulteo/sessionmanager," \
		-e "s,@WEBDIR@,$(datadir)/ulteo/sessionmanager," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
			< $(srcdir)/sm-admin.in > $@

sm-admin.8:
	sed -e "s,@WEBDIR@,$(datadir)/ulteo/sessionmanager," \
		< $(srcdir)/sm-admin.1.in > $@

clean-local:
	rm -f $(bin_SCRIPTS)
