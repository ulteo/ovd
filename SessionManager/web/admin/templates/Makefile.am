SUBDIRS = servers applications
scripts_DATA = getchart.php

HOOKS =

if LIBCHART
HOOKS += libchart-hook
endif

scriptsdir = $(datadir)/ulteo/sessionmanager/admin/templates
EXTRA_DIST = $(scripts_DATA)

install-exec-hook: $(HOOKS)

libchart-hook:
	$(INSTALL) -d $(DESTDIR)$(datadir)/ulteo/sessionmanager/admin/templates
	ln -sf $(LIBCHARTDIR) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/admin/templates/libchart

