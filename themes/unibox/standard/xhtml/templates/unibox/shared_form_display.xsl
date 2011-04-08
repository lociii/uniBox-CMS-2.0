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
    <xsl:template match="content">
    	<xsl:apply-templates select="form"/>
    </xsl:template>
</xsl:stylesheet>