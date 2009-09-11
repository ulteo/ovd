SUBDIRS = media

scripts_DATA = application.js \
               apps.php \
               common.css \
               daemon.js \
               external_app.php \
               functions.js \
               get_news.php \
               index.php \
               running_apps.php \
               resume.php \
               resume_app.php

scriptsdir = $(datadir)/ulteo/applicationserver/portal

EXTRA_DIST = $(scripts_DATA) ajaxplorer.tar.gz

install-ajaxplorer:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/applicationserver/portal
	tar xzf $(srcdir)/ajaxplorer.tar.gz -C $(DESTDIR)$(datadir)/ulteo/applicationserver/portal
	ln -sf $(localstatedir)/spool/ulteo/applicationserver/portal/users \
		   $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer/server/users

install-exec-hook: install-ajaxplorer

uninstall-hook:
	rm -rf $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer

# Maintainer script
ajaxplorer.tar.gz:
	tar czf $(srcdir)/ajaxplorer.tar.gz --exclude=.svn -C $(srcdir) ajaxplorer
