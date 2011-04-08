<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     06.04.2005  pr		shows media browser inside editor
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">

	<xsl:template name="content">
		<xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='media_browse']/path" />
		<xsl:if test="item != ''">
			<xsl:for-each select="item">
				<xsl:variable name="class">browser_item<xsl:if test="position() mod 5 = 0"> last</xsl:if></xsl:variable>
				
				<div class="{$class}">
					<xsl:choose>
						<xsl:when test="file_extension != ''">
                            <div class="container">
                                <div style="float: left;">
                                    <img src="media_preview/media_id/{id}" border="0" onclick="top.uniBoxMedia.set_media({id}, {width}, {height})"/>
                                </div>
                                <div style="text-align: right;">
                                    <img src="{$theme_base}/media/images/zoom.gif" border="0" width="16" height="16" onclick="top.uniBoxMedia.show_image({id}, {width}, {height})" />
                                </div>
                            </div>
                            <div class="description">
                                <xsl:value-of select="name" />
                            </div> 
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="parent = 1">
									<div class="container system"><a href="media_editor_browse/category_id/{id}/"><img src="{$theme_base}/media/images/folder_up.gif" border="0" /><br/></a></div>
								</xsl:when>
								<xsl:otherwise>
									<div class="container system"><a href="media_editor_browse/category_id/{id}/"><img src="{$theme_base}/media/images/folder_down.gif" border="0" /></a></div>
                                    <br/>
                                    <xsl:value-of select="name" />
								</xsl:otherwise>
							</xsl:choose>								
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</xsl:for-each>
		</xsl:if>
    </xsl:template>
    
</xsl:stylesheet>