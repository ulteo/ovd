Pour avoir un graphic en articleinfo:


# cp /usr/share/xml/docbook/stylesheet/nwalsh/html/titlepage.templates.xml mytemplate.xml
# cp /usr/share/xml/docbook/stylesheet/nwalsh/template/titlepage.xsl .
# add <graphic/> just between ... and ...
# xsltproc  --output  mytitlepage.xsl titlepage.xsl mytemplate.xml
