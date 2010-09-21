hadir = $(datadir)/ulteo/ovd/ha
ha_DATA = install.sh utils.sh su_cmd.sh ulteo_ha README

confdir = $(hadir)/conf
conf_DATA = conf/authkeys \
			conf/crm.conf \
			conf/ha.cf \
			conf/mysql-ocf \
			conf/sm0.res

HAshell = shell/Makefile \
		  shell/main.cpp \
		  shell/Action.h \
		  shell/Action.cpp \
		  shell/Utils.h \
		  shell/Utils.cpp

EXTRA_DIST = $(ha_DATA) $(conf_DATA) $(HAshell)
