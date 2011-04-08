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
	<xsl:template match="form">
		<xsl:if test="error != ''">
			<xsl:for-each select="error">
				<div class="form_error">
					<xsl:value-of select="." />
				</div>
			</xsl:for-each>
			<br />
		</xsl:if>
		
		<form id="form_{@name}" action="{@action}" method="{@method}">
			<xsl:if test="@encoding">
				<xsl:attribute name="enctype"><xsl:value-of select="@encoding" /></xsl:attribute>
			</xsl:if>
			<xsl:variable name="form_name" select="@name" />
			<xsl:if test="@info_require != ''">
				<div><xsl:value-of select="/root/translations/TRL_NOTICE_FORM_FIELDS_REQUIRED" /><br/><br/></div>
			</xsl:if>
			<xsl:apply-templates select="input">
				<xsl:with-param name="form_name" select="$form_name" />
			</xsl:apply-templates>
		</form>
	</xsl:template>

	<xsl:template match="form//input">
		<xsl:param name="form_name" />
		<xsl:variable name="class">
			<xsl:choose>
				<xsl:when test="error = 1">
					<xsl:choose>
						<xsl:when test="@type = 'text'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'color'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'password'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'file'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'textarea'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'checkbox'">form_label form_error</xsl:when>
                        <xsl:when test="@type = 'editor'">form_legend form_error</xsl:when>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="@error = 1">
					<xsl:choose>
						<xsl:when test="@type = 'fieldset'">form_legend form_error</xsl:when>
						<xsl:when test="@type = 'select'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'select_multi'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'radio'">form_legend form_error</xsl:when>
						<xsl:when test="@type = 'checkbox_multi'">form_legend form_error</xsl:when>
					</xsl:choose>
				</xsl:when>
    			<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="@type = 'fieldset'">form_legend</xsl:when>
						<xsl:when test="@type = 'text'">form_label</xsl:when>
						<xsl:when test="@type = 'color'">form_label</xsl:when>
						<xsl:when test="@type = 'password'">form_label</xsl:when>
						<xsl:when test="@type = 'file'">form_label</xsl:when>
						<xsl:when test="@type = 'textarea'">form_label</xsl:when>
						<xsl:when test="@type = 'checkbox'">form_label</xsl:when>
                        <xsl:when test="@type = 'checkbox_multi'">form_legend</xsl:when>
						<xsl:when test="@type = 'select'">form_label</xsl:when>
						<xsl:when test="@type = 'select_multi'">form_label</xsl:when>
						<xsl:when test="@type = 'radio'">form_legend</xsl:when>
                        <xsl:when test="@type = 'editor'">form_legend</xsl:when>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:choose>
<!-- fieldset -->
			<xsl:when test="@type = 'fieldset'">
				<fieldset class="form_fieldset">
					<legend class="{$class}">
						<xsl:value-of select="@descr" />
						<xsl:if test="@required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
					</legend>
					<xsl:apply-templates select="input">
						<xsl:with-param name="form_name" select="$form_name" />
					</xsl:apply-templates>
				</fieldset>
			</xsl:when>
<!-- text -->
			<xsl:when test="@type = 'text'">
				<div class="form_container">
					<div class="{$class}">
						<label for="{name}">
							<xsl:value-of select="label" />
							<xsl:if test="required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
						</label>
					</div>
					<div class="form_input">
						<input type="text" class="form_input_text" name="{name}" id="{name}" value="{value}">
							<xsl:if test="maxlength">
								<xsl:attribute name="maxlength"><xsl:value-of select="maxlength"/></xsl:attribute>
							</xsl:if>
							<xsl:if test="width">
								<xsl:attribute name="size"><xsl:value-of select="width"/></xsl:attribute>
							</xsl:if>
							<xsl:if test="disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
							<xsl:if test="help">
								<xsl:attribute name="title"><xsl:value-of select="help" /></xsl:attribute>
							</xsl:if>
						</input>
					</div>
					<xsl:apply-templates select="comment" />
				</div>
			</xsl:when>
