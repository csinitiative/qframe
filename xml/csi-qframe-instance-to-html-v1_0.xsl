<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:csi="http://www.csinitiative.com/ns/csi-qframe"
elementFormDefault="qualified">
<xsl:output method="xml" indent="yes"/>

<xsl:template match="csi:questionnaire">
  <html>
  <head>
  <title><xsl:value-of select="@questionnaireName"/><xsl:text> </xsl:text><xsl:value-of select="@questionnaireVersion"/>:<xsl:text> </xsl:text><xsl:value-of select="@instanceName"/></title>
  <style type="text/css">
    div.page {
      page-break-after: always;
    }
    h1 {
      color: #00653a;
      background-color: #ccc;
      border-bottom: thick solid #00653a;
    }
    body {
      font-size: 10pt;
      margin: 1.25cm 1.25cm 2.50cm 1.25cm;
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
    <pre><xsl:value-of select="csi:headerText"/></pre>
    <xsl:if test="csi:sections/*">
      <xsl:for-each select="csi:sections/csi:section">
        <h3><xsl:value-of select="csi:sectionHeader"/></h3>
        <xsl:if test="csi:questions/*">
          <xsl:for-each select="csi:questions/csi:question | csi:questions/csi:questionGroup">
            <xsl:choose>
              <xsl:when test="./csi:question/*">
                <strong><xsl:value-of select="csi:groupQuestionNumber"/><xsl:text> </xsl:text><xsl:value-of select="csi:qText"/></strong><br/>
                <xsl:for-each select="./csi:question">
                  <strong><xsl:value-of select="csi:questionNumber"/><xsl:text> </xsl:text><xsl:value-of select="csi:qText"/></strong><br/>
                  <xsl:if test="./csi:responses/csi:response/*">
                    <xsl:value-of select="csi:responses/csi:response/csi:responseText"/><br/><br/>
                    <xsl:if test="csi:responses/csi:additionalInfo">
                      Additional Information: <p><xsl:call-template name="break"><xsl:with-param name="stringIn" select="csi:responses/csi:additionalInfo"/></xsl:call-template></p><br/><br/>
                    </xsl:if>
                  </xsl:if>
                  <xsl:if test="count(csi:responses/csi:response) = 0">
                    <i>No response</i><br/><br/>
                  </xsl:if>
                </xsl:for-each>
              </xsl:when>
              <xsl:otherwise>
                <xsl:if test="csi:questionType != 'V'">
                  <strong><xsl:value-of select="csi:questionNumber"/><xsl:text> </xsl:text><xsl:value-of select="./csi:qText"/></strong><br/>
                  <xsl:if test="./csi:responses/csi:response/*">
                    <xsl:value-of select="csi:responses/csi:response/csi:responseText"/><br/><br/>
                    <xsl:if test="csi:responses/csi:additionalInfo">
                      Additional Information: <p><xsl:call-template name="break"><xsl:with-param name="stringIn" select="csi:responses/csi:additionalInfo"/></xsl:call-template></p><br/><br/>
                    </xsl:if>
                  </xsl:if>
                  <xsl:if test="count(csi:responses/csi:response) = 0">
                    <i>No response</i><br/><br/>
                  </xsl:if>
                </xsl:if>
                <xsl:if test="csi:questionType = 'V'">
                  <xsl:variable name="questionGUID" select="csi:questionGUID/text()"/>
                  <strong><xsl:value-of select="csi:questionNumber"/><xsl:text> </xsl:text><xsl:value-of select="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:qText"/></strong><br/>
                  <xsl:if test="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:response/*">
                    <xsl:value-of select="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:response/csi:responseText"/><br/><br/>
                    <xsl:if test="//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:additionalInfo">
                      Additional Information: <p><xsl:call-template name="break"><xsl:with-param name="stringIn" select="csi:responses/csi:additionalInfo"/></xsl:call-template></p><br/><br/>
                    </xsl:if>
                  </xsl:if>
                  <xsl:if test="count(//csi:question[csi:questionGUID = $questionGUID and csi:questionType != 'V']/csi:responses/csi:response) = 0">
                    <i>No response</i><br/><br/>
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
