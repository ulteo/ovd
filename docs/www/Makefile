DATA=\
	index.php

all:

install:
	install $(DATA) $(dir $(DIST))
	@for d in $(SUBDIRS); do \
		$(MAKE) -w -C $$d $(MAKECMDGOALS) DIST=$(abspath $(DIST))/$$d; \
	done

