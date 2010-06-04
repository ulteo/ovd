# $Id: Makefile.am 2257 2006-12-19 19:49:00Z nick $

INCLUDES =								\
	-I$(top_srcdir)							\
	-DG_LOG_DOMAIN=\"xfce4-restricted-menu-plugin\"				\
	-DPACKAGE_LOCALE_DIR=\"$(localedir)\"				\
	-DSYSCONFDIR=\"$(sysconfdir)\"				\
	$(PLATFORM_CPPFLAGS)

#
# Sample plugin
#
plugin_PROGRAMS =							\
	xfce4-restricted-menu-plugin

plugindir =								\
	$(libexecdir)/xfce4/panel-plugins

xfce4_restricted_menu_plugin_SOURCES =						\
	main.c \
	s-menu.c s-menu.h \
	image.c image.h

xfce4_restricted_menu_plugin_CFLAGS =						\
	-Wall \
	$(LIBXFCE4UTIL_CFLAGS)						\
	$(LIBXFCEGUI4_CFLAGS)						\
	$(LIBXFCE4PANEL_CFLAGS)						\
	$(THUNARVFS_CFLAGS)						\
	$(PLATFORM_CFLAGS)

xfce4_restricted_menu_plugin_LDADD =						\
	$(LIBXFCE4UTIL_LIBS)						\
	$(LIBXFCEGUI4_LIBS)						\
	$(THUNARVFS_LIBS)						\
	$(LIBXFCE4PANEL_LIBS)

desktopdir =								\
	$(datadir)/xfce4/panel-plugins
desktop_in_in_files =							\
	restricted-menu.desktop.in.in
desktop_in_files =							\
	$(desktop_in_in_files:.desktop.in.in=.desktop.in)
%.desktop.in: %.desktop.in.in
	sed -e "s,\@libexecdir\@,$(libexecdir),g" < $< > $@
desktop_DATA =								\
	$(desktop_in_files:.desktop.in=.desktop)

@INTLTOOL_DESKTOP_RULE@

EXTRA_DIST =								\
	$(desktop_in_in_files)

CLEANFILES =								\
	$(desktop_in_files)						\
	$(desktop_DATA)

# vi:set ts=8 sw=8 noet ai nocindent syntax=automake:
