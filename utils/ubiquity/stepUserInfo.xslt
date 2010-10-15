<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:template match="node()">
        <xsl:copy> <xsl:apply-templates select="node()|@*"/> </xsl:copy>
    </xsl:template>
    <xsl:template match="text()"> <xsl:value-of select="."/> </xsl:template>
    <xsl:template match="@*">
        <xsl:attribute name="{name()}"><xsl:value-of select="."/></xsl:attribute>
    </xsl:template>
    <xsl:template match="//object[@id='login_vbox']/property[@name='visible']/text()">False</xsl:template>
</xsl:stylesheet>
