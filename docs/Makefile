.SUFFIXES: .xml .html

DIST?=dist

SUBDIRS=img www

STYLE_LATEX=style/ulteo-style.sty
STYLE_XSL_LATEX=style/latex.xsl

STYLE_XSL_XHTML=style/xhtml.xsl

SRC=\
	Architecture.xml \
	FAQ.xml \
	Installation_ApS_Windows.xml \
	Installation_Gateway_Lucid.xml \
	Installation_Gateway_Precise.xml \
	Internationalization.xml \
	NativeClient.xml \
	Protocol.xml \
	QuickStart.xml \
	Virtual_Appliance.xml \
	Support_Debian_Squeeze.xml \
	Support_Debian_Wheezy.xml \
	Support_RHEL_6.0.xml \
	Support_openSUSE_11.3.xml \
	Support_SLES_11.SP1.xml \
	Support_Ubuntu_Precise.xml \
	Support_Ubuntu_Lucid.xml \
	WebClient.xml \
	WsdlApi.xml \
	Premium_Edition.xml \
	Web_Applications_Gateway.xml

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
Installation_Gateway_Lucid.html Installation_Gateway_Lucid.pdf: common.xml
NativeClient.html NativeClient.pdf: common.xml
Support_Debian_Lenny.html Support_Debian_Lenny.pdf: Support_Debian_Squeeze.xml Support_Ubuntu_Hardy.xml
Support_Debian_Squeeze.html Support_Debian_Squeeze.pdf: Support_Ubuntu_Lucid.xml
Support_RHEL_5.5.html Support_RHEL_5.5.pdf: Support_SLES_11.SP1.xml
Support_SLES_11.SP1.html Support_SLES_11.SP1.pdf: Support_Debian_Squeeze.xml Support_Ubuntu_Hardy.xml
Support_Ubuntu_Hardy.html Support_Ubuntu_Hardy.pdf: Support_Ubuntu_Lucid.xml
Support_Ubuntu_Precise.html Support_Ubuntu_Precise.pdf: Support_Ubuntu_Lucid.xml
Support_openSUSE_11.2.html Support_openSUSE_11.2.pdf: Support_SLES_11.SP1.xml
Support_openSUSE_11.3.html Support_openSUSE_11.3.pdf: Support_SLES_11.SP1.xml


%.html: %.xml.out $(STYLE_XSL_XHTML)
	xsltproc $(STYLE_XSL_XHTML) $< >$@

%.pdf:  %.xml.out $(STYLE_XSL_LATEX) $(STYLE_LATEX)
	dblatex -t pdf -T db2latex -p $(STYLE_XSL_LATEX) -s $(STYLE_LATEX) $< -o $@ 
	# dblatex -B -d -D -v -t pdf -T db2latex -p $(STYLE_XSL_LATEX) -s $(STYLE_LATEX) $< -o $@

%.xml.out: %.xml
	xmllint --nonet --xinclude $< | ./transform >$@

WsdlApi.xml: xsl2/wsdl.xsl api.wsdl
	xsltproc $^ > $@

clean:
	$(RM) $(DST)
	$(RM) $(PDF)
	# $(RM) *~ *.xml.out
	$(RM) WsdlApi.xml

install: all
	install -d 		$(DIST)
	install default.css	$(DIST)
	install fonts.css	$(DIST)
	install $(DST) 		$(DIST)
	install $(PDF) 		$(DIST)
	@for d in $(SUBDIRS); do \
		$(MAKE) -w -C $$d $(MAKECMDGOALS) DIST=$(abspath $(DIST))/$$d; \
	done

uninstall:
	rm 	-rf          	$(DIST)
