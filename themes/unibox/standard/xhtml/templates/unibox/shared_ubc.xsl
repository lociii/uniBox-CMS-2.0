<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ubc="http://www.media-soma.de/ubc">

	<xsl:template match="ubc">
		<xsl:apply-templates />
	</xsl:template>

<!--  SMILEY -->
	<xsl:template match="ubc//smiley">
		<xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='main']/path"/>
		<img src="{$theme_base}/media/images/smileys/{@id}.gif" class="ubc_smiley" /> 
	</xsl:template>
<!-- BR -->
    <xsl:template match="ubc//br">
        <br />
    </xsl:template>
<!-- FORM -->
    <xsl:template match="ubc//ubc_form">
        <xsl:apply-templates select="form" />
    </xsl:template>
<!-- STRONG -->
    <xsl:template match="ubc//strong">
        <strong>
            <xsl:apply-templates />
        </strong>
    </xsl:template>
<!-- ITALIC -->
    <xsl:template match="ubc//italic">
        <em>
            <xsl:apply-templates />
        </em>
    </xsl:template>
<!-- UNDERLINE -->
    <xsl:template match="ubc//underline">
        <u>
            <xsl:apply-templates />
        </u>
    </xsl:template>
<!-- STRIKE -->
	<xsl:template match="ubc//strike">
	    <strike>
	        <xsl:apply-templates />
	    </strike>
	</xsl:template>
<!-- ALIGN -->
    <xsl:template match="ubc//align">
        <div>
            <xsl:attribute name="style">text-align: <xsl:value-of select="@value" />;</xsl:attribute>
            <xsl:apply-templates />
        </div>
    </xsl:template>
<!-- ABBR -->
    <xsl:template match="ubc//abbr">
        <acronym>
            <xsl:if test="@lang != ''"><xsl:attribute name="lang"><xsl:value-of select="@lang" /></xsl:attribute></xsl:if>
            <xsl:if test="@title != ''"><xsl:attribute name="title"><xsl:value-of select="@title" /></xsl:attribute></xsl:if>
            <xsl:apply-templates />
        </acronym>
    </xsl:template>
<!-- LANG -->
    <xsl:template match="ubc//lang">
        <span lang="{@lang}">
            <xsl:apply-templates />
        </span>
    </xsl:template>
<!-- SUP -->
    <xsl:template match="ubc//sup">
        <sup>
            <xsl:apply-templates />
        </sup>
    </xsl:template>
<!-- SUB -->
    <xsl:template match="ubc//sub">
        <sub>
            <xsl:apply-templates />
        </sub>
    </xsl:template>
<!-- COLOR -->
    <xsl:template match="ubc//color">
        <span style="color: {@value};">
        	<xsl:apply-templates />
        </span>
    </xsl:template>
<!-- SIZE -->
    <xsl:template match="ubc//size">
        <span style="font-size: {@value}">
        	<xsl:apply-templates />
        </span>
    </xsl:template>
<!-- CODE -->
    <xsl:template match="ubc//code">
        <span class="ubc_code_header">
        	TRL_SOURCE_CODE:
        </span>
        <pre class="ubc_code">
        	<xsl:apply-templates />
        </pre>
    </xsl:template>
<!-- FIXED -->
    <xsl:template match="ubc//fixed">
        <xsl:if test="@name">
        	<span class="ubc_fixed_header">
        		<xsl:value-of select="@name"/>:
        	</span>
        </xsl:if>
        <div class="ubc_fixed">
        	<xsl:apply-templates />
        </div>
    </xsl:template>
<!-- QUOTE -->
    <xsl:template match="ubc//quote">
        <xsl:choose>
            <xsl:when test="@author">
                <cite>
                    <xsl:value-of select="@author"/>
                </cite>
            </xsl:when>
            <xsl:otherwise>
                <span class="ubc_quote_header">
                    ||WORD_QUOTE||:
                </span>
            </xsl:otherwise>
        </xsl:choose>
        <br/>
        <div class="ubc_quote">
            <q>
                <xsl:if test="@url">
                	<xsl:attribute name="cite">
                		<xsl:value-of select="@url" />
                	</xsl:attribute>
                </xsl:if>
                <xsl:apply-templates />
            </q>
        </div>
    </xsl:template>
