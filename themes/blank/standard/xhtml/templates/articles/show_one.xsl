<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     06.04.2005  jn      loginform testfile
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">

    <xsl:template name="content">
        <xsl:for-each select="message">
            <xsl:call-template name="ubc" />
        </xsl:for-each>
    </xsl:template>

</xsl:stylesheet>