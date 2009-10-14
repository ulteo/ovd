SUBDIRS = users

EXTRA_DIST = apache2.conf.in dot.htaccess

conf_DATA = apache2.conf dot.htaccess
confdir = $(sysconfdir)/ulteo/applicationserver

apache2.conf:
	sed -e "s,@DATADIR@,$(datadir)," \
	    -e "s,@LOCALSTATEDIR@,$(localstatedir)," \
			< $(srcdir)/apache2.conf.in > $@

install-exec-hook:
	mkdir -p $(DESTDIR)$(localstatedir)/log/ulteo/applicationserver

clean-local:
	rm -f apache2.conf

.PHONY: apache2.conf
