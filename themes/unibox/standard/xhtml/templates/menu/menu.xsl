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
	<xsl:template match="content[@type='menu_process']">
		<xsl:apply-templates select="menu_item">
			<xsl:with-param name="nesting" select="0" />
			<xsl:with-param name="show_type" select="show_type" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="menu_item">
        <xsl:param name="nesting" />
        <xsl:param name="show_type" />
        <xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='main']/path" />
        <div>
            <xsl:choose>
                <xsl:when test="$nesting = 0">
                    <xsl:choose>
                        <xsl:when test="active = 1">
                            <xsl:attribute name="class">menu menu_active</xsl:attribute>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:attribute name="class">menu</xsl:attribute>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:choose>
                        <xsl:when test="active = 1">
                            <xsl:attribute name="class">submenu submenu_active</xsl:attribute>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:attribute name="class">submenu</xsl:attribute>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:otherwise>
            </xsl:choose>
            <div class="menu_spaced">
                <xsl:choose>
                    <xsl:when test="alias != ''">
                        <a>
                        	<xsl:choose>
                            	<xsl:when test="get/getvar">
	                            	<xsl:attribute name="href"><xsl:value-of select="alias" />/<xsl:for-each select="get/getvar"><xsl:value-of select="name"/>/<xsl:value-of select="value"/><xsl:if test="position() != last()">/</xsl:if></xsl:for-each></xsl:attribute>
    	                        </xsl:when>
    	                        <xsl:otherwise>
    	                        	<xsl:attribute name="href"><xsl:value-of select="alias" /></xsl:attribute>
    	                        </xsl:otherwise>
    	                    </xsl:choose>
                            <xsl:if test="item_descr != ''"><xsl:attribute name="title"><xsl:value-of select="item_descr" /></xsl:attribute></xsl:if>
                            <xsl:value-of select="item_name"/>
                        </a>
                    </xsl:when>
                    <xsl:when test="link != ''">
                        <a href="{link}" rel="external">
                            <xsl:if test="item_descr != ''"><xsl:attribute name="title"><xsl:value-of select="item_descr" /></xsl:attribute></xsl:if>
                            <xsl:value-of select="item_name"/>
                        </a>
                    </xsl:when>
                    <xsl:otherwise>
                    	<span>
                            <xsl:if test="item_descr != ''"><xsl:attribute name="title"><xsl:value-of select="item_descr" /></xsl:attribute></xsl:if>
                            <strong><xsl:value-of select="item_name"/></strong>
						</span>
                    </xsl:otherwise>
                </xsl:choose>
            </div>
        </div>

        <xsl:if test="menu_item and (active or child_active or $show_type = 0)">
            <xsl:apply-templates select="menu_item">
                <xsl:with-param name="nesting" select="$nesting + 1" />
                <xsl:with-param name="show_type" select="$show_type" />
            </xsl:apply-templates>
        </xsl:if>
	</xsl:template>
</xsl:stylesheet>