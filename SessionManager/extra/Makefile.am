EXTRA_DIST = libchart.tar.gz

if LIBCHART
HOOKS = link-libchart
else
HOOKS = install-libchart
endif

install-libchart:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/sessionmanager/extra
	tar xzf $(srcdir)/libchart.tar.gz -C $(DESTDIR)$(datadir)/ulteo/sessionmanager/extra

link-libchart:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/sessionmanager/extra
	ln -sf $(LIBCHARTDIR) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/extra/libchart

install-exec-hook: $(HOOKS)

uninstall-hook:
	rm -rf $(DESTDIR)$(datadir)/ulteo/sessionmanager/extra/libchart
