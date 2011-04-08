<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     06.04.2005  jn      loginform testfile
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:php="http://php.net/xsl">

	<xsl:template name="content">
		<xsl:variable name="theme_base" select="/root/theme_base[@template='articles_show_more']/path"/>
        <div class="box_gradient">
            <span class="line_t1"></span><span class="line_t2"></span><span class="line_t3"></span><span class="line_t4"></span>
            <div class="content">
                <h2><xsl:value-of select="/root/translations/TRL_ARTICLES_CATEGORY_OVERVIEW" /></h2>
            </div>
            <span class="line_b4"></span><span class="line_b3"></span><span class="line_b2"></span><span class="line_b1"></span>
        </div>

        <div class="spacer">
            &#160;
        </div>

		<xsl:if test="/root/content/article != ''">
	        <div class="box_white">
	            <span class="line_t1"></span><span class="line_t2"></span><span class="line_t3"></span><span class="line_t4"></span>
	            <div class="content">
        			<table class="overview">
		            	<xsl:call-template name="table_sort_header" />
						<tbody>
							<xsl:for-each select="/root/content/article">
								<xsl:variable name="class">
					    			<xsl:choose>
										<xsl:when test="position() mod 2 = 1">highlight_dark</xsl:when>
										<xsl:otherwise>highlight_light</xsl:otherwise>
					    			</xsl:choose>
					    		</xsl:variable>
					    		<tr>
				    				<xsl:if test="date != ''">
										<td class="{$class}"><xsl:value-of select="date" /></td>
									</xsl:if>
				    				<xsl:if test="author != ''">
										<td class="{$class}"><xsl:value-of select="author" /></td>
									</xsl:if>
					    			<td class="{$class}"><xsl:value-of select="title" /></td>
					    			<td class="{$class} symbol"><a href="{alias}"><img src="{$theme_base}/media/images/symbols/details.gif" alt="Details" title="Details" /></a></td>
						    	</tr>
			    			</xsl:for-each>
			    		</tbody>
		    		</table>
		    		
			        <xsl:call-template name="page_browse" />
		    		
	            </div>
	            <span class="line_b4"></span><span class="line_b3"></span><span class="line_b2"></span><span class="line_b1"></span>
	        </div>
        </xsl:if>
	</xsl:template>

</xsl:stylesheet>