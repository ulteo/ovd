install-exec-hook:
	mkdir -p $(DESTDIR)$(datadir)/ulteo/applicationserver/applet
	ln -s $(ULTEOAPPLET) \
		$(DESTDIR)$(datadir)/ulteo/applicationserver/applet/ulteo-applet.jar
	ln -s $(PRINTINGAPPLET) \
		$(DESTDIR)$(datadir)/ulteo/applicationserver/applet/ulteo-printing.jar

uninstall-hook:
	rm -rf $(DESTDIR)$(datadir)/ulteo/applicationserver/applet

