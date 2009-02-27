# http://www.ds9a.nl/docbook/minimal-page.html

.SUFFIXES: .xml .html

SRC=AdminConsole.xml \
	Documentation_Debian.xml \
	Documentation_from_sources.xml \
	Documentation_RedHat.xml \
	FAQ.xml \
	Protocol.xml \
	Windows_Applications.xml


DST=$(SRC:.xml=.html)
PDF=$(SRC:.xml=.pdf)
ANW=$(SRC:.xml=.anw)

ANWIKI_REGEXP=-e 's/src=\"img/src=\"\/main\/images\/uovd\/doc\/img/g' -e 's/<img/<br\/><br\/><img/' -e 's/.*<body[^>]*>//' -e 's/<\/body>.*//' -e 's/<\/a>/\n<\/a>/g'


all: $(DST) $(PDF)

%.html: %.xml
	xsltproc --xinclude xhtml.xsl $< >$@

%.pdf:  %.xml 
	dblatex -T db2latex $<

%.anw: %.html
	sed $(ANWIKI_REGEXP) $< > $@

clean:
	$(RM) $(DST)
	$(RM) $(PDF)
	$(RM) $(ANW)
	$(RM) *~

#install:
#	cp $(DST) ...
#	cp -R img ...

# Generer pdf avec fop
#
# xsltproc /usr/share/xml/docbook/stylesheet/nwalsh/fo/docbook.xsl HowTo_QuickInstall.xml > test.fo
# fop -fo test.fo -pdf test.pdf
#
#  ou
#
# fop -xsl /usr/share/xml/docbook/stylesheet/nwalsh/fo/docbook.xsl -xml HowTo_QuickInstall.xml -pdf test.pdf

#      xmlto xhtml -m config.xsl $<
#      cat index.html | sed -e 's/index\.html//g' > $@ 
#      rm index.html
