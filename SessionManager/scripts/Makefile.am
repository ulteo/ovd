EXTRA_DIST = sm-config.in sm-config.1.in

bin_SCRIPTS = sm-config
man_MANS = sm-config.1

sm-config:
	sed -e "s,@CONFFILE@,$(sysconfdir)/ulteo/sessionmanager/config.inc.php," \
		-e "s,@LOCALSTATEDIR@,$(localstatedir)," \
		-e "s,@WEBDIR@,$(datadir)/ulteo/sessionmanager," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
			< $(srcdir)/sm-config.in > $@

sm-config.1:
	sed -e "s,@WEBDIR@,$(datadir)/ulteo/sessionmanager," \
		< $(srcdir)/sm-config.1.in > $@

clean-local:
	rm -f $(bin_SCRIPTS) $(man_MANS)

.PHONY: sm-config.1 sm-config
