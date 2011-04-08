<?xml version="1.0" encoding="UTF-8"?>

<!--
#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
### 0.1     31.03.2005  jn      1st release
### 0.11	04.04.2005	jn		changed button handling
### 0.12    11.10.2005  jn      changed editor behaviour
###
#################################################################################################
-->

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template name="form_parse">
		<xsl:param name="form" />
		<xsl:if test="$form/error != ''">
			<xsl:for-each select="$form/error">
				<div class="form_error">
					<xsl:value-of select="." />
				</div>
			</xsl:for-each>
			<br />
		</xsl:if>

		<form id="{$form/@name}" action="{$form/@action}" method="{$form/@method}" enctype="{$form/@encoding}">
			<div class="box_white">
				<xsl:for-each select="$form/input">
					<xsl:call-template name="form_parse_input" />
				</xsl:for-each>
			</div>
			<xsl:for-each select="$form/input">
				<xsl:if test="@type = 'buttonset'">
					<xsl:variable name="class_buttonset" select="@class" />
					<div class="form_container_buttons">
						<xsl:for-each select="input">
							<xsl:call-template name="form_parse_button" />
						</xsl:for-each>
					</div>
				</xsl:if>
			</xsl:for-each>
		</form>
	</xsl:template>

	<xsl:template name="form_parse_input">
		<xsl:variable name="class">
			<xsl:choose>
				<xsl:when test="error = 1">
					<xsl:choose>
						<xsl:when test="@type = 'text'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'password'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'file'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'textarea'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'checkbox'">form_label form_error</xsl:when>
					</xsl:choose>
				</xsl:when>
				<xsl:when test="@error = 1">
					<xsl:choose>
						<xsl:when test="@type = 'fieldset'">form_legend form_error</xsl:when>
						<xsl:when test="@type = 'select'">form_label form_error</xsl:when>
						<xsl:when test="@type = 'radio'">form_legend form_error</xsl:when>
						<xsl:when test="@type = 'checkbox_multi'">form_legend form_error</xsl:when>
					</xsl:choose>
				</xsl:when>
    			<xsl:otherwise>
					<xsl:choose>
						<xsl:when test="@type = 'fieldset'">form_legend</xsl:when>
						<xsl:when test="@type = 'text'">form_label</xsl:when>
						<xsl:when test="@type = 'password'">form_label</xsl:when>
						<xsl:when test="@type = 'file'">form_label</xsl:when>
						<xsl:when test="@type = 'textarea'">form_label</xsl:when>
						<xsl:when test="@type = 'checkbox'">form_label</xsl:when>
						<xsl:when test="@type = 'select'">form_label</xsl:when>
						<xsl:when test="@type = 'radio'">form_legend</xsl:when>
					</xsl:choose>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<xsl:choose>
<!-- fieldset -->
			<xsl:when test="@type = 'fieldset'">
				<fieldset class="form_fieldset">
					<xsl:choose>
						<xsl:when test="@required = 1">
							<legend class="{$class}"><xsl:value-of select="@descr" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></legend>
						</xsl:when>
						<xsl:otherwise>
							<legend class="{$class}"><xsl:value-of select="@descr" /></legend>
						</xsl:otherwise>
					</xsl:choose>								
					<xsl:for-each select="input">
						<xsl:call-template name="form_parse_input" />
					</xsl:for-each>
				</fieldset>
			</xsl:when>
