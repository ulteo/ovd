SUBDIRS = ajax media includes init classes

if HA
SUBDIRS += ha
endif

scripts_DATA = actions.php \
               applications.php \
               applications_static.php \
               appsgroup.php \
               checkup.php \
               configuration.php \
               configuration-partial.php \
               configuration-profile.php \
               configuration-sumup.php \
               index.php \
               login.php \
               logout.php \
               logs.php \
               mimetypes.php \
               news.php \
               password.php \
               profiles.php \
               publications.php \
               reporting.php \
               servers.php \
               sharedfolders.php \
               sessions.php \
               sumup.php \
               tasks.php \
               users.php \
               usersgroup.php \
               wizard.php

scriptsdir = $(datadir)/ulteo/sessionmanager/admin

DIST_SUBDIRS=$(SUBDIRS)
EXTRA_DIST = $(scripts_DATA)
