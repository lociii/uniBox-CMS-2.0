<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     19.05.2005  pr      shared dialog step handler
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template name="dialog_handler">
    	<table class="dialog_bar" border="0" cellpadding="0" cellspacing="0">
			<tr>
		    	<xsl:for-each select="/root/steps/step">
					<td>
                        <xsl:choose>
                            <xsl:when test="position() = last()"><xsl:attribute name="class"><xsl:value-of select="status" /> last</xsl:attribute></xsl:when>
                            <xsl:otherwise><xsl:attribute name="class"><xsl:value-of select="status" /></xsl:attribute></xsl:otherwise>
                        </xsl:choose>
						<xsl:choose>
							<xsl:when test="linked = 1">
								<a href="{/root/steps/link_url}/step/{number}/"><xsl:value-of select="number"/>. <xsl:value-of select="descr" /></a>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="number" />. <xsl:value-of select="descr" />
								<xsl:if test="status = 'disabled'">
									(<xsl:value-of select="/root/translations/TRL_DIALOG_STEP_DISABLED"/>)
								</xsl:if>
							</xsl:otherwise>
						</xsl:choose>
					</td>
				</xsl:for-each>
			</tr>
		</table>
    </xsl:template>

</xsl:stylesheet>