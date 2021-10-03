<?xml version="1.0" encoding="utf-8"?>
<!-- Transform FRITZ!Box telephone book into grandstream IP phone phonebook -->
<!-- Copyright Volker Püschel -->
<!-- MIT Licence -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" indent="yes" encoding="utf-8" />

<xsl:template match="phonebooks/phonebook">
    <AdressBook>
        <version>1</version>
        <xsl:apply-templates select="contact" />
    </AdressBook>
</xsl:template>

<xsl:template match="contact">
    <Contact>
        <xsl:apply-templates select="person/realName" />
        <xsl:apply-templates select="telephony/number" />
    </Contact>
</xsl:template>

<xsl:template match="person/realName">
    <xsl:variable name="fullname" select="." />
    <xsl:choose>
        <xsl:when test="contains($fullname,',')">
            <LastName>
                <xsl:value-of select ="substring-before($fullname,', ')" />
            </LastName>
            <FirstName>
                <xsl:value-of select ="substring-after($fullname,', ')" />
            </FirstName>
        </xsl:when>
        <xsl:otherwise>
            <LastName>
                <xsl:value-of select="$fullname" />
            </LastName>
            <FirstName/>
        </xsl:otherwise>
    </xsl:choose>
</xsl:template>

<xsl:template match="telephony/number" >
    <!-- exclude fax numbers-->
    <xsl:if test="@type!='fax_work'">
        <xsl:variable name="type" select="@type"/>
        <Phone>
            <xsl:choose>
                <xsl:when test="$type='work'">
                    <xsl:attribute name="type">Work</xsl:attribute>
                </xsl:when>
                <xsl:when test="$type='home'">
                    <xsl:attribute name="type">Home</xsl:attribute>
                </xsl:when>
                <xsl:when test="$type='mobile'">
                    <xsl:attribute name="type">Mobile</xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="type">Others</xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <phonenumber>
                <xsl:value-of select="." />
            </phonenumber>
            <accountindex>1</accountindex>
        </Phone>
    </xsl:if>
</xsl:template>

</xsl:stylesheet>
