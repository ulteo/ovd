.SUFFIXES: .xml .html

SRC=AccountsIntegration.xml \
	AdminConsole.xml \
	AD_Primary_Authentication.xml \
	Architecture.xml \
	Easy_Installation.xml \
	FAQ.xml \
	Installation_ApS_Lenny.xml \
	Installation_ApS_Lucid.xml \
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
	Support_SLES_11.SP1.xml \
	Support_Ubuntu_Hardy.xml \
	Support_Ubuntu_Lucid.xml \
	SystemUpgrade.xml \
	Vpn.xml \
	WebClient.xml

DIRS=xsl2 \
     media \
     img \
     style

VERSION=2.0~rc1
RELEASE_DIR=ovd-documentation-$(VERSION)

DST=$(SRC:.xml=.html)
PDF=$(SRC:.xml=.pdf)
ANW=$(SRC:.xml=.anw)

all: $(DST) 
#$(PDF)

# Add the depends between sources
Installation_Iso.xml: Easy_Installation.xml
Installation_Sources.xml: Installation.xml Installation_RedHat.xml
Support_Debian_Lenny.xml: Support_Ubuntu_Hardy.xml Support_Ubuntu_Lucid.xml 
Support_Debian_Squeeze.xml: Support_Ubuntu_Lucid.xml
Support_openSUSE_11.2.xml: Support_SLES_11.SP1.xml
Support_RHEL_5.5.xml: Support_Ubuntu_Hardy.xml Support_Ubuntu_Lucid.xml
Support_SLES_11.SP1.xml: Support_Ubuntu_Hardy.xml Support_Ubuntu_Lucid.xml
Support_Ubuntu_Hardy.xml: Support_Ubuntu_Lucid.xml


%.html: %.xml
	xsltproc --xinclude style/xhtml.xsl $< >$@

%.pdf:  %.xml 
	dblatex -t pdf -T db2latex -p style/latex.xsl $<

%.anw: %.xml
	xsltproc --xinclude style/anw.xsl $< >$@
	sed -i 's/<!DOCTYPE.*//' $@

clean:
	$(RM) $(DST)
	$(RM) $(PDF)
	$(RM) $(ANW)
	$(RM) *~

install: all
	install -d 		dist
	install default.css	dist
	install $(DST) 		dist
	install $(PDF) 		dist

	svn export --force img  dist/img

	# Make the tarball
	rm -rf doc/ doc.tar.gz
	cp -R  dist doc
	rm -f doc/doc.tar.gz
	tar czf doc.tar.gz doc
	install doc.tar.gz dist

uninstall:
	rm -rf dist

tarball: clean
	rm -rf $(RELEASE_DIR)
	mkdir $(RELEASE_DIR)
	cp -r Makefile $(SRC) $(DIRS) $(RELEASE_DIR)
	tar czf $(RELEASE_DIR).tar.gz --exclude=.svn $(RELEASE_DIR)
	rm -rf $(RELEASE_DIR)
