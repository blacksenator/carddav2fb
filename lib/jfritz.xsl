<?xml version="1.0" encoding="utf-8"?>
<!-- Transform FRITZ!Box telephone book into jFritz phonebook -->
<!-- Copyright Volker Püschel -->
<!-- MIT Licence -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" indent="yes" encoding="utf-8" />

<xsl:template match="phonebooks/phonebook">
    <phonebook>
        <comment>Phonebook for JFritz v0.7.6</comment>
        <xsl:apply-templates select="contact" />
    </phonebook>
</xsl:template>

<xsl:template match="contact">
    <entry private="false">
        <xsl:apply-templates select="person/realName" />
        <phonenumbers standard="home">
            <xsl:apply-templates select="telephony/number" />
        </phonenumbers>
            <xsl:apply-templates select="services/email" />
    </entry>
</xsl:template>

<xsl:template match="person/realName">
    <xsl:variable name="fullname" select="." />
    <xsl:choose>
        <xsl:when test="contains($fullname,',')">
            <name>
            <firstname>
                <xsl:value-of select ="substring-after($fullname,', ')" />
            </firstname>
            <lastname>
                <xsl:value-of select ="substring-before($fullname,', ')" />
            </lastname>
            </name>
        </xsl:when>
        <xsl:otherwise>
            <company>
                <xsl:value-of select="$fullname" />
            </company>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>
        
<xsl:template match="telephony/number" >
    <number>
        <xsl:variable name="type" select="@type" />
        <xsl:choose>
            <xsl:when test="contains(.,'@')">
                <number_type>sip</number_type>
            </xsl:when>
            <xsl:when test="$type='work'">
                <xsl:attribute name="type">business</xsl:attribute>
            </xsl:when>
            <xsl:when test="$type='fax_work'">
                <xsl:attribute name="type">fax</xsl:attribute>
            </xsl:when>
            <xsl:otherwise>
                <xsl:attribute name="type">
                    <xsl:value-of select="$type"/>
                </xsl:attribute>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:value-of select="." />
    </number>
 </xsl:template>

<xsl:template match="services/email" >
    <internet>
         <email>
            <xsl:value-of select="." />
        </email>
    </internet>
 </xsl:template>

 </xsl:stylesheet>