if PORTAL
portal_dir = portal
endif

SUBDIRS = applet classes includes media webservices desktop share $(portal_dir)

scripts_DATA = access.php \
               exit.php \
               index.php \
               invite.php \
               print.php \
               start.php \
               start_app.php \
               suspend.php \
               whatsup.php

scriptsdir = $(datadir)/ulteo/applicationserver

EXTRA_DIST = $(scripts_DATA) README

install-exec-hook:
	ln -sf $(sysconfdir)/ulteo/applicationserver/dot.htaccess $(DESTDIR)$(scriptsdir)/.htaccess

uninstall-hook:
	rm -f $(DESTDIR)$(scriptsdir)/.htaccess
