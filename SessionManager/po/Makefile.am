EXTRA_DIST = LINGUAS POTFILES.in

itlocaledir = $(prefix)/$(DATADIRNAME)/locale

MSGMERGE = INTLTOOL_EXTRACT=$(INTLTOOL_EXTRACT) srcdir=$(srcdir) $(INTLTOOL_UPDATE) --gettext-package $(GETTEXT_PACKAGE) --dist
GENPOT   = INTLTOOL_EXTRACT=$(INTLTOOL_EXTRACT) srcdir=$(srcdir) $(INTLTOOL_UPDATE) --gettext-package $(GETTEXT_PACKAGE) --pot

GMOS = $(foreach lang,$(ALL_LINGUAS),$(lang).gmo)
POS = $(foreach lang,$(ALL_LINGUAS),$(lang).po)

EXTRA_DIST += $(POS)

.po.gmo:
	file=`echo $* | sed 's,.*/,,'`.gmo \
	  && rm -f $$file && $(MSGFMT) -o $$file $<

all: $(GMOS)

clean:
	rm -f *.gmo

distclean: clean
	rm -f Makefile

# from intltool
install: all
	$(mkdir_p) $(DESTDIR)$(itlocaledir)
	for lang in $(ALL_LINGUAS); do \
		dir=$(DESTDIR)$(itlocaledir)/$$lang/LC_MESSAGES; \
		$(mkdir_p) $$dir; \
		if test -r $$lang.gmo; then \
			$(INSTALL_DATA) $$lang.gmo $$dir/$(GETTEXT_PACKAGE).mo; \
			echo "installing $$lang.gmo as $$dir/$(GETTEXT_PACKAGE).mo"; \
		else \
			$(INSTALL_DATA) $(srcdir)/$$lang.gmo $$dir/$(GETTEXT_PACKAGE).mo; \
			echo "installing $(srcdir)/$$lang.gmo as" \
			"$$dir/$(GETTEXT_PACKAGE).mo"; \
		fi; \
		if test -r $$lang.gmo.m; then \
			$(INSTALL_DATA) $$lang.gmo.m $$dir/$(GETTEXT_PACKAGE).mo.m; \
			echo "installing $$lang.gmo.m as $$dir/$(GETTEXT_PACKAGE).mo.m"; \
		else \
			if test -r $(srcdir)/$$lang.gmo.m ; then \
				$(INSTALL_DATA) $(srcdir)/$$lang.gmo.m \
				$$dir/$(GETTEXT_PACKAGE).mo.m; \
				echo "installing $(srcdir)/$$lang.gmo.m as" \
				"$$dir/$(GETTEXT_PACKAGE).mo.m"; \
			else \
				true; \
			fi; \
		fi; \
	done

uninstall:
	for lang in $(ALL_LINGUAS); do \
		rm -f $(DESTDIR)$(itlocaledir)/$$lang/LC_MESSAGES/$(GETTEXT_PACKAGE).mo; \
		rm -f $(DESTDIR)$(itlocaledir)/$$lang/LC_MESSAGES/$(GETTEXT_PACKAGE).mo.m; \
	done

update-po: Makefile
	$(MAKE) $(GETTEXT_PACKAGE).pot
	tmpdir=`pwd`; \
	for lang in $(ALL_LINGUAS); do \
		echo "$$lang:"; \
		result="`$(MSGMERGE) -o $$tmpdir/$$lang.new.po $$lang`"; \
		if $$result; then \
			if cmp $(srcdir)/$$lang.po $$tmpdir/$$lang.new.po >/dev/null 2>&1; then \
				rm -f $$tmpdir/$$lang.new.po; \
					else \
				if mv -f $$tmpdir/$$lang.new.po $$lang.po; then \
					:; \
				else \
					echo "msgmerge for $$lang.po failed: cannot move $$tmpdir/$$lang.new.po to $$lang.po" 1>&2; \
					rm -f $$tmpdir/$$lang.new.po; \
					exit 1; \
				fi; \
			fi; \
		else \
			echo "msgmerge for $$lang.gmo failed!"; \
			rm -f $$tmpdir/$$lang.new.po; \
		fi; \
	done

$(GETTEXT_PACKAGE).pot:
	$(GENPOT)
