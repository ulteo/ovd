SUBDIRS = media

scripts_DATA = application_exit.php \
               apps.php \
               common.css \
               external_app.php \
               focus.php \
               get_news.php \
               index.php \
               running_apps.php \
               resume.php \
               resume_app.php \
               share_app.php

scriptsdir = $(datadir)/ulteo/applicationserver/portal

EXTRA_DIST = $(scripts_DATA) ajaxplorer

install-exec-hook: install-ajaxplorer
install-ajaxplorer:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/applicationserver/portal
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/applicationserver/portal

	cd $(srcdir); \
	for i in `find ajaxplorer -type d`; do \
		install -d -m 0755 $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/$$i; \
	done; \
	for i in `find ajaxplorer -type f`; do \
		install -m 0644 $$i $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/$$i; \
	done
	rm -rf `find $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ -type d -name .svn`

	ln -sf $(localstatedir)/spool/ulteo/applicationserver/portal/users \
		   $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer/server/users

	mv $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer/client/images/crystal/actions \
	   $(DESTDIR)$(localstatedir)/spool/ulteo/applicationserver/portal/
	ln -sf $(localstatedir)/spool/ulteo/applicationserver/portal/actions \
		   $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer/client/images/crystal/

uninstall-hook:
	rm -rf $(DESTDIR)$(datadir)/ulteo/applicationserver/portal/ajaxplorer
	rm -rf $(DESTDIR)$(localstatedir)/spool/ulteo/applicationserver/portal/actions

dist-hook:
	rm -rf `find $(distdir)/ajaxplorer -type d -name .svn`

