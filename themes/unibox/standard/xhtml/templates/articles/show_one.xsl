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

	<xsl:template match="content[@type='articles_show']">
        <h2 class="article_title"><xsl:value-of select="title" /></h2>

		<div class="article_content">
			<xsl:apply-templates select="message" />
		</div>

		<xsl:if test="author">
			<div class="article_author">
				<xsl:value-of select="author" />
			</div>
		</xsl:if>

		<xsl:if test="time">
			<div class="article_time">
				<xsl:value-of select="time/datetime" />
			</div>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>