<!-- color -->
			<xsl:when test="@type = 'color'">
				<div class="form_container">
					<div class="{$class}">
						<label for="{name}">
							<xsl:value-of select="label" />
							<xsl:if test="required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
						</label>
					</div>
					<div class="form_input">
						<input type="text" class="form_input_text" name="{name}" id="{name}" maxlength="6" value="{value}" size="10" onkeyup="JavaScript: set_color('{$form_name}', '{name}', this.value);">
							<xsl:if test="disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
							<xsl:if test="help">
								<xsl:attribute name="title"><xsl:value-of select="help" /></xsl:attribute>
							</xsl:if>
						</input>
					</div>
					<div class="color_preview" id="color_preview_{name}">
						<xsl:if test="value"><xsl:attribute name="style">background-color: #<xsl:value-of select="value" />;</xsl:attribute></xsl:if>
						<xsl:if test="name = ''">?</xsl:if>
					</div>
					<xsl:call-template name="form_color_picker">
						<xsl:with-param name="field_name" select="name" />
						<xsl:with-param name="form_name" select="$form_name" />
					</xsl:call-template>
				</div>
			</xsl:when>
<!-- password -->
			<xsl:when test="@type = 'password'">
				<div class="form_container">
					<div class="{$class}">
						<label for="{name}">
							<xsl:value-of select="label" />
							<xsl:if test="required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
						</label>
					</div>
					<div class="form_input">
						<input type="password" class="form_input_password" name="{name}" id="{name}" value="{value}">
							<xsl:if test="maxlength">
								<xsl:attribute name="maxlength"><xsl:value-of select="maxlength"/></xsl:attribute>
							</xsl:if>
							<xsl:if test="width">
								<xsl:attribute name="size"><xsl:value-of select="width"/></xsl:attribute>
							</xsl:if>
							<xsl:if test="disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
							<xsl:if test="help">
								<xsl:attribute name="title"><xsl:value-of select="help" /></xsl:attribute>
							</xsl:if>
						</input>
                        <xsl:if test="@comment != ''">
                            <span class="form_comment">
                                <xsl:value-of select="@comment" />
                            </span>
                        </xsl:if>
					</div>
					<xsl:apply-templates select="comment" />
				</div>
			</xsl:when>
<!-- file -->
			<xsl:when test="@type = 'file'">
				<div class="form_container">
					<div class="{$class}" style="height: 2em;">
						<label for="{name}">
							<xsl:value-of select="label" />
							<xsl:if test="required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
						</label>
					</div>
					<div class="form_input">
						<input type="file" class="form_input_file" name="{name}" id="{name}">
							<xsl:if test="@maxlength">
								<xsl:attribute name="maxlength"><xsl:value-of select="@maxlength" /></xsl:attribute>
							</xsl:if>
							<xsl:if test="accept">
								<xsl:attribute name="accept"><xsl:value-of select="accept" /></xsl:attribute>
							</xsl:if>
							<xsl:if test="disabled = 1">
								<xsl:attribute name="disabled"><xsl:value-of select="disabled" /></xsl:attribute>
							</xsl:if>
							<xsl:if test="help">
								<xsl:attribute name="title"><xsl:value-of select="help" /></xsl:attribute>
							</xsl:if>
						</input>
					</div>
					<xsl:apply-templates select="comment" />
				</div>
			</xsl:when>
<!-- textarea -->
			<xsl:when test="@type = 'textarea'">
				<div class="form_container">
					<div class="{$class}">
						<label for="{name}">
							<xsl:value-of select="label" />
							<xsl:if test="required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
						</label>
					</div>
					<div class="form_input">
						<textarea class="form_input_textarea" name="{name}" id="{name}">
							<xsl:if test="width">
								<xsl:attribute name="cols"><xsl:value-of select="width"/></xsl:attribute>
							</xsl:if>
							<xsl:if test="height">
								<xsl:attribute name="rows"><xsl:value-of select="height"/></xsl:attribute>
							</xsl:if>
							<xsl:if test="maxlength">
								<xsl:attribute name="maxlength"><xsl:value-of select="maxlength"/></xsl:attribute>
							</xsl:if>
							<xsl:if test="disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
							<xsl:if test="help">
								<xsl:attribute name="title"><xsl:value-of select="help" /></xsl:attribute>
							</xsl:if>
							<xsl:value-of select="value" />
						</textarea>
					</div>
 					<xsl:apply-templates select="comment" />
				</div>
			</xsl:when>
