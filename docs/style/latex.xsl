<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<!--  
     <xsl:import href="/usr/share/xml/docbook/stylesheet/nwalsh/xhtml/docbook.xsl" />
  <xsl:import href="xsl2/mytitlepage.xsl" />
  <xsl:output encoding="utf-8" indent="no"/>
-->

<xsl:template name="copyright2">
  <xsl:param name="node"/>

  <xsl:call-template name="gentext">
    <xsl:with-param name="key" select="'Copyright'"/>
  </xsl:call-template>
  <xsl:call-template name="gentext.space"/>
  <xsl:call-template name="dingbat">
    <xsl:with-param name="dingbat">copyright</xsl:with-param>
  </xsl:call-template>

  <xsl:call-template name="gentext.space"/>
  <xsl:value-of select="$node/year"/>
  <xsl:call-template name="gentext.space"/>
  <xsl:value-of select="$node/holder"/>
</xsl:template>

<xsl:template match="book|article">
  <xsl:variable name="info" select="bookinfo|articleinfo|artheader|info"/>
  <xsl:variable name="lang">
    <xsl:call-template name="l10n.language">
      <xsl:with-param name="target" select="(/set|/book|/article)[1]"/>
      <xsl:with-param name="xref-context" select="true()"/>
    </xsl:call-template>
  </xsl:variable>

  <!-- Latex preamble -->
  <xsl:apply-templates select="." mode="preamble">
    <xsl:with-param name="lang" select="$lang"/>
  </xsl:apply-templates>

  <xsl:value-of select="$latex.begindocument"/>
  <xsl:call-template name="lang.document.begin">
    <xsl:with-param name="lang" select="$lang"/>
  </xsl:call-template>

  <!-- Apply the legalnotices here, when language is active -->
  <xsl:call-template name="print.legalnotice">
    <xsl:with-param name="nodes" select="$info/legalnotice"/>
  </xsl:call-template>

<!--  <xsl:text>\maketitle&#10;</xsl:text>-->


<xsl:variable name="title">
  <xsl:text> 
    \textbf{\huge{
  </xsl:text>
  <xsl:call-template name="normalize-scape">
    <xsl:with-param name="string">
      <xsl:choose>
	<xsl:when test="title">
	    <xsl:value-of select="title"/>
	</xsl:when>
	<xsl:when test="$info">
	    <xsl:value-of select="$info/title"/>
	</xsl:when>
      </xsl:choose>
    </xsl:with-param>
  </xsl:call-template>

  <xsl:choose>
    <xsl:when test="$info/subtitle">
      <xsl:text>}\\[1cm]}</xsl:text>
    </xsl:when>
    <xsl:otherwise>
      <xsl:text>}\\[4cm]}</xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:variable>

<xsl:variable name="subtitle">
  <xsl:if test="$info/subtitle">
    <xsl:text>
      \textbf{\LARGE{
    </xsl:text>
    <xsl:call-template name="normalize-scape">
      <xsl:with-param name="string">
        <xsl:value-of select="$info/subtitle"/>
      </xsl:with-param>
    </xsl:call-template>
    <xsl:text>
      }\\[3cm]}
    </xsl:text>
  </xsl:if>
</xsl:variable>


<xsl:variable name="copyright">

  <xsl:call-template name="copyright2">
    <xsl:with-param name="node" select="$info/copyright"/>
  </xsl:call-template>


</xsl:variable>

<xsl:text>
\begin{titlepage}
  \begin{center}
</xsl:text>
<xsl:value-of select="$title"/>
<xsl:value-of select="$subtitle"/>
<xsl:apply-templates select="$info/graphic"/>
<xsl:text>
  \end{center}
</xsl:text>

<xsl:text>
\textbf{}\\[4cm]
\begin{flushright}
 \textit{
</xsl:text>
<xsl:value-of select="$copyright"/>
<xsl:text>
 }
\end{flushright}
</xsl:text>
<xsl:text>
\end{titlepage}
</xsl:text>

  <!-- Print the TOC/LOTs -->
  <xsl:apply-templates select="." mode="toc_lots"/>
  <xsl:call-template name="label.id"/>
  <xsl:text>
    \newpage
  </xsl:text>

  <!-- Print the abstract and front matter content -->
  <xsl:apply-templates select="(abstract|$info/abstract)[1]"/>
  <xsl:apply-templates select="dedication|preface"/>

  <!-- Body content -->
  <xsl:apply-templates select="*[not(self::abstract or
                                     self::preface or
                                     self::dedication or
                                     self::colophon)]"/>

  <!-- Back matter -->
  <xsl:if test="*//indexterm|*//keyword">
    <xsl:text>\printindex&#10;</xsl:text>
  </xsl:if>
  <xsl:apply-templates select="colophon"/>
  <xsl:call-template name="lang.document.end">
    <xsl:with-param name="lang" select="$lang"/>
  </xsl:call-template>
  <xsl:value-of select="$latex.enddocument"/>
</xsl:template>

</xsl:stylesheet>
