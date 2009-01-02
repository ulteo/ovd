SUBDIRS = web po config scripts

AUTOMAKE_OPTIONS = filename-length-max=99

noinst_DATA = ovd-session-manager.spec
ovd-session-manager.spec:
	sed -e "s,@PKG_VERSION@,$(PACKAGE_VERSION)," \
		< $(srcdir)/ovd-session-manager.spec.in > $@

clean-local:
	rm -f ovd-session-manager.spec

EXTRA_DISTS = ovd-session-manager.spec.in
