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
    <xsl:template match="messages/message">
        <xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='shared_message']/path"/>

        <div class="box_gradient box_gradient_large box_gradient_header box_gradient_header_image">
            <img class="corner_tl" alt="" src="{$theme_base}/media/images/corner_tl.gif" />
            <img class="corner_tr" id="main_corner_tr" alt="" src="{$theme_base}/media/images/corner_tr.gif" />

			<!-- show 'toggle instanthelp' if there is no content -->
            <xsl:if test="not(normalize-space(/root/content))">
	            <a href="JavaScript:;" onclick="JavaScript:toggle_instanthelp('{$theme_base}', '{/root/translations/TRL_INSTANTHELP_SHOW}', '{/root/translations/TRL_INSTANTHELP_VANISH}')"><img src="{$theme_base}/media/images/instanthelp_vanish.gif" alt="{/root/translations/TRL_INSTANTHELP_VANISH}" title="{/root/translations/TRL_INSTANTHELP_VANISH}" id="image_toggle_instanthelp" class="right_float" /></a>
			</xsl:if>

            <xsl:choose>
                <xsl:when test="type = 'ERROR'">
                    <img src="{$theme_base}/media/images/symbols/message/error.gif" alt="{message/title}" class="header message left_float" />
                </xsl:when>
                <xsl:when test="type = 'SUCCESS'">
                    <img src="{$theme_base}/media/images/symbols/message/success.gif" alt="{message/title}" class="header message left_float" />
                </xsl:when>
                <xsl:when test="type = 'QUESTION'">
                    <img src="{$theme_base}/media/images/symbols/message/question.gif" alt="{message/title}" class="header message left_float" />
                </xsl:when>
                <xsl:when test="type = 'WARNING'">
                    <img src="{$theme_base}/media/images/symbols/message/warning.gif" alt="{message/title}" class="header message left_float" />
                </xsl:when>
                <xsl:when test="type = 'NOTICE'">
                    <img src="{$theme_base}/media/images/symbols/message/notice.gif" alt="{message/title}" class="header message left_float" />
                </xsl:when>
                <xsl:when test="type = 'INFO'">
                    <img src="{$theme_base}/media/images/symbols/message/info.gif" alt="{message/title}" class="header message left_float"/>
                </xsl:when>
            </xsl:choose>
            <div class="header">
                <h2><xsl:value-of select="title" /></h2>
            </div>
        </div>

        <div class="box_blue box_content">
            <div class="message_body">
				<xsl:apply-templates select="ubc" />
            </div>
        </div>

        <div class="box_gradient box_gradient_large box_gradient_footer">
            <img class="corner_bl" alt="" src="{$theme_base}/media/images/corner_bl.gif" />
            <img class="corner_br" id="main_corner_br" alt="" src="{$theme_base}/media/images/corner_br.gif" />
        </div>
    </xsl:template>
</xsl:stylesheet>