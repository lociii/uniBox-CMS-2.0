<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     27.07.2005  jn      first release
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">

	<xsl:template name="content">
		<xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='shared_administration']/path"/>

		<xsl:if test="preselect_ident != ''">
            <h3 class="invisible"><xsl:value-of select="/root/translations/TRL_PRESELECT" /></h3>
			<xsl:call-template name="form_parse">
				<xsl:with-param name="form" select="form[@name=../preselect_ident]" />
			</xsl:call-template>
		</xsl:if>

        <xsl:for-each select="administration">

            <xsl:if test="links != ''">
                <div id="administrate_links">
                    <xsl:for-each select="links/link">
                        <a href="{alias}"><xsl:value-of select="name" /></a><br />
                    </xsl:for-each>
                    <br/>
                </div>
            </xsl:if>

            <xsl:if test="datasets/dataset != ''">
                <xsl:if test="pagebrowser != ''">
                    <h3 class="invisible"><xsl:value-of select="/root/translations/TRL_PAGEBROWSER" /></h3>
                    <xsl:for-each select="pagebrowser">
                        <xsl:call-template name="pagebrowser" />
                    </xsl:for-each>
                </xsl:if>
    
                <form id="administration_{@ident}" action="administration_process" method="post">
                <div class="table_administrate">
                    <h3 class="invisible"><xsl:value-of select="/root/translations/TRL_ADMINISTRATION_TABLE" /></h3>
        			<table class="administrate">
        				<xsl:call-template name="table_sort_header" />
        				<tbody>
        					<xsl:for-each select="datasets/dataset">
        			    		<tr>
                                    <xsl:attribute name="class">
                                        <xsl:choose>
                                            <xsl:when test="position() mod 2 = 1">highlight_dark</xsl:when>
                                            <xsl:otherwise>highlight_light</xsl:otherwise>
                                        </xsl:choose>
                                        <xsl:if test="@status = -1"> highlight_error</xsl:if>
                                    </xsl:attribute>
                                    <xsl:choose>
                                        <xsl:when test="checkbox != ''">
                                            <td class="checkbox">
                                                <input type="checkbox" name="administration_checkbox[]" id="administration_checkbox_{../../@ident}_{position()}" value="{checkbox}">
                                                    <xsl:if test="@status = 1">
                                                        <xsl:attribute name="checked">checked</xsl:attribute>
                                                    </xsl:if>
                                                </input>
                                            </td>
                                        </xsl:when>
                                        <xsl:when test="../../table_header/checkbox_column != ''">
                                            <td class="blind">&#160;</td>
                                        </xsl:when>
                                    </xsl:choose>
                                    <xsl:variable name="position" select="position()" />
        			    			<xsl:for-each select="data">
                                        <xsl:choose>
                                            <xsl:when test="../checkbox != '' and position() = 1">
                                                <td><label for="administration_checkbox_{../../../@ident}_{$position}"><xsl:value-of select="." /></label></td>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <td><xsl:value-of select="." /></td>
                                            </xsl:otherwise>
                                        </xsl:choose>
        			    			</xsl:for-each>
        			    			<xsl:for-each select="option">
                                        <td class="symbol">
            					    		<xsl:choose>
            					    			<xsl:when test="link != ''">
                                                    <a>
                                                        <xsl:attribute name="href"><xsl:value-of select="link" /></xsl:attribute>
                                                        <xsl:if test="onclick != ''"><xsl:attribute name="onclick"><xsl:value-of select="onclick" /></xsl:attribute></xsl:if>
                                                        <img src="{$theme_base}/media/images/symbols/{image}" alt="{text}" title="{text}" />
                                                    </a>
            				    			    </xsl:when>
            					    			<xsl:otherwise>
            					    				<xsl:choose>
                                                        <xsl:when test="image != ''">
            									    		<img src="{$theme_base}/media/images/symbols/{image}" alt="{text}" title="{text}" />
            					    					</xsl:when>
            					    					<xsl:otherwise>&#160;</xsl:otherwise>
            				    					</xsl:choose>
            					    			</xsl:otherwise>
            					    		</xsl:choose>
                                        </td>
        				    		</xsl:for-each>
        				    	</tr>
        	    			</xsl:for-each>
        	    		</tbody>
        	    	</table>
                </div>
                
                <xsl:if test="multi_options/option != ''">
                    <div class="box_gradient box_gradient_large box_gradient_separator"></div>
                    <div class="box_blue multi_options">
                        <a href="JavaScript:;" onclick="JavaScript:administration_check_all('{@ident}')">Alle auswählen</a>
                        |
                        <a href="JavaScript:;" onclick="JavaScript:administration_uncheck_all('{@ident}')">Alle abwählen</a>
                        <span class="invisible"><br/></span>
                        <span style="margin-left: 20px;">Mit gewählten:</span>
                        <xsl:for-each select="multi_options/option">
                            <input type="image" src="{$theme_base}/media/images/symbols/{image}" name="administration_submit_{link}" alt="{text}" title="{text}" />
<!--
                            <button type="submit" name="administration_submit" value="{link}" title="{text}">
                                <img src="{$theme_base}/media/images/symbols/{image}" alt="{text}" title="{text}" />
                            </button>
-->
                        </xsl:for-each>
                    </div>
                </xsl:if>

                </form>
    		</xsl:if>
        </xsl:for-each>
    </xsl:template>

    <xsl:template name="table_sort_header">
        <xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='shared_administration']/path"/>
        <thead>
            <tr>
                <xsl:if test="table_header/checkbox_column != ''">
                    <th class="checkbox">&#160;</th>
                </xsl:if>
                <xsl:for-each select="table_header/column">
                    <th>
                        <xsl:if test="width != 0"><xsl:attribute name="style">width: <xsl:value-of select="width" />px;</xsl:attribute></xsl:if>
                        <xsl:choose>
                            <xsl:when test="sort = 'ASC'">
                                <a href="{../alias}/sort/{ident}/" title="{/root/translations/TRL_COL_SORT_DESC}"><xsl:value-of select="name" /></a><span class="invisible">&#160;(<xsl:value-of select="/root/translations/TRL_COL_IS_SORTED_ASC" />)</span>&#160;<img src="{$theme_base}/media/images/{image}.gif" alt="{/root/translations/TRL_COL_IS_SORTED_ASC}" title="{/root/translations/TRL_COL_IS_SORTED_ASC}" />
                            </xsl:when>
                            <xsl:otherwise>
                                <a href="{../alias}/sort/{ident}/" title="{/root/translations/TRL_COL_SORT_ASC}"><xsl:value-of select="name" /></a><span class="invisible">&#160;(<xsl:value-of select="/root/translations/TRL_COL_IS_SORTED_DESC" />)</span>&#160;<img src="{$theme_base}/media/images/{image}.gif" alt="{/root/translations/TRL_COL_IS_SORTED_DESC}" title="{/root/translations/TRL_COL_IS_SORTED_DESC}" />
                            </xsl:otherwise>
                        </xsl:choose>
                    </th>
                </xsl:for-each>
                <xsl:for-each select="table_header/option_column">
                    <th class="blind">&#160;</th>
                </xsl:for-each>
            </tr>
        </thead>
    </xsl:template>

</xsl:stylesheet>