<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="/usr/share/xml/docbook/stylesheet/nwalsh/xhtml/docbook.xsl" />
  <xsl:import href="xsl2/mytitlepage.xsl" />

  <xsl:output encoding="utf-8" indent="no"/>
  <xsl:param name="admon.graphics" select="1"/>
  <xsl:param name="admon.graphics.path">img/</xsl:param>
  <xsl:param name="html.stylesheet" select="'default.css'"/>
  <xsl:param name="html.cleanup">1</xsl:param>
  <xsl:param name="make.valid.html">1</xsl:param>
</xsl:stylesheet>
