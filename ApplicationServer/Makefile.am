if DAEMON
daemon_dir = daemon
endif

if WEB
web_dir = web tools config po
endif

SUBDIRS = $(daemon_dir) $(web_dir) OvdShells examples

DISTCHECK_CONFIGURE_FLAGS = --enable-daemon

noinst_DATA = ovd-application-server.rhel.spec ovd-application-server.suse.spec

%.spec:
	sed -e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/$@.in > $@

clean-local:
	rm -f $(noinst_DATA)

EXTRA_DIST = ovd-application-server.rhel.spec.in ovd-application-server.suse.spec.in

#.PHONY: ovd-application-server.spec

# maintainer rules
update-potfiles-in:
	find web -name "*.php" | grep -v ajaxplorer > po/POTFILES.in
	sed -i '/session_path\.inc\.php/d' po/POTFILES.in
