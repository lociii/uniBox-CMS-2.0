<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     06.04.2005  pr		shows media browser inside editor
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">

	<xsl:template name="content">
        <xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='media_theme_browse']/path" />
        <form name="themes">
            <div class="theme" id="general">
                <fieldset id="fieldset_general" style="display: none;">
                    <strong><xsl:value-of select="/root/translations/TRL_GENERAL" /></strong>
                    <br /><br />
                    <table>
                        <tr>
                            <td>
                                <span class="descr"><xsl:value-of select="/root/translations/TRL_WIDTH" />:</span>
                                <input type="text" id="media_width_general" onkeyup="top.uniBoxMedia.process_input_width()" /> px
                                <br />
                                <span class="descr"><xsl:value-of select="/root/translations/TRL_HEIGHT" />:</span>
                                <input type="text" id="media_height_general" onkeyup="top.uniBoxMedia.process_input_height()" /> px
                            </td>
                            <td>
                                <img src="{$theme_base}/media/images/link_size.gif" />
                            </td>
                            <td style="padding-left: 5px;">
                                <input type="checkbox" id="media_size_link_general" onchange="top.uniBoxMedia.process_checkbox()" />
                                <input type="hidden" id="media_size_relation_general" />
                            </td>
                        </tr>
                    </table>
                    <div class="image">
                        <img src="" id="thumbnail_general" style="display: none;" />
                    </div>
                    <div id="general_info"><xsl:value-of select="/root/translations/TRL_NO_IMAGE_SELECTED" /></div>
                    <input type="hidden" id="media_id_general" value="" />
                </fieldset>
            </div>

			<xsl:for-each select="theme">
	            <xsl:variable name="current_theme_ident" select="ident"/>
	            <xsl:variable name="current_subtheme_ident" select="subtheme/ident"/>
            
	            <div id="theme_{ident}_{subtheme/ident}">
	                <fieldset id="fieldset_theme_{ident}_{subtheme/ident}" style="display: none;" onclick="top.uniBoxMedia.process_fieldset(this.id)" >
	                    <strong><xsl:value-of select="descr" /> - <xsl:value-of select="subtheme/descr" /></strong>
	                    <br /><br />
	                    <div class="descr"><xsl:value-of select="/root/translations/TRL_TYPE" />:</div>
	                    <select id="select_theme_{ident}_{subtheme/ident}" class="input_select" onchange="top.uniBoxMedia.process_select(this.id, '{/root/html_base}')">
	                        <option value="" selected="selected">Eigenes</option>
	                        <xsl:for-each select="/root/content/theme">
	                        	<xsl:if test="ident != $current_theme_ident or subtheme/ident != $current_subtheme_ident">
	                        		<option value="{ident}_{subtheme/ident}"><xsl:value-of select="/root/translations/TRL_SAME_AS" />'<xsl:value-of select="descr" /> - <xsl:value-of select="subtheme/descr" />'</option>
	                        	</xsl:if>
							</xsl:for-each>
	                    </select>
                        <br />
                        <div id="size_theme_{ident}_{subtheme/ident}">
                            <table>
                                <tr>
                                    <td>
                                        <span class="descr"><xsl:value-of select="/root/translations/TRL_WIDTH" />:</span>
                                        <input type="text" id="media_width_theme_{ident}_{subtheme/ident}" value="" onkeyup="top.uniBoxMedia.process_input_width()" /> px
                                        <br />
                                        <span class="descr"><xsl:value-of select="/root/translations/TRL_HEIGHT" />:</span>
                                        <input type="text" id="media_height_theme_{ident}_{subtheme/ident}" value="" onkeyup="top.uniBoxMedia.process_input_height()" /> px
                                    </td>
                                    <td>
                                        <img src="{$theme_base}/media/images/link_size.gif" />
                                    </td>
                                    <td style="padding-left: 5px;">
                                        <input type="checkbox" id="media_size_link_theme_{ident}_{subtheme/ident}" onchange="top.uniBoxMedia.process_checkbox()" />
                                        <input type="hidden" id="media_size_relation_theme_{ident}_{subtheme/ident}" />
                                    </td>
                                </tr>
                            </table>
                        </div>
	                    <div class="image">
	                        <img src="" id="thumbnail_theme_{ident}_{subtheme/ident}" style="display: none;" />
	                    </div>
	                    <input type="hidden" id="media_id_theme_{ident}_{subtheme/ident}" value="" />
	                </fieldset>
	            </div>
			</xsl:for-each>
        </form>
		<script type="text/javascript">
			<xsl:comment>
				var themes = Array(
				<xsl:for-each select="/root/content/theme">
					'<xsl:value-of select="ident" />_<xsl:value-of select="subtheme/ident" />'
					<xsl:if test="position() != last()">,</xsl:if>
				</xsl:for-each>
				);
				top.uniBoxMedia.set_themes(themes);
			</xsl:comment>
		</script>
    </xsl:template>
    
</xsl:stylesheet>