<!-- text -->
			<xsl:when test="@type = 'text'">
				<xsl:variable name="input_name" select="name" />
				<xsl:variable name="input_value" select="value" />
				<xsl:variable name="input_size" select="width" />
				<xsl:variable name="input_maxlength" select="maxlength" />
				<div class="form_container">
					<div class="{$class}">
						<label for="{$input_name}">
							<xsl:value-of select="label" />
							<xsl:if test="date_format != ''">&#160;<xsl:value-of select="date_format" /></xsl:if>
							<xsl:if test="time_format != ''">&#160;<xsl:value-of select="time_format" /></xsl:if>
							<xsl:if test="required = 1">&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></xsl:if>
						</label>
					</div>

					<div class="form_input">
						<xsl:choose>
							<xsl:when test="$input_maxlength != ''">
								<xsl:choose>
									<xsl:when test="$input_size != ''">
										<input type="text" class="form_input_text" name="{$input_name}" id="{$input_name}" value="{$input_value}" maxlength="{$input_maxlength}" size="{$input_size}" />
									</xsl:when>
									<xsl:otherwise>
										<input type="text" class="form_input_text" name="{$input_name}" id="{$input_name}" value="{$input_value}" maxlength="{$input_maxlength}" />
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="$input_size != ''">
										<input type="text" class="form_input_text" name="{$input_name}" id="{$input_name}" value="{$input_value}" size="{$input_size}" />
									</xsl:when>
									<xsl:otherwise>
										<input type="text" class="form_input_text" name="{$input_name}" id="{$input_name}" value="{$input_value}" />
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>
<!-- password -->
			<xsl:when test="@type = 'password'">
				<xsl:variable name="input_name" select="name" />
				<xsl:variable name="input_value" select="value" />
				<xsl:variable name="input_size" select="width" />
				<xsl:variable name="input_maxlength" select="maxlength" />
				<div class="form_container">
					<div class="{$class}">
						<label for="{$input_name}">
							<xsl:choose>
								<xsl:when test="required = 1">
									<xsl:value-of select="label" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="label" />
								</xsl:otherwise>
							</xsl:choose>
						</label>
					</div>
					<div class="form_input">
						<xsl:choose>
							<xsl:when test="$input_maxlength != ''">
								<xsl:choose>
									<xsl:when test="$input_size != ''">
										<input type="password" class="form_input_password" name="{$input_name}" id="{$input_name}" value="{$input_value}" maxlength="{$input_maxlength}" size="{$input_size}" />
									</xsl:when>
									<xsl:otherwise>
										<input type="password" class="form_input_password" name="{$input_name}" id="{$input_name}" value="{$input_value}" maxlength="{$input_maxlength}" />
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="$input_size != ''">
										<input type="password" class="form_input_password" name="{$input_name}" id="{$input_name}" value="{$input_value}" size="{$input_size}" />
									</xsl:when>
									<xsl:otherwise>
										<input type="password" class="form_input_password" name="{$input_name}" id="{$input_name}" value="{$input_value}" />
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>
<!-- file -->
			<xsl:when test="@type = 'file'">
				<xsl:variable name="input_name" select="name" />
				<xsl:variable name="input_accept" select="accept" />
				<xsl:variable name="input_maxlength" select="maxlength" />
				<div class="form_container">
					<div class="{$class}" style="height: 2em;">
						<label for="{$input_name}">
							<xsl:choose>
								<xsl:when test="required = 1">
									<xsl:value-of select="label" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="label" />
								</xsl:otherwise>
							</xsl:choose>
						</label>
					</div>
					<div class="form_input">
						<xsl:choose>
							<xsl:when test="$input_maxlength != ''">
								<b><xsl:value-of select="/root/translations/TRL_MAX_FILE_SIZE" />:&#160;</b> <xsl:value-of select="hr_file_size" /> <xsl:value-of select="hr_size_unit" /><br />
								<xsl:choose>
									<xsl:when test="$input_accept != ''">
										<input type="hidden" name="MAX_FILE_SIZE" value="{$input_maxlength}" />
										<input type="file" class="form_input_file" name="{$input_name}" id="{$input_name}" maxlength="{$input_maxlength}" accept="{$input_accept}" />
									</xsl:when>
									<xsl:otherwise>
										<input type="hidden" name="MAX_FILE_SIZE" value="{$input_maxlength}" />
										<input type="file" class="form_input_file" name="{$input_name}" id="{$input_name}" maxlength="{$input_maxlength}" />
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="$input_accept != ''">
										<input type="file" class="form_input_file" name="{$input_name}" id="{$input_name}" accept="{$input_accept}" />
									</xsl:when>
									<xsl:otherwise>
										<input type="file" class="form_input_file" name="{$input_name}" id="{$input_name}" />
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>
<!-- textarea -->
			<xsl:when test="@type = 'textarea'">
				<xsl:variable name="input_name" select="name" />
				<xsl:variable name="input_cols" select="width" />
				<xsl:variable name="input_rows" select="height" />
				<xsl:variable name="input_maxlength" select="maxlength" />
				<div class="form_container">
					<div class="{$class}">
						<label for="{$input_name}">
							<xsl:choose>
								<xsl:when test="required = 1">
									<xsl:value-of select="label" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="label" />
								</xsl:otherwise>
							</xsl:choose>
						</label>
					</div>
					<div class="form_input">
						<xsl:choose>
							<xsl:when test="$input_cols != ''">
								<xsl:choose>
									<xsl:when test="$input_rows != ''">
										<xsl:choose>
											<xsl:when test="$input_maxlength != ''">
												<textarea class="form_input_textarea" name="{$input_name}" id="{$input_name}" cols="{$input_cols}" rows="{$input_rows}" maxlength="{$input_maxlength}"><xsl:value-of select="value"/></textarea>
											</xsl:when>
											<xsl:otherwise>
												<textarea class="form_input_textarea" name="{$input_name}" id="{$input_name}" cols="{$input_cols}" rows="{$input_rows}"><xsl:value-of select="value"/></textarea>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>
										<xsl:choose>
											<xsl:when test="$input_maxlength != ''">
												<textarea class="form_input_textarea" name="{$input_name}" id="{$input_name}" cols="{$input_cols}" maxlength="{$input_maxlength}"><xsl:value-of select="value"/></textarea>
											</xsl:when>
											<xsl:otherwise>
												<textarea class="form_input_textarea" name="{$input_name}" id="{$input_name}" cols="{$input_cols}"><xsl:value-of select="value"/></textarea>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="$input_rows != ''">
										<xsl:choose>
											<xsl:when test="$input_maxlength != ''">
												<textarea class="form_input_textarea" name="{$input_name}" id="{$input_name}" rows="{$input_rows}" maxlength="{$input_maxlength}"><xsl:value-of select="value"/></textarea>
											</xsl:when>
											<xsl:otherwise>
												<textarea class="form_input_textarea" name="{$input_name}" id="{$input_name}" rows="{$input_rows}"><xsl:value-of select="value"/></textarea>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>
										<xsl:choose>
											<xsl:when test="$input_maxlength != ''">
												<textarea class="form_input_textarea" name="{$input_name}" id="{$input_name}" maxlength="{$input_maxlength}"><xsl:value-of select="value"/></textarea>
											</xsl:when>
											<xsl:otherwise>
												<textarea class="form_input_textarea" name="{$input_name}" id="{$input_name}"><xsl:value-of select="value"/></textarea>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>
