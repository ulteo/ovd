EXTRA_DIST = apache2.conf.in

conf_DATA = apache2.conf
confdir = $(sysconfdir)/ulteo/applicationserver

apache2.conf:
	sed -e "s,@DATADIR@,$(datadir)," \
	    -e "s,@LOCALSTATEDIR@,$(localstatedir)," \
			< $(srcdir)/apache2.conf.in > $@

install-exec-hook:
	mkdir -p $(DESTDIR)$(localstatedir)/log/ulteo/applicationserver
	mkdir -p $(DESTDIR)$(localstatedir)/spool/ulteo/applicationserver

clean-local:
	rm -f $(conf_DATA)

.PHONY: apache2.conf
