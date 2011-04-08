<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
	<xsl:output method="xml" encoding="UTF-8" indent="yes" omit-xml-declaration="yes" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"/>
	<xsl:template match="content[@type='articles_show_category']">
		<xsl:if test="category">
            <xsl:for-each select="category">
                <a href="{alias}/category_id/{id}"><xsl:value-of select="name" /></a><br /><br />
            </xsl:for-each>
            <hr />
        </xsl:if>

        <xsl:if test="article">
            <xsl:for-each select="article">
                <a href="{alias}/article_id/{id}"><xsl:value-of select="title" /></a><br /><br />
            </xsl:for-each>
        </xsl:if>
        
        <xsl:apply-templates select="pagebrowser" />
	</xsl:template>

</xsl:stylesheet>