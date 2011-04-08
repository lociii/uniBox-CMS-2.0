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
	<xsl:template match="content">
		<xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='shared_administration']/path" />

		<xsl:if test="preselect_ident">
            <h3 class="invisible"><xsl:value-of select="/root/translations/TRL_PRESELECT" /></h3>
			<xsl:apply-templates select="form">
				<xsl:with-param name="form" select="form[@name=../preselect_ident]" />
			</xsl:apply-templates>
		</xsl:if>

        <xsl:for-each select="administration">

            <xsl:if test="links">
                <div id="administrate_links">
                    <xsl:for-each select="links/link">
                        <a href="{alias}"><xsl:value-of select="text" /></a><br />
                    </xsl:for-each>
                    <br/>
                </div>
            </xsl:if>

            <xsl:if test="datasets/dataset">
                <div class="box_gradient box_gradient_large box_gradient_separator"></div>

                <xsl:if test="pagebrowser">
                    <h3 class="invisible"><xsl:value-of select="/root/translations/TRL_PAGEBROWSER" /></h3>
                    <xsl:apply-templates select="pagebrowser" />
                    <div class="box_gradient box_gradient_large box_gradient_separator"></div>
                </xsl:if>
    
                <form id="administration_{@ident}" action="administration_process" method="post">
                	<div>
						<input type="hidden" name="administration_fallback" id="administration_fallback" value="{@fallback}" />
					</div>

					<div class="table_administrate">
						<h3>
							<span class="invisible"><xsl:value-of select="/root/translations/TRL_ADMINISTRATION_TABLE" /></span>
							<xsl:if test="@label">
								<span class="invisible">: </span>
								<xsl:value-of select="@label" />
							</xsl:if>
						</h3>
						<table class="administrate">
							<xsl:apply-templates select="table_header" />
							<tbody>
								<xsl:for-each select="datasets/dataset">
									<xsl:apply-templates select=".">
										<xsl:with-param name="ident" select="../../@ident" />
										<xsl:with-param name="level" select="0" />
										<xsl:with-param name="position" select="position()" />
										<xsl:with-param name="address" select="position()" />
									</xsl:apply-templates>
								</xsl:for-each>
							</tbody>
						</table>
					</div>
					
					<xsl:if test="multi_options/option != ''">
						<div class="box_gradient box_gradient_large box_gradient_separator"></div>
						<div class="box_blue multi_options">
							<a href="JavaScript:;" onclick="JavaScript:administration_check_all('{@ident}')"><xsl:value-of select="/root/translations/TRL_SELECT_ALL" /></a>
							|
							<a href="JavaScript:;" onclick="JavaScript:administration_uncheck_all('{@ident}')"><xsl:value-of select="/root/translations/TRL_DESELECT_ALL" /></a>
							<span class="invisible"><br/></span>
							<span style="margin-left: 20px;">
								<xsl:for-each select="multi_options/option">
									<input type="image" src="{$theme_base}/media/images/symbols/{image}" name="administration_submit_{link}" alt="{text}" title="{text}" />
								</xsl:for-each>
							</span>
						</div>
					</xsl:if>

                </form>
    
                <xsl:if test="pagebrowser != ''">
                    <div class="box_gradient box_gradient_large box_gradient_separator"></div>
                    <h3 class="invisible"><xsl:value-of select="/root/translations/TRL_PAGEBROWSER" /></h3>
                    <xsl:apply-templates select="pagebrowser" />
                </xsl:if>
    		</xsl:if>
        </xsl:for-each>
    </xsl:template>

	<xsl:template match="dataset">
		<xsl:param name="ident" />
		<xsl:param name="level" />
		<xsl:param name="position" />
		<xsl:param name="address" />
		<xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='shared_administration']/path"/>
		<tr id="dataset_{$ident}_{$address}">
			<xsl:attribute name="class">
				<xsl:choose>
<!-- color only parents
					<xsl:when test="$position mod 2 = 1">highlight_dark</xsl:when>
