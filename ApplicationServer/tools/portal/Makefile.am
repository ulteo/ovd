INCLUDES = \
    -I$(top_builddir) \
    -I$(top_srcdir)

bin_PROGRAMS = aps-shell

aps_shell_SOURCES = main.cpp \
                    Utils.cpp Utils.h \
                    Action.cpp Action.h

aps_shell_CXXFLAGS = \
	-DSYSCONFDIR=\""$(sysconfdir)"\"

install-exec-hook:
	chmod 4755 $(DESTDIR)$(bindir)/aps-shell
