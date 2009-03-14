if DAEMON
daemon_dir = daemon
endif

if WEB
web_dir = web scripts config po
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

.PHONY: ovd-application-server.spec

# maintainer rules
update-potfiles-in:
	find web -name "*.php" > po/POTFILES.in