<!-- editor -->
			<xsl:when test="@type = 'editor'">
				<fieldset class="form_fieldset">
					<legend class="{$class}">
						<xsl:value-of select="label" />
						<xsl:if test="required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
					</legend>								
					<textarea class="editor" name="{name}" id="{name}" cols="50" rows="10">
						<xsl:attribute name="style">width: 100%; <xsl:if test="height">height: <xsl:value-of select="height" />px;</xsl:if></xsl:attribute>
						<xsl:value-of select="value"/>
					</textarea>
				</fieldset>
			</xsl:when>
<!-- show editor -->
            <xsl:when test="@type = 'editors_show'">
                <xsl:variable name="elements"><xsl:for-each select="editor"><xsl:value-of select="." /><xsl:if test="position() != last()">,</xsl:if></xsl:for-each></xsl:variable>
                <xsl:variable name="themes">{<xsl:for-each select="theme"><xsl:value-of select="ident" />_<xsl:value-of select="subtheme/ident" />: "<xsl:value-of select="descr" /> - <xsl:value-of select="subtheme/descr" />"<xsl:if test="position() != last()">,</xsl:if></xsl:for-each>}</xsl:variable>
                <script type="text/javascript" src="{/root/unibox/html_base}{editor_dir}tiny_mce.js"></script>
                <script type="text/javascript">
                    <xsl:comment>
                        tinyMCE.init
                        ({
                            language : "de",
                            mode : "exact",
                            elements : "<xsl:value-of select="$elements" />",
                            theme : "advanced",
                            plugins: "<xsl:value-of select="plugins" />",
                            theme_advanced_buttons1: "<xsl:value-of select="buttons1" />",
                            theme_advanced_buttons2: "<xsl:value-of select="buttons2" />",
                            theme_advanced_buttons3: "<xsl:value-of select="buttons3" />",
                            extended_valid_elements : "hr[class|width|size|noshade],span[class|align|style],acronym[title|lang],table[border|cellspacing|cellpadding|width|height|class|align|summary|style|dir|id|lang],img[longdesc|class|src|border=0|alt|title|width|height|align|themes|curtheme|style|zoom]",
                            theme_advanced_toolbar_location : "top",
                            theme_advanced_toolbar_align : "left",
                            theme_advanced_statusbar_location : "bottom",
                            force_br_newlines : true,
                            force_p_newlines : false,
                            fix_list_elements : true,
                            entity_encoding : "named",
                            entities : "60,lt,62,gt",
                            inline_styles : true,
                            paste_use_dialog : false,
                            theme_advanced_resizing : false,
                            theme_advanced_resize_horizontal : false,
                            convert_urls : false,
                            document_base_path : "<xsl:value-of select="/root/unibox/html_base" />",
                            external_link_list_url : "<xsl:value-of select="/root/unibox/html_base" />framework_editor_link_list",
                            flash_external_list_url : "<xsl:value-of select="/root/unibox/html_base" />framework_editor_link_list",
                            unibox_themes : <xsl:value-of select="$themes"/>
                        });
                    </xsl:comment>
                </script>
            </xsl:when>
