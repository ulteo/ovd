install-exec-hook:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/sessionmanager/applet
	ln -sf $(ULTEOAPPLET) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/applet/ulteo-applet.jar
	ln -sf $(CHECKAPPLET) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/applet/CheckJava.jar

uninstall-hook:
	rm -rf $(DESTDIR)$(datadir)/ulteo/sessionmanager/applet

