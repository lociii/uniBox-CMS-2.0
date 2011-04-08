<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     20.04.2005  jn      shared template to generate a pagebrowser
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">

    <xsl:template name="pagebrowser">
        <div class="box_pagebrowser">
            <div class="pagebrowser">
                <xsl:if test="overall_count > 1">
                    <xsl:for-each select="pages">
    	            	<h2 class="pagebrowser"><xsl:value-of select="/root/translations/TRL_PAGE" />&#160;<xsl:value-of select="dataset[@active=0]/text" />&#160;<xsl:value-of select="/root/translations/TRL_OF" />&#160;<xsl:value-of select="../overall_count" />: </h2>
    	                [<span>&#160;</span>
    	                <xsl:if test="first != ''">
    	                    <a href="{first/link}">&#171;<span class="invisible"> (<xsl:value-of select="/root/translations/TRL_TEMPLATE_FIRST_FEMALE" /> <xsl:value-of select="count" />&#160;<xsl:value-of select="/root/translations/TRL_TEMPLATE_PAGE" />)</span></a><span>&#160;</span>
	                   </xsl:if>

    	                <xsl:if test="previous">
    	                    <a href="{previous/link}">&#8249;<span class="invisible"> (<xsl:value-of select="/root/translations/TRL_TEMPLATE_PREVIOUS_FEMALE" /> <xsl:value-of select="count" />&#160;<xsl:value-of select="/root/translations/TRL_TEMPLATE_PAGE" />)</span></a><span>&#160;</span>
    	                </xsl:if>
    	                
    	                <xsl:for-each select="dataset">
    	                    <xsl:choose>
    	                        <xsl:when test="@active = 1">
    	                            <a href="{link}"><span class="invisible">Seite </span><xsl:value-of select="text" /></a><span>&#160;</span>
    	                        </xsl:when>
    	                        <xsl:otherwise>
    	                            <strong><span class="invisible"><xsl:value-of select="/root/translations/TRL_TEMPLATE_PAGE" /> </span><xsl:value-of select="text" /></strong><span>&#160;</span>
    	                        </xsl:otherwise>
    	                    </xsl:choose>
    	                </xsl:for-each>
    	                
    	                <xsl:if test="next">
	                       <a href="{next/link}">&#8250;<span class="invisible"> (<xsl:value-of select="/root/translations/TRL_TEMPLATE_NEXT_FEMALE" /> <xsl:value-of select="count" />&#160;<xsl:value-of select="/root/translations/TRL_TEMPLATE_PAGE" />)</span></a><span>&#160;</span>
    	                </xsl:if>
    	                
    	                <xsl:if test="last">
    	                    <a href="{last/link}">&#187;<span class="invisible"> (<xsl:value-of select="/root/translations/TRL_TEMPLATE_LAST_FEMALE" /> <xsl:value-of select="count" />&#160;<xsl:value-of select="/root/translations/TRL_TEMPLATE_PAGE" />)</span></a>
    	                </xsl:if>
    	                <span>&#160;</span>]
                    </xsl:for-each>

                    <xsl:variable name="form_name" select="concat('pagebrowser_', @ident, '_page')" />
                    <xsl:for-each select="goto/form[@name=$form_name]">
                        <form id="{@name}" action="{@action}" method="{@method}" enctype="{@encoding}">
                            <xsl:for-each select="input">
                                <xsl:choose>
                                    <xsl:when test="@type = 'text'">
                                        <input type="text" class="form_input_text" name="{name}" id="{name}" value="{value}" style="width: 3em;"/>
                                    </xsl:when>
                                    <xsl:when test="@type = 'submit'">
                                        <span>&#160;</span><input type="submit" class="form_input_submit" style="display: inline;" name="{name}" id="{name}" value="{label}" />
                                    </xsl:when>
                                </xsl:choose>
                            </xsl:for-each>
							<input type="hidden" name="form_validation_hash_{@name}" value="{/root/content/form_hash_pagebrowser}" />
                        </form>
                    </xsl:for-each>
                </xsl:if>
            </div>

            <div class="switch_view">
				<form id="media_editor_browse_view" action="media_editor_browse" method="post">
					<input type="radio" name="view" id="view_details" value="details" onclick="document.forms.media_editor_browse_view.submit()">
						<xsl:if test="/root/content/view = 'details'">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
					<label for="view_details">Detailansicht</label>
					&#160;
					<input type="radio" name="view" id="view_thumbnails" value="thumbnails" onclick="document.forms.media_editor_browse_view.submit()">
						<xsl:if test="/root/content/view = 'thumbnails'">
							<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</input>
					<label for="view_thumbnails">Miniaturansicht</label>
					<input type="hidden" name="media_editor_browse_view_submit_0" value="1" />
					<input type="hidden" name="form_validation_hash_media_editor_browse_view" value="{/root/content/form_hash_view}" />
				</form>
            </div>

        </div>
	</xsl:template>

</xsl:stylesheet>