<!-- LIST -->
    <xsl:template match="ubc//list">
        <xsl:choose>
            <xsl:when test="@listtype = 'ol'">
                <ol class="ubc_ol">
                    <xsl:if test="@vistype">
                    	<xsl:attribute name="style">list-style-type: <xsl:value-of select="@vistype" />;</xsl:attribute>
                    </xsl:if>
                    <xsl:apply-templates />
                </ol>
            </xsl:when>
            <xsl:otherwise>
                <ul class="ubc_ul">
                    <xsl:if test="@vistype">
                    	<xsl:attribute name="style">list-style-type: <xsl:value-of select="@vistype" />;</xsl:attribute>
                    </xsl:if>
                    <xsl:apply-templates />
                </ul>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
<!-- LISTENTRY -->
    <xsl:template match="ubc//listentry">
        <li>
        	<xsl:apply-templates />
		</li>
    </xsl:template>
<!-- URL -->
    <xsl:template match="ubc//url">
        <a>
            <xsl:if test="@lang"><xsl:attribute name="lang"><xsl:value-of select="@lang" /></xsl:attribute></xsl:if>
            <xsl:if test="@rel"><xsl:attribute name="rel"><xsl:value-of select="@rel" /></xsl:attribute></xsl:if>
            <xsl:if test="@hreflang"><xsl:attribute name="hreflang"><xsl:value-of select="@hreflang" /></xsl:attribute></xsl:if>
            <xsl:if test="@title"><xsl:attribute name="title"><xsl:value-of select="@title" /></xsl:attribute></xsl:if>
            <xsl:if test="@dir"><xsl:attribute name="dir"><xsl:value-of select="@dir" /></xsl:attribute></xsl:if>
            <xsl:if test="@tabindex"><xsl:attribute name="tabindex"><xsl:value-of select="@tabindex" /></xsl:attribute></xsl:if>
            <xsl:if test="@accesskey"><xsl:attribute name="accesskey"><xsl:value-of select="@accesskey" /></xsl:attribute></xsl:if>
            <xsl:choose>
                <xsl:when test="@onclick">
                    <xsl:attribute name="onclick"><xsl:value-of select="@onclick" /></xsl:attribute>
                    <xsl:choose>
                        <xsl:when test="/root/unibox/editor_mode = 1">
                            <xsl:attribute name="href"><xsl:value-of select="@href" /></xsl:attribute>
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:attribute name="href">JavaScript:;</xsl:attribute>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:when>
                <xsl:otherwise>
                	<xsl:choose>
                		<xsl:when test="substring(@href, 1, 1) = '#'">
                			<xsl:attribute name="href"><xsl:value-of select="/root/unibox/query_string" /><xsl:value-of select="@href" /></xsl:attribute>
                		</xsl:when>
                		<xsl:otherwise>
		                    <xsl:attribute name="href"><xsl:value-of select="@href" /></xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:apply-templates />
        </a>
    </xsl:template>
<!-- ANCHOR -->
    <xsl:template match="ubc//anchor">
        <a name="{@name}">
            <xsl:if test="@style">
            	<xsl:attribute name="style"><xsl:value-of select="@style" /></xsl:attribute>
            </xsl:if>
        </a>
    </xsl:template>
<!-- EMAIL -->
    <xsl:template match="ubc//email">
    	<xsl:variable name="href">
    		<xsl:choose>
    			<xsl:when test="@obfuscate = '1'">
            		<xsl:for-each select="user"><xsl:value-of select="." /><xsl:if test="position() != last()"><xsl:value-of select="/root/translations/TRL_EMAIL_OBFUSCATE_DOT" /></xsl:if></xsl:for-each>
					<xsl:value-of select="/root/translations/TRL_EMAIL_OBFUSCATE_AT" />
    				<xsl:for-each select="host"><xsl:value-of select="." /><xsl:if test="position() != last()"><xsl:value-of select="/root/translations/TRL_EMAIL_OBFUSCATE_DOT" /></xsl:if></xsl:for-each>
    			</xsl:when>
    			<xsl:otherwise>
    				<xsl:value-of select="@href" />
    			</xsl:otherwise>
    		</xsl:choose>
    	</xsl:variable>
        <a href="mailto:{$href}">
            <xsl:if test="@descr"><xsl:attribute name="descr"><xsl:value-of select="@descr" /></xsl:attribute></xsl:if>
            <xsl:apply-templates />
        </a>
    </xsl:template>
	<xsl:template match="ubc//email/*" />
