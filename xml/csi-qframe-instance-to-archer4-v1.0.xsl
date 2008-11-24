<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:csi="http://www.csinitiative.com/ns/csi-qframe"
elementFormDefault="qualified">
<xsl:output method="xml" encoding="UTF-8" indent="yes"/>

<xsl:template match="csi:questionnaire">
  <ArcherRecords xmlns="http://www.archer-tech.com/">
  <xsl:for-each select="csi:pages/csi:page">
    <xsl:if test="csi:sections/*">
      <xsl:for-each select="csi:sections/csi:section">
        <xsl:if test="csi:questions/*">
          <xsl:for-each select="csi:questions/csi:question | csi:questions/csi:questionGroup">
            <xsl:choose>
              <xsl:when test="./csi:question/*">
                <xsl:for-each select="./csi:question">
                  <ArcherRecord>
                    <Field name="questionnaireName"><xsl:value-of select="/csi:questionnaire/@questionnaireName"/></Field>
                    <Field name="questionnaireVersion"><xsl:value-of select="/csi:questionnaire/@questionnaireVersion"/></Field>
                    <Field name="revision"><xsl:value-of select="/csi:questionnaire/@revision"/></Field>
                    <Field name="instanceName"><xsl:value-of select="/csi:questionnaire/@instanceName"/></Field>
                    <Field name="pageHeader"><xsl:value-of select="ancestor::csi:page[1]/csi:pageHeader"/></Field>
                    <Field name="pageGUID"><xsl:value-of select="ancestor::csi:page[1]/csi:pageGUID"/></Field>
                    <Field name="pageSeqNumber"><xsl:value-of select="ancestor::csi:page[1]/csi:seqNumber"/></Field>
                    <Field name="sectionHeader"><xsl:value-of select="ancestor::csi:section[1]/csi:sectionHeader"/></Field>
                    <Field name="sectionGUID"><xsl:value-of select="ancestor::csi:section[1]/csi:sectionGUID"/></Field>
                    <Field name="sectionSeqNumber"><xsl:value-of select="ancestor::csi:section[1]/csi:seqNumber"/></Field>
                    <Field name="questionGroupNumber"><xsl:value-of select="ancestor::csi:questionGroup[1]/csi:groupQuestionNumber"/></Field>
                    <Field name="questionGroupGUID"><xsl:value-of select="ancestor::csi:questionGroup[1]/csi:questionGUID"/></Field>
                    <Field name="questionGroupSeqNumber"><xsl:value-of select="ancestor::csi:questionGroup[1]/csi:seqNumber"/></Field>
                    <Field name="questionGroupQText"><xsl:value-of select="ancestor::csi:questionGroup[1]/csi:qText"/></Field>
                    <Field name="questionNumber"><xsl:value-of select="csi:questionNumber"/></Field>
                    <Field name="questionGUID"><xsl:value-of select="csi:questionGUID"/></Field>
                    <Field name="questionSeqNumber"><xsl:value-of select="csi:seqNumber"/></Field>
                    <Field name="qText"><xsl:value-of select="csi:qText"/></Field>
                    <Field name="questionType"><xsl:value-of select="csi:questionType"/></Field>
                    <xsl:for-each select="./csi:questionPrompt">
                      <Field name="promptText"><xsl:value-of select="csi:promptText"/></Field>
                    </xsl:for-each>
                    <xsl:for-each select="./csi:questionReferences/csi:reference">
                      <Field name="referenceShortName"><xsl:value-of select="csi:shortName"/></Field>
                      <Field name="referenceName"><xsl:value-of select="csi:referenceName"/></Field>
                      <Field name="referenceItem"><xsl:value-of select="csi:item"/></Field>
                      <Field name="referenceURL"><xsl:value-of select="csi:referenceURL"/></Field>
                      <Field name="referenceText"><xsl:value-of select="csi:referenceText"/></Field>
                    </xsl:for-each>
                    <xsl:for-each select="./csi:responses/csi:response">
                      <Field name="responseText"><xsl:value-of select="csi:responseText"/></Field>
                      <Field name="responseState"><xsl:value-of select="ancestor::csi:responses[1]/csi:state"/></Field>
                      <xsl:if test="ancestor::csi:responses[1]/csi:additionalInfo">
                        <Field name="additionalInfo"><xsl:value-of select="ancestor::csi:responses[1]/csi:additionalInfo"/></Field>
                      </xsl:if>
                    </xsl:for-each>
                  </ArcherRecord>
                </xsl:for-each>
              </xsl:when>
              <xsl:otherwise>
                <xsl:if test="csi:questionType != 'V'">
                  <ArcherRecord>
                    <Field name="questionnaireName"><xsl:value-of select="/csi:questionnaire/@questionnaireName"/></Field>
                    <Field name="questionnaireVersion"><xsl:value-of select="/csi:questionnaire/@questionnaireVersion"/></Field>
                    <Field name="revision"><xsl:value-of select="/csi:questionnaire/@revision"/></Field>
                    <Field name="instanceName"><xsl:value-of select="/csi:questionnaire/@instanceName"/></Field>
                    <Field name="pageHeader"><xsl:value-of select="ancestor::csi:page[1]/csi:pageHeader"/></Field>
                    <Field name="pageGUID"><xsl:value-of select="ancestor::csi:page[1]/csi:pageGUID"/></Field>
                    <Field name="pageSeqNumber"><xsl:value-of select="ancestor::csi:page[1]/csi:seqNumber"/></Field>
                    <Field name="sectionHeader"><xsl:value-of select="ancestor::csi:section[1]/csi:sectionHeader"/></Field>
                    <Field name="sectionGUID"><xsl:value-of select="ancestor::csi:section[1]/csi:sectionGUID"/></Field>
                    <Field name="sectionSeqNumber"><xsl:value-of select="ancestor::csi:section[1]/csi:seqNumber"/></Field>
                    <Field name="questionNumber"><xsl:value-of select="csi:questionNumber"/></Field>
                    <Field name="questionGUID"><xsl:value-of select="csi:questionGUID"/></Field>
                    <Field name="questionSeqNumber"><xsl:value-of select="csi:seqNumber"/></Field>
                    <Field name="qText"><xsl:value-of select="csi:qText"/></Field>
                    <Field name="questionType"><xsl:value-of select="csi:questionType"/></Field>
                    <xsl:for-each select="./csi:questionPrompt">
                      <Field name="promptText"><xsl:value-of select="csi:promptText"/></Field>
                    </xsl:for-each>
                    <xsl:for-each select="./csi:questionReferences/csi:reference">
                      <Field name="referenceShortName"><xsl:value-of select="csi:shortName"/></Field>
                      <Field name="referenceName"><xsl:value-of select="csi:referenceName"/></Field>
                      <Field name="referenceItem"><xsl:value-of select="csi:item"/></Field>
                      <Field name="referenceURL"><xsl:value-of select="csi:referenceURL"/></Field>
                      <Field name="referenceText"><xsl:value-of select="csi:referenceText"/></Field>
                    </xsl:for-each>
                    <xsl:for-each select="./csi:responses/csi:response">
                      <Field name="responseText"><xsl:value-of select="csi:responseText"/></Field>
                      <Field name="responseState"><xsl:value-of select="ancestor::csi:responses[1]/csi:state"/></Field>
                      <xsl:if test="ancestor::csi:responses[1]/csi:additionalInfo">
                        <Field name="additionalInfo"><xsl:value-of select="ancestor::csi:responses[1]/csi:additionalInfo"/></Field>
                      </xsl:if>
                    </xsl:for-each>
                  </ArcherRecord>
                </xsl:if>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:for-each>
        </xsl:if>
      </xsl:for-each>
    </xsl:if>
  </xsl:for-each>
  </ArcherRecords>
</xsl:template>
</xsl:stylesheet>
