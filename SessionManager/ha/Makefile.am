hadir = $(datadir)/ulteo/ovd/ha
ha_DATA = install.sh utils.sh su_cmd.sh ulteo_ha README

confdir = $(hadir)/conf
conf_DATA = conf/authkeys \
			conf/crm.conf \
			conf/ha.cf \
			conf/resources.conf \
			conf/sm0.res

ocfdir = $(libdir)/ocf/resource.d/heartbeat
ocf_DATA= conf/mysql-ovd

HAshell = shell/Makefile \
		  shell/main.cpp \
		  shell/Action.h \
		  shell/Action.cpp \
		  shell/Utils.h \
		  shell/Utils.cpp

EXTRA_DIST = $(ha_DATA) $(conf_DATA) $(ocf_DATA) $(HAshell)

install-data-hook:
	chmod 755 $(DESTDIR)$(libdir)/ocf/resource.d/heartbeat/mysql-ovd
