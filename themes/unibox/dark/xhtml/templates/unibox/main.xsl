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
					<xsl:variable name="path" select="path" />
					<xsl:variable name="filename" select="filename" />
					<xsl:variable name="media" select="media" />
					<link rel="stylesheet" type="text/css" media="{$media}" href="{$html_base}{$path}{$filename}.css" />
				</xsl:for-each>

				<xsl:for-each select="/root/unibox/meta/entry">
					<meta name="{name}" content="{value}" /> 
				</xsl:for-each>

                <xsl:if test="/root/unibox/font_size != 0">
                    <style type="text/css">
                        <xsl:comment>
                            body
                            {
                                font-size: <xsl:value-of select="/root/unibox/font_size" />%;
                            }
                        </xsl:comment>
                    </style>
                </xsl:if>
			</head>
			
			<body onload="JavaScript: external_links(); toggle_instanthelp('{$theme_base}', '{/root/translations/TRL_INSTANTHELP_SHOW}', '{/root/translations/TRL_INSTANTHELP_VANISH}'); toggle_color_picker('{$theme_base}', '{/root/translations/TRL_COLOR_PICKER_SHOW}', '{/root/translations/TRL_COLOR_PICKER_VANISH}');">
				<div id="page_header" class="box_gradient box_gradient_large">
					<img class="corner_tl" alt="" src="{$theme_base}/media/images/corner_tl.gif" />
					<img class="corner_tr" alt="" src="{$theme_base}/media/images/corner_tr.gif" />
					<img class="corner_bl" alt="" src="{$theme_base}/media/images/corner_bl.gif" />
					<img class="corner_br" alt="" src="{$theme_base}/media/images/corner_br.gif" />
					
				    <div class="logo"><img id="logo_unibox" src="{$theme_base}/media/images/logo_unibox.gif" alt="{/root/translations/TRL_DESCR_UNIBOX}" title="{/root/translations/TRL_DESCR_UNIBOX}" /></div>
                    <div class="anchors">
                        <div class="left_float anchors_links">
                            <h1><xsl:value-of select="/root/translations/TRL_JUMP_TO" />:</h1>
                            &#160;
                            <a href="{/root/unibox/query_string}#unibox_styles"><xsl:value-of select="/root/translations/TRL_STYLEMENU" /></a>
                            |
                            <a href="{/root/unibox/query_string}#unibox_navigation"><xsl:value-of select="/root/translations/TRL_NAVIGATION" /></a>
                            |
                            <a href="{/root/unibox/query_string}#unibox_instanthelp"><xsl:value-of select="/root/translations/TRL_INSTANTHELP" /></a>
                            |
                            <a href="{/root/unibox/query_string}#unibox_content"><xsl:value-of select="/root/translations/TRL_CONTENT" /></a>
                        </div>
                        <xsl:if test="/root/unibox/user_id != 1">
                            <div class="right_align logout">
								<span>
	                                <a href="unibox_logout"><img alt="{/root/translations/TRL_LOGOUT}" title="{/root/translations/TRL_LOGOUT}" src="{$theme_base}/media/images/logout.gif" /></a>
								</span>
                            </div>
                        </xsl:if>
                    </div>
				</div>

				<div id="page_infobar">
					<div class="box_gradient box_gradient_large box_gradient_header">
						<img class="corner_tl" alt="" src="{$theme_base}/media/images/corner_tl.gif" />
						<img class="corner_tr" alt="" src="{$theme_base}/media/images/corner_tr.gif" />
						<div class="left_float">
							<h1 class="invisible"><xsl:value-of select="/root/translations/TRL_TEMPLATE_PAGE_TO_ADMINISTRATE" /></h1><strong><xsl:value-of select="/root/unibox/user_name" /> @ </strong><a href="{/root/unibox/html_base}"><xsl:value-of select="/root/unibox/page_name" /></a>
						</div>
						<div  class="right_align">
							<strong><xsl:value-of select="/root/unibox/datetime" /></strong>
						</div>
					</div>
					
					<div class="box_blue">
                        <div class="left_float">
    						<h1><xsl:value-of select="/root/translations/TRL_LOCATION" />:</h1>
                            &#160;
    						<xsl:for-each select="/root/location/component">
                                <xsl:choose>
                                    <xsl:when test="url != ''">
                                        <a href="{url}"><xsl:value-of select="value" /></a>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select="value" />
                                    </xsl:otherwise>
                                </xsl:choose>
                                <xsl:if test="position() != last()"> &#187; </xsl:if>
    						</xsl:for-each>
    						<xsl:for-each select="/root/location/component_step">
    							&#187; <xsl:value-of select="step_no"/>. <xsl:value-of select="step_descr"/>
    						</xsl:for-each>
                        </div>
                        <div class="right_align stylemenu">
                        	<a name="unibox_styles" />
                            <h1 class="invisible"><xsl:value-of select="/root/translations/TRL_STYLEMENU" /></h1>
                            <h2><xsl:value-of select="/root/translations/TRL_STYLES" />: </h2>
                            <a href="framework_subtheme/subtheme_ident/light" title="{/root/translations/TRL_SWITCH_STYLE_TO_LIGHT}"><img src="{$theme_base}/media/images/icon_contrast_light.gif" alt="{/root/translations/TRL_SYMBOL_STYLE_LIGHT}" style="vertical-align: middle; margin: 0px 4px;" /></a>
                            <a href="framework_subtheme/subtheme_ident/standard" title="{/root/translations/TRL_SWITCH_STYLE_TO_STANDARD}"><img src="{$theme_base}/media/images/icon_contrast_standard.gif" alt="{/root/translations/TRL_SYMBOL_STYLE_STANDARD}" style="vertical-align: middle; margin-right: 4px;" /></a>
                            <a href="framework_subtheme/subtheme_ident/dark" title="{/root/translations/TRL_SWITCH_STYLE_TO_DARK}"><img src="{$theme_base}/media/images/icon_contrast_dark.gif" alt="{/root/translations/TRL_SYMBOL_STYLE_DARK}" style="vertical-align: middle; margin-right: 4px;" /></a>
                            <h2><xsl:value-of select="/root/translations/TRL_FONT_SIZE" />: </h2>
                            <a href="framework_font_size/resize/smaller" title="{/root/translations/TRL_DECREASE_FONT_SIZE}"><img src="{$theme_base}/media/images/icon_font_smaller.gif" alt="{/root/translations/TRL_SYMBOL_FONT_SMALLER}" style="vertical-align: middle; margin: 0px 4px;" /></a>
                            <a href="framework_font_size/resize/standard" title="{/root/translations/TRL_NORMALIZE_FONT_SIZE}"><img src="{$theme_base}/media/images/icon_font_standard.gif" alt="{/root/translations/TRL_SYMBOL_FONT_STANDARD}" style="vertical-align: middle; margin-right: 4px;" /></a>
                            <a href="framework_font_size/resize/bigger" title="{/root/translations/TRL_INCREASE_FONT_SIZE}"><img src="{$theme_base}/media/images/icon_font_bigger.gif" alt="{/root/translations/TRL_SYMBOL_FONT_BIGGER}" style="vertical-align: middle;" /></a>
                        </div>
					</div>

					<div class="box_gradient box_gradient_large box_gradient_footer">
						<img class="corner_bl" alt="" src="{$theme_base}/media/images/corner_bl.gif" />
						<img class="corner_br" alt="" src="{$theme_base}/media/images/corner_br.gif" />
					</div>

					<!-- show extension from group 'top' -->
					<xsl:apply-templates select="/root/extensions/group[@ident='top']">
						<xsl:sort select="content/@sort" order="ascending" />
					</xsl:apply-templates>
				</div>

				<!-- show extension from group 'navigation' -->
				<div id="page_navigation">
					<xsl:apply-templates select="/root/extensions/group[@ident='navigation']">
						<xsl:sort select="content/@sort" order="ascending" />
					</xsl:apply-templates>
				</div>

				<div id="page_help">
					<div class="box_gradient box_gradient_small box_gradient_header">
						<img class="corner_tl" alt="" src="{$theme_base}/media/images/corner_tl.gif" />
						<img class="corner_tr" alt="" src="{$theme_base}/media/images/corner_tr.gif" />
						<a name="unibox_instanthelp" /><h1><xsl:value-of select="/root/translations/TRL_INSTANTHELP" /></h1>
					</div>

					<div class="box_blue">
                        <xsl:apply-templates select="/root/unibox/help" />
					</div>

					<div class="box_gradient box_gradient_small box_gradient_footer">
						<img class="corner_bl" alt="" src="{$theme_base}/media/images/corner_bl.gif" />
						<img class="corner_br" alt="" src="{$theme_base}/media/images/corner_br.gif" />
					</div>
				</div>

				<div id="page_main">
                    <div id="page_main_inner">
                        <a name="unibox_content" />
                        <h1><span class="invisible"><xsl:value-of select="/root/translations/TRL_CONTENT" /></span></h1>

						<!-- show messages -->
						<xsl:apply-templates select="/root/messages" />

                        <xsl:for-each select="/root/content">
                            <xsl:if test="normalize-space(.)">
                                <div class="box_gradient box_gradient_large box_gradient_header box_gradient_header_image">
                                    <img class="corner_tl" alt="" src="{$theme_base}/media/images/corner_tl.gif" />
                                    <img class="corner_tr" id="main_corner_tr" alt="" src="{$theme_base}/media/images/corner_tr.gif" />
                                    <a href="JavaScript:;" onclick="JavaScript:toggle_instanthelp('{$theme_base}', '{/root/translations/TRL_INSTANTHELP_SHOW}', '{/root/translations/TRL_INSTANTHELP_VANISH}')"><img src="{$theme_base}/media/images/instanthelp_vanish.gif" alt="{/root/translations/TRL_INSTANTHELP_VANISH}" title="{/root/translations/TRL_INSTANTHELP_VANISH}" id="image_toggle_instanthelp" class="right_float" /></a>
                                    <div class="left_align header">
                                        <h2>
                                            <xsl:choose>
                                                <xsl:when test="/root/unibox/content_title != ''">
                                                    <xsl:value-of select="/root/unibox/content_title" />
                                                </xsl:when>
                                                <xsl:otherwise>
                                                    <xsl:value-of select="/root/unibox/action_descr" />
                                                </xsl:otherwise>
                                            </xsl:choose>
                                        </h2>
                                    </div>
                                </div>

            					<xsl:if test="normalize-space(/root/dialog)">
            						<div id="page_dialog">
            							<xsl:apply-templates select="/root/dialog" />
            						</div>
            						<div class="box_gradient box_gradient_large box_gradient_separator_dialog"></div>
            					</xsl:if>

            					<div id="content" class="box_blue box_content">
									<xsl:apply-templates select="." />
								</div>

            					<div class="box_gradient box_gradient_large box_gradient_footer">
            						<img class="corner_bl" alt="" src="{$theme_base}/media/images/corner_bl.gif" />
            						<img class="corner_br" id="main_corner_br" alt="" src="{$theme_base}/media/images/corner_br.gif" />
            					</div>
                            </xsl:if>
                        </xsl:for-each>
                    </div>
				</div>

			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>