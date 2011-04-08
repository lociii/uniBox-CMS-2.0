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
	<xsl:output method="xml" encoding="UTF-8" omit-xml-declaration="no" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"/>
	<xsl:template match="/">
		<xsl:variable name="html_base" select="/root/unibox/html_base" />
		<xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='main']/path" />
		<html lang="de" id="unibox_backend">
            <head>
                <base href="{$html_base}" />
                <script src="{$html_base}{$theme_base}/scripts/main.js" type="text/javascript"></script>
                <title>
                    <xsl:value-of select="/root/translations/TRL_UNIBOX_2_0" /> - 
                    <xsl:value-of select="/root/unibox/page_name" /> - 
                    <xsl:value-of select="/root/translations/TRL_LOCATION" />:
                    <xsl:for-each select="/root/location/component">
                        <xsl:if test="position() != 1"> &#187; </xsl:if>
                        <xsl:value-of select="value" />
                    </xsl:for-each>
                    <xsl:for-each select="/root/location/component_step">
                        &#187; <xsl:value-of select="step_no"/>. <xsl:value-of select="step_descr"/>
                    </xsl:for-each>
                </title>
                <link rel="icon" type="image/x-icon" href="{$html_base}{$theme_base}/media/icons/favicon.ico" />
                <link rel="shortcut icon" type="image/x-icon" href="{$html_base}{$theme_base}/media/icons/favicon.ico" />
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
                <xsl:for-each select="/root/unibox/styles/style">
                    <link rel="stylesheet" type="text/css" media="{media}" href="{$html_base}{path}{filename}.css" />
                </xsl:for-each>
                <meta http-equiv="Content-Style-Type" content="text/css" />
                <xsl:for-each select="/root/unibox/meta/entry">
                    <meta name="{name}" content="{value}" /> 
                </xsl:for-each>
			</head>
			<body id="body_editor">
				<xsl:if test="/root/steps != ''">
					<div id="page_dialog">
						<xsl:call-template name="dialog_handler" />
					</div>
				</xsl:if>

                <xsl:for-each select="/root/content">
					<xsl:if test="node() or string(.)">
					    <xsl:call-template name="content" />
					</xsl:if>
                </xsl:for-each>				
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>