<!-- checkbox -->
			<xsl:when test="@type = 'checkbox'">
				<div class="form_container">
					<div class="{$class}">
						<label for="{name}">
							<xsl:value-of select="label" />
							<xsl:if test="required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
						</label>
					</div>
					<div class="form_input">
						<input type="checkbox" class="form_input_checkbox" name="{name}" id="{name}" value="1">
							<xsl:if test="checked = 1">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
							<xsl:if test="disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
							<xsl:if test="help">
								<xsl:attribute name="title"><xsl:value-of select="help" /></xsl:attribute>
							</xsl:if>
						</input>
					</div>
 					<xsl:apply-templates select="comment" />
				</div>
			</xsl:when>
<!-- checkbox_multi -->
			<xsl:when test="@type = 'checkbox_multi'">
				<xsl:variable name="name" select="@name" />
				<xsl:variable name="disabled" select="@disabled" />
				<fieldset class="form_fieldset">
					<legend class="{$class}">
						<xsl:value-of select="@descr" />
						<xsl:if test="@required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
					</legend>
					<xsl:apply-templates select="input[@type = 'option']">
						<xsl:with-param name="type">checkbox_multi</xsl:with-param>
						<xsl:with-param name="name" select="$name" />
						<xsl:with-param name="disabled" select="$disabled" />
					</xsl:apply-templates>
					<br />
					<xsl:if test="count(input[@type = 'option']) > 1">
						<div class="form_container">
							<a href="JavaScript: set_checked_value('form_{$form_name}', '{@name}', true)"><xsl:value-of select="/root/translations/TRL_CHECK_ALL" /></a> / <a href="JavaScript: set_checked_value('form_{$form_name}', '{@name}', false)"><xsl:value-of select="/root/translations/TRL_UNCHECK_ALL" /></a>
						</div>
					</xsl:if>
					<xsl:apply-templates select="input[@type != 'option']" />
				</fieldset>
			</xsl:when>
<!-- radio -->
			<xsl:when test="@type = 'radio'">
				<xsl:variable name="name" select="@name" />
				<xsl:variable name="disabled" select="@disabled" />
				<fieldset class="form_fieldset">
					<legend class="{$class}">
						<xsl:value-of select="@descr" />
						<xsl:if test="@required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
					</legend>								
					<xsl:apply-templates select="input[@type = 'option']">
						<xsl:with-param name="type">radio</xsl:with-param>
						<xsl:with-param name="name" select="$name" />
						<xsl:with-param name="disabled" select="$disabled" />
					</xsl:apply-templates>
					<xsl:apply-templates select="input[@type != 'option']">
						<xsl:with-param name="form_name" select="$form_name" />
					</xsl:apply-templates>
				</fieldset>
			</xsl:when>
<!-- select -->
			<xsl:when test="@type = 'select'">
				<xsl:variable name="name" select="@name" />
				<xsl:variable name="disabled" select="@disabled" />
				<div class="form_container">
					<div class="{$class}">
						<label for="{@name}">
							<xsl:value-of select="@descr" />
							<xsl:if test="@required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
						</label>
					</div>
					<div class="form_input">
						<select class="form_input_select" name="{@name}" id="{@name}">
							<xsl:if test="size">
								<xsl:attribute name="size"><xsl:value-of select="size" /></xsl:attribute>
							</xsl:if>
							<xsl:if test="@disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
							<xsl:if test="@help">
								<xsl:attribute name="title"><xsl:value-of select="@help" /></xsl:attribute>
							</xsl:if>
							<xsl:apply-templates select="input[@type = 'option' or @type = 'optgroup']">
								<xsl:with-param name="type">select</xsl:with-param>
								<xsl:with-param name="name" select="$name" />
								<xsl:with-param name="disabled" select="0" />
							</xsl:apply-templates>
						</select>
					</div>
 					<xsl:apply-templates select="comment" />
				</div>
			</xsl:when>
<!-- buttonset -->
			<xsl:when test="@type = 'buttonset'">
				<div class="form_container_buttons {@class}">
					<xsl:apply-templates select="input">
						<xsl:with-param name="form_name" select="$form_name" />
					</xsl:apply-templates>
				</div>
			</xsl:when>
<!-- plaintext -->
            <xsl:when test="@type = 'plaintext'">
                <div>
                    <xsl:apply-templates select="ubc" />
                </div>
            </xsl:when>
