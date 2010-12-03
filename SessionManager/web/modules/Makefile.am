SUBDIRS = ApplicationDB \
          ApplicationsGroupDB \
          AuthMethod \
          SessionManagement \
          UserDB \
          UserGroupDB

scriptsdir = $(datadir)/ulteo/sessionmanager/modules

scripts_DATA = ApplicationDB.php \
               ApplicationsGroupDB.php \
               AuthMethod.php \
               SessionManagement.php \
               UserDB.php \
               UserGroupDB.php

if HA
scripts_DATA += HA.php

hadir=$(datadir)/ulteo/sessionmanager/modules/HA
ha_DATA = HA/high_availability.php
endif

EXTRA_DIST = $(scripts_DATA) $(ha_DATA)
