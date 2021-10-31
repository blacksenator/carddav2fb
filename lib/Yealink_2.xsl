<?xml version="1.0" encoding="utf-8"?>
<!-- Transform FRITZ!Box telephone book into Yealink IP phonebook -->
<!-- Copyright Volker Püschel -->
<!-- MIT Licence -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" indent="yes" encoding="utf-8" />

<xsl:template match="phonebooks/phonebook">
    <xsl:variable name="Name" select="@name"/>
    <YealinkIPPhoneBook>
        <Title>Yealink</Title>
        <Menue>
            <xsl:attribute name="Name">
                <xsl:text>FRITZ!Box </xsl:text>
                <xsl:value-of select="$Name" />
            </xsl:attribute>
            <xsl:apply-templates select="contact" />
        </Menue>
    </YealinkIPPhoneBook>
</xsl:template>

<xsl:template match="contact">
    <Unit>
        <xsl:apply-templates select="telephony/number | person/realName" />
    </Unit>
</xsl:template>

<xsl:template match="person/realName">
    <xsl:attribute name="Name">
        <xsl:value-of select="." />
    </xsl:attribute>
</xsl:template>
        
<xsl:template match="telephony/number" >
    <!-- exclude fax numbers-->
    <xsl:if test="@type!='fax_work'">
        <xsl:variable name="type" select="@type"/>
        <xsl:choose>
            <xsl:when test="$type='work'">
                <xsl:attribute name="Phone1">
                    <xsl:value-of select="." />
                </xsl:attribute>
            </xsl:when>
            <xsl:when test="$type='mobile'">
                <xsl:attribute name="Phone2">
                    <xsl:value-of select="." />
                </xsl:attribute>
            </xsl:when>
            <xsl:otherwise>
                <xsl:attribute name="Phone3">
                    <xsl:value-of select="." />
                </xsl:attribute>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:if>
 </xsl:template>

</xsl:stylesheet>