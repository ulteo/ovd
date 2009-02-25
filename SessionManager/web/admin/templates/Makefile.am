SUBDIRS = servers applications
HOOKS =

if LIBCHART
HOOKS += libchart-hook
endif

install-exec-hook: $(HOOKS)

libchart-hook:
	$(INSTALL) -d $(DESTDIR)$(datadir)/ulteo/sessionmanager/admin/templates
	ln -sf $(LIBCHARTDIR) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/admin/templates/libchart