<!-- IMG -->
    <xsl:template match="ubc//img">
        <xsl:variable name="theme_ident" select="/root/unibox/combined_theme_ident" />

        <xsl:variable name="margin">margin: <xsl:value-of select="@margin-top" />px <xsl:value-of select="@margin-right" />px <xsl:value-of select="@margin-bottom" />px <xsl:value-of select="@margin-left" />px;</xsl:variable>
        <xsl:variable name="themes"><xsl:for-each select="theme"><xsl:value-of select="@name" />: <xsl:value-of select="media_id" />|<xsl:value-of select="width" />|<xsl:value-of select="height" />; </xsl:for-each></xsl:variable>
        <xsl:variable name="float"><xsl:if test="@float">float: <xsl:value-of select="@float"/>;</xsl:if></xsl:variable>
        <xsl:choose>
            <xsl:when test="/root/unibox/editor_mode = 1">
                <img class="ubc_image" alt="">
                	<xsl:choose>
                		<xsl:when test="@session_name and @session_id">
                            <xsl:attribute name="src"><xsl:value-of select="/root/unibox/html_base" />media.php5?media_id=<xsl:value-of select="theme[@name=$theme_ident]/media_id" />&#38;width=<xsl:value-of select="theme[@name=$theme_ident]/width" />&#38;height=<xsl:value-of select="theme[@name=$theme_ident]/height" />&#38;<xsl:value-of select="@session_name" />_id=<xsl:value-of select="@session_id" /></xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="src"><xsl:value-of select="/root/unibox/html_base" />media.php5?media_id=<xsl:value-of select="theme[@name=$theme_ident]/media_id" />&#38;width=<xsl:value-of select="theme[@name=$theme_ident]/width" />&#38;height=<xsl:value-of select="theme[@name=$theme_ident]/height" /></xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
                    <xsl:attribute name="themes"><xsl:value-of select="$themes" /></xsl:attribute>
                    <xsl:attribute name="curtheme"><xsl:value-of select="$theme_ident" /></xsl:attribute>
                    <xsl:attribute name="style"><xsl:value-of select="$float" /> <xsl:value-of select="$margin" /></xsl:attribute>
                    <xsl:attribute name="zoom"><xsl:value-of select="@zoom" /></xsl:attribute>
                    <xsl:if test="theme[@name=$theme_ident]/width"><xsl:attribute name="width"><xsl:value-of select="theme[@name=$theme_ident]/width" /></xsl:attribute></xsl:if>
                    <xsl:if test="theme[@name=$theme_ident]/height"><xsl:attribute name="height"><xsl:value-of select="theme[@name=$theme_ident]/height" /></xsl:attribute></xsl:if>
                </img>
            </xsl:when>
            <xsl:otherwise>
                <xsl:choose>
                    <xsl:when test="@zoom = '1'">
                        <div class="ubc_image_container">
                            <xsl:attribute name="style"><xsl:value-of select="$float" /> <xsl:value-of select="$margin" /></xsl:attribute>
                            <img class="ubc_image">
                            	<xsl:attribute name="alt" />
                                <xsl:attribute name="style">width: <xsl:value-of select="theme[@name=$theme_ident]/width" />px; height: <xsl:value-of select="theme[@name=$theme_ident]/height" />px;</xsl:attribute>
                            	<xsl:choose>
                            		<xsl:when test="@session_name and @session_id">
		                                <xsl:attribute name="src"><xsl:value-of select="/root/unibox/html_base" />media.php5?media_id=<xsl:value-of select="theme[@name=$theme_ident]/media_id" />&#38;width=<xsl:value-of select="theme[@name=$theme_ident]/width" />&#38;height=<xsl:value-of select="theme[@name=$theme_ident]/height" />&#38;<xsl:value-of select="@session_name" />_id=<xsl:value-of select="@session_id" /></xsl:attribute>
									</xsl:when>
									<xsl:otherwise>
										<xsl:attribute name="src"><xsl:value-of select="/root/unibox/html_base" />media.php5?media_id=<xsl:value-of select="theme[@name=$theme_ident]/media_id" />&#38;width=<xsl:value-of select="theme[@name=$theme_ident]/width" />&#38;height=<xsl:value-of select="theme[@name=$theme_ident]/height" /></xsl:attribute>
									</xsl:otherwise>
								</xsl:choose>
                                <xsl:if test="theme[@name=$theme_ident]/alt"><xsl:attribute name="alt"><xsl:value-of select="theme[@name=$theme_ident]/alt" /></xsl:attribute></xsl:if>
                                <xsl:if test="theme[@name=$theme_ident]/title"><xsl:attribute name="title"><xsl:value-of select="theme[@name=$theme_ident]/title" /></xsl:attribute></xsl:if>
                                <xsl:if test="theme[@name=$theme_ident]/longdesc"><xsl:attribute name="longdesc"><xsl:value-of select="theme[@name=$theme_ident]/longdesc" /></xsl:attribute></xsl:if>
                                <xsl:if test="theme[@name=$theme_ident]/width"><xsl:attribute name="width"><xsl:value-of select="theme[@name=$theme_ident]/width" /></xsl:attribute></xsl:if>
                                <xsl:if test="theme[@name=$theme_ident]/height"><xsl:attribute name="height"><xsl:value-of select="theme[@name=$theme_ident]/height" /></xsl:attribute></xsl:if>
                            </img>
                            <br />
                            <xsl:choose>
                                <xsl:when test="@session_name and @session_id">
                                    <a href="JavaScript:;" onclick="show_image({theme[@name=$theme_ident]/media_id}, {theme[@name=$theme_ident]/width_orig}, {theme[@name=$theme_ident]/height_orig}, '{@session_name}', '{@session_id}')">
                                        <xsl:value-of select="/root/translations/TRL_ZOOM" />
                                    </a>
                                </xsl:when>
                                <xsl:otherwise>
                                    <a href="JavaScript:;" onclick="show_image({theme[@name=$theme_ident]/media_id}, {theme[@name=$theme_ident]/width_orig}, {theme[@name=$theme_ident]/height_orig})">
                                        <xsl:value-of select="/root/translations/TRL_ZOOM" />
                                    </a>
                                </xsl:otherwise>
                            </xsl:choose>
                        </div>
                    </xsl:when>
                    <xsl:otherwise>
                        <img class="ubc_image">
                        	<xsl:attribute name="alt" />
                        	<xsl:choose>
                        		<xsl:when test="@session_name and @session_id">
	                                <xsl:attribute name="src"><xsl:value-of select="/root/unibox/html_base" />media.php5?media_id=<xsl:value-of select="theme[@name=$theme_ident]/media_id" />&#38;width=<xsl:value-of select="theme[@name=$theme_ident]/width" />&#38;height=<xsl:value-of select="theme[@name=$theme_ident]/height" />&#38;<xsl:value-of select="@session_name" />_id=<xsl:value-of select="@session_id" /></xsl:attribute>
								</xsl:when>
								<xsl:otherwise>
									<xsl:attribute name="src"><xsl:value-of select="/root/unibox/html_base" />media.php5?media_id=<xsl:value-of select="theme[@name=$theme_ident]/media_id" />&#38;width=<xsl:value-of select="theme[@name=$theme_ident]/width" />&#38;height=<xsl:value-of select="theme[@name=$theme_ident]/height" /></xsl:attribute>
								</xsl:otherwise>
							</xsl:choose>
                            <xsl:attribute name="style"><xsl:value-of select="$float" /> <xsl:value-of select="$margin" /></xsl:attribute>
                            <xsl:if test="theme[@name=$theme_ident]/title"><xsl:attribute name="title"><xsl:value-of select="theme[@name=$theme_ident]/title" /></xsl:attribute></xsl:if>
                            <xsl:if test="theme[@name=$theme_ident]/alt"><xsl:attribute name="alt"><xsl:value-of select="theme[@name=$theme_ident]/alt" /></xsl:attribute></xsl:if>
                            <xsl:if test="theme[@name=$theme_ident]/longdesc"><xsl:attribute name="longdesc"><xsl:value-of select="theme[@name=$theme_ident]/longdesc" /></xsl:attribute></xsl:if>
                            <xsl:if test="theme[@name=$theme_ident]/width"><xsl:attribute name="width"><xsl:value-of select="theme[@name=$theme_ident]/width" /></xsl:attribute></xsl:if>
                            <xsl:if test="theme[@name=$theme_ident]/height"><xsl:attribute name="height"><xsl:value-of select="theme[@name=$theme_ident]/height" /></xsl:attribute></xsl:if>
                        </img>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
