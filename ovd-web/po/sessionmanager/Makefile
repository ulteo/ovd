PACKAGE = uovdsm
LANGS = fr en
MSGFMT = msgfmt
SOURCES = $(LANGS:=.po)
OBJECTS = $(SOURCES:.po=.mo)

all: $(OBJECTS)

%.mo:
	$(MSGFMT) $*.po -o $@

clean:
	rm -f *.mo

install: $(OBJECTS)
	for lang in $(LANGS); do \
		install -d -m 0755 $(DESTDIR)/usr/share/locale/$$lang/LC_MESSAGES; \
		install -m 0644 $$lang.mo $(DESTDIR)/usr/share/locale/$$lang/LC_MESSAGES/$(PACKAGE).mo; \
	done

