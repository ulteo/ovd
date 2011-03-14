EXTRA_DIST = sm-config.in sm-config.8.in

sbin_SCRIPTS = sm-config
man_MANS = sm-config.8

sm-config:
	sed -e "s,@CONFDIR@,$(sysconfdir)/ulteo/sessionmanager," \
		-e "s,@LOCALSTATEDIR@,$(localstatedir)," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
			< $(srcdir)/sm-config.in > $@

sm-config.8:
	sed -e "s,@WEBDIR@,$(datadir)/ulteo/sessionmanager," \
		-e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/sm-config.8.in > $@

clean-local:
	rm -f $(sbin_SCRIPTS) $(man_MANS)

.PHONY: sm-config.8 sm-config
