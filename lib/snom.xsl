<?xml version="1.0" encoding="utf-8"?>
<!-- Transform FRITZ!Box telephone book into snom IP phone phonebook -->
<!-- Copyright Volker Püschel -->
<!-- MIT Licence -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" indent="yes" encoding="utf-8" />

<xsl:template match="phonebooks/phonebook">
    <tbook complete="true">
        <xsl:apply-templates select="contact" />
    </tbook>
</xsl:template>

<xsl:template match="contact">
    <!-- get first phone number of contact as link of items-->
    <xsl:call-template name="connection">
        <xsl:with-param name="number" select="telephony/number" />
    </xsl:call-template>
    <xsl:call-template name="phonenumber">
        <xsl:with-param name="number" select="telephony/number" />
    </xsl:call-template>
</xsl:template>

<xsl:template name="connection">
    <xsl:param name="number" />
    <!-- regardless of the number of phone numbers, the 1: n version is always used: "MASTER"-->
    <item context="" type="MASTER" fav="false" mod="" index="">
        <xsl:if test="category=1">
            <xsl:attribute name="fav">true</xsl:attribute>
        </xsl:if>
        <!-- number is mandatory! Here link to associated items with numbers -->
        <number>
            <xsl:value-of select="$number" />
        </number>
        <xsl:variable name="fullname" select="person/realName" />
        <xsl:choose>
            <xsl:when test="contains($fullname,',')">
                <first_name>
                    <xsl:value-of select ="substring-after($fullname,', ')" />
                </first_name>
                <last_name>
                    <xsl:value-of select ="substring-before($fullname,', ')" />
                </last_name>
            </xsl:when>
            <xsl:otherwise>
                <organisation>
                    <xsl:value-of select="$fullname" />
                </organisation>
            </xsl:otherwise>
        </xsl:choose>
    </item>
</xsl:template>

<xsl:template name="phonenumber" >
    <xsl:param name="number" />
    <xsl:variable name="vip" select="category" />
    <xsl:for-each select="telephony/number">
        <xsl:variable name="type" select="@type" />
        <!-- exclude fax numbers-->
        <xsl:if test="$type!='fax_work'">
            <item context="" type="" fav="false" mod="" index="">
                <xsl:if test="$vip=1">
                    <xsl:attribute name="type">vip</xsl:attribute>
                    <xsl:attribute name="fav">true</xsl:attribute>
                </xsl:if>
                <number>
                    <xsl:value-of select="." />
                </number>
                <xsl:choose>
                    <xsl:when test="contains(.,'@')">
                        <number_type>sip</number_type>
                    </xsl:when>
                    <xsl:when test="$type='work'">
                        <number_type>business</number_type>
                    </xsl:when>
                    <xsl:when test="$type='other'">
                        <number_type>business</number_type>
                    </xsl:when>
                    <xsl:otherwise>
                        <number_type>
                            <xsl:value-of select="$type"/>
                        </number_type>
                    </xsl:otherwise>
                </xsl:choose>
                <last_name>
                    <!-- link to MASTER:<number> -->
                    <xsl:value-of select="$number" />
                </last_name>
            </item>
        </xsl:if>
    </xsl:for-each>
</xsl:template>

</xsl:stylesheet>