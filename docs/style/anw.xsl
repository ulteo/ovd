<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="xhtml.xsl" />

  <!--
      Keep only the content and not push <html> <head> and <body> tags
  -->
  <xsl:template match="*" mode="process.root">
    <xsl:apply-templates select="."/>
  </xsl:template>

  <xsl:param name="admon.graphics.path">/main/images/uovd/doc/img/</xsl:param>
  <xsl:param name="img.src.path">/main/images/uovd/doc/</xsl:param>

</xsl:stylesheet>
