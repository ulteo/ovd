SUBDIRS = \
	functions.d \
	log.d \
	modules_fs.d

daemon_SCRIPTS = prestart.sh \
                 startsession.sh \
				 timeout.sh \
				 uumount.sh \
				 ulteo-ovdd
daemondir = $(datadir)/ulteo-ovd

daemonlib_DATA = functions.sh \
                 log.sh \
				 modules_fs.sh
daemonlibdir = $(datadir)/ulteo-ovd

sysconf_DATA = ulteo-ovd.conf

init_DATA = ulteo-ovd.init
initdir = $(sysconfdir)/init.d

ulteo-ovd.init:
	sed -e "s,@SYSCONFDIR@,$(sysconfdir),g" \
	    -e "s,@DATADIR@,$(datadir),g" \
		< ulteo-ovd.init.in > $@

local-clean:
	rm -f ulteo-ovd.init

EXTRA_DIST = ulteo-ovd.init.in \
             $(daemon_SCRIPTS) \
			 $(daemonlib_DATA) \
			 $(sysconf_DATA) \
			 $(init_DATA)
