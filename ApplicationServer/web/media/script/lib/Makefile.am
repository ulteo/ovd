SUBDIRS = nifty
HOOKS =

if !PROTOTYPE
SUBDIRS += prototype
else
HOOKS += prototype-hook
endif

if !SCRIPTACULOUS
SUBDIRS += scriptaculous
else
HOOKS += scriptaculous-hook
endif

install-exec-hook: $(HOOKS)

prototype-hook:
	$(MKDIR_P) $(DESTDIR)$(datadir)/ulteo/applicationserver/media/script/lib
	ln -sf $(PROTOTYPEDIR) \
        $(DESTDIR)$(datadir)/ulteo/applicationserver/media/script/lib/prototype

scriptaculous-hook:
	$(MKDIR_P) $(DESTDIR)$(datadir)/ulteo/applicationserver/media/script/lib
	ln -sf $(SCRIPTACULOUSDIR) \
        $(DESTDIR)$(datadir)/ulteo/applicationserver/media/script/lib/scriptaculous

