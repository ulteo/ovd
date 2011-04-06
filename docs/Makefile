.SUFFIXES: .xml .html

DIST?=dist

SUBDIRS=img www

SRC=\
	Architecture.xml \
	Easy_Installation.xml \
	FAQ.xml \
	Installation_ApS_Windows.xml \
	Installation_Gateway_Lucid.xml \
	Installation_Iso.xml \
	NativeClient.xml \
	Protocol.xml \
	StartASession.xml \
	Support_Debian_Lenny.xml \
	Support_Debian_Squeeze.xml \
	Support_RHEL_5.5.xml \
	Support_openSUSE_11.2.xml \
	Support_openSUSE_11.3.xml \
	Support_SLES_11.SP1.xml \
	Support_Ubuntu_Hardy.xml \
	Support_Ubuntu_Lucid.xml \
	WebClient.xml

DIRS=xsl2 \
     media \
     img \
     style

DST=$(SRC:.xml=.html)
PDF=$(SRC:.xml=.pdf)

all: $(DST) $(PDF)

html: $(DST)

pdf: $(PDF)

# Add the depends between sources
Installation_Iso.xml: Easy_Installation.xml
Support_Debian_Lenny.xml: Support_Debian_Squeeze.xml Support_Ubuntu_Hardy.xml
Support_Debian_Squeeze.xml: Support_Ubuntu_Lucid.xml
Support_RHEL_5.5.xml: Support_SLES_11.SP1.xml
Support_SLES_11.SP1.xml: Support_Debian_Squeeze.xml Support_Ubuntu_Hardy.xml
Support_Ubuntu_Hardy.xml: Support_Ubuntu_Lucid.xml
Support_openSUSE_11.2.xml: Support_SLES_11.SP1.xml
Support_openSUSE_11.3.xml: Support_SLES_11.SP1.xml


%.html: %.xml.out
	xsltproc style/xhtml.xsl $< >$@

%.pdf:  %.xml.out
	dblatex -t pdf -T db2latex -p style/latex.xsl $< -o $@ >/dev/null 2>&1

%.xml.out: %.xml
	xmllint --xinclude $< | ./transform >$@

clean:
	$(RM) $(DST)
	$(RM) $(PDF)
	$(RM) *~

install: all
	install -d 		$(DIST)
	install default.css	$(DIST)
	install $(DST) 		$(DIST)
	install $(PDF) 		$(DIST)
	@for d in $(SUBDIRS); do \
		$(MAKE) -w -C $$d $(MAKECMDGOALS) DIST=$(abspath $(DIST))/$$d; \
	done

uninstall:
	rm 	-rf          	$(DIST)