-->
					<xsl:when test="(count(preceding::dataset) + count(ancestor-or-self::dataset)) mod 2 = 1">highlight_dark</xsl:when>
					<xsl:otherwise>highlight_light</xsl:otherwise>
				</xsl:choose>
				<xsl:if test="@status = -1"> highlight_error</xsl:if>
			</xsl:attribute>

			<xsl:if test="$level > 0">
				<xsl:attribute name="style">display: none;</xsl:attribute>
			</xsl:if>

			<td class="address invisible">
				<xsl:value-of select="translate($address, '_', '.')" />
			</td>

			<xsl:choose>
				<xsl:when test="checkbox != ''">
					<td class="checkbox">
						<input type="checkbox" name="administration_checkbox[]" id="administration_checkbox_{$ident}_{$address}" value="{checkbox}">
							<xsl:if test="@status = 1">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
						</input>
					</td>
				</xsl:when>
				<xsl:when test="/root/content/administration[@ident = $ident]/table_header/checkbox_column != ''">
					<td class="checkbox">&#160;</td>
				</xsl:when>
			</xsl:choose>
			
			<xsl:for-each select="icon">
				<td class="icon">
					<xsl:choose>
						<xsl:when test="image != ''">
							<img src="{$theme_base}/media/images/symbols/icons/{image}" alt="{text}" title="{text}" />
						</xsl:when>
						<xsl:otherwise>&#160;</xsl:otherwise>
					</xsl:choose>
				</td>
			</xsl:for-each>

			<xsl:for-each select="data">
				<xsl:choose>
					<xsl:when test="link != ''">
						<td headers="{$ident}_header_{position()}">
							<a>
								<xsl:attribute name="href"><xsl:value-of select="link" /></xsl:attribute>
								<xsl:if test="onclick != ''"><xsl:attribute name="onclick"><xsl:value-of select="onclick" /></xsl:attribute></xsl:if>
								<xsl:value-of select="value" />
							</a>
						</td>
					</xsl:when>
					<xsl:otherwise>
						<td headers="{$ident}_header_{position()}">
							<xsl:if test="position() = 1">
								<xsl:attribute name="style">padding-left: <xsl:value-of select="$level * 20 + 5" />px;</xsl:attribute>
							</xsl:if>
							
							<xsl:if test="/root/content/administration[@ident = $ident]/@nested_data = 1">
								<xsl:choose>
									<xsl:when test="../dataset and position() = 1">
										<a style="text-decoration: none;" href="JavaScript:;" onclick="JavaScript:administration_toggle('{$theme_base}', '{$ident}', '{$address}', false)">
											<img id="nesting_{$ident}_{$address}" class="nesting" src="{$theme_base}/media/images/expand.gif" border="0" />
										</a>
									</xsl:when>
									<xsl:when test="position() = 1">
										<xsl:attribute name="style">padding-left: <xsl:value-of select="$level * 20 + 22" />px;</xsl:attribute>
									</xsl:when>
								</xsl:choose>
							</xsl:if>

							<xsl:choose>
								<xsl:when test="../checkbox != '' and position() = 1">
									<label for="administration_checkbox_{$ident}_{$address}"><xsl:value-of select="." /></label>
								</xsl:when>
								<xsl:otherwise>
									<span><xsl:value-of select="." /></span>
								</xsl:otherwise>
							</xsl:choose>
						</td>
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

		<xsl:for-each select="dataset">
			<xsl:apply-templates select=".">
				<xsl:with-param name="ident" select="$ident" />
				<xsl:with-param name="level" select="$level + 1" />
				<xsl:with-param name="position" select="$position" />
				<xsl:with-param name="address" select="concat($address, '_', position())" />
			</xsl:apply-templates>
		</xsl:for-each>
	</xsl:template>
 
    <xsl:template match="table_header">
        <xsl:variable name="theme_base" select="/root/unibox/theme_base[@template='shared_administration']/path"/>
        <thead>
            <tr>
            	<th class="address">
	            	<div class="invisible">
                   		<xsl:value-of select="/root/translations/TRL_ADMINISTRATION_MULTI_RANKING_DIGITS" />
	            	</div>
	            </th>
            		
                <xsl:if test="checkbox_column != ''">
                    <th class="checkbox">
                    	<div class="invisible">
                    		<xsl:value-of select="/root/translations/TRL_ADMINISTRATION_MULTI_CHECKBOX" />
                    	</div>
                    </th>
                </xsl:if>

                <xsl:for-each select="icon_column">
                    <th class="blind" />
                </xsl:for-each>

                <xsl:for-each select="column">
                    <th id="{../../@ident}_header_{position()}">
                        <xsl:if test="width != 0">
							<xsl:attribute name="style">width: <xsl:value-of select="width" />%;</xsl:attribute>
						</xsl:if>

                        <xsl:choose>
                            <xsl:when test="sort = 'ASC'">
                                <a href="{../alias}/sort/{ident}/order/DESC" title="{/root/translations/TRL_COL_SORT_DESC}"><xsl:value-of select="name" /></a><span class="invisible"></span>&#160;<img src="{$theme_base}/media/images/{image}.gif" alt="{/root/translations/TRL_COL_IS_SORTED_ASC}" title="{/root/translations/TRL_COL_IS_SORTED_ASC}" />
                            </xsl:when>
                            <xsl:otherwise>
                                <a href="{../alias}/sort/{ident}/order/ASC" title="{/root/translations/TRL_COL_SORT_ASC}"><xsl:value-of select="name" /></a><span class="invisible"></span>&#160;<img src="{$theme_base}/media/images/{image}.gif" alt="{/root/translations/TRL_COL_IS_SORTED_DESC}" title="{/root/translations/TRL_COL_IS_SORTED_DESC}" />
                            </xsl:otherwise>
                        </xsl:choose>
                    </th>
                </xsl:for-each>

                <xsl:for-each select="option_column">
                    <th class="blind" />
                </xsl:for-each>
            </tr>
        </thead>
    </xsl:template>

</xsl:stylesheet>