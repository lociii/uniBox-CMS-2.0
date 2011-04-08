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
	<xsl:output method="xml" encoding="UTF-8" indent="yes" omit-xml-declaration="yes" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"/>
    <xsl:template match="pagebrowser">
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
						            <xsl:when test="@type = 'hidden'">
						                <input type="hidden" name="{@name}" id="{@name}" value="{value}" />
						            </xsl:when>
                                    <xsl:when test="@type = 'submit'">
                                        <span>&#160;</span><input type="submit" class="form_input_submit" style="display: inline;" name="{name}" id="{name}" value="{label}" />
                                    </xsl:when>
                                </xsl:choose>
                            </xsl:for-each>
                        </form>
                    </xsl:for-each>
                </xsl:if>
            </div>
            <div class="show_count">
                <strong><xsl:value-of select="/root/translations/TRL_DISPLAY_PER_PAGE" /> (<xsl:value-of select="/root/translations/TRL_TOTAL" />&#160;<xsl:value-of select="count/total" />):</strong>
                <xsl:for-each select="count/dataset">
                    <xsl:choose>
                        <xsl:when test="link != ''">
                            <a href="{link}"><xsl:value-of select="text" /></a>
                        </xsl:when>
                        <xsl:otherwise>
                            <strong><xsl:value-of select="text" /> <span class="invisible">(<xsl:value-of select="/root/translations/TRL_SELECTED" />)</span></strong>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:for-each>
            </div>
        </div>
	</xsl:template>

</xsl:stylesheet>