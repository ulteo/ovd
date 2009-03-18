<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="/usr/share/xml/docbook/stylesheet/nwalsh/xhtml/docbook.xsl" />

  <!--
      Overwrite some templates to customize the title page
  -->
  <xsl:template name="article.titlepage.recto">
    <xsl:apply-templates mode="article.titlepage.recto.auto.mode" select="articleinfo/title"/>
    <xsl:apply-templates mode="article.titlepage.recto.auto.mode" select="articleinfo/subtitle"/>

    <xsl:apply-templates mode="article.titlepage.recto.auto.mode" select="articleinfo/graphic"/>

    <xsl:apply-templates mode="article.titlepage.recto.auto.mode" select="articleinfo/copyright"/>
  </xsl:template>

  <xsl:template match="graphic" mode="article.titlepage.recto.auto.mode">
    <xsl:apply-templates select="." mode="article.titlepage.recto.mode"/>
  </xsl:template>


  <xsl:output encoding="utf-8" indent="no"/>
  <xsl:param name="admon.graphics" select="1"/>
  <xsl:param name="admon.graphics.path">img/</xsl:param>
  <xsl:param name="html.stylesheet" select="'default.css'"/>
  <xsl:param name="html.cleanup">1</xsl:param>
  <xsl:param name="make.valid.html">1</xsl:param>
</xsl:stylesheet>
