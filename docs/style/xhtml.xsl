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


  <!-- redefine colophon to remove title and add an hr tag -->
  <xsl:template match="colophon">
    <xsl:call-template name="id.warning"/>

    <div>
      <xsl:call-template name="common.html.attributes">
	<xsl:with-param name="inherit" select="1"/>
      </xsl:call-template>
      <xsl:if test="$generate.id.attributes != 0">
	<xsl:attribute name="id">
	  <xsl:call-template name="object.id"/>
	</xsl:attribute>
      </xsl:if>

      <xsl:call-template name="component.separator"/>
      <xsl:element name="hr" />


      <!-- <xsl:call-template name="component.title"/> -->
      <!-- <xsl:call-template name="component.subtitle"/> -->

      <xsl:apply-templates/>
      <xsl:call-template name="process.footnotes"/>
    </div>
  </xsl:template>

  <xsl:template match="mediaobject">
    <xsl:if test="not(parent::colophon)">
      <xsl:apply-templates/>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>
