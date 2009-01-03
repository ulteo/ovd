if DAEMON
daemon_dir = daemon
endif

if WEB
web_dir = web scripts config
endif

SUBDIRS = $(daemon_dir) $(web_dir) examples

DISTCHECK_CONFIGURE_FLAGS = --enable-daemon

noinst_DATA = ovd-application-server.spec
ovd-application-server.spec:
	sed -e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/ovd-application-server.spec.in > $@

clean-local:
	rm -f ovd-application-server.spec

EXTRA_DIST = ovd-application-server.spec.in
