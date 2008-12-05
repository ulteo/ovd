EXTRA_DIST = web daemon

WEB_INSTALL_DIR = $(DESTDIR)/$(datadir)/ulteo/applicationserver
install-data-local:
	$(MKDIR_P) $(WEB_INSTALL_DIR)
	cp -R $(srcdir)/web/* $(WEB_INSTALL_DIR)
	find $(WEB_INSTALL_DIR) -type d -exec chmod 775 {} \;
	find $(WEB_INSTALL_DIR) -type f -exec chmod 644 {} \;

uninstall-local:
	rm -rf $(WEB_INSTALL_DIR)

