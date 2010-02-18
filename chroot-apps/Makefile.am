if CUPS2ALL
cups2all_dir = cups2all
endif

DISTCHECK_CONFIGURE_FLAGS = --disable-cups2all

SUBDIRS = restricted-menu xfsm-compat lmessage po desktopfiles2xml $(cups2all_dir)

EXTRA_DIST = \
	intltool-extract.in \
	intltool-merge.in \
	intltool-update.in

DISTCLEANFILES = \
	intltool-extract \
	intltool-merge \
	intltool-update

