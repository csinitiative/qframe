<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:csi="http://www.csinitiative.com/ns/csi-qframe"
xmlns:pdf="http://www.xhtml2pdf.com/pdf"
elementFormDefault="qualified">
<xsl:output method="xml" indent="yes"/>

<xsl:template match="csi:questionnaire">
  <html>
  <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title><xsl:value-of select="@questionnaireName"/><xsl:text> </xsl:text><xsl:value-of select="@questionnaireVersion"/>:<xsl:text> </xsl:text><xsl:value-of select="@instanceName"/></title>
  <style type="text/css">
    @page {
      @frame left_footer1 {
        -pdf-frame-content: leftFooterContent;
        margin-left: 1cm;
        margin-right: 1cm;
        height: 1cm;
        bottom: 1cm;
      }
      @frame center_footer1 {
        -pdf-frame-content: centerFooterContent1;
        height: 1cm;
        bottom: 1cm;
        left: 3.9cm;
        width: 9.8cm;
      }
      @frame center_footer2 {
        -pdf-frame-content: centerFooterContent2;
        height: 1cm;
        bottom: 0.5cm;
        left: 3.9cm;
        width: 9.8cm;
      }
      @frame right_footer {
        -pdf-frame-content: rightFooterContent;
        margin-left: 1cm;
        margin-right: 1cm;
        height: 1cm;
        bottom: 1cm;
        left: 17cm;
      }
      margin-top: 1cm;
      margin-left: 1cm;
      margin-right: 1cm;
      margin-bottom: 2.5cm;
    }
    div.page {
      page-break-after: always;
    }
    div.footer {
      font-size: 8pt;
    }
    h1 {
      color: #00653a;
      background-color: #ccc;
      border-bottom: thick solid #00653a;
    }
    body {
      font-size: 10pt;
    }
    p {
      white-space:normal;
    }
  </style>
  </head>
  <body>
  <xsl:for-each select="csi:pages/csi:page">
    <div class='page'>
    <h1><xsl:value-of select="csi:pageHeader"/></h1>
    <p><xsl:call-template name="break"><xsl:with-param name="stringIn" select="csi:headerText"/></xsl:call-template></p>
    <xsl:if test="csi:sections/*">
      <xsl:for-each select="csi:sections/csi:section">
        <h3><xsl:value-of select="csi:sectionHeader"/></h3>
        <xsl:if test="csi:questions/*">
          <xsl:for-each select="csi:questions/csi:question | csi:questions/csi:questionGroup">
            <xsl:choose>
              <xsl:when test="./csi:question/*">
                <p><strong><xsl:value-of select="csi:groupQuestionNumber"/><xsl:text> </xsl:text><xsl:value-of select="csi:qText"/></strong></p>
                <xsl:for-each select="./csi:question">
                  <p><strong><xsl:value-of select="csi:questionNumber"/><xsl:text> </xsl:text><xsl:value-of select="csi:qText"/></strong></p>
                  <xsl:if test="./csi:responses/csi:response/*">
                    <p><xsl:value-of select="csi:responses/csi:response/csi:responseText"/></p><br/>
                    <xsl:if test="csi:responses/csi:additionalInfo">
                      Additional Information: <p><xsl:call-template name="break"><xsl:with-param name="stringIn" select="csi:responses/csi:additionalInfo"/></xsl:call-template></p><br/>
                    </xsl:if>
                  </xsl:if>
                  <xsl:if test="count(csi:responses/csi:response) = 0">
                    <i>No response</i><br/>
                  </xsl:if>
                </xsl:for-each>
              </xsl:when>
              <xsl:otherwise>
                <xsl:if test="csi:questionType != 'V'">
                  <p><strong><xsl:value-of select="csi:questionNumber"/><xsl:text> </xsl:text><xsl:value-of select="./csi:qText"/></strong></p>
                  <xsl:if test="./csi:responses/csi:response/*">
                    <p><xsl:value-of select="csi:responses/csi:response/csi:responseText"/></p><br/>
                    <xsl:if test="csi:responses/csi:additionalInfo">
                      Additional Information: <p><xsl:call-template name="break"><xsl:with-param name="stringIn" select="csi:responses/csi:additionalInfo"/></xsl:call-template></p><br/>
                    </xsl:if>
                  </xsl:if>
                  <xsl:if test="count(csi:responses/csi:response) = 0">
                    <i>No response</i><br/>
                  </xsl:if>
                </xsl:if>
                <xsl:if test="csi:questionType = 'V'">
                  <xsl:variable name="questionGUID" select="csi:questionGUID/text()"/>
                  <p><strong><xsl:value-of select="csi:questionNumber"/><xsl:text> </xsl:text><xsl:value-of select="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:qText"/></strong></p>
                  <xsl:if test="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:response/*">
                    <p><xsl:value-of select="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:response/csi:responseText"/></p><br/>
                    <xsl:if test="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:additionalInfo">
                      Additional Information: <p><xsl:call-template name="break"><xsl:with-param name="stringIn" select="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:additionalInfo"/></xsl:call-template></p><br/>
                    </xsl:if>
                  </xsl:if>
                  <xsl:if test="count(//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:response) = 0">
                    <i>No response</i><br/>
                  </xsl:if>
                </xsl:if>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:for-each>
        </xsl:if>
      </xsl:for-each>
    </xsl:if>
    <pre><xsl:value-of select="csi:footerText"/></pre><br/>
    </div>
  </xsl:for-each>
  <div class="footer" id="leftFooterContent" style="border-top: thin solid black;">
    CSI-SIG-DATE
  </div>
  <div class="footer" id="centerFooterContent1" style="text-align: center;">
    FOOTER1
  </div>
  <div class="footer" id="centerFooterContent2" style="text-align: center;">
    FOOTER2
  </div>
  <div class="footer" id="rightFooterContent" style="text-align: right">
    Page #<pdf:pagenumber/>
  </div>
  </body>
  </html>
</xsl:template>

<xsl:template name="break">
  <xsl:param name="stringIn"/>
  <xsl:choose>
    <xsl:when test="contains($stringIn, '&#xa;')">
       <xsl:value-of select="substring-before($stringIn, '&#xa;')"/>
       <br/>
       <xsl:call-template name="break">
         <xsl:with-param name="stringIn" select="substring-after($stringIn, '&#xa;')"/>
       </xsl:call-template>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$stringIn"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>
