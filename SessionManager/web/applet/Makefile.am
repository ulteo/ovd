install-exec-hook:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/sessionmanager/applet
	ln -sfT $(ULTEOAPPLET) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/applet/ulteo-applet.jar
	ln -sfT $(CHECKAPPLET) \
		$(DESTDIR)$(datadir)/ulteo/sessionmanager/applet/CheckJava.jar

uninstall-hook:
	rm -rf $(DESTDIR)$(datadir)/ulteo/sessionmanager/applet

