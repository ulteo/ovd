AC_INIT([ovd-session-manager], [1.0~beta2], [http://bugs.ulteo.com])
AC_PREREQ(2.61)

# Init automake
AM_INIT_AUTOMAKE([AC_PACKAGE_TARNAME()], [AC_PACKAGE_VERSION()])

# Checks for programs.
AC_PROG_INSTALL

# i18n magic
IT_PROG_INTLTOOL
GETTEXT_PACKAGE=uovdsm
AC_SUBST(GETTEXT_PACKAGE)
AM_GLIB_GNU_GETTEXT()

AC_CONFIG_FILES([Makefile po/Makefile.in])
AC_OUTPUT
