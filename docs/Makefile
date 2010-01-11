.SUFFIXES: .xml .html

SRC=AccountsIntegration.xml \
	AdminConsole.xml \
	AD_Primary_Authentication.xml \
	FAQ.xml \
	Installation.xml \
	Installation_Iso.xml \
	Installation_Suse.xml \
	Installation_RedHat.xml \
	Installation_Sources.xml \
	Protocol.xml \
	SystemUpgrade.xml \
	Vpn.xml \
	Windows_Applications.xml

DIRS=xsl2 \
     media \
     img \
     style

VERSION=2.0.2
RELEASE_DIR=ovd-documentation-$(VERSION)

DST=$(SRC:.xml=.html)
PDF=$(SRC:.xml=.pdf)
ANW=$(SRC:.xml=.anw)

all: $(DST) $(PDF)

# Add the depends between sources
Installation_RedHat.html: Installation.xml
Installation_RedHat.anw:  Installation.xml
Installation_RedHat.pdf:  Installation.xml
Installation_Suse.pdf:  Installation.xml
Installation_Sources.html: Installation_RedHat.xml Installation.xml
Installation_Sources.anw:  Installation_RedHat.xml Installation.xml
Installation_Sources.pdf:  Installation_RedHat.xml Installation.xml
Installation_Iso.html: Installation.xml
Installation_Iso.anw: Installation.xml
Installation_Iso.pdf: Installation.xml


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

	install -d 		dist/img
	install img/*.png 	dist/img


uninstall:
	rm -rf dist

tarball: clean
	rm -rf $(RELEASE_DIR)
	mkdir $(RELEASE_DIR)
	cp -r Makefile $(SRC) $(DIRS) $(RELEASE_DIR)
	tar czf $(RELEASE_DIR).tar.gz --exclude=.svn $(RELEASE_DIR)
	rm -rf $(RELEASE_DIR)
