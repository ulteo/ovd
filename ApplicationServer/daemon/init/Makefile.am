init_SCRIPTS = ulteo-ovdd
initdir = $(sysconfdir)/init.d

ulteo-ovdd:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir),g" \
		-e "s,@DATADIR@,$(datadir),g" \
		< $(srcdir)/ulteo-ovdd.in > $@

clean-local:
	rm -f ulteo-ovdd

EXTRA_DIST = ulteo-ovdd.in

.PHONY: ulteo-ovdd