<!-- newline -->
			<xsl:when test="@type = 'newline'">
				<br/>
			</xsl:when>
<!-- hidden -->
            <xsl:when test="@type = 'hidden'">
            	<div>
	                <input type="hidden" name="{@name}" id="{@name}" value="{value}" />
				</div>
            </xsl:when>
<!-- submit -->
			<xsl:when test="@type = 'submit'">
				<input type="submit" class="form_input_submit" name="{name}" id="{name}" value="{label}">
					<xsl:if test="disabled = 1">
						<xsl:attribute name="disabled">disabled</xsl:attribute>
						<xsl:attribute name="class">form_input_submit form_input_disabled</xsl:attribute>
					</xsl:if>
					<xsl:if test="help">
						<xsl:attribute name="title"><xsl:value-of select="help" /></xsl:attribute>
					</xsl:if>
				</input>
			</xsl:when>
<!-- cancel -->
			<xsl:when test="@type = 'cancel'">
				<input type="submit" class="form_input_cancel" name="{name}" id="{name}" value="{label}">
					<xsl:if test="disabled = 1">
						<xsl:attribute name="disabled">disabled</xsl:attribute>
						<xsl:attribute name="class">form_input_cancel form_input_disabled</xsl:attribute>
					</xsl:if>
					<xsl:if test="help">
						<xsl:attribute name="title"><xsl:value-of select="help" /></xsl:attribute>
					</xsl:if>
				</input>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="form//input[@type = 'option' or @type = 'optgroup']">
		<xsl:param name="type" />
		<xsl:param name="name" />
		<xsl:param name="disabled" />
		<xsl:choose>
			<xsl:when test="$type = 'checkbox_multi'">
				<div class="form_container">
					<div class="form_container_checkbox">
						<input type="checkbox" class="form_input_checkbox_multi" name="{$name}[]" id="{$name}_{count}" value="{value}">
							<xsl:if test="selected = 1">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
							<xsl:if test="$disabled = 1 or @disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
						</input>
						<label for="{$name}_{count}">
							<xsl:value-of select="label" />
						</label>
					</div>
				</div>
			</xsl:when>
			<xsl:when test="$type = 'radio'">
				<div class="form_container">
					<div class="form_container_radio">
						<input type="radio" class="form_input_radio" name="{$name}" id="{$name}_{count}" value="{value}">
							<xsl:if test="selected = 1">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
							<xsl:if test="$disabled = 1 or @disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
						</input>
						<label for="{$name}_{count}">
							<xsl:value-of select="label" />
						</label>
					</div>
				</div>
			</xsl:when>
			<xsl:when test="$type = 'select'">
				<xsl:choose>
					<xsl:when test="@type = 'optgroup'">
						<optgroup label="{@descr}">
							<xsl:apply-templates select="input">
								<xsl:with-param name="type" select="$type" />
								<xsl:with-param name="name" select="$name" />
								<xsl:with-param name="disabled" select="$disabled" />
							</xsl:apply-templates>
						</optgroup>
					</xsl:when>
					<xsl:when test="@type = 'option'">
						<option value="{value}">
							<xsl:if test="selected = 1">
								<xsl:attribute name="selected">selected</xsl:attribute>
							</xsl:if>
							<xsl:if test="$disabled = 1 or @disabled = 1">
								<xsl:attribute name="disabled">disabled</xsl:attribute>
							</xsl:if>
							<xsl:value-of select="label" />
						</option>
					</xsl:when>
				</xsl:choose>
			</xsl:when>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="form//comment">
		<div class="form_comment">
			<span class="form_comment">
				<xsl:if test="label != ''">
					<strong><xsl:value-of select="label" />: </strong>
				</xsl:if>
				<xsl:value-of select="value" />
			</span>
			<xsl:if test="position() != last()"><br /></xsl:if>
		</div>
		<div class="form_comment_clear"></div>
	</xsl:template>
</xsl:stylesheet>