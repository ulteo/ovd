if DAEMON
daemon_dir = daemon
endif

if WEB
web_dir = web
endif

SUBDIRS = $(daemon_dir) $(web_dir)

DISTCHECK_CONFIGURE_FLAGS = --enable-daemon
