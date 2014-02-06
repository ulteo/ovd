DATA=\
	index.php \
	jquery-1.11.0.min.js
all:

install:
	install $(DATA) $(dir $(DIST))
	@for d in $(SUBDIRS); do \
		$(MAKE) -w -C $$d $(MAKECMDGOALS) DIST=$(abspath $(DIST))/$$d; \
	done

