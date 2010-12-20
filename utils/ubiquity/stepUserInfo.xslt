<?xml version="1.0" encoding="UTF-8"?>
<!--
 Copyright (C) 2008-2010 Ulteo SAS
 http://www.ulteo.com
 Author Samuel BOVEE <samuel@ulteo.com> 2010

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
-->
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
