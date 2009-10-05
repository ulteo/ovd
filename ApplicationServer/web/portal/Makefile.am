SUBDIRS = media

scripts_DATA = application.js \
               application_exit.php \
               apps.php \
               common.css \
               external_app.php \
               focus.php \
               functions.js \
               get_news.php \
               index.php \
               running_apps.php \
               resume.php \
               resume_app.php \
               share_app.php

scriptsdir = $(datadir)/ulteo/applicationserver/portal

EXTRA_DIST = $(scripts_DATA) ajaxplorer.tar.gz

install-ajaxplorer:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/applicationserver/portal
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/applicationserver/portal/

	tar xzf $(srcdir)/ajaxplorer.tar.gz -C $(DESTDIR)$(datadir)/ulteo/applicationserver/portal
	ln -sf $(localstatedir)/spool/ulteo/applicationserver/portal/users \
		   $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer/server/users

	mv $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer/client/images/crystal/actions \
	   $(DESTDIR)$(localstatedir)/spool/ulteo/applicationserver/portal/
	ln -sf $(localstatedir)/spool/ulteo/applicationserver/portal/actions \
		   $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer/client/images/crystal/

install-exec-hook: install-ajaxplorer

uninstall-hook:
	rm -rf $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer

dist-hook: ajaxplorer.tar.gz
ajaxplorer.tar.gz:
	tar czf $(srcdir)/ajaxplorer.tar.gz --exclude=.svn -C $(srcdir) ajaxplorer

.PHONY: ajaxplorer.tar.gz
