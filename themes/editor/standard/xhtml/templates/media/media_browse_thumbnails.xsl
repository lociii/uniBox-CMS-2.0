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
		<xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='main']/path" />

        <xsl:if test="pagebrowser != ''">
            <h3 class="invisible"><xsl:value-of select="/root/translations/TRL_PAGEBROWSER" /></h3>
            <xsl:for-each select="pagebrowser">
                <xsl:call-template name="pagebrowser" />
            </xsl:for-each>
        </xsl:if>

		<xsl:variable name="container_count" select="count(container) + 4" />
		
		<xsl:for-each select="container">
			<div class="browser" title="{name}">
				<div class="box_white container_outer" onclick="location.href = this.getElementsByTagName('a')[0].href;">
	            	<div class="container_middle">
	            		<div class="container_inner" style="top: -16px; left: -16px;">
							<a href="media_editor_browse/category_id/{id}/">
								<xsl:choose>
									<xsl:when test="parent = 1">
										<img src="{$theme_base}/media/images/container_up.gif" border="0" />
									</xsl:when>
									<xsl:otherwise>
										<img src="{$theme_base}/media/images/container_down.gif" border="0" />
									</xsl:otherwise>
								</xsl:choose>
							</a>
						</div>
					</div>
				</div>
				<div class="description">
                    <xsl:value-of select="name" />
				</div>
			</div>
		</xsl:for-each>

		<xsl:for-each select="item">
			<div class="browser" title="{name}">
	            <div class="box_white container_outer" onclick="top.uniBoxMedia.set_media({id}, {width}, {height})">
	            	<div class="container_middle">
	            		<div class="container_inner" style="top: -{tn_height div 2}px; left: -{tn_width div 2}px;">
							<a href="JavaScript:;">
								<xsl:choose>
									<xsl:when test="../session_name and ../session_id">
										<img src="media.php5?media_id={id}&#38;width=100&#38;height=100&#38;{../session_name}_id={../session_id}" width="{tn_width}" height="{tn_height}" border="0"/>
									</xsl:when>
									<xsl:otherwise>
										<img src="media.php5?media_id={id}&#38;width=100&#38;height=100" width="{tn_width}" height="{tn_height}" border="0"/>
									</xsl:otherwise>
								</xsl:choose>
							</a>
						</div>
					</div>
                </div>
                <div class="description">
                    <xsl:value-of select="name" />
                </div> 
			</div>
		</xsl:for-each>
    </xsl:template>
    
</xsl:stylesheet>