<!-- editor -->
			<xsl:when test="@type = 'editor'">
				<fieldset class="form_fieldset">
					<xsl:choose>
						<xsl:when test="error = 1">
							<xsl:choose>
								<xsl:when test="required = 1">
									<legend class="form_legend form_error" for="{name}"><xsl:value-of select="label" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></legend>
								</xsl:when>
								<xsl:otherwise>
									<legend class="form_legend form_error" for="{name}"><xsl:value-of select="label" /></legend>
								</xsl:otherwise>
							</xsl:choose>								
						</xsl:when>
						<xsl:otherwise>
							<xsl:choose>
								<xsl:when test="required = 1">
									<legend><xsl:value-of select="label" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></legend>
								</xsl:when>
								<xsl:otherwise>
									<legend><xsl:value-of select="label" /></legend>
								</xsl:otherwise>
							</xsl:choose>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:choose>
						<xsl:when test="height != ''">
							<xsl:variable name="height" select="height" />
							<textarea class="editor" name="{name}" id="{name}" style="width: 100%; height: {height}px;"><xsl:value-of select="value"/></textarea>
						</xsl:when>
						<xsl:otherwise>
							<textarea class="editor" name="{name}" id="{name}" style="width: 100%;"><xsl:value-of select="value"/></textarea>
						</xsl:otherwise>
					</xsl:choose>
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
                            language : "<xsl:value-of select="language" />",
                            mode : "exact",
                            elements : "<xsl:value-of select="$elements" />",
                            theme : "advanced",
                            plugins : "table,acronym,media,newsletter,advhr,advlink,emotions,insertdatetime,preview,zoom,flash,searchreplace,print,paste,directionality,fullscreen,noneditable,contextmenu",
                            theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,insertdate,inserttime,separator,forecolor,separator,acronym",
<!--
                            theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,insertdate,inserttime,separator,forecolor,separator,acronym",
-->
                            theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator,bullist,numlist,separator,undo,redo,separator,link,unlink,anchor,image,newsletter,cleanup,help,code",
                            theme_advanced_buttons3 : "tablecontrols,separator,removeformat,visualaid,separator,sub,sup,separator,charmap,iespell,flash,separator,print,separator,fullscreen",
                            theme_advanced_toolbar_location : "top",
                            theme_advanced_toolbar_align : "left",
                            theme_advanced_statusbar_location : "bottom",
                            extended_valid_elements : "hr[class|width|size|noshade],span[class|align|style],acronym[title|lang],table[border|cellspacing|cellpadding|width|height|class|align|summary|style|dir|id|lang],img[longdesc|class|src|border=0|alt|title|width|height|align|themes|curtheme|style|zoom]",
                            force_br_newlines : true,
                            force_p_newlines : false,
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
				<xsl:variable name="input_name" select="name" />
				<xsl:variable name="input_checked" select="checked" />
				<div class="form_container">
					<div class="{$class}">
						<label for="{$input_name}">
							<xsl:choose>
								<xsl:when test="required = 1">
									<xsl:value-of select="label" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="label" />
								</xsl:otherwise>
							</xsl:choose>
						</label>
					</div>
					<div class="form_input">
						<xsl:choose>
							<xsl:when test="$input_checked = 1">
								<input type="checkbox" class="form_input_checkbox" name="{$input_name}" id="{$input_name}" value="1" checked="checked" />
							</xsl:when>
							<xsl:otherwise>
								<input type="checkbox" class="form_input_checkbox" name="{$input_name}" id="{$input_name}" value="1" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>
