SUBDIRS = UserDB ApplicationDB ApplicationsGroupDB UserGroupDB AuthMethod

scriptsdir = $(datadir)/ulteo/sessionmanager/modules
scripts_DATA = UserDB.php ApplicationDB.php ApplicationsGroupDB.php \
			   UserGroupDB.php AuthMethod.php

if HA
scripts_DATA += HA.php

hadir=$(datadir)/ulteo/sessionmanager/modules/HA
ha_DATA = HA/high_availability.php
endif

EXTRA_DIST = $(scripts_DATA) $(ha_DATA)
