<?xml version="1.0" encoding="utf-8"?>
<!--
 Copyright (C) 2013 Ulteo SAS
 http://www.ulteo.com
 Author David PHAM-VAN <d.pham-van@ulteo.com> 2013

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

 Based on http://tomi.vanek.sk/xml/wsdl-viewer.xsl
 Author: tomi vanek
-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ws="http://schemas.xmlsoap.org/wsdl/" xmlns:ws2="http://www.w3.org/ns/wsdl" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:local="http://tomi.vanek.sk/xml/wsdl-viewer" version="1.0" exclude-result-prefixes="ws ws2 xsd soap local">
  <xsl:output method="xml" version="1.0" encoding="utf-8" indent="yes" omit-xml-declaration="no" media-type="text/xml" doctype-system="http://www.oasis-open.org/docbook/xml/4.1.2/docbookx.dtd" doctype-public="-//OASIS//DTD DocBook XML V4.1.2//EN" />
  <xsl:strip-space elements="*" />
  <xsl:param name="wsdl-viewer.version">3.1.01</xsl:param>
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       Begin of included transformation: wsdl-viewer-global.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <xsl:param name="ENABLE-ANTIRECURSION-PROTECTION" select="true()" />
  <xsl:param name="ANTIRECURSION-DEPTH">3</xsl:param>
  <xsl:variable name="PORT-TYPE-TEXT">Port type </xsl:variable>
  <xsl:variable name="IFACE-TEXT">Interface</xsl:variable>
  <xsl:variable name="RECURSIVE">... is recursive</xsl:variable>
  <xsl:variable name="SRC-PREFIX">src.</xsl:variable>
  <xsl:variable name="SRC-FILE-PREFIX">src.file.</xsl:variable>
  <xsl:variable name="OPERATIONS-PREFIX">op.</xsl:variable>
  <xsl:variable name="PORT-PREFIX">port.</xsl:variable>
  <xsl:variable name="IFACE-PREFIX">iface.</xsl:variable>
  <xsl:variable name="global.wsdl-name" select="/*/*[(local-name() = 'import' or local-name() = 'include') and @location][1]/@location" />
  <xsl:variable name="consolidated-wsdl" select="/* | document($global.wsdl-name)/*" />
  <xsl:variable name="global.xsd-name" select="($consolidated-wsdl/*[local-name() = 'types']//xsd:import[@schemaLocation] | $consolidated-wsdl/*[local-name() = 'types']//xsd:include[@schemaLocation])[1]/@schemaLocation" />
  <xsl:variable name="consolidated-xsd" select="(document($global.xsd-name)/xsd:schema/xsd:*|/*/*[local-name() = 'types']/xsd:schema/xsd:*)[local-name() = 'complexType' or local-name() = 'element' or local-name() = 'simpleType']" />
  <xsl:variable name="global.service-name" select="concat($consolidated-wsdl/ws:service/@name, $consolidated-wsdl/ws2:service/@name)" />
  <xsl:variable name="global.binding-name" select="concat($consolidated-wsdl/ws:binding/@name, $consolidated-wsdl/ws2:binding/@name)" />
  <xsl:variable name="html-title">
    <xsl:apply-templates select="/*" mode="html-title.render" />
  </xsl:variable>
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       End of included transformation: wsdl-viewer-global.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       Begin of included transformation: wsdl-viewer-util.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <xsl:template match="@*" mode="qname.normalized">
    <xsl:variable name="local" select="substring-after(., ':')" />
    <xsl:choose>
      <xsl:when test="$local">
        <xsl:value-of select="$local" />
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="." />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="ws:definitions | ws2:description" mode="html-title.render">
    <xsl:choose>
      <xsl:when test="$global.service-name">
        <xsl:value-of select="concat('Web Service ', $global.service-name)" />
      </xsl:when>
      <xsl:when test="$global.binding-name">
        <xsl:value-of select="concat('WS Binding ', $global.binding-name)" />
      </xsl:when>
      <xsl:when test="ws2:interface/@name">
        <xsl:value-of select="concat('WS Interface ', ws2:interface/@name)" />
      </xsl:when>
      <xsl:otherwise>Web Service Fragment</xsl:otherwise>
      <!--		<xsl:otherwise><xsl:message terminate="yes">Syntax error in element <xsl:call-template name="src.syntax-error.path"/></xsl:message>
           </xsl:otherwise>
      -->
    </xsl:choose>
  </xsl:template>
  
  <xsl:template name="src.syntax-error">
    <xsl:message terminate="yes">
      Syntax error by WSDL source rendering in element
      <xsl:call-template name="src.syntax-error.path" />
    </xsl:message>
  </xsl:template>
  
  <xsl:template name="src.syntax-error.path">
    <xsl:for-each select="parent::*">
      <xsl:call-template name="src.syntax-error.path" />
    </xsl:for-each>
    <xsl:value-of select="concat('/', name(), '[', position(), ']')" />
  </xsl:template>
  
  <xsl:template match="*[local-name(.) = 'documentation']" mode="documentation.render">
    <para>
      <xsl:value-of select="." disable-output-escaping="yes" />
    </para>
  </xsl:template>
  
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       End of included transformation: wsdl-viewer-util.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       Begin of included transformation: wsdl-viewer-service.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <xsl:template match="ws:service|ws2:service" mode="service-start">
    <itemizedlist>
			<listitem>
        <para>
          <emphasis role="strong">Target Namespace: </emphasis>
          <xsl:value-of select="$consolidated-wsdl/@targetNamespace" />
        </para>
      </listitem>
      <xsl:apply-templates select="*[local-name(.) = 'documentation']" mode="documentation.render" />
      <xsl:apply-templates select="ws:port|ws2:endpoint" mode="service" />
    </itemizedlist>
  </xsl:template>
  
  <xsl:template match="ws2:endpoint" mode="service">
    <xsl:variable name="binding-name">
      <xsl:apply-templates select="@binding" mode="qname.normalized" />
    </xsl:variable>
    <xsl:variable name="binding" select="$consolidated-wsdl/ws2:binding[@name = $binding-name]" />
    <xsl:variable name="binding-type" select="$binding/@type" />
    <xsl:variable name="binding-protocol" select="$binding/@*[local-name() = 'protocol']" />
    <xsl:variable name="protocol">
      <xsl:choose>
        <xsl:when test="starts-with($binding-type, 'http://schemas.xmlsoap.org/wsdl/soap')">SOAP 1.1</xsl:when>
        <xsl:when test="starts-with($binding-type, 'http://www.w3.org/2005/08/wsdl/soap')">SOAP 1.2</xsl:when>
        <xsl:when test="starts-with($binding-type, 'http://schemas.xmlsoap.org/wsdl/mime')">MIME</xsl:when>
        <xsl:when test="starts-with($binding-type, 'http://schemas.xmlsoap.org/wsdl/http')">HTTP</xsl:when>
        <xsl:otherwise>Unknown</xsl:otherwise>
      </xsl:choose>
      <!-- TODO: Add all bindings to transport protocols -->
      <xsl:choose>
        <xsl:when test="starts-with($binding-protocol, 'http://www.w3.org/2003/05/soap/bindings/HTTP')">over HTTP</xsl:when>
        <xsl:otherwise />
      </xsl:choose>
    </xsl:variable>
    <div class="label">Location:</div>
    <div class="value">
      <xsl:value-of select="@address" />
    </div>
    <div class="label">Protocol:</div>
    <div class="value">
      <xsl:value-of select="$protocol" />
    </div>
    <xsl:apply-templates select="$binding" mode="service" />
    <xsl:variable name="iface-name">
      <xsl:apply-templates select="../@interface" mode="qname.normalized" />
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-wsdl/ws2:interface[@name = $iface-name]" mode="service" />
  </xsl:template>
  
  <xsl:template match="ws2:interface" mode="service">
    <h3>
      Interface
      <emphasis role="strong">
        <xsl:value-of select="@name" />
      </emphasis>
    </h3>
    <xsl:variable name="base-iface-name">
      <xsl:apply-templates select="@extends" mode="qname.normalized" />
    </xsl:variable>
    <xsl:if test="$base-iface-name">
      <div class="label">Extends:</div>
      <div class="value">
        <xsl:value-of select="$base-iface-name" />
      </div>
    </xsl:if>
    <xsl:variable name="base-iface" select="$consolidated-wsdl/ws2:interface[@name = $base-iface-name]" />
    <div class="label">Operations:</div>
    <div class="value">
      <xsl:text />
      <ol style="line-height: 180%;">
        <xsl:apply-templates select="$base-iface/ws2:operation | ws2:operation" mode="service">
          <xsl:sort select="@name" />
        </xsl:apply-templates>
      </ol>
    </div>
  </xsl:template>
  
  <xsl:template match="ws:port" mode="service">
    <xsl:variable name="binding-name">
      <xsl:apply-templates select="@binding" mode="qname.normalized" />
    </xsl:variable>
    <xsl:variable name="binding" select="$consolidated-wsdl/ws:binding[@name = $binding-name]" />
    <xsl:variable name="binding-uri" select="namespace-uri( $binding/*[local-name() = 'binding'] )" />
    <xsl:variable name="protocol">
      <xsl:choose>
        <xsl:when test="starts-with($binding-uri, 'http://schemas.xmlsoap.org/wsdl/soap')">SOAP</xsl:when>
        <xsl:when test="starts-with($binding-uri, 'http://schemas.xmlsoap.org/wsdl/mime')">MIME</xsl:when>
        <xsl:when test="starts-with($binding-uri, 'http://schemas.xmlsoap.org/wsdl/http')">HTTP</xsl:when>
        <xsl:otherwise>unknown</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="port-type-name">
      <xsl:apply-templates select="$binding/@type" mode="qname.normalized" />
    </xsl:variable>
    <xsl:variable name="port-type" select="$consolidated-wsdl/ws:portType[@name = $port-type-name]" />
    <listitem>
      <para>
        <emphasis role="strong">Port: </emphasis>
        <xsl:value-of select="@name" />
      </para>
    </listitem>  
    <listitem>
      <para>     
        <emphasis role="strong">Location: </emphasis>
        <xsl:value-of select="*[local-name() = 'address']/@location" />
      </para>
    </listitem>  
    <listitem>
      <para>     
        <emphasis role="strong">Protocol: </emphasis>
        <xsl:value-of select="$protocol" />
      </para>
    </listitem>  
    <xsl:apply-templates select="$binding" mode="service" />
  </xsl:template>
  
  <xsl:template match="ws:operation|ws2:operation" mode="service">
    <li>
      <emphasis>
        <xsl:value-of select="@name" />
      </emphasis>
      <xsl:if test="string-length(ws:documentation) &gt; 0">
        :
        <xsl:value-of select="ws:documentation" disable-output-escaping="yes" />
      </xsl:if>
    </li>
  </xsl:template>
  
  <xsl:template match="ws:binding|ws2:binding" mode="service">
    <xsl:variable name="real-binding" select="*[local-name() = 'binding']|self::ws2:*" />
    <xsl:if test="$real-binding/@style">
      <listitem>
        <para>
          <emphasis role="strong">Default style: </emphasis>
          <xsl:value-of select="$real-binding/@style" />
        </para>
      </listitem>
    </xsl:if>
    <xsl:if test="$real-binding/@transport|$real-binding/*[local-name() = 'protocol']">
      <xsl:variable name="protocol" select="concat($real-binding/@transport, $real-binding/*[local-name() = 'protocol'])" />
      <listitem>
        <para>
          <emphasis role="strong">Transport protocol: </emphasis>
          <xsl:choose>
            <xsl:when test="$protocol = 'http://schemas.xmlsoap.org/soap/http'">SOAP over HTTP</xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="$protocol" />
            </xsl:otherwise>
          </xsl:choose>
          </para>
        </listitem>
    </xsl:if>
    <xsl:if test="$real-binding/@verb">
      <listitem>
        <para>
          <emphasis role="strong">Default method: </emphasis>
          <xsl:value-of select="$real-binding/@verb" />
        </para>
      </listitem>
    </xsl:if>
  </xsl:template>
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       End of included transformation: wsdl-viewer-service.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       Begin of included transformation: wsdl-viewer-operations.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <xsl:template match="ws2:interface" mode="operations">
    <h3>
        <xsl:value-of select="$IFACE-TEXT" />
        <xsl:text />
        b
          <xsl:value-of select="@name" />
        b
    </h3>
    <ol>
      <xsl:apply-templates select="ws2:operation" mode="operations">
        <xsl:sort select="@name" />
      </xsl:apply-templates>
    </ol>
  </xsl:template>
  
  <xsl:template match="ws2:operation" mode="operations">
    <xsl:variable name="binding-info" select="$consolidated-wsdl/ws2:binding[@interface = current()/../@name or substring-after(@interface, ':') = current()/../@name]/ws2:operation[@ref = current()/@name or substring-after(@ref, ':') = current()/@name]" />
    <li>
      <xsl:if test="position() != last()">
        <xsl:attribute name="class">operation</xsl:attribute>
      </xsl:if>
      big
        b
            <xsl:value-of select="@name" />
        /b
      /big
      <div class="value">
        <xsl:text />
      </div>
      <xsl:apply-templates select="ws2:documentation" mode="documentation.render" />
      <xsl:apply-templates select="ws2:input|ws2:output|../ws2:fault[@name = ws2:infault/@ref or @name = ws2:outfault/@ref]" mode="operations.message">
        <xsl:with-param name="binding-data" select="$binding-info" />
      </xsl:apply-templates>
    </li>
  </xsl:template>
  
  <xsl:template match="ws2:input|ws2:output|ws2:fault" mode="operations.message">
    <xsl:param name="binding-data" />
    <div class="label">
      <xsl:value-of select="concat(translate(substring(local-name(.), 1, 1), 'abcdefghijklmnoprstuvwxyz', 'ABCDEFGHIJKLMNOPRSTUVWXYZ'), substring(local-name(.), 2), ': ')" />
    </div>
    <div class="value">
      <xsl:variable name="type-name">
        <xsl:apply-templates select="@element" mode="qname.normalized" />
      </xsl:variable>
      <xsl:call-template name="render-type">
        <xsl:with-param name="type-local-name" select="$type-name" />
      </xsl:call-template>
      <xsl:variable name="type-tree" select="$consolidated-xsd[@name = $type-name and not(xsd:simpleType)][1]" />
      <xsl:apply-templates select="$type-tree" mode="operations.message.part" />
    </div>
  </xsl:template>
  
  <xsl:template match="ws:portType" mode="operations">
    <section>
      <title>
          Operations for
          <xsl:value-of select="@name" />
      </title>
      <xsl:apply-templates select="ws:operation" mode="operations">
        <xsl:sort select="@name" />
      </xsl:apply-templates>
    </section>
  </xsl:template>
  
  <xsl:template match="ws:operation" mode="operations">
    <xsl:variable name="binding-info" select="$consolidated-wsdl/ws:binding[@type = current()/../@name or substring-after(@type, ':') = current()/../@name]/ws:operation[@name = current()/@name]" />
    <section>
      <title><xsl:value-of select="@name" /></title>
      <xsl:if test="string-length(ws:documentation) &gt; 0">
        <para>
          <xsl:value-of select="ws:documentation" disable-output-escaping="yes" />
        </para>
      </xsl:if>
      
      <para>
        <xsl:choose>
          <xsl:when test="$binding-info/ws:input[not(../ws:output)]">
            <emphasis>One-way.</emphasis>
            The endpoint receives a message.
          </xsl:when>
          <xsl:when test="$binding-info/ws:input[following-sibling::ws:output]">
            <emphasis>Request-response.</emphasis>
            The endpoint receives a message, and sends a correlated message.
          </xsl:when>
          <xsl:when test="$binding-info/ws:input[preceding-sibling::ws:output]">
            <emphasis>Solicit-response.</emphasis>
            The endpoint sends a message, and receives a correlated message.
          </xsl:when>
          <xsl:when test="$binding-info/ws:output[not(../ws:input)]">
            <emphasis>Notification.</emphasis>
            The endpoint sends a message.
          </xsl:when>
          <xsl:otherwise>unknown</xsl:otherwise>
        </xsl:choose>
      </para>
      
      <itemizedlist>
        <xsl:variable name="binding-operation" select="$binding-info/*[local-name() = 'operation']" />
        <xsl:if test="$binding-operation/@style">
          <listitem>
            <para>
              <emphasis role="strong">Style: </emphasis>
              <xsl:value-of select="$binding-operation/@style" />
            </para>
          </listitem>
        </xsl:if>
        <xsl:if test="string-length($binding-operation/@soapAction) &gt; 0">
          <listitem>
            <para>
              <emphasis role="strong">SOAP action: </emphasis>
              <xsl:value-of select="$binding-operation/@soapAction" />
            </para>
          </listitem>
        </xsl:if>
        <xsl:if test="$binding-operation/@location">
          <listitem>
            <para>
              <emphasis role="strong">HTTP path: </emphasis>
              <xsl:value-of select="$binding-operation/@location" />
            </para>
          </listitem>
        </xsl:if>
      <xsl:apply-templates select="ws:input|ws:output|ws:fault" mode="operations.message">
        <xsl:with-param name="binding-data" select="$binding-info" />
      </xsl:apply-templates>
      </itemizedlist>
    </section>
  </xsl:template>
  
  <xsl:template match="ws:input|ws:output|ws:fault" mode="operations.message">
    <xsl:param name="binding-data" />
    <listitem>
      <para>
        <emphasis role="strong">
          <xsl:value-of select="concat(translate(substring(local-name(.), 1, 1), 'abcdefghijklmnoprstuvwxyz', 'ABCDEFGHIJKLMNOPRSTUVWXYZ'), substring(local-name(.), 2), ': ')" />
        </emphasis>
        <xsl:variable name="msg-local-name" select="substring-after(@message, ':')" />
        <xsl:variable name="msg-name">
          <xsl:choose>
            <xsl:when test="$msg-local-name">
              <xsl:value-of select="$msg-local-name" />
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="@message" />
            </xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        <xsl:variable name="msg" select="$consolidated-wsdl/ws:message[@name = $msg-name]" />
        <xsl:choose>
          <xsl:when test="$msg">
            <xsl:apply-templates select="$msg" mode="operations.message">
              <xsl:with-param name="binding-data" select="$binding-data/ws:*[local-name(.) = local-name(current())]/*" />
            </xsl:apply-templates>
          </xsl:when>
          <xsl:otherwise>
            <emphasis>none</emphasis>
          </xsl:otherwise>
        </xsl:choose>
      </para>
    </listitem>
  </xsl:template>
  
  <xsl:template match="ws:message" mode="operations.message">
    <xsl:param name="binding-data" />
    
      <xsl:value-of select="@name" />
      <xsl:text> </xsl:text>
      <xsl:if test="$binding-data">
        <xsl:text>(</xsl:text>
        <xsl:value-of select="name($binding-data)" />
        <xsl:variable name="use" select="$binding-data/@use" />
        <xsl:if test="$use">
          <xsl:text>, use: </xsl:text>
          <xsl:value-of select="$use" />
        </xsl:if>
        <xsl:variable name="part" select="$binding-data/@part" />
        <xsl:if test="$part">
          <xsl:text>, part: </xsl:text>
          <xsl:value-of select="$part" />
        </xsl:if>
        <xsl:text>)</xsl:text>
      </xsl:if>
    
    <xsl:apply-templates select="ws:part" mode="operations.message" />
  </xsl:template>
  
  <xsl:template match="ws:part" mode="operations.message">
    <para>
      <xsl:choose>
        <xsl:when test="string-length(@name) &gt; 0">
          <emphasis role="strong">
            <xsl:value-of select="@name" />
          </emphasis>
          <xsl:variable name="elem-or-type">
            <xsl:choose>
              <xsl:when test="@type">
                <xsl:value-of select="@type" />
              </xsl:when>
              <xsl:otherwise>
                <xsl:value-of select="@element" />
              </xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:variable name="type-local-name" select="substring-after($elem-or-type, ':')" />
          <xsl:variable name="type-name">
            <xsl:choose>
              <xsl:when test="$type-local-name">
                <xsl:value-of select="$type-local-name" />
              </xsl:when>
              <xsl:when test="$elem-or-type">
                <xsl:value-of select="$elem-or-type" />
              </xsl:when>
              <xsl:otherwise>unknown</xsl:otherwise>
            </xsl:choose>
          </xsl:variable>
          <xsl:call-template name="render-type">
            <xsl:with-param name="type-local-name" select="$type-name" />
          </xsl:call-template>
          <xsl:variable name="part-type" select="$consolidated-xsd[@name = $type-name and not(xsd:simpleType)][1]" />
          <xsl:apply-templates select="$part-type" mode="operations.message.part" />
        </xsl:when>
        <xsl:otherwise>
          <emphasis>none</emphasis>
        </xsl:otherwise>
      </xsl:choose>
    </para>
  </xsl:template>
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       End of included transformation: wsdl-viewer-operations.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       Begin of included transformation: wsdl-viewer-xsd-tree.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <xsl:template match="xsd:simpleType" mode="operations.message.part" />
  <xsl:template name="recursion.should.continue">
    <xsl:param name="anti.recursion" />
    <xsl:param name="recursion.label" />
    <xsl:param name="recursion.count">1</xsl:param>
    <xsl:variable name="has.recursion" select="contains($anti.recursion, $recursion.label)" />
    <xsl:variable name="anti.recursion.fragment" select="substring-after($anti.recursion, $recursion.label)" />
    <xsl:choose>
      <xsl:when test="$recursion.count &gt; $ANTIRECURSION-DEPTH" />
      <xsl:when test="not($ENABLE-ANTIRECURSION-PROTECTION) or string-length($anti.recursion) = 0 or not($has.recursion)">
        <xsl:text>1</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:call-template name="recursion.should.continue">
          <xsl:with-param name="anti.recursion" select="$anti.recursion.fragment" />
          <xsl:with-param name="recursion.label" select="$recursion.label" />
          <xsl:with-param name="recursion.count" select="$recursion.count + 1" />
        </xsl:call-template>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="xsd:complexType" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="recursion.label" select="concat('[', @name, ']')" />
    <xsl:variable name="recursion.test">
      <xsl:call-template name="recursion.should.continue">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
        <xsl:with-param name="recursion.label" select="$recursion.label" />
      </xsl:call-template>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="string-length($recursion.test) != 0">
        <xsl:apply-templates select="*" mode="operations.message.part">
          <xsl:with-param name="anti.recursion" select="concat($anti.recursion, $recursion.label)" />
        </xsl:apply-templates>
      </xsl:when>
      <xsl:otherwise>
        small style="color:blue" 1
          <xsl:value-of select="$RECURSIVE" />
        /small
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="xsd:complexContent" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <xsl:apply-templates select="*" mode="operations.message.part">
      <xsl:with-param name="anti.recursion" select="$anti.recursion" />
    </xsl:apply-templates>
  </xsl:template>
  
  <xsl:template match="xsd:complexType[descendant::xsd:attribute[ not(@*[local-name() = 'arrayType']) ]]" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="recursion.label" select="concat('[', @name, ']')" />
    <xsl:variable name="recursion.test">
      <xsl:call-template name="recursion.should.continue">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
        <xsl:with-param name="recursion.label" select="$recursion.label" />
      </xsl:call-template>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="string-length($recursion.test) != 0">
        <ul type="circle">
          <xsl:apply-templates select="*" mode="operations.message.part">
            <xsl:with-param name="anti.recursion" select="concat($anti.recursion, $recursion.label)" />
          </xsl:apply-templates>
        </ul>
      </xsl:when>
      <xsl:otherwise>
        small style="color:blue" 2
          <xsl:value-of select="$RECURSIVE" />
        /small
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="xsd:restriction | xsd:extension" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="type-local-name" select="substring-after(@base, ':')" />
    <xsl:variable name="type-name">
      <xsl:choose>
        <xsl:when test="$type-local-name">
          <xsl:value-of select="$type-local-name" />
        </xsl:when>
        <xsl:when test="@base">
          <xsl:value-of select="@base" />
        </xsl:when>
        <xsl:otherwise>unknown type</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="base-type" select="$consolidated-xsd[@name = $type-name][1]" />
    <!-- xsl:if test="not($type/@abstract)">
         <xsl:apply-templates select="$type"/>
         </xsl:if -->
    <xsl:if test="$base-type != 'Array'">
      <xsl:apply-templates select="$base-type" mode="operations.message.part">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
      </xsl:apply-templates>
    </xsl:if>
    <xsl:apply-templates select="*" mode="operations.message.part">
      <xsl:with-param name="anti.recursion" select="$anti.recursion" />
    </xsl:apply-templates>
  </xsl:template>
  
  <xsl:template match="xsd:union" mode="operations.message.part">
    <xsl:call-template name="process-union">
      <xsl:with-param name="set" select="@memberTypes" />
    </xsl:call-template>
  </xsl:template>
  
  <xsl:template name="process-union">
    <xsl:param name="set" />
    <xsl:if test="$set">
      <xsl:variable name="item" select="substring-before($set, ' ')" />
      <xsl:variable name="the-rest" select="substring-after($set, ' ')" />
      <xsl:variable name="type-local-name" select="substring-after($item, ':')" />
      <xsl:variable name="type-name">
        <xsl:choose>
          <xsl:when test="$type-local-name">
            <xsl:value-of select="$type-local-name" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$item" />
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:call-template name="render-type">
        <xsl:with-param name="type-local-name" select="$type-name" />
      </xsl:call-template>
      <xsl:call-template name="process-union">
        <xsl:with-param name="set" select="$the-rest" />
      </xsl:call-template>
    </xsl:if>
  </xsl:template>
  
  <xsl:template match="xsd:sequence" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <ul type="square">
      <xsl:apply-templates select="*" mode="operations.message.part">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
      </xsl:apply-templates>
    </ul>
  </xsl:template>
  
  <xsl:template match="xsd:all|xsd:any|xsd:choice" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="list-type">
      <xsl:choose>
        <xsl:when test="self::xsd:all">disc</xsl:when>
        <xsl:when test="self::xsd:any">circle</xsl:when>
        <xsl:otherwise>square</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:element name="ul">
      <xsl:attribute name="style">
        <xsl:value-of select="concat('list-style-type:', $list-type)" />
      </xsl:attribute>
      <xsl:apply-templates select="*" mode="operations.message.part">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
      </xsl:apply-templates>
    </xsl:element>
  </xsl:template>
  
  <xsl:template match="xsd:element[parent::xsd:schema]" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="recursion.label" select="concat('[', @name, ']')" />
    <xsl:variable name="recursion.test">
      <xsl:call-template name="recursion.should.continue">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
        <xsl:with-param name="recursion.label" select="$recursion.label" />
      </xsl:call-template>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="string-length($recursion.test) != 0">
        <xsl:variable name="type-name">
          <xsl:call-template name="xsd.element-type" />
        </xsl:variable>
        <xsl:variable name="elem-type" select="$consolidated-xsd[generate-id() != generate-id(current()) and $type-name and @name=$type-name and contains(local-name(), 'Type')][1]" />
        <xsl:if test="$type-name != @name">
          <xsl:apply-templates select="$elem-type" mode="operations.message.part">
            <xsl:with-param name="anti.recursion" select="concat($anti.recursion, $recursion.label)" />
          </xsl:apply-templates>
          <xsl:if test="not($elem-type)">
            <xsl:call-template name="render-type">
              <xsl:with-param name="type-local-name" select="$type-name" />
            </xsl:call-template>
          </xsl:if>
          <xsl:apply-templates select="*" mode="operations.message.part">
            <xsl:with-param name="anti.recursion" select="concat($anti.recursion, $recursion.label)" />
          </xsl:apply-templates>
        </xsl:if>
      </xsl:when>
      <xsl:otherwise>
        small style="color:blue" 3
          <xsl:value-of select="$RECURSIVE" />
        /small
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="xsd:element | xsd:attribute" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <!--
         <xsl:variable name="recursion.label" select="concat('[', @name, ']')"/>
    -->
    <li>
      <xsl:variable name="local-ref" select="concat(@name, substring-after(@ref, ':'))" />
      <xsl:variable name="elem-name">
        <xsl:choose>
          <xsl:when test="@name">
            <xsl:value-of select="@name" />
          </xsl:when>
          <xsl:when test="$local-ref">
            <xsl:value-of select="$local-ref" />
          </xsl:when>
          <xsl:when test="@ref">
            <xsl:value-of select="@ref" />
          </xsl:when>
          <xsl:otherwise>anonymous</xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:value-of select="$elem-name" />
      <xsl:variable name="type-name">
        <xsl:call-template name="xsd.element-type" />
      </xsl:variable>
      <xsl:call-template name="render-type">
        <xsl:with-param name="type-local-name" select="$type-name" />
      </xsl:call-template>
      <xsl:variable name="elem-type" select="$consolidated-xsd[@name = $type-name and contains(local-name(), 'Type')][1]" />
      <xsl:apply-templates select="$elem-type | *" mode="operations.message.part">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
      </xsl:apply-templates>
    </li>
  </xsl:template>
  
  <xsl:template match="xsd:attribute[ @*[local-name() = 'arrayType'] ]" mode="operations.message.part">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="array-local-name" select="substring-after(@*[local-name() = 'arrayType'], ':')" />
    <xsl:variable name="type-local-name" select="substring-before($array-local-name, '[')" />
    <xsl:variable name="array-type" select="$consolidated-xsd[@name = $type-local-name][1]" />
    <xsl:variable name="recursion.label" select="concat('[', $type-local-name, ']')" />
    <xsl:variable name="recursion.test">
      <xsl:call-template name="recursion.should.continue">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
        <xsl:with-param name="recursion.label" select="$recursion.label" />
      </xsl:call-template>
    </xsl:variable>
    <xsl:choose>
      <xsl:when test="string-length($recursion.test) != 0">
        <xsl:apply-templates select="$array-type" mode="operations.message.part">
          <xsl:with-param name="anti.recursion" select="concat($anti.recursion, $recursion.label)" />
        </xsl:apply-templates>
      </xsl:when>
      <xsl:otherwise>
        small style="color:blue" 4
          <xsl:value-of select="$RECURSIVE" />
        /small
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template name="xsd.element-type">
    <xsl:variable name="ref-or-type">
      <xsl:choose>
        <xsl:when test="@type">
          <xsl:value-of select="@type" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="@ref" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="type-local-name" select="substring-after($ref-or-type, ':')" />
    <xsl:variable name="type-name">
      <xsl:choose>
        <xsl:when test="$type-local-name">
          <xsl:value-of select="$type-local-name" />
        </xsl:when>
        <xsl:when test="$ref-or-type">
          <xsl:value-of select="$ref-or-type" />
        </xsl:when>
        <xsl:otherwise>undefined</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:value-of select="$type-name" />
  </xsl:template>
  
  <xsl:template match="xsd:documentation" mode="operations.message.part">
    <div style="color:green">
      <xsl:value-of select="." disable-output-escaping="yes" />
    </div>
  </xsl:template>
  
  <xsl:template name="render-type">
    <xsl:param name="anti.recursion" />
    <xsl:param name="type-local-name" />
    <xsl:variable name="properties">
      <xsl:if test="self::xsd:element | self::xsd:attribute[parent::xsd:complexType]">
        <xsl:variable name="min">
          <xsl:if test="@minOccurs = '0'">optional</xsl:if>
        </xsl:variable>
        <xsl:variable name="max">
          <xsl:if test="@maxOccurs = 'unbounded'">unbounded</xsl:if>
        </xsl:variable>
        <xsl:variable name="nillable">
          <xsl:if test="@nillable">nillable</xsl:if>
        </xsl:variable>
        <xsl:if test="(string-length($min) + string-length($max) + string-length($nillable) + string-length(@use)) &gt; 0">
          <xsl:text>-</xsl:text>
          <xsl:value-of select="$min" />
          <xsl:if test="string-length($min) and string-length($max)">
            <xsl:text>,</xsl:text>
          </xsl:if>
          <xsl:value-of select="$max" />
          <xsl:if test="(string-length($min) + string-length($max)) &gt; 0 and string-length($nillable)">
            <xsl:text>,</xsl:text>
          </xsl:if>
          <xsl:value-of select="$nillable" />
          <xsl:if test="(string-length($min) + string-length($max) + string-length($nillable)) &gt; 0 and string-length(@use)">
            <xsl:text>,</xsl:text>
          </xsl:if>
          <xsl:value-of select="@use" />
          <xsl:text>;</xsl:text>
        </xsl:if>
      </xsl:if>
    </xsl:variable>
    <xsl:variable name="recursion.label" select="concat('[', $type-local-name, ']')" />
    <xsl:variable name="recursion.test">
      <xsl:call-template name="recursion.should.continue">
        <xsl:with-param name="anti.recursion" select="$anti.recursion" />
        <xsl:with-param name="recursion.label" select="$recursion.label" />
        <xsl:with-param name="recursion.count" select="$ANTIRECURSION-DEPTH" />
      </xsl:call-template>
    </xsl:variable>
    <xsl:if test="string-length($recursion.test) != 0">
      
        <xsl:value-of select="$properties" />
        <xsl:variable name="elem-type" select="$consolidated-xsd[@name = $type-local-name and (not(contains(local-name(current()), 'element')) or contains(local-name(), 'Type'))][1]" />
        <xsl:if test="string-length($type-local-name) &gt; 0">
          <xsl:call-template name="render-type.write-name">
            <xsl:with-param name="type-local-name" select="$type-local-name" />
          </xsl:call-template>
        </xsl:if>
        <xsl:choose>
          <xsl:when test="$elem-type">
            <xsl:apply-templates select="$elem-type" mode="render-type">
              <xsl:with-param name="anti.recursion" select="concat($anti.recursion, $recursion.label)" />
            </xsl:apply-templates>
          </xsl:when>
          <xsl:otherwise>
            <xsl:apply-templates select="*" mode="render-type">
              <xsl:with-param name="anti.recursion" select="concat($anti.recursion, $recursion.label)" />
            </xsl:apply-templates>
          </xsl:otherwise>
        </xsl:choose>
      
    </xsl:if>
  </xsl:template>
  
  <xsl:template name="render-type.write-name">
    <xsl:param name="type-local-name" />
    <xsl:text> : </xsl:text>
      <emphasis>
        <xsl:choose>
          <xsl:when test="$type-local-name">
            <xsl:value-of select="$type-local-name" />
          </xsl:when>
          <xsl:otherwise>undefined</xsl:otherwise>
        </xsl:choose>
      </emphasis>
  </xsl:template>
  
  <xsl:template match="*" mode="render-type" />
  
  <xsl:template match="xsd:element | xsd:complexType | xsd:simpleType | xsd:complexContent" mode="render-type">
    <xsl:param name="anti.recursion" />
    <xsl:apply-templates select="*" mode="render-type">
      <xsl:with-param name="anti.recursion" select="$anti.recursion" />
    </xsl:apply-templates>
  </xsl:template>
  
  <xsl:template match="xsd:restriction[ parent::xsd:simpleType ]" mode="render-type">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="type-local-name" select="substring-after(@base, ':')" />
    <xsl:variable name="type-name">
      <xsl:choose>
        <xsl:when test="$type-local-name">
          <xsl:value-of select="$type-local-name" />
        </xsl:when>
        <xsl:when test="@base">
          <xsl:value-of select="@base" />
        </xsl:when>
        <xsl:otherwise>undefined</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:text>-</xsl:text>
    <xsl:call-template name="render-type.write-name">
      <xsl:with-param name="type-local-name" select="$type-local-name" />
    </xsl:call-template>
    <xsl:text>with</xsl:text>
    <xsl:value-of select="local-name()" />
    <xsl:apply-templates select="*" mode="render-type">
      <xsl:with-param name="anti.recursion" select="$anti.recursion" />
    </xsl:apply-templates>
  </xsl:template>
  
  <xsl:template match="xsd:simpleType/xsd:restriction/xsd:*[not(self::xsd:enumeration)]" mode="render-type">
    <xsl:text />
    <xsl:value-of select="local-name()" />
    <xsl:text>(</xsl:text>
    <xsl:value-of select="@value" />
    <xsl:text>)</xsl:text>
  </xsl:template>
  
  <xsl:template match="xsd:restriction | xsd:extension" mode="render-type">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="type-local-name" select="substring-after(@base, ':')" />
    <xsl:variable name="type-name">
      <xsl:choose>
        <xsl:when test="$type-local-name">
          <xsl:value-of select="$type-local-name" />
        </xsl:when>
        <xsl:when test="@base">
          <xsl:value-of select="@base" />
        </xsl:when>
        <xsl:otherwise>undefined</xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="base-type" select="$consolidated-xsd[@name = $type-name][1]" />
    <xsl:variable name="abstract">
      <xsl:if test="$base-type/@abstract">abstract</xsl:if>
    </xsl:variable>
    <xsl:if test="not($type-name = 'Array')">
      <xsl:value-of select="concat(' - ', local-name(), ' of ', $abstract)" />
      <xsl:call-template name="render-type.write-name">
        <xsl:with-param name="type-local-name" select="$type-name" />
      </xsl:call-template>
    </xsl:if>
    <xsl:apply-templates select="$base-type | *" mode="render-type">
      <xsl:with-param name="anti.recursion" select="$anti.recursion" />
    </xsl:apply-templates>
  </xsl:template>
  
  <xsl:template match="xsd:attribute[ @*[local-name() = 'arrayType'] ]" mode="render-type">
    <xsl:param name="anti.recursion" />
    <xsl:variable name="array-local-name" select="substring-after(@*[local-name() = 'arrayType'], ':')" />
    <xsl:variable name="type-local-name" select="substring-before($array-local-name, '[')" />
    <xsl:variable name="array-type" select="$consolidated-xsd[@name = $type-local-name][1]" />
    <xsl:text>- array of</xsl:text>
    <xsl:call-template name="render-type.write-name">
      <xsl:with-param name="type-local-name" select="$type-local-name" />
    </xsl:call-template>
    <xsl:apply-templates select="$array-type" mode="render-type">
      <xsl:with-param name="anti.recursion" select="$anti.recursion" />
    </xsl:apply-templates>
  </xsl:template>
  
  <xsl:template match="xsd:enumeration" mode="render-type" />
  
  <xsl:template match="xsd:enumeration[not(preceding-sibling::xsd:enumeration)]" mode="render-type">
    <xsl:text>- enum {</xsl:text>
    <xsl:apply-templates select="self::* | following-sibling::xsd:enumeration" mode="render-type.enum" />
    <xsl:text>}</xsl:text>
  </xsl:template>
  
  <xsl:template match="xsd:enumeration" mode="render-type.enum">
    <xsl:if test="preceding-sibling::xsd:enumeration">
      <xsl:text>,</xsl:text>
    </xsl:if>
    <xsl:text disable-output-escaping="yes">'</xsl:text>
    <xsl:value-of select="@value" />
    <xsl:text disable-output-escaping="yes">'</xsl:text>
  </xsl:template>
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       End of included transformation: wsdl-viewer-xsd-tree.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       Begin of included transformation: wsdl-viewer-src.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <xsl:template match="@*" mode="src.import">
    <xsl:param name="src.import.stack" />
    <xsl:variable name="recursion.label" select="concat('[', string(.), ']')" />
    <xsl:variable name="recursion.check" select="concat($src.import.stack, $recursion.label)" />
    <xsl:choose>
      <xsl:when test="contains($src.import.stack, $recursion.label)">
        <h2 style="red">
          <xsl:value-of select="concat('Cyclic include / import: ', $recursion.check)" />
        </h2>
      </xsl:when>
      <xsl:otherwise>
        <h2>
          <a name="{concat($SRC-FILE-PREFIX, generate-id(..))}">
            <xsl:choose>
              <xsl:when test="parent::xsd:include">Included</xsl:when>
              <xsl:otherwise>Imported</xsl:otherwise>
            </xsl:choose>
            <xsl:choose>
              <xsl:when test="name() = 'location'">WSDL</xsl:when>
              <xsl:otherwise>Schema</xsl:otherwise>
            </xsl:choose>
            <emphasis>
              <xsl:value-of select="." />
            </emphasis>
          </a>
        </h2>
        <div class="box">
          <xsl:apply-templates select="document(string(.))" mode="src" />
        </div>
        <xsl:apply-templates select="document(string(.))/*/*[local-name() = 'import'][@location]/@location" mode="src.import">
          <xsl:with-param name="src.import.stack" select="$recursion.check" />
        </xsl:apply-templates>
        <xsl:apply-templates select="document(string(.))//xsd:import[@schemaLocation]/@schemaLocation" mode="src.import">
          <xsl:with-param name="src.import.stack" select="$recursion.check" />
        </xsl:apply-templates>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="*" mode="src">
    <div class="xml-element">
      <a name="{concat($SRC-PREFIX, generate-id(.))}">
        <xsl:apply-templates select="." mode="src.link" />
        <xsl:apply-templates select="." mode="src.start-tag" />
      </a>
      <xsl:apply-templates select="*|comment()|processing-instruction()|text()[string-length(normalize-space(.)) &gt; 0]" mode="src" />
      <xsl:apply-templates select="." mode="src.end-tag" />
    </div>
  </xsl:template>
  
  <xsl:template match="*" mode="src.start-tag">
    <xsl:call-template name="src.elem">
      <xsl:with-param name="src.elem.end-slash">/</xsl:with-param>
    </xsl:call-template>
  </xsl:template>
  
  <xsl:template match="*[*|comment()|processing-instruction()|text()[string-length(normalize-space(.)) &gt; 0]]" mode="src.start-tag">
    <xsl:call-template name="src.elem" />
  </xsl:template>
  
  <xsl:template match="*" mode="src.end-tag" />
  
  <xsl:template match="*[*|comment()|processing-instruction()|text()[string-length(normalize-space(.)) &gt; 0]]" mode="src.end-tag">
    <xsl:call-template name="src.elem">
      <xsl:with-param name="src.elem.start-slash">/</xsl:with-param>
    </xsl:call-template>
  </xsl:template>
  
  <xsl:template match="*" mode="src.link" />
  
  <xsl:template match="ws2:service|ws2:binding" mode="src.link">
    <xsl:variable name="iface-name">
      <xsl:apply-templates select="@interface" mode="qname.normalized" />
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-wsdl/ws2:interface[@name = $iface-name]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws2:endpoint" mode="src.link">
    <xsl:variable name="binding-name">
      <xsl:apply-templates select="@binding" mode="qname.normalized" />
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-wsdl/ws2:binding[@name = $binding-name]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws2:binding/ws2:operation" mode="src.link">
    <xsl:variable name="operation-name">
      <xsl:apply-templates select="@ref" mode="qname.normalized" />
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-wsdl/ws2:interface/ws2:operation[@name = $operation-name]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws2:binding/ws2:fault|ws2:interface/ws2:operation/ws2:infault|ws2:interface/ws2:operation/ws2:outfault" mode="src.link">
    <xsl:variable name="operation-name">
      <xsl:apply-templates select="@ref" mode="qname.normalized" />
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-wsdl/ws2:interface/ws2:fault[@name = $operation-name]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws2:interface/ws2:operation/ws2:input|ws2:interface/ws2:operation/ws2:output|ws2:interface/ws2:fault" mode="src.link">
    <xsl:variable name="elem-name">
      <xsl:apply-templates select="@element" mode="qname.normalized" />
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-xsd[@name = $elem-name]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws:operation/ws:input[@message] | ws:operation/ws:output[@message] | ws:operation/ws:fault[@message] | soap:header[ancestor::ws:operation and @message]" mode="src.link">
    <xsl:apply-templates select="$consolidated-wsdl/ws:message[@name = substring-after( current()/@message, ':' )]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws:operation/ws:input[@message] | ws:operation/ws:output[@message] | ws:operation/ws:fault[@message] | soap:header[ancestor::ws:operation and @message]" mode="src.link">
    <xsl:apply-templates select="$consolidated-wsdl/ws:message[@name = substring-after( current()/@message, ':' )]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws:message/ws:part[@element or @type]" mode="src.link">
    <xsl:variable name="elem-local-name" select="substring-after(@element, ':')" />
    <xsl:variable name="type-local-name" select="substring-after(@type, ':')" />
    <xsl:variable name="elem-name">
      <xsl:choose>
        <xsl:when test="$elem-local-name">
          <xsl:value-of select="$elem-local-name" />
        </xsl:when>
        <xsl:when test="$type-local-name">
          <xsl:value-of select="$type-local-name" />
        </xsl:when>
        <xsl:when test="@element">
          <xsl:value-of select="@element" />
        </xsl:when>
        <xsl:when test="@type">
          <xsl:value-of select="@type" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:call-template name="src.syntax-error" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-xsd[@name = $elem-name]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws:service/ws:port[@binding]" mode="src.link">
    <xsl:variable name="binding-name">
      <xsl:apply-templates select="@binding" mode="qname.normalized" />
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-wsdl/ws:binding[@name = $binding-name]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="ws:operation[@name and parent::ws:binding/@type]" mode="src.link">
    <xsl:variable name="type-name">
      <xsl:apply-templates select="../@type" mode="qname.normalized" />
    </xsl:variable>
    <xsl:apply-templates select="$consolidated-wsdl/ws:portType[@name = $type-name]/ws:operation[@name = current()/@name]" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template match="xsd:element[@ref or @type]" mode="src.link">
    <xsl:variable name="ref-or-type">
      <xsl:choose>
        <xsl:when test="@type">
          <xsl:value-of select="@type" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="@ref" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="type-local-name" select="substring-after($ref-or-type, ':')" />
    <xsl:variable name="xsd-name">
      <xsl:choose>
        <xsl:when test="$type-local-name">
          <xsl:value-of select="$type-local-name" />
        </xsl:when>
        <xsl:when test="$ref-or-type">
          <xsl:value-of select="$ref-or-type" />
        </xsl:when>
        <xsl:otherwise />
      </xsl:choose>
    </xsl:variable>
    <xsl:if test="$xsd-name">
      <xsl:variable name="msg" select="$consolidated-xsd[@name = $xsd-name and contains(local-name(), 'Type')][1]" />
      <xsl:apply-templates select="$msg" mode="src.link-attribute" />
    </xsl:if>
  </xsl:template>
  
  <xsl:template match="xsd:attribute[contains(@ref, 'arrayType')]" mode="src.link">
    <xsl:variable name="att-array-type" select="substring-before(@*[local-name() = 'arrayType'], '[]')" />
    <xsl:variable name="xsd-local-name" select="substring-after($att-array-type, ':')" />
    <xsl:variable name="xsd-name">
      <xsl:choose>
        <xsl:when test="$xsd-local-name">
          <xsl:value-of select="$xsd-local-name" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="$att-array-type" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:if test="$xsd-name">
      <xsl:variable name="msg" select="$consolidated-xsd[@name = $xsd-name][1]" />
      <xsl:apply-templates select="$msg" mode="src.link-attribute" />
    </xsl:if>
  </xsl:template>
  
  <xsl:template match="xsd:extension | xsd:restriction" mode="src.link">
    <xsl:variable name="xsd-local-name" select="substring-after(@base, ':')" />
    <xsl:variable name="xsd-name">
      <xsl:choose>
        <xsl:when test="$xsd-local-name">
          <xsl:value-of select="$xsd-local-name" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="@type" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>
    <xsl:variable name="msg" select="$consolidated-xsd[@name = $xsd-name][1]" />
    <xsl:apply-templates select="$msg" mode="src.link-attribute" />
  </xsl:template>
  
  <xsl:template name="src.elem">
    <xsl:param name="src.elem.start-slash" />
    <xsl:param name="src.elem.end-slash" />
    <xsl:value-of select="concat('&lt;', $src.elem.start-slash, name(.))" disable-output-escaping="no" />
    <xsl:if test="not($src.elem.start-slash)">
      <xsl:apply-templates select="@*" mode="src" />
      <xsl:apply-templates select="." mode="src.namespace" />
    </xsl:if>
    <xsl:value-of select="concat($src.elem.end-slash, '&gt;')" disable-output-escaping="no" />
  </xsl:template>
  
  <xsl:template match="@*" mode="src">
    <xsl:text />
    <span class="xml-att">
      <xsl:value-of select="concat(name(), '=')" />
      <span class="xml-att-val">
        <xsl:value-of select="concat('&#34;', ., '&#34;')" disable-output-escaping="yes" />
      </span>
    </span>
  </xsl:template>
  
  <xsl:template match="*" mode="src.namespace">
    <xsl:variable name="supports-namespace-axis" select="count(/*/namespace::*) &gt; 0" />
    <xsl:variable name="current" select="current()" />
    <xsl:choose>
      <xsl:when test="count(/*/namespace::*) &gt; 0">
        <!--
             When the namespace axis is present (e.g. Internet Explorer), we can simulate
             the namespace declarations by comparing the namespaces in scope on this element
             with those in scope on the parent element.  Any difference must have been the
             result of a namespace declaration.  Note that this doesn't reflect the actual
             source - it will strip out redundant namespace declarations.
        -->
        <xsl:for-each select="namespace::*[. != 'http://www.w3.org/XML/1998/namespace']">
          <xsl:if test="not($current/parent::*[namespace::*[. = current()]])">
            <div class="xml-att">
              <xsl:text>xmlns</xsl:text>
              <xsl:if test="string-length(name())">:</xsl:if>
              <xsl:value-of select="concat(name(), '=')" />
              <span class="xml-att-val">
                <xsl:value-of select="concat('&#34;', ., '&#34;')" disable-output-escaping="yes" />
              </span>
            </div>
          </xsl:if>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
        <!-- 
             When the namespace axis isn't supported (e.g. Mozilla), we can simulate
             appropriate declarations from namespace elements.
             This currently doesn't check for namespaces on attributes.
             In the general case we can't reliably detect the use of QNames in content, but
             in the case of schema, we know which content could contain a QName and look
             there too.  This mechanism is rather unpleasant though, since it records
             namespaces where they are used rather than showing where they are declared 
             (on some parent element) in the source.  Yukk!
        -->
        <xsl:if test="namespace-uri(.) != namespace-uri(parent::*) or not(parent::*)">
          <span class="xml-att">
            <xsl:text>xmlns</xsl:text>
            <xsl:if test="substring-before(name(),':') != ''">:</xsl:if>
            <xsl:value-of select="substring-before(name(),':')" />
            <xsl:text>=</xsl:text>
            <span class="xml-att-val">
              <xsl:value-of select="concat('&#34;', namespace-uri(.), '&#34;')" disable-output-escaping="yes" />
            </span>
          </span>
        </xsl:if>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  
  <xsl:template match="text()" mode="src">
    <span class="xml-text">
      <xsl:value-of select="." disable-output-escaping="no" />
    </span>
  </xsl:template>
  
  <xsl:template match="comment()" mode="src">
    <div class="xml-comment">
      <xsl:text disable-output-escaping="no">&lt;!--</xsl:text>
      <xsl:value-of select="." disable-output-escaping="no" />
      <xsl:text disable-output-escaping="no">--&gt;</xsl:text>
    </div>
  </xsl:template>
  
  <xsl:template match="processing-instruction()" mode="src">
    <div class="xml-proc">
      <xsl:text disable-output-escaping="no">&lt;?</xsl:text>
      <xsl:copy-of select="name(.)" />
      <xsl:value-of select="concat(' ', .)" disable-output-escaping="yes" />
      <xsl:text disable-output-escaping="no">?&gt;</xsl:text>
    </div>
  </xsl:template>
  <!--
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
       End of included transformation: wsdl-viewer-src.xsl
       @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
  -->
  <!--
       ==================================================================
       Starting point
       ==================================================================
  -->
  <xsl:template match="/">
    <article xmlns:xi="http://www.w3.org/2001/XInclude">
      <articleinfo>
      <title>Ulteo Open Virtual Desktop v@VERSION@</title>
      <subtitle>SOAP Api</subtitle>
      <graphic fileref="img/ovd.png" align="center"/>

      <copyright>
        <year>@YEAR@</year>
        <holder>Ulteo SAS - <ulink url="http://www.ulteo.com" /></holder>
      </copyright>
    </articleinfo>

    <section>
      <title><xsl:value-of select="$html-title" /></title>    
      <xsl:apply-templates select="$consolidated-wsdl/*[local-name(.) = 'documentation']" mode="documentation.render" />
      <xsl:apply-templates select="$consolidated-wsdl/ws:service|$consolidated-wsdl/ws2:service" mode="service-start" />
      <xsl:if test="not($consolidated-wsdl/*[local-name() = 'service']/@name)">
        <!-- If the WS is without implementation, just with binding points = WS interface -->
        <xsl:apply-templates select="$consolidated-wsdl/ws:binding" mode="service-start" />
        <xsl:apply-templates select="$consolidated-wsdl/ws2:interface" mode="service" />
      </xsl:if>
    </section>
    
    <xsl:apply-templates select="$consolidated-wsdl/ws:portType" mode="operations">
      <xsl:sort select="@name" />
    </xsl:apply-templates>
    <xsl:choose>
      <xsl:when test="$consolidated-wsdl/*[local-name() = 'service']/@name">
        <xsl:variable name="iface-name">
          <xsl:apply-templates select="$consolidated-wsdl/*[local-name() = 'service']/@interface" mode="qname.normalized" />
        </xsl:variable>
        <xsl:apply-templates select="$consolidated-wsdl/ws2:interface[@name = $iface-name]" mode="operations">
          <xsl:sort select="@name" />
        </xsl:apply-templates>
      </xsl:when>
      <xsl:when test="$consolidated-wsdl/ws2:interface/@name">
        <!-- TODO: What to do if there are more interfaces? -->
        <xsl:apply-templates select="$consolidated-wsdl/ws2:interface[1]" mode="operations" />
      </xsl:when>
      <xsl:otherwise><!-- TODO: Error message or handling somehow this unexpected situation --></xsl:otherwise>
    </xsl:choose>
    
    </article>
  </xsl:template>

</xsl:stylesheet>
