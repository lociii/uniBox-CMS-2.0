<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl" xmlns:ubc="http://www.media-soma.de/ubc">
    <xsl:template match="content[@type='actionbar_show']">
		<xsl:variable name="theme_base_main" select="/root/unibox/theme_base[@template='main']/path" />

		<a name="unibox_navigation" />
		<xsl:for-each select="menu">
			<xsl:if test="module">
				<div class="box_gradient box_gradient_small box_gradient_header">
					<img class="corner_tl" alt="" src="{$theme_base_main}/media/images/corner_tl.gif" />
					<img class="corner_tr" alt="" src="{$theme_base_main}/media/images/corner_tr.gif" />
					<h1><xsl:value-of select="@name" /></h1>
				</div>
				
				<div class="box_blue box_actionbar">
					<ul class="actionbar_modules">
						<xsl:apply-templates select="module" />
					</ul>
				</div>
	
				<div class="box_gradient box_gradient_small box_gradient_footer">
					<img class="corner_bl" alt="" src="{$theme_base_main}/media/images/corner_bl.gif" />
					<img class="corner_br" alt="" src="{$theme_base_main}/media/images/corner_br.gif" />
				</div>
			</xsl:if>
		</xsl:for-each>
    </xsl:template>

    <xsl:template match="content[@type='actionbar_show']/menu/module">
        <li>
            <xsl:choose>
                <xsl:when test="position() mod 2 = 1"><xsl:attribute name="class">highlight_dark</xsl:attribute></xsl:when>
                <xsl:otherwise><xsl:attribute name="class">highlight_light</xsl:attribute></xsl:otherwise>
            </xsl:choose>
            <xsl:if test="position() = 1"><xsl:attribute name="style">padding-top: 0px;</xsl:attribute></xsl:if>
            <xsl:if test="child_active">
                <span class="invisible"><xsl:value-of select="/root/translations/TRL_SELECTED" />: </span>
            </xsl:if>
            <xsl:if test="active">
                <span class="invisible"><xsl:value-of select="/root/translations/TRL_ACTIVE" />: </span>
            </xsl:if>
            <a href="{module_ident}_welcome"><span class="invisible"><xsl:value-of select="position()" />:&#160;</span><xsl:value-of select="module_name" /></a>
            <xsl:if test="menu_item">
            	<ul class="actionbar_items">
	                <xsl:apply-templates select="menu_item">
	                	<xsl:with-param name="position" select="position()" />
	                </xsl:apply-templates>
				</ul>
            </xsl:if>
        </li>
    </xsl:template>

	<xsl:template match="content[@type='actionbar_show']/menu/module//menu_item">
		<xsl:param name="position" />
        <li>
            <xsl:if test="child_active">
                <span class="invisible"><xsl:value-of select="/root/translations/TRL_SELECTED" />: </span>
            </xsl:if>
            <xsl:if test="active">
                <span class="invisible"><xsl:value-of select="/root/translations/TRL_ACTIVE" />: </span>
            </xsl:if>
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
                        <span class="invisible"><xsl:value-of select="$position" />.<xsl:value-of select="position()" />:&#160;</span><xsl:value-of select="item_name"/>
                    </a>
                </xsl:when>
                <xsl:when test="link != ''">
                    <a href="{link}" rel="external">
                        <xsl:if test="item_descr != ''"><xsl:attribute name="title"><xsl:value-of select="item_descr" /></xsl:attribute></xsl:if>
                        <span class="invisible"><xsl:value-of select="$position" />.<xsl:value-of select="position()" />:&#160;</span><xsl:value-of select="item_name"/>
                    </a>
                </xsl:when>
                <xsl:when test="menu_item">
					<xsl:if test="item_descr != ''"><xsl:attribute name="title"><xsl:value-of select="item_descr" /></xsl:attribute></xsl:if>
					<span class="invisible"><xsl:value-of select="$position" />.<xsl:value-of select="position()" />:&#160;</span><strong><xsl:value-of select="item_name"/></strong>
                </xsl:when>
            </xsl:choose>
            <xsl:if test="menu_item">
            	<ul class="actionbar_items">
		            <xsl:apply-templates select="menu_item">
		            	<xsl:with-param name="position" select="concat($position, '.', position())" />
		            </xsl:apply-templates>
				</ul>
			</xsl:if>
        </li>
	</xsl:template>
</xsl:stylesheet>