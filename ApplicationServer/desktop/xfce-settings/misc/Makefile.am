desktop_DATA = quit.desktop
desktopdir =  $(localstatedir)/spool/menus-common/

ulteoxdgconf_DATA = xinitrc
ulteoxdgconfdir = $(sysconfdir)/xdg/ulteo

EXTRA_DIST = $(ulteoxdgconf_DATA) $(desktop_DATA)

install-data-hook:
	chmod +x $(DESTDIR)$(sysconfdir)/xdg/ulteo/xinitrc