<!-- checkbox_multi -->
			<xsl:when test="@type = 'checkbox_multi'">
				<xsl:variable name="input_name" select="@name" />
				<fieldset class="form_fieldset">
					<xsl:choose>
						<xsl:when test="@required = 1">
							<legend class="{$class}"><xsl:value-of select="@descr" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></legend>
						</xsl:when>
						<xsl:otherwise>
							<legend class="{$class}"><xsl:value-of select="@descr" /></legend>
						</xsl:otherwise>
					</xsl:choose>								
					<xsl:for-each select="input[@type = 'option']">
						<xsl:call-template name="form_parse_option">
							<xsl:with-param name="input_type">checkbox_multi</xsl:with-param>
							<xsl:with-param name="input_name" select="$input_name" />
						</xsl:call-template>
					</xsl:for-each>
					<xsl:for-each select="input[@type != 'option']">
						<xsl:call-template name="form_parse_input" />
					</xsl:for-each>

				</fieldset>
			</xsl:when>
<!-- radio -->
			<xsl:when test="@type = 'radio'">
				<xsl:variable name="input_name" select="@name" />
				<fieldset class="form_fieldset">
					<xsl:choose>
						<xsl:when test="@required = 1">
							<legend class="{$class}"><xsl:value-of select="@descr" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/></legend>
						</xsl:when>
						<xsl:otherwise>
							<legend class="{$class}"><xsl:value-of select="@descr" /></legend>
						</xsl:otherwise>
					</xsl:choose>								
					<xsl:for-each select="input[@type = 'option']">
						<xsl:call-template name="form_parse_option">
							<xsl:with-param name="input_type">radio</xsl:with-param>
							<xsl:with-param name="input_name" select="$input_name" />
						</xsl:call-template>
					</xsl:for-each>
					<xsl:for-each select="input[@type != 'option']">
						<xsl:call-template name="form_parse_input" />
					</xsl:for-each>
				</fieldset>
			</xsl:when>
<!-- select -->
			<xsl:when test="@type = 'select'">
				<xsl:variable name="input_name" select="@name" />
				<div class="form_container">
					<div class="{$class}">
						<label for="{$input_name}">
							<xsl:choose>
								<xsl:when test="@required = 1">
									<xsl:value-of select="@descr" />&#160;<xsl:value-of select="/root/translations/TRL_FORM_FIELD_REQUIRED"/>
								</xsl:when>
								<xsl:otherwise>
									<xsl:value-of select="@descr" />
								</xsl:otherwise>
							</xsl:choose>
						</label>
					</div>
					<div class="form_input">
						<xsl:choose>
							<xsl:when test="size != ''">
								<xsl:variable name="size" select="size" />
								<select class="form_input_select" name="{$input_name}" id="{$input_name}" size="{$size}">
									<xsl:for-each select="input">
										<xsl:call-template name="form_parse_option">
											<xsl:with-param name="input_type">select</xsl:with-param>
											<xsl:with-param name="input_name" select="$input_name" />
										</xsl:call-template>
									</xsl:for-each>
								</select>
							</xsl:when>
							<xsl:otherwise>
								<select class="form_input_select" name="{$input_name}" id="{$input_name}">
									<xsl:for-each select="input">
										<xsl:call-template name="form_parse_option">
											<xsl:with-param name="input_type">select</xsl:with-param>
											<xsl:with-param name="input_name" select="$input_name" />
										</xsl:call-template>
									</xsl:for-each>
								</select>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</div>
			</xsl:when>
<!-- buttonset -->
			<xsl:when test="@type = 'buttonset'">
<!--
				<xsl:variable name="class_buttonset" select="@class" />
				<div class="form_container_buttons {$class_buttonset}">
					<xsl:for-each select="input">
						<xsl:call-template name="form_parse_button" />
					</xsl:for-each>
				</div>
//-->
			</xsl:when>
<!-- plaintext -->
            <xsl:when test="@type = 'plaintext'">
                <xsl:call-template name="ubc" />
            </xsl:when>