<!-- SEPERATOR -->
    <xsl:template match="ubc//separator">
        <hr class="ubc_separator" />
    </xsl:template>
<!-- FLASH -->
    <xsl:template match="ubc//flash">
        <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="{@width}" height="{@height}">
            <param name="movie" value="{@src}" />
            <param name="quality" value="high" />
            <param name="menu" value="true" />
            <param name="wmode" value="" />
            <embed src="{@src}" wmode="" quality="high" menu="false" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="{@width}" height="{@height}"></embed>
        </object>
    </xsl:template>
<!-- TABLE -->
    <xsl:template match="ubc//table">
        <table>
            <xsl:attribute name="style">
                <xsl:if test="@text-align">text-align: <xsl:value-of select="@text-align" />; </xsl:if>
                <xsl:if test="@width">width: <xsl:value-of select="@width" />; </xsl:if>
                <xsl:if test="@height">height: <xsl:value-of select="@height" />; </xsl:if>
                <xsl:if test="@background-color">background-color: <xsl:value-of select="@background-color" />; </xsl:if>
            </xsl:attribute>
            <xsl:attribute name="border">
                <xsl:choose>
                    <xsl:when test="@border = '1'">1</xsl:when>
                    <xsl:otherwise>0</xsl:otherwise>
                </xsl:choose>
            </xsl:attribute>
            <xsl:if test="@dir"><xsl:attribute name="dir"><xsl:value-of select="@dir" /></xsl:attribute></xsl:if>
            <xsl:if test="@lang"><xsl:attribute name="lang"><xsl:value-of select="@lang" /></xsl:attribute></xsl:if>
            <xsl:if test="@class"><xsl:attribute name="class"><xsl:value-of select="@class" /></xsl:attribute></xsl:if>
            <xsl:if test="@summary"><xsl:attribute name="summary"><xsl:value-of select="@summary" /></xsl:attribute></xsl:if>
            <xsl:choose>
                <xsl:when test="@cellpadding">
                    <xsl:attribute name="cellpadding"><xsl:value-of select="@cellpadding" /></xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="cellpadding">0</xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:choose>
                <xsl:when test="@cellspacing">
                    <xsl:attribute name="cellspacing"><xsl:value-of select="@cellspacing" /></xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="cellspacing">0</xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:apply-templates />
        </table>
    </xsl:template>
