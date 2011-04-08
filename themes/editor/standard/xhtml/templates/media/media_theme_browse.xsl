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
        <form name="themes">
            <div class="theme" id="general">
                <fieldset id="fieldset_general" style="display: none;">
                    <legend><xsl:value-of select="/root/translations/TRL_GENERAL" /></legend>
                    <table border="0" cellpadding="0" cellspacing="2">
                        <tr>
                            <td>
                                <label for="media_width_general"><strong><xsl:value-of select="/root/translations/TRL_WIDTH" />:</strong></label>
                            </td>
                            <td rowspan="2" style="width: 5px;">&#160;</td>
                            <td style="width: 55px;">
                                <input type="text" id="media_width_general" onkeyup="top.uniBoxMedia.process_input_width()" size="4" /> px
                            </td>
                            <td rowspan="2" style="width: 5px;">&#160;</td>
                            <td rowspan="2" style="padding-top: 2px; width: 20px;">
                                <img src="{/root/unibox/theme_base[@template='media_theme_browse']/path}/media/images/link_size.gif" />
                            </td>
                            <td rowspan="2" style="padding-left: 5px;">
                                <input type="checkbox" class="input_noborder" id="media_size_link_general" onchange="top.uniBoxMedia.process_checkbox()" />
                                <input type="hidden" id="media_size_relation_general" />
                            </td>
						</tr>
						<tr>
							<td>
                                <label for="media_height_general"><strong><xsl:value-of select="/root/translations/TRL_HEIGHT" />:</strong></label>
                            </td>
                            <td>
                                <input type="text" id="media_height_general" onkeyup="top.uniBoxMedia.process_input_height()" size="4" /> px
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
            
	            <div class="theme" id="theme_{ident}_{subtheme/ident}">
	                <fieldset id="fieldset_theme_{ident}_{subtheme/ident}" style="display: none;" onclick="top.uniBoxMedia.process_fieldset(this.id)" >
	                    <legend><xsl:value-of select="descr" /> - <xsl:value-of select="subtheme/descr" /></legend>
	                    <table border="0" cellpadding="0" cellspacing="2">
							<tr>
								<td style="width: 45px;">
									<label for="select_theme_{ident}_{subtheme/ident}"><strong><xsl:value-of select="/root/translations/TRL_TYPE" />:</strong></label>
								</td>
								<td>
				                    <select id="select_theme_{ident}_{subtheme/ident}" class="input_select" onchange="top.uniBoxMedia.process_select(this.id, '{/root/unibox/html_base}')">
				                        <option value="" selected="selected">Eigenes</option>
				                        <xsl:for-each select="/root/content/theme">
				                        	<xsl:if test="ident != $current_theme_ident or subtheme/ident != $current_subtheme_ident">
				                        		<option value="{ident}_{subtheme/ident}"><xsl:value-of select="/root/translations/TRL_SAME_AS" /> '<xsl:value-of select="descr" /> - <xsl:value-of select="subtheme/descr" />'</option>
				                        	</xsl:if>
										</xsl:for-each>
				                    </select>
								</td>
							</tr>
						</table>
						<div id="size_theme_{ident}_{subtheme/ident}">
							<table border="0" cellpadding="0" cellspacing="2">
		                        <tr>
		                            <td style="width: 45px;">
		                                <label for="media_width_theme_{ident}_{subtheme/ident}"><strong><xsl:value-of select="/root/translations/TRL_WIDTH" />:</strong></label>
		                            </td>
		                            <td style="width: 55px;">
		                                <input type="text" id="media_width_theme_{ident}_{subtheme/ident}" onkeyup="top.uniBoxMedia.process_input_width()" size="4" /> px
		                            </td>
		                            <td rowspan="2" style="width: 5px;">&#160;</td>
		                            <td rowspan="2" style="padding-top: 2px; width: 20px;">
		                                <img src="{/root/unibox/theme_base[@template='media_theme_browse']/path}/media/images/link_size.gif" />
		                            </td>
		                            <td rowspan="2" style="width: 130px;">
		                                <input type="checkbox" class="input_noborder" id="media_size_link_theme_{ident}_{subtheme/ident}" onchange="top.uniBoxMedia.process_checkbox()" />
		                                <input type="hidden" id="media_size_relation_theme_{ident}_{subtheme/ident}" />
		                            </td>
								</tr>
								<tr id="size_2_theme_{ident}_{subtheme/ident}">
									<td>
		                                <label for="media_height_theme_{ident}_{subtheme/ident}"><strong><xsl:value-of select="/root/translations/TRL_HEIGHT" />:</strong></label>
		                            </td>
		                            <td>
		                                <input type="text" id="media_height_theme_{ident}_{subtheme/ident}" onkeyup="top.uniBoxMedia.process_input_height()" size="4" /> px
		                            </td>
		                        </tr>
		                    </table>
		                </div>
	                    <div class="image">
	                        <img src="" id="thumbnail_theme_{ident}_{subtheme/ident}" style="display: none;" />
	                    </div>
                        <div id="info_theme_{ident}_{subtheme/ident}"><xsl:value-of select="/root/translations/TRL_NO_IMAGE_SELECTED" /></div>
	                    <input type="hidden" id="media_id_theme_{ident}_{subtheme/ident}" value="" />
	                </fieldset>
	            </div>
	            <br />
			</xsl:for-each>
        </form>
		<script type="text/javascript">
			<xsl:comment>
				var themes = Array(
				<xsl:for-each select="theme">
					'<xsl:value-of select="ident" />_<xsl:value-of select="subtheme/ident" />'
					<xsl:if test="position() != last()">,</xsl:if>
				</xsl:for-each>
				);
				top.uniBoxMedia.set_themes(themes);
				<xsl:if test="session_name and session_id">
					top.uniBoxMedia.set_session_data(<xsl:value-of select="session_name" />, <xsl:value-of select="session_id" />);
				</xsl:if>
			</xsl:comment>
		</script>
    </xsl:template>
    
</xsl:stylesheet>