<!-- newline -->
			<xsl:when test="@type = 'newline'">
				<br/>
			</xsl:when>
<!-- hidden -->
            <xsl:when test="@type = 'hidden'">
                <xsl:variable name="input_name" select="name" />
                <xsl:variable name="input_value" select="value" />
                <input type="hidden" name="{$input_name}" id="{$input_name}" value="{$input_value}" />
            </xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="form_parse_button">
		<xsl:choose>
<!-- submit -->
			<xsl:when test="@type = 'submit'">
				<xsl:choose>
					<xsl:when test="@class != ''">
						<input type="submit" class="{@class}" name="{name}" id="{name}" value="{label}">
							<xsl:if test="onclick != ''">
								<xsl:attribute name="onclick">
									<xsl:value-of select="onclick" />
								</xsl:attribute>
							</xsl:if>
						</input>
					</xsl:when>
					<xsl:otherwise>
						<input type="submit" class="form_input_submit" name="{name}" id="{name}" value="{label}">
							<xsl:if test="onclick != ''">
								<xsl:attribute name="onclick">
									<xsl:value-of select="onclick" />
								</xsl:attribute>
							</xsl:if>
						</input>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
<!-- cancel -->
			<xsl:when test="@type = 'cancel'">
				<xsl:choose>
					<xsl:when test="@class != ''">
						<input type="submit" class="{@class}" name="{name}" id="{name}" value="{label}">
							<xsl:if test="onclick != ''">
								<xsl:attribute name="onclick">
									<xsl:value-of select="onclick" />
								</xsl:attribute>
							</xsl:if>
						</input>
					</xsl:when>
					<xsl:otherwise>
						<input type="submit" class="form_input_cancel" name="{name}" id="{name}" value="{label}">
							<xsl:if test="onclick != ''">
								<xsl:attribute name="onclick">
									<xsl:value-of select="onclick" />
								</xsl:attribute>
							</xsl:if>
						</input>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="form_parse_option">
		<xsl:param name="input_type" />
		<xsl:param name="input_name" />
		<xsl:variable name="input_value" select="value" />
		<xsl:variable name="input_selected" select="selected" />
		<xsl:variable name="count" select="count" />
		<xsl:choose>
			<xsl:when test="$input_type = 'checkbox_multi'">
				<div class="form_container">
					<div class="form_container_checkbox">
						<xsl:choose>
							<xsl:when test="$input_selected = 1">
								<input type="checkbox" class="form_input_checkbox" name="{$input_name}[]" id="{$input_name}_{$count}" value="{$input_value}" checked="checked" />
							</xsl:when>
							<xsl:otherwise>
								<input type="checkbox" class="form_input_checkbox" name="{$input_name}[]" id="{$input_name}_{$count}" value="{$input_value}" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
					<div class="form_label_checkbox">
						<label for="{$input_name}_{$count}">
							<xsl:value-of select="label" />
						</label>
					</div>
				</div>
			</xsl:when>
			<xsl:when test="$input_type = 'radio'">
				<div class="form_container">
					<div class="form_container_radio">
						<xsl:choose>
							<xsl:when test="$input_selected = 1">
								<input type="radio" class="form_input_radio" name="{$input_name}" id="{$input_name}_{$count}" value="{$input_value}" checked="checked" />
							</xsl:when>
							<xsl:otherwise>
								<input type="radio" class="form_input_radio" name="{$input_name}" id="{$input_name}_{$count}" value="{$input_value}" />
							</xsl:otherwise>
						</xsl:choose>
					</div>
					<div class="form_label_radio">
						<label for="{$input_name}_{$count}">
							<xsl:value-of select="label" />
						</label>
					</div>
				</div>
			</xsl:when>
			<xsl:when test="$input_type = 'select'">
				<xsl:choose>
					<xsl:when test="@type = 'optgroup'">
						<xsl:variable name="input_descr" select="@descr" />
						<optgroup label="{$input_descr}">
							<xsl:for-each select="input">
									<xsl:call-template name="form_parse_option">
										<xsl:with-param name="input_type" select="$input_type" />
										<xsl:with-param name="input_name" select="$input_name" />
									</xsl:call-template>
							</xsl:for-each>
						</optgroup>
					</xsl:when>
					<xsl:when test="@type = 'option'">
						<xsl:choose>
							<xsl:when test="$input_selected = 1">
								<option value="{$input_value}" selected="selected"><xsl:value-of select="label" /></option>
							</xsl:when>
							<xsl:otherwise>
								<option value="{$input_value}"><xsl:value-of select="label" /></option>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
				</xsl:choose>
			</xsl:when>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>