<!-- THEAD -->
    <xsl:template match="ubc//table_head">
        <thead>
            <xsl:apply-templates />
        </thead>
    </xsl:template>
<!-- TBODY -->
    <xsl:template match="ubc//table_body">
        <tbody>
            <xsl:apply-templates />
        </tbody>
    </xsl:template>
<!-- TFOOT -->
    <xsl:template match="ubc//table_foot">
        <tfoot>
            <xsl:apply-templates />
        </tfoot>
    </xsl:template>
<!-- TABLE_ROW -->
    <xsl:template match="ubc//table_row">
        <tr>
            <xsl:attribute name="style">
                <xsl:if test="@text-align">text-align: <xsl:value-of select="@text-align" />; </xsl:if>
                <xsl:if test="@vertical-align">vertical-align: <xsl:value-of select="@vertical-align" />; </xsl:if>
                <xsl:if test="@background-color">background-color: <xsl:value-of select="@background-color" />; </xsl:if>
            </xsl:attribute>
            <xsl:if test="@dir"><xsl:attribute name="dir"><xsl:value-of select="@dir" /></xsl:attribute></xsl:if>
            <xsl:if test="@lang"><xsl:attribute name="lang"><xsl:value-of select="@lang" /></xsl:attribute></xsl:if>
            <xsl:if test="@class"><xsl:attribute name="class"><xsl:value-of select="@class" /></xsl:attribute></xsl:if>
            <xsl:apply-templates />
        </tr>
    </xsl:template>
