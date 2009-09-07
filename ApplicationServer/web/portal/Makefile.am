SUBDIRS = media

scripts_DATA = application.js \
               apps.php \
               common.css \
               daemon.js \
               functions.js \
               external_app.php \
               index.php \
               running_apps.php \
               resume.php \
               resume_app.php

scriptsdir = $(datadir)/ulteo/applicationserver/portal

EXTRA_DIST = $(scripts_DATA) ajaxplorer.tar.gz

install-ajaxplorer:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/applicationserver/portal
	tar xzf $(srcdir)/ajaxplorer.tar.gz -C $(DESTDIR)$(datadir)/ulteo/applicationserver/portal

install-exec-hook: install-ajaxplorer

# Maintainer script
ajaxplorer.tar.gz:
	tar czf $(srcdir)/ajaxplorer.tar.gz --exclude=.svn -C $(srcdir) ajaxplorer
