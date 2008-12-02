#!/usr/bin/make -f

DIRS = modules_fs.d log.d functions.d

SCRIPTS = ulteo-ovdd \
		  prestart.sh \
		  startsession.sh \
		  timeout.sh \
		  uumount.sh
LIBS    = log.sh \
          functions.sh \
		  modules_fs.sh


SHAREDIR = $(DESTDIR)/usr/share/ulteo-ovd
ETCDIR   = $(DESTDIR)/etc
INITDIR  = $(DESTDIR)/etc/init.d

install:
	mkdir -p -m 0750 $(SHAREDIR)

	for d in $(DIRS); do \
		cp -R $$d $(SHAREDIR); \
	done

	for file in $(SCRIPTS); do \
		install -m 0755 $$file $(SHAREDIR)/$$file ; \
	done
	for file in $(LIBS); do \
		install -m 0644 $$file $(SHAREDIR)/$$file ; \
	done

	install -d $(ETCDIR)
	install -m 0640 ulteo-ovd.conf.default $(ETCDIR)/ulteo-ovd.conf

	install -d $(INITDIR)
	install -m 755 ulteo-ovd.init $(INITDIR)/ulteo-ovdd