<!-- TABLE_CELL_HEAD -->
    <xsl:template match="ubc//table_cell_head">
        <th>
            <xsl:attribute name="style">
                <xsl:if test="@text-align">text-align: <xsl:value-of select="@text-align" />; </xsl:if>
                <xsl:if test="@vertical-align">vertical-align: <xsl:value-of select="@vertical-align" />; </xsl:if>
                <xsl:if test="@background-color">background-color: <xsl:value-of select="@background-color" />; </xsl:if>
                <xsl:if test="@width">width: <xsl:value-of select="@width" />; </xsl:if>
                <xsl:if test="@height">height: <xsl:value-of select="@height" />; </xsl:if>
            </xsl:attribute>
            <xsl:if test="@dir"><xsl:attribute name="dir"><xsl:value-of select="@dir" /></xsl:attribute></xsl:if>
            <xsl:if test="@lang"><xsl:attribute name="lang"><xsl:value-of select="@lang" /></xsl:attribute></xsl:if>
            <xsl:if test="@scope"><xsl:attribute name="scope"><xsl:value-of select="@scope" /></xsl:attribute></xsl:if>
            <xsl:if test="@colspan"><xsl:attribute name="colspan"><xsl:value-of select="@colspan" /></xsl:attribute></xsl:if>
            <xsl:if test="@rowspan"><xsl:attribute name="rowspan"><xsl:value-of select="@rowspan" /></xsl:attribute></xsl:if>
            <xsl:if test="@class"><xsl:attribute name="class"><xsl:value-of select="@class" /></xsl:attribute></xsl:if>
            <xsl:apply-templates />
        </th>
    </xsl:template>
<!-- TABLE_CELL -->
    <xsl:template match="ubc//table_cell">
        <td>
            <xsl:attribute name="style">
                <xsl:if test="@text-align">text-align: <xsl:value-of select="@text-align" />; </xsl:if>
                <xsl:if test="@vertical-align">vertical-align: <xsl:value-of select="@vertical-align" />; </xsl:if>
                <xsl:if test="@background-color">background-color: <xsl:value-of select="@background-color" />; </xsl:if>
                <xsl:if test="@width">width: <xsl:value-of select="@width" />; </xsl:if>
                <xsl:if test="@height">height: <xsl:value-of select="@height" />; </xsl:if>
            </xsl:attribute>
            <xsl:if test="@dir"><xsl:attribute name="dir"><xsl:value-of select="@dir" /></xsl:attribute></xsl:if>
            <xsl:if test="@lang"><xsl:attribute name="lang"><xsl:value-of select="@lang" /></xsl:attribute></xsl:if>
            <xsl:if test="@colspan"><xsl:attribute name="colspan"><xsl:value-of select="@colspan" /></xsl:attribute></xsl:if>
            <xsl:if test="@rowspan"><xsl:attribute name="rowspan"><xsl:value-of select="@rowspan" /></xsl:attribute></xsl:if>
            <xsl:if test="@class"><xsl:attribute name="class"><xsl:value-of select="@class" /></xsl:attribute></xsl:if>
            <xsl:apply-templates />
        </td>
    </xsl:template>

<!-- TEXTNODE -->

	<xsl:template match="ubc//text()">
        <xsl:variable name="text">
            <xsl:call-template name="nl2br_win">
                <xsl:with-param name="string_win" select="."/>
            </xsl:call-template>
        </xsl:variable>
        <xsl:call-template name="nl2br_unix">
            <xsl:with-param name="string_unix" select="$text"/>
        </xsl:call-template>
    </xsl:template>

    <xsl:template name="nl2br_win">
        <xsl:param name="string_win" select="string_win"/>
        <xsl:choose>
            <xsl:when test="contains($string_win, '&#13;&#10;')">
                <xsl:value-of select="substring-before($string_win, '&#13;&#10;')"/>
                <br/>
                <xsl:call-template name="nl2br_win">
                    <xsl:with-param name="string_win" select="substring-after($string_win, '&#13;&#10;')"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$string_win"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="nl2br_unix">
        <xsl:param name="string_unix" select="string_unix"/>
        <xsl:choose>
            <xsl:when test="contains($string_unix, '&#13;')">
                <xsl:copy-of select="substring-before($string_unix, '&#13;')"/>
                <br/>
                <xsl:call-template name="nl2br_unix">
                    <xsl:with-param name="string_unix" select="substring-after($string_unix, '&#13;')"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:copy-of select="$string_unix"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

</xsl:stylesheet>