<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
xmlns:xs="http://www.w3.org/2001/XMLSchema"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
xmlns:csi="http://www.csinitiative.com/ns/csi-qframe"
elementFormDefault="qualified">
<xsl:strip-space elements="*"/>
<xsl:output method="xml" indent="yes"/>

<xsl:template match="csi:questionnaire">
  <xs:schema 
  xmlns:csi="http://www.csinitiative.com/ns/csi-qframe"
  targetNamespace="http://www.csinitiative.com/ns/csi-qframe"
  elementFormDefault="qualified">

  <xs:element name="questionnaire">
    <xs:complexType>
      <xs:all>
        <xs:element name="pages">
          <xs:complexType>
            <xs:sequence>
<xsl:for-each select="csi:pages/csi:page">
              <xs:element name="page">
                <xs:complexType>
                  <xs:all>
                    <xs:element name="pageHeader" type="xs:string" minOccurs="0"/>
                    <xs:element name="pageGUID" type="xs:integer">
                      <xsl:attribute name="fixed">
                        <xsl:value-of select="csi:pageGUID"/>
                      </xsl:attribute>
                    </xs:element>
                    <xs:element name="seqNumber" type="xs:integer">
                      <xsl:attribute name="fixed">
                        <xsl:value-of select="csi:seqNumber"/>
                      </xsl:attribute>
                    </xs:element>
                    <xs:element name="description" type="xs:string" minOccurs="0"/>
                    <xs:element name="headerText" type="xs:string" minOccurs="0"/>
                    <xs:element name="footerText" type="xs:string" minOccurs="0"/>
                    <xs:element name="cloneable" type="xs:integer" minOccurs="0"/>
                    <xs:element name="defaultPageHidden" type="xs:integer" minOccurs="0"/>
                    <xs:element name="pageReferences" minOccurs="0">
                      <xs:complexType>
                        <xs:sequence>
                          <xs:element name="reference" minOccurs="0" maxOccurs="unbounded">
                            <xs:complexType>
                              <xs:all>
                                <xs:element name="shortName" type="xs:string" minOccurs="0"/>
                                <xs:element name="referenceName" type="xs:string" minOccurs="0"/>
                                <xs:element name="item" type="xs:string" minOccurs="0"/>
                                <xs:element name="referenceURL" type="xs:string" minOccurs="0"/>
                                <xs:element name="referenceText" type="xs:string" minOccurs="0"/>
                              </xs:all>
                            </xs:complexType>
                          </xs:element>
                        </xs:sequence>
                      </xs:complexType>
                    </xs:element>
                    <xs:element name="sections">
                      <xs:complexType>
                        <xs:sequence>
                          <xsl:if test="csi:sections/*">
  <xsl:for-each select="csi:sections/csi:section">
                            <xs:element name="section">
                              <xs:complexType>
                                <xs:all>
                                  <xs:element name="sectionHeader" type="xs:string" minOccurs="0"/>
                                  <xs:element name="sectionGUID" type="xs:integer">
                                    <xsl:attribute name="fixed">
                                      <xsl:value-of select="csi:sectionGUID"/>
                                    </xsl:attribute>
                                  </xs:element>
                                  <xs:element name="seqNumber" type="xs:integer">
                                    <xsl:attribute name="fixed">
                                      <xsl:value-of select="csi:seqNumber"/>
                                    </xsl:attribute>
                                  </xs:element>
                                  <xs:element name="description" type="xs:string" minOccurs="0"/>
                                  <xs:element name="cloneable" type="xs:integer" minOccurs="0"/>
                                  <xs:element name="defaultSectionHidden" type="xs:integer" minOccurs="0"/>
                                  <xs:element name="sectionReferences" minOccurs="0">
                                    <xs:complexType>
                                      <xs:sequence>
                                        <xs:element name="reference" minOccurs="0" maxOccurs="unbounded">
                                          <xs:complexType>
                                            <xs:all>
                                              <xs:element name="shortName" type="xs:string" minOccurs="0"/>
                                              <xs:element name="referenceName" type="xs:string" minOccurs="0"/>
                                              <xs:element name="item" type="xs:string" minOccurs="0"/>
                                              <xs:element name="referenceURL" type="xs:string" minOccurs="0"/>
                                              <xs:element name="referenceText" type="xs:string" minOccurs="0"/>
                                            </xs:all>
                                          </xs:complexType>
                                        </xs:element>
                                      </xs:sequence>
                                    </xs:complexType>
                                  </xs:element>
                                  <xsl:if test="csi:questions/*">
                                    <xs:element name="questions" minOccurs="1">
                                      <xs:complexType>
                                        <xs:sequence>
    <xsl:for-each select="csi:questions/csi:question | csi:questions/csi:questionGroup">
                                          <xsl:choose>
                                            <xsl:when test="./csi:question/*">
                                              <xs:element name="questionGroup">
                                                <xs:complexType>
                                                  <xs:sequence>
                                                    <xs:element name="qText" type="xs:string" minOccurs="0"/>
                                                    <xs:element name="questionGUID" type="xs:integer">
                                                      <xsl:attribute name="fixed">
                                                        <xsl:value-of select="csi:questionGUID"/>
                                                      </xsl:attribute>
                                                    </xs:element>
                                                    <xs:element name="seqNumber" type="xs:integer">
                                                      <xsl:attribute name="fixed">
                                                        <xsl:value-of select="csi:seqNumber"/>
                                                      </xsl:attribute>
                                                    </xs:element>
                                                    <xs:element name="groupQuestionNumber" type="xs:string" minOccurs="0"/>
                                                    <xs:element name="cloneable" type="xs:integer" minOccurs="0"/>
                                                    <xs:element name="groupDefaultQuestionHidden" type="xs:integer" minOccurs="0"/>
                                                    <xs:element name="groupQuestionReferences" minOccurs="0">
                                                      <xs:complexType>
                                                        <xs:sequence>
                                                          <xs:element name="reference" minOccurs="0" maxOccurs="unbounded">
                                                            <xs:complexType>
                                                              <xs:all>
                                                                <xs:element name="shortName" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="referenceName" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="item" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="referenceURL" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="referenceText" type="xs:string" minOccurs="0"/>
                                                              </xs:all>
                                                            </xs:complexType>
                                                          </xs:element>
                                                        </xs:sequence>
                                                      </xs:complexType>
                                                    </xs:element>
                                                    <xs:element name="attachments" minOccurs="0">
                                                      <xs:complexType>
                                                        <xs:sequence>
                                                          <xs:element name="attachment" minOccurs="0" maxOccurs="unbounded">
                                                            <xs:complexType>
                                                              <xs:all>
                                                                <xs:element name="filename" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="mime" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="location" type="xs:string" minOccurs="0"/>
                                                              </xs:all>
                                                            </xs:complexType>
                                                          </xs:element>
                                                        </xs:sequence>
                                                      </xs:complexType>
                                                    </xs:element>
      <xsl:for-each select="./csi:question">
                                                    <xs:element name="question">
                                                      <xs:complexType>
                                                        <xs:sequence>
                                                          <xs:element name="qText" type="xs:string" minOccurs="0"/>
                                                          <xs:element name="questionGUID" type="xs:integer">
                                                            <xsl:attribute name="fixed">
                                                              <xsl:value-of select="csi:questionGUID"/>
                                                            </xsl:attribute>
                                                          </xs:element>
                                                          <xs:element name="seqNumber" type="xs:integer">
                                                            <xsl:attribute name="fixed">
                                                              <xsl:value-of select="csi:seqNumber"/>
                                                            </xsl:attribute>
                                                          </xs:element>
                                                          <xs:element name="questionNumber" type="xs:string" minOccurs="0"/>
                                                          <xs:element name="defaultQuestionHidden" type="xs:integer" minOccurs="0"/>
                                                          <xs:element name="questionReferences" minOccurs="0">
                                                            <xs:complexType>
                                                              <xs:sequence>
                                                                <xs:element name="reference" minOccurs="0" maxOccurs="unbounded">
                                                                  <xs:complexType>
                                                                    <xs:all>
                                                                      <xs:element name="shortName" type="xs:string" minOccurs="0"/>
                                                                      <xs:element name="referenceName" type="xs:string" minOccurs="0"/>
                                                                      <xs:element name="item" type="xs:string" minOccurs="0"/>
                                                                      <xs:element name="referenceURL" type="xs:string" minOccurs="0"/>
                                                                      <xs:element name="referenceText" type="xs:string" minOccurs="0"/>
                                                                    </xs:all>
                                                                  </xs:complexType>
                                                                </xs:element>
                                                              </xs:sequence>
                                                            </xs:complexType>
                                                          </xs:element>
                                                          <xs:element name="questionType" type="xs:string" minOccurs="0"/>
                                                          <xs:element name="questionPrompt" minOccurs="0" maxOccurs="unbounded">
                                                            <xs:complexType>
                                                              <xs:sequence>
                                                                <xs:element name="promptText" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="requireAdditionalInfo" type="xs:integer" minOccurs="0"/>
                                                                <xs:element name="enablePage" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                                <xs:element name="enableSection" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                                <xs:element name="enableQuestion" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                                <xs:element name="disablePage" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                                <xs:element name="disableSection" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                                <xs:element name="disableQuestion" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                              </xs:sequence>
                                                            </xs:complexType>
                                                          </xs:element>
                                                          <xs:element name="responses" minOccurs="0">
                                                            <xs:complexType>
                                                              <xs:sequence>
                                                                <xs:element name="state" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="additionalInfo" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="approverComments" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="response" minOccurs="0" maxOccurs="unbounded">
                                                                  <xs:complexType>
                                                                    <xs:all>
                                                                      <xs:element name="responseDate" type="xs:dateTime"/>
                                                                      <xs:element name="responseText" type="xs:string"/>
                                                                    </xs:all>
                                                                  </xs:complexType>
                                                                </xs:element>
                                                              </xs:sequence>
                                                            </xs:complexType>
                                                          </xs:element>
                                                        </xs:sequence>
                                                      </xs:complexType>
                                                    </xs:element>
      </xsl:for-each>
                                                  </xs:sequence>
                                                </xs:complexType>
                                              </xs:element>
                                            </xsl:when>
                                            <xsl:otherwise>
                                              <xs:element name="question">
                                                <xs:complexType>
                                                  <xs:sequence>
                                                    <xs:element name="qText" type="xs:string" minOccurs="0"/>
                                                    <xs:element name="questionGUID" type="xs:integer">
                                                      <xsl:attribute name="fixed">
                                                        <xsl:value-of select="csi:questionGUID"/>
                                                      </xsl:attribute>
                                                    </xs:element>
                                                    <xs:element name="seqNumber" type="xs:integer" minOccurs="0">
                                                      <xsl:attribute name="fixed">
                                                        <xsl:value-of select="csi:seqNumber"/>
                                                      </xsl:attribute>
                                                    </xs:element>
                                                    <xs:element name="questionNumber" type="xs:string" minOccurs="0"/>
                                                    <xs:element name="cloneable" type="xs:integer" minOccurs="0"/>
                                                    <xs:element name="questionReferences" minOccurs="0">
                                                      <xs:complexType>
                                                        <xs:sequence>
                                                          <xs:element name="reference" minOccurs="0" maxOccurs="unbounded">
                                                            <xs:complexType>
                                                              <xs:all>
                                                                <xs:element name="shortName" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="referenceName" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="item" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="referenceURL" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="referenceText" type="xs:string" minOccurs="0"/>
                                                              </xs:all>
                                                            </xs:complexType>
                                                          </xs:element>
                                                        </xs:sequence>
                                                      </xs:complexType>
                                                    </xs:element>
                                                    <xs:element name="questionType" type="xs:string" minOccurs="0"/>
                                                    <xs:element name="questionPrompt" minOccurs="0" maxOccurs="unbounded">
                                                      <xs:complexType>
                                                        <xs:sequence>
                                                          <xs:element name="promptText" type="xs:string" minOccurs="0"/>
                                                          <xs:element name="requireAdditionalInfo" type="xs:integer" minOccurs="0"/>
                                                          <xs:element name="enablePage" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                          <xs:element name="enableSection" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                          <xs:element name="enableQuestion" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                          <xs:element name="disablePage" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                          <xs:element name="disableSection" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                          <xs:element name="disableQuestion" type="xs:integer" minOccurs="0" maxOccurs="unbounded"/>
                                                        </xs:sequence>
                                                      </xs:complexType>
                                                    </xs:element>
                                                    <xs:element name="responses" minOccurs="0">
                                                      <xs:complexType>
                                                        <xs:sequence>
                                                          <xs:element name="state" type="xs:string" minOccurs="0"/>
                                                          <xs:element name="additionalInfo" type="xs:string" minOccurs="0"/>
                                                          <xs:element name="approverComments" type="xs:string" minOccurs="0"/>
                                                          <xs:element name="response" minOccurs="0" maxOccurs="unbounded">
                                                            <xs:complexType>
                                                              <xs:all>
                                                                <xs:element name="responseDate" type="xs:dateTime"/>
                                                                <xs:element name="responseText" type="xs:string"/>
                                                              </xs:all>
                                                            </xs:complexType>
                                                          </xs:element>
                                                        </xs:sequence>
                                                      </xs:complexType>
                                                    </xs:element>
                                                    <xs:element name="attachments" minOccurs="0">
                                                      <xs:complexType>
                                                        <xs:sequence>
                                                          <xs:element name="attachment" minOccurs="0" maxOccurs="unbounded">
                                                            <xs:complexType>
                                                              <xs:all>
                                                                <xs:element name="filename" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="mime" type="xs:string" minOccurs="0"/>
                                                                <xs:element name="location" type="xs:string" minOccurs="0"/>
                                                              </xs:all>
                                                            </xs:complexType>
                                                          </xs:element>
                                                        </xs:sequence>
                                                      </xs:complexType>
                                                    </xs:element>
                                                  </xs:sequence>
                                                </xs:complexType>
                                              </xs:element>
                                            </xsl:otherwise>
                                          </xsl:choose>
    </xsl:for-each>
                                        </xs:sequence>
                                      </xs:complexType>
                                    </xs:element>
                                  </xsl:if>
                                </xs:all>
                              </xs:complexType>
                            </xs:element>
  </xsl:for-each>
                          </xsl:if>
                        </xs:sequence>
                      </xs:complexType>
                    </xs:element>
                  </xs:all>
                </xs:complexType>
              </xs:element>
</xsl:for-each>
            </xs:sequence>
          </xs:complexType>
        </xs:element>
      </xs:all>
      <xs:attribute name="questionnaireName" type="xs:string" use="required">
        <xsl:attribute name="fixed">
          <xsl:value-of select="@questionnaireName"/>
        </xsl:attribute>
      </xs:attribute>
      <xs:attribute name="questionnaireVersion" type="xs:string" use="required">
        <xsl:attribute name="fixed">
          <xsl:value-of select="@questionnaireVersion"/>
        </xsl:attribute>
      </xs:attribute>
      <xs:attribute name="revision" type="xs:integer" use="required">
        <xsl:attribute name="fixed">
          <xsl:value-of select="@revision"/>
        </xsl:attribute>
      </xs:attribute>
      <xs:attribute name="targetQFrameVersion" type="xs:string" use="required" fixed="1.0"/>
      <xs:attribute name="instanceName" type="xs:string" use="required"/>
    </xs:complexType>
  </xs:element>
  </xs:schema>
</xsl:template>

</xsl:stylesheet>
