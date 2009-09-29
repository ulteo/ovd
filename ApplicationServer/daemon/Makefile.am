SUBDIRS = \
    init \
    functions.d \
    log.d \
    modules_fs.d

daemon_SCRIPTS = application.sh \
                 cpu_load.py \
                 del_user.sh \
                 desktop.sh \
                 set_user_env.sh \
                 timeout.sh \
                 ulteo-ovdd \
                 unset_user_env.sh \
                 xml2desktopfile
daemondir = $(datadir)/ulteo-ovd

daemonlib_DATA = functions.sh \
                 log.sh \
                 modules_fs.sh
daemonlibdir = $(datadir)/ulteo-ovd

sysconf_DATA = ulteo-ovd.conf

EXTRA_DIST = $(daemon_SCRIPTS) \
             $(daemonlib_DATA) \
             $(sysconf_DATA)
