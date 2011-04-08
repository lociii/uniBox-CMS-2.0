<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     31.03.2005  jn      1st release
### 0.11	06.04.2005	jn		changed way of loading css / images
###								added dynamic fontsize
###	0.12	13.05.2005	jn		use language-dependent strings, changed from h2 to h1
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">
	<xsl:output method="xml" encoding="UTF-8" indent="yes" omit-xml-declaration="yes" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"/>
	<xsl:template match="/">
		<xsl:variable name="html_base" select="/root/unibox/html_base" />
		<xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='main']/path" />
		<html lang="de" id="unibox_backend">
			<head>
				<base href="{/root/unibox/html_base}" />
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

				<script src="{/root/unibox/html_base}{/root/unibox/theme_base[@template='main']/path}/scripts/main.js" type="text/javascript"></script>

				<title>
					<xsl:value-of select="/root/unibox/page_name" /> -
					<xsl:value-of select="/root/translations/TRL_LOCATION" />:
					<xsl:for-each select="/root/location/component">
						<xsl:value-of select="value" />
						<xsl:if test="position() != last()"> &#187; </xsl:if>
					</xsl:for-each>
				</title>

				<link rel="icon" type="image/x-icon" href="{$html_base}{$theme_base}/media/icons/favicon.ico" />
				<link rel="shortcut icon" type="image/x-icon" href="{$html_base}{$theme_base}/media/icons/favicon.ico" />

				<meta http-equiv="Content-Style-Type" content="text/css" />
                <xsl:for-each select="/root/unibox/styles/style">
                    <link rel="stylesheet" type="text/css" media="{media}" href="{$html_base}{path}{filename}.css" />
                </xsl:for-each>

				<xsl:for-each select="/root/unibox/meta/entry">
					<meta name="{name}" content="{value}" /> 
				</xsl:for-each>
			</head>
			<body>
				<xsl:for-each select="/root/content">
					<xsl:if test="node() or string(.)">
						<xsl:call-template name="content" />
					</xsl:if>
				</xsl:for-each>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>