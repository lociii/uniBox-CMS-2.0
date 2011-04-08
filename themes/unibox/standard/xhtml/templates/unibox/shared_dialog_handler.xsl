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
    <xsl:template match="dialog">
        <xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='shared_dialog_handler']/path" />
    	<h2 class="invisible"><xsl:value-of select="/root/translations/TRL_DIALOG_HANDLER" /></h2>
    	<div>
            <xsl:attribute name="class">dialog_bar <xsl:value-of select="step[position() = last()]/status" /></xsl:attribute>
			<ul>
		    	<xsl:for-each select="step">
					<li>
                        <xsl:choose>
                            <xsl:when test="position() = last()"><xsl:attribute name="class"><xsl:value-of select="status" /> last</xsl:attribute></xsl:when>
                            <xsl:otherwise><xsl:attribute name="class"><xsl:value-of select="status" /></xsl:attribute></xsl:otherwise>
                        </xsl:choose>
						<xsl:choose>
							<xsl:when test="linked = 1">
								<a href="{../link_url}/step/{number}/"><xsl:value-of select="position()"/>. <xsl:value-of select="descr" /></a>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="position()" />. <xsl:value-of select="descr" />
								<xsl:if test="status = 'disabled'">
									(<xsl:value-of select="/root/translations/TRL_DIALOG_STEP_DISABLED"/>)
								</xsl:if>
							</xsl:otherwise>
						</xsl:choose>
					</li>
				</xsl:for-each>
			</ul>
		</div>
    </xsl:template>

</xsl:stylesheet>