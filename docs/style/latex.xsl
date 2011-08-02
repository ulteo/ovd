<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <!--  
       <xsl:import href="/usr/share/xml/docbook/stylesheet/nwalsh/xhtml/docbook.xsl" />
       <xsl:import href="xsl2/mytitlepage.xsl" />
       <xsl:output encoding="utf-8" indent="no"/>
  -->
  <xsl:param name="latex.class.options">11pt,a4paper,oneside</xsl:param>
  <xsl:param name="latex.hyperparam">colorlinks, linkcolor=black, urlcolor=blue, linktoc=black, citecolor=green, filecolor=black, pdfstartview=FitH</xsl:param>
  <xsl:param name="figure.important">img/dialog-warning.png</xsl:param>
  <xsl:param name="figure.note">img/dialog-information.png</xsl:param>
  
  
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
      \textit{\DBKcopyright}
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
    <xsl:text>
      \newpage
      \pagestyle{empty}
      \vspace*{\fill}
      \begin{center}

      \begin{minipage}{10cm}
      \begin{center}
      <!-- \DBKtitle -->
      {\LARGE \THETITLE}

      \vspace{0.3cm}
      

      {\Large \DBKsubtitle}
      \normalsize
      \vspace{0.4cm}
    </xsl:text>
    <xsl:apply-templates select="colophon"/>
    <xsl:text>
      \vspace{0.1cm}
      

      \DBKcopyright
      \end{center}

      
      \end{minipage}
      \end{center}      
      \vspace*{\fill}
    </xsl:text>
    <xsl:call-template name="lang.document.end">
      <xsl:with-param name="lang" select="$lang"/>
    </xsl:call-template>
    <xsl:value-of select="$latex.enddocument"/>
  </xsl:template>

  <!-- Harold Leboulanger <harold@ulteo.com> 2011 -->

  <!-- redefine the colohpon template: prevent the "colophon" title from showing up -->
  <!-- the colophon is used as the document last page with the Document title, a small text where to find new information, the Ulteo logo and the copyright -->
  <xsl:template match="colophon">
    <xsl:call-template name="section.unnumbered">
      <xsl:with-param name="tocdepth" select="number($colophon.tocdepth)"/>
      <xsl:with-param name="title">
	<!-- <xsl:call-template name="gentext"> -->
	<!--   <xsl:with-param name="key" select="'Colophon'"/> -->
	<!-- </xsl:call-template> -->
      </xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <!-- redefine sections to add  a page break before each \section -->
  <xsl:template name="map.sect.level">
    <xsl:param name="level" select="''"/>
    <xsl:param name="name" select="''"/>
    <xsl:param name="num" select="'1'"/>
    <xsl:param name="allnum" select="'0'"/>
    <xsl:text>&#10;</xsl:text>
    <xsl:choose>
      <xsl:when test="$level &lt; 6">
	<xsl:choose>
	  <xsl:when test='$level=1'>\newpage \section</xsl:when>
	  <xsl:when test='$level=2'>\subsection</xsl:when>
	  <xsl:when test='$level=3'>\subsubsection</xsl:when>
	  <xsl:when test='$level=4'>\paragraph</xsl:when>
	  <xsl:when test='$level=5'>\subparagraph</xsl:when>
	  <!-- rare case -->
	  <xsl:when test='$level=0'>\chapter</xsl:when>
	  <xsl:when test='$level=-1'>\part</xsl:when>
	</xsl:choose>
      </xsl:when>
      <xsl:when test="$name!=''">
	<xsl:choose>
	  <xsl:when test="$name='sect1'">\section</xsl:when>
	  <xsl:when test="$name='sect2'">\subsection</xsl:when>
	  <xsl:when test="$name='sect3'">\subsubsection</xsl:when>
	  <xsl:when test="$name='sect4'">\paragraph</xsl:when>
	  <xsl:when test="$name='sect5'">\subparagraph</xsl:when>
	</xsl:choose>
      </xsl:when>
      <xsl:otherwise>
	<xsl:message>Section level &gt; 6 not well supported</xsl:message> 
	<xsl:text>\subparagraph</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:choose>
      <xsl:when test="$allnum = '1'"/>
      <xsl:when test="$num = '0'">
	<xsl:text>*</xsl:text>
      </xsl:when>
      <xsl:when test="ancestor::preface|ancestor::colophon|
		      ancestor::dedication|ancestor::partintro|
		      ancestor::glossary|ancestor::qandaset">
	<xsl:text>*</xsl:text>
      </xsl:when>
    </xsl:choose>
  </xsl:template>


  <!-- Display verbatim text inside admonition -->
  <!-- changed the if to add admonitions -->
  <xsl:template match="programlisting|screen"
		mode="save.verbatim.preamble">
    <xsl:if test="not(ancestor::table or ancestor::informaltable) and
		  (ancestor::footnote or 
		  ancestor::note or
		  ancestor::caution or
		  ancestor::warning or
		  ancestor::tip or
		  ancestor::important)">
      <xsl:apply-templates select="." mode="save.verbatim"/>
    </xsl:if>
  </xsl:template>
  
  <!-- changed the match to add admonitions -->
  <xsl:template match="programlisting[ancestor::entry or
		       ancestor::entrytbl or
		       ancestor::footnote or
		       ancestor::note or
		       ancestor::caution or
		       ancestor::warning or
		       ancestor::tip or
		       ancestor::important] |
		       screen[ancestor::entry or
		       ancestor::entrytbl or
		       ancestor::footnote or
		       ancestor::note or
		       ancestor::caution or
		       ancestor::warning or
		       ancestor::tip or
		       ancestor::important]">
    <xsl:variable name="lsopt">
      <!-- language option is only for programlisting -->
      <xsl:if test="@language">
	<xsl:text>language=</xsl:text>
	<xsl:value-of select="@language"/>
	<xsl:text>,</xsl:text>
      </xsl:if>
    </xsl:variable>

    <xsl:variable name="fvopt">
      <!-- print line numbers -->
      <xsl:if test="@linenumbering='numbered'">
	<xsl:text>numbers=left,</xsl:text>
	<!-- find the fist line number to print -->
	<xsl:choose>
	  <xsl:when test="@startinglinenumber">
	    <xsl:text>firstnumber=</xsl:text>
	    <xsl:value-of select="@startinglinenumber"/>
	    <xsl:text>,</xsl:text>
	  </xsl:when>
	  <xsl:when test="@continuation and (@continuation='continues')">
	    <!-- ask for continuation -->
	    <xsl:text>firstnumber=last</xsl:text>
	    <xsl:text>,</xsl:text>
	  </xsl:when>
	  <xsl:otherwise>
	    <!-- explicit restart numbering -->
	    <xsl:text>firstnumber=1</xsl:text>
	    <xsl:text>,</xsl:text>
	  </xsl:otherwise>
	</xsl:choose>
      </xsl:if>
      <!-- TODO: TeX delimiters if <co>s are embedded -->
    </xsl:variable>

    <xsl:text>
      \begin{fvlisting}
    </xsl:text>
    <xsl:if test="$lsopt!=''">
      <xsl:text>[</xsl:text>
      <xsl:value-of select="$lsopt"/>
      <xsl:text>]</xsl:text>
    </xsl:if>
    <xsl:text>&#10;</xsl:text>

    <xsl:text>\VerbatimInput</xsl:text>
    <xsl:if test="$fvopt!=''">
      <xsl:text>[</xsl:text>
      <xsl:value-of select="$fvopt"/>
      <xsl:text>]</xsl:text>
    </xsl:if>

    <xsl:text>{</xsl:text>
    <xsl:choose>
      <xsl:when test="descendant::imagedata[@format='linespecific']|
		      descendant::inlinegraphic[@format='linespecific']|
		      descendant::textdata">
	<!-- the listing content is in a (real) external file -->
	<xsl:apply-templates
	    select="descendant::imagedata|descendant::inlinegraphic|
		    descendant::textdata"
	    mode="filename.abs.get"/>
      </xsl:when>
      <xsl:otherwise>
	<!-- the listing is outputed in a temporary file -->
	<xsl:text>tmplst-</xsl:text>
	<xsl:value-of select="generate-id(.)"/>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:text>}&#10;</xsl:text>

    <xsl:text>
      \end{fvlisting}&#10;
    </xsl:text>

  </xsl:template>


  <xsl:template match="*" mode="filename.abs.get">
    <xsl:choose>
      <xsl:when test="@entityref">
	<xsl:value-of select="unparsed-entity-uri(@entityref)"/>
      </xsl:when>
      <xsl:when test="contains(@fileref, ':')">
	<!-- absolute uri scheme -->
	<xsl:value-of select="substring-after(@fileref, ':')"/>
      </xsl:when>
      <xsl:when test="starts-with(@fileref, '/')">
	<!-- absolute unix like path -->
	<xsl:value-of select="@fileref"/>
      </xsl:when>
      <xsl:otherwise>
	<!-- relative to the doc directory -->
	<xsl:value-of select="$current.dir"/>
	<xsl:text>/</xsl:text>
	<xsl:value-of select="@fileref"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>



  <!-- modify this template to protect # URL inside admonitions -->
  <xsl:template name="ulink-encode">
    <xsl:param name="escape" select="0"/>

    
    <xsl:call-template name="scape-encode">
      <xsl:with-param name="string">
	<xsl:choose>
	  <xsl:when test="$escape != 0 or ancestor::entry or ancestor::revision or
			  ancestor::footnote or ancestor::term or 
		
			  ancestor::note or
			  ancestor::caution or
			  ancestor::warning or
			  ancestor::tip or
			  ancestor::important">
	    <xsl:call-template name="string-replace">
	      <xsl:with-param name="string">
		<xsl:call-template name="string-replace">
		  <xsl:with-param name="string">
		    <xsl:call-template name="string-replace">
		      <xsl:with-param name="string" select="@url"/>
		      <xsl:with-param name="from" select="'%'"/>
		      <xsl:with-param name="to" select="'\%'"/>
		    </xsl:call-template>
		  </xsl:with-param>
		  <xsl:with-param name="from" select="'#'"/>
		  <xsl:with-param name="to" select="'\#'"/>
		</xsl:call-template>
	      </xsl:with-param>
	      <xsl:with-param name="from" select="'&amp;'"/>
	      <xsl:with-param name="to" select="'\&amp;'"/>
	    </xsl:call-template>
	  </xsl:when>
	  <xsl:otherwise>
	    <xsl:value-of select="@url"/>
	  </xsl:otherwise>
	</xsl:choose>
      </xsl:with-param>
    </xsl:call-template>
  </xsl:template>
</xsl:stylesheet>
