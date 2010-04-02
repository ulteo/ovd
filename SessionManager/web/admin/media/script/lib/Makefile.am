SUBDIRS = nifty calendarpopup

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
	$(INSTALL) -d $(DESTDIR)$(datadir)/ulteo/sessionmanager/admin/media/script/lib
	ln -sf $(PROTOTYPEDIR) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/admin/media/script/lib/prototype

scriptaculous-hook:
	$(INSTALL) -d $(DESTDIR)$(datadir)/ulteo/sessionmanager/admin/media/script/lib
	ln -sf $(SCRIPTACULOUSDIR) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/admin/media/script/lib/scriptaculous
