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
	<xsl:template match="/">
		<html>
		<head>
			<base href="{/root/unibox/html_base}" />
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

			<meta http-equiv="Content-Style-Type" content="text/css" />
			<xsl:for-each select="/root/unibox/styles/style">
				<link rel="stylesheet" type="text/css" href="{/root/unibox/html_base}{path}{filename}.css" />
			</xsl:for-each>
		</head>
		<body>
			<xsl:apply-templates select="/root/content/ubc" />
		</body>
		</html>
    </xsl:template>
</xsl:stylesheet>