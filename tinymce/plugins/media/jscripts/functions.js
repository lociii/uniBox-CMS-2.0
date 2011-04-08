var undefined;

function uniBoxMedia()
{
	this.version = '0.1';
	this.themes = new Array();
	this.session_name = null;
	this.session_id = null;
	this.current_theme = null;
	// 1 = one for all, 2 = one for each
	this.current_type = 1;
	this.inherited_themes = new Array();

	uniBoxMedia.prototype.init = function(tinyMCE, tinyMCELang)
	{
		this.tinyMCE = tinyMCE;
		top.tinyMCE = tinyMCE;
		top.tinyMCELang = tinyMCELang;
	}

	uniBoxMedia.prototype.reset = function()
	{
		form = top.themeBrowser.document.forms.themes;
		for (var i = 0; i < this.themes.length; ++i)
		{
			form.elements['select_theme_' + this.themes[i]].options[0].selected = true;
			form.elements['fieldset_theme_' + this.themes[i]].className = '';
		}
	}

	// resize and move window
	uniBoxMedia.prototype.move = function()
	{
		window.resizeTo(1024, 768);
		window.moveTo((screen.width - 1024) / 2, (screen.height - 768) / 2);
	}

	// set available themes
	uniBoxMedia.prototype.set_themes = function(themes_array)
	{
		this.themes = themes_array;
	}

	// set available themes
	uniBoxMedia.prototype.set_session_data = function(session_name, session_id)
	{
		this.session_name = session_name;
		this.session_id = session_id;
	}

	uniBoxMedia.prototype.check_themes = function()
	{
		if (top.themeBrowser.document.forms.themes)
			this.process_themes();
		else
			setTimeout('uniBoxMedia.check_themes()', 100);
	}

	uniBoxMedia.prototype.process_themes = function()
	{
		// check if we're updating
		inst = this.tinyMCE.getInstanceById(this.tinyMCE.getWindowArg('editor_id'));
		elm = inst.getFocusElement();
		
		if (elm != null && elm.nodeName == "IMG")
		{
			form = top.options.document.forms.options;
			styles = this.tinyMCE.parseStyle(this.tinyMCE.getAttrib(elm, 'style'));
			
			// refill zoom
			form.elements['zoom'].checked = (this.tinyMCE.getAttrib(elm, 'zoom') == 1);

			// refill float
			if (styles["float"] != undefined)
			{
				for (i = 0; i < form.elements['align'].length; ++i)
					if (form.elements['align'].options[i].value == styles["float"])
						form.elements['align'].options[i].selected = true;
			}

			// refill margin
			if (styles["margin"] != undefined)
			{
				if (result = styles["margin"].match(/(\d+)px (\d+)px (\d+)px (\d+)px/))
				{
					form.elements['margin_top'].value = result[1];
					form.elements['margin_right'].value = result[2];
					form.elements['margin_bottom'].value = result[3];
					form.elements['margin_left'].value = result[4];
				}
				else if (result = styles["margin"].match(/(\d+)px (\d+)px (\d+)px/))
				{
					form.elements['margin_top'].value = result[1];
					form.elements['margin_right'].value = form.elements['margin_left'].value = result[2];
					form.elements['margin_bottom'].value = result[3];
				}
				else if (result = styles["margin"].match(/(\d+)px (\d+)px/))
				{
					
				}
				else if (result = styles["margin"].match(/(\d+)px/))
					form.elements['margin_top'].value = form.elements['margin_bottom'].value = form.elements['margin_right'].value = form.elements['margin_left'].value = result[1];
			}
			else
			{
				if (styles["margin_left"] != undefined)
				{
					result = styles["margin_left"].match(/(\d+)px/);
					form.elements['margin_left'].value = result[1];
				}
				if (styles["margin_right"] != undefined)
				{
					result = styles["margin_right"].match(/(\d+)px/);
					form.elements['margin_right'].value = result[1];
				}
				if (styles["margin_top"] != undefined)
				{
					result = styles["margin_top"].match(/(\d+)px/);
					form.elements['margin_top'].value = result[1];
				}
				if (styles["margin_bottom"] != undefined)
				{
					result = styles["margin_bottom"].match(/(\d+)px/);
					form.elements['margin_bottom'].value = result[1];
				}
			}

			// Setup form data
			form = top.themeBrowser.document.forms.themes;
			this.current_type = 2;
			cur_media_id = -1;
			cur_media_width = -1;
			cur_media_height = -1;
			media_general = true;
			
			matches = this.tinyMCE.getAttrib(elm, 'themes').match(/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/gi);
			if (matches)
			{
				for (i = 0; i < matches.length; i++)
				{
					media_info = matches[i].match(/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/);
					
					// try if theme still exists
					theme_found = false;
					for (j = 0; j < this.themes.length; j++)
						if (this.themes[j] == media_info[1])
							theme_found = true;

					if (theme_found)
					{
						if (i != 0 && (cur_media_id != media_info[2] || cur_media_width != media_info[3] || cur_media_height != media_info[4]))
							media_general = false;
	
						this.current_theme = media_info[1];
						this.set_media(media_info[2]);
	
						if (media_info[3] < 0)
							media_info[3] = 0;
						form.elements['media_width_theme_' + media_info[1]].value = media_info[3];
						if (media_info[4] < 0)
							media_info[4] = 0;
						form.elements['media_height_theme_' + media_info[1]].value = media_info[4];
						if (media_info[3] > 0 && media_info[4] > 0)
						{
							form.elements['media_size_relation_theme_' + media_info[1]].value = (Math.round(media_info[3] / media_info[4] * 1000) / 1000);
							form.elements['media_size_link_theme_' + media_info[1]].checked = 'checked';
						}
						else
							form.elements['media_size_relation_theme_' + media_info[1]].value = 0;
	
						cur_media_id = media_info[2];
						cur_media_width = media_info[3];
						cur_media_height = media_info[4];
					}
				}

				if (media_general)
				{
					this.current_type = 1;
					this.tinyMCE.setAttrib(elm, 'curtheme', '__all');
					this.set_media(cur_media_id, media_info[3], media_info[4]);
					if (media_info[3] > 0 && media_info[4] > 0)
					{
						form.elements['media_size_relation_general'].value = (Math.round(media_info[3] / media_info[4] * 1000) / 1000);
						form.elements['media_size_link_general'].checked = 'checked';
					}
					else
						form.elements['media_size_relation_general'].value = 0;
				}
				else
					top.options.document.forms.options.type[1].checked = true;
			}
		}
		this.switch_type();
		window.focus();
	}

	// switch image selection method
	uniBoxMedia.prototype.switch_type = function()
	{
		// get selected method
		for (i = 0; i < top.options.document.forms.options.type.length; i++)
			if (top.options.document.forms.options.type[i].checked)
				break;

		// process it
		if (i == 0)
		{
			this.current_type = 1;
			this.type_one_for_all();
		}
		else
		{
			this.current_type = 2;
			this.type_one_for_each();
		}
	}

	// show form - one image for all themes
	uniBoxMedia.prototype.type_one_for_all = function()
	{
		// enable general form
		form = top.themeBrowser.document.forms.themes;
		form.elements['fieldset_general'].style.display = 'block';
		
		// disable all themes
		for (i = 0; i < this.themes.length; ++i)
		{
			form.elements['fieldset_theme_' + this.themes[i]].className = '';
			form.elements['fieldset_theme_' + this.themes[i]].style.display = 'none';
		}
	}

	// show form - one image for each theme
	uniBoxMedia.prototype.type_one_for_each = function()
	{
		// disable general form
		form = top.themeBrowser.document.forms.themes;
		form.elements['fieldset_general'].style.display = 'none';

		// enable all themes
		for (i = 0; i < this.themes.length; ++i)
			form.elements['fieldset_theme_' + this.themes[i]].style.display = 'block';
	}

	// set media from media_browser
	uniBoxMedia.prototype.set_media = function(media_id, width, height)
	{
		form = top.themeBrowser.document.forms.themes;
		images = top.themeBrowser.document.images;
		
		// switch method
		if (this.current_type == 1)
		{
			// remove 'no image' information
			top.themeBrowser.document.getElementById('general_info').style.display = 'none';
			// show thumbnail and set media_id
			form.elements['media_id_general'].value = media_id;
			images['thumbnail_general'].src = 'media.php5?media_id=' + media_id + '&width=80&height=80';
			if (this.session_name != null && this.session_id != null)
				images['thumbnail_general'].src += "&" + this.session_name + "_id=" + session_id;
			images['thumbnail_general'].style.display = 'inline';
			form.elements['media_width_general'].value = width;
			form.elements['media_height_general'].value = height;
			form.elements['media_size_relation_general'].value = (Math.round(width / height * 1000) / 1000);
			form.elements['media_size_link_general'].checked = 'checked';
			
		}
		else if (this.current_type == 2 && this.current_theme != null)
		{
			// remove 'no image' information
			top.themeBrowser.document.getElementById('info_theme_' + this.current_theme).style.display = 'none';
			// set to own image
			form.elements['select_theme_' + this.current_theme].options[0].selected = true;
			// show thumbnail and set media_id
			form.elements['media_id_theme_' + this.current_theme].value = media_id;
			images['thumbnail_theme_' + this.current_theme].src = 'media.php5?media_id=' + media_id + '&width=80&height=80';
			if (this.session_name != null && this.session_id != null)
				images['thumbnail_theme_' + this.current_theme].src += "&" + this.session_name + "_id=" + session_id;
			images['thumbnail_theme_' + this.current_theme].style.display = 'inline';
			form.elements['media_width_theme_' + this.current_theme].value = width;
			form.elements['media_height_theme_' + this.current_theme].value = height;
			form.elements['media_size_relation_theme_' + this.current_theme].value = (Math.round(width / height * 1000) / 1000);
			form.elements['media_size_link_theme_' + this.current_theme].checked = 'checked';
		}
	}

	// process fieldset click
	uniBoxMedia.prototype.process_fieldset = function(element_id)
	{
		// get current element
		elm = top.themeBrowser.document.forms.themes.elements[element_id].firstChild;
		// extract current theme name from current elements name
		theme = element_id.replace(/fieldset_theme_/, "");

		// re-color current fieldset and set current theme
		elm.className = 'active';
		this.current_theme = theme;

		// disable all other themes
		for (i = 0; i < this.themes.length; ++i)
			if (this.themes[i] != this.current_theme)
				// re-color fieldset
				top.themeBrowser.document.forms.themes.elements['fieldset_theme_' + this.themes[i]].firstChild.className = '';
	}

	// process select change
	uniBoxMedia.prototype.process_select = function(element_id, base_dir)
	{
		// get current element
		elm = top.themeBrowser.document.forms.themes.elements[element_id];

		// extract current theme name from current elements name
		theme = element_id.replace(/select_theme_/, "");

		// disable/enable width/height and thumbnail
		image_elm = top.themeBrowser.document.images['thumbnail_theme_' + theme];
		if (elm.options[0].selected)
		{
			top.themeBrowser.document.getElementById('size_theme_' + theme).style.display = 'block';
			top.themeBrowser.document.getElementById('info_theme_' + theme).style.display = 'block';
			if (image_elm.src != '' && image_elm.src != null && image_elm.src != base_dir)
				image_elm.style.display = 'inline';
		}
		else
		{
			top.themeBrowser.document.getElementById('size_theme_' + theme).style.display = 'none';
			top.themeBrowser.document.getElementById('info_theme_' + theme).style.display = 'none';
			image_elm.style.display = 'none';
		}
	}

	uniBoxMedia.prototype.process_checkbox = function()
	{
		form = top.themeBrowser.document.forms.themes;
		if (this.current_type == 1)
		{
			if (form.elements['media_width_general'].value > 0 && form.elements['media_height_general'].value > 0)
				form.elements['media_size_relation_general'].value = (Math.round(form.elements['media_width_general'].value / form.elements['media_height_general'].value * 1000) / 1000);
			else
				form.elements['media_size_relation_general'].value = 0;
		}
		else
		{
			if (form.elements['media_width_theme_' + this.current_theme].value > 0 && form.elements['media_height_theme_' + this.current_theme].value > 0)
				form.elements['media_size_relation_theme_' + this.current_theme].value = (Math.round(form.elements['media_width_theme_' + this.current_theme].value / form.elements['media_height_theme_' + this.current_theme].value * 1000) / 1000);
			else
				form.elements['media_size_relation_theme_' + this.current_theme].value = 0;
		}
	}

	uniBoxMedia.prototype.process_input_width = function()
	{
		form = top.themeBrowser.document.forms.themes;
		if (this.current_type == 1)
		{
			if (form.elements['media_size_link_general'].checked && form.elements['media_size_relation_general'].value > 0)
				form.elements['media_height_general'].value = Math.round(form.elements['media_width_general'].value / form.elements['media_size_relation_general'].value);
		}
		else
		{
			if (form.elements['media_size_link_theme_' + this.current_theme].checked && form.elements['media_size_relation_theme_' + this.current_theme].value > 0)
				form.elements['media_height_theme_' + this.current_theme].value = Math.round(form.elements['media_width_theme_' + this.current_theme].value / form.elements['media_size_relation_theme_' + this.current_theme].value);
		}
	}

	uniBoxMedia.prototype.process_input_height = function()
	{
		form = top.themeBrowser.document.forms.themes;
		if (this.current_type == 1)
		{
			if (form.elements['media_size_link_general'].checked && form.elements['media_size_relation_general'].value > 0)
				form.elements['media_width_general'].value = Math.round(form.elements['media_height_general'].value * form.elements['media_size_relation_general'].value);
		}
		else
		{
			if (form.elements['media_size_link_theme_' + this.current_theme].checked && form.elements['media_size_relation_theme_' + this.current_theme].value > 0)
				form.elements['media_width_theme_' + this.current_theme].value = Math.round(form.elements['media_height_theme_' + this.current_theme].value * form.elements['media_size_relation_theme_' + this.current_theme].value);
		}
	}

	// close popup
	uniBoxMedia.prototype.close = function()
	{
		top.close();
	}

	// insert image to editor
	uniBoxMedia.prototype.insert = function()
	{
		form = top.themeBrowser.document.forms.themes;
		error = false;
		media_id = null;
		width = 0;
		height = 0;
		theme = null;
		html = '<img';
		style = '';

		// check what type of inserting
		if (this.current_type == 1)
		{
			// if no image ist set on general insert
			if (form.elements['media_id_general'].value == null || form.elements['media_id_general'].value == '' || form.elements['media_id_general'].value != Number(form.elements['media_id_general'].value))
			{
				alert(tinyMCELang['lang_media_error_no_image_selected']);
				error = true;
			}
			// build html for general
			else
			{
				// validate width and height
				if (isNaN(width = parseInt(form.elements['media_width_general'].value, 10)))
					width = 0;

				if (isNaN(height = parseInt(form.elements['media_height_general'].value, 10)))
					height = 0;

				// add source
				html += ' src="' + this.tinyMCE.baseURL + '/../media.php5?media_id=' + form.elements['media_id_general'].value + '&width=' + width + '&height=' + height + '"';
				if (this.session_name != null && this.session_id != null)
					html += '&' + this.session_name + '_id=' + session_id;

				html += ' curtheme="__all"';
				if (width > 0)
					html += ' width="' + width + '"';

				if (height > 0)
					html += ' height="' + height + '"';

				html += ' themes="';

				// add each theme
				for (i = 0; i < this.themes.length; i++)
					if (i == 0)
						html += this.themes[i] + ': ' + form.elements['media_id_general'].value + '|' + width + '|' + height + ';';
					else
						html += ' ' + this.themes[i] + ': ' + form.elements['media_id_general'].value + '|' + width + '|' + height + ';';
				
				html += '"';
			}
		}
		else
		{
			
			if (this.current_theme != null)
			{
				width = form.elements['media_width_theme_' + this.current_theme].value;
				height = form.elements['media_height_theme_' + this.current_theme].value;
				media_id = form.elements['media_id_theme_' + this.current_theme].value;
				theme = this.current_theme;
			}
			else
			{
				width = form.elements['media_width_theme_' + this.themes[0]].value;
				height = form.elements['media_height_theme_' + this.themes[0]].value;
				media_id = form.elements['media_id_theme_' + this.themes[0]].value;
				theme = this.themes[0];
			}

			// set picture to show in editor
			// validate width/height
			if (isNaN(parseInt(width, 10)))
				width = 0;

			if (isNaN(parseInt(height, 10)))
				height = 0;

			html += ' src="' + this.tinyMCE.baseURL + '/../media.php5?media_id=' + media_id + '&width=' + width + '&height=' + height + '"';
			if (this.session_name != null && this.session_id != null)
				html += '&' + this.session_name + '_id=' + session_id;

			html += ' curtheme="' + theme + '"';
			if (width > 0)
				html += ' width="' + width + '"';
			if (height > 0)
				html += ' height="' + height + '"';
			html += ' themes="';

			// add data for each theme
			for (i = 0; i < this.themes.length; i++)
			{
				this.inherited_themes = new Array();
				if ((media_info_array = this.get_media_id(this.themes[i])) instanceof Array)
				{
					if (i != 0)
						html += ' ';
					html += this.themes[i] + ': ' + media_info_array[0] + '|' + media_info_array[1] + '|' + media_info_array[2] + ';';
				}
				else
					error = true;
			}
			html += '"';

			if (error)
			{
				alert(tinyMCELang['lang_media_error_no_image_selected_for_all_themes']);
				return;
			}
		}
		
		elm_align = top.options.document.forms.options.elements['align'];
		for (var i = 0; i < elm_align.length; i++)
		{
			if (elm_align.options[i].selected)
			{
				style = ' display: inline; float: ' + elm_align.options[i].value + ';';
				break;
			}
		}

		if (top.options.document.forms.options.elements['zoom'].checked)
			html += ' zoom="1"';
		else
			html += ' zoom="0"';

		if (parseInt(top.options.document.forms.options.elements['margin_left'].value, 10) > 0)
			style += ' margin-left: ' + top.options.document.forms.options.elements['margin_left'].value + 'px;'
		else
			style += ' margin-left: 0px;'

		if (parseInt(top.options.document.forms.options.elements['margin_right'].value, 10) > 0)
			style += ' margin-right: ' + top.options.document.forms.options.elements['margin_right'].value + 'px;'
		else
			style += ' margin-right: 0px;'

		if (parseInt(top.options.document.forms.options.elements['margin_top'].value, 10) > 0)
			style += ' margin-top: ' + top.options.document.forms.options.elements['margin_top'].value + 'px;'
		else
			style += ' margin-top: 0px;'

		if (parseInt(top.options.document.forms.options.elements['margin_bottom'].value, 10) > 0)
			style += ' margin-bottom: ' + top.options.document.forms.options.elements['margin_bottom'].value + 'px;'
		else
			style += ' margin-bottom: 0px;'

		if (style != '')
			html += 'style="' + style + '"';
		html += ' />';

		if (!error)
		{
			this.tinyMCE.execCommand("mceInsertContent", false, html);
			this.close();
		}
	}

	// get media id (probably inherited)
	uniBoxMedia.prototype.get_media_id = function(theme_ident)
	{
		form = top.themeBrowser.document.forms.themes;
		width = 0;
		height = 0;
		// if media is selected for current theme
		if (form.elements['select_theme_' + theme_ident].options[0].selected && form.elements['media_id_theme_' + theme_ident].value != null && form.elements['media_id_theme_' + theme_ident].value != '')
		{
			if (isNaN(width = parseInt(form.elements['media_width_theme_' + theme_ident].value, 10)))
				width = 0;
			if (isNaN(height = parseInt(form.elements['media_height_theme_' + theme_ident].value, 10)))
				height = 0;
			return Array(
				form.elements['media_id_theme_' + theme_ident].value,
				width,
				height
				);
		}
		// get inherited media
		else
		{
			if (!form.elements['select_theme_' + theme_ident].options[0].selected)
			{
				// get selected option
				for (j = 0; j < form.elements['select_theme_' + theme_ident].options.length; j++)
				{
					if (form.elements['select_theme_' + theme_ident].options[j].selected && !this.in_array(theme_ident, this.inherited_themes))
					{
						this.inherited_themes.push(theme_ident);
						return this.get_media_id(form.elements['select_theme_' + theme_ident].options[j].value);
					}
				}
			}
		}
		return null;
	}

	// check if a given needle exists as value of an array
	uniBoxMedia.prototype.in_array = function(needle, haystack)
	{
    	for (i = 0; i < haystack.length; i++)
	        if (haystack[i] == needle)
	            return true;
    	return false;
    }

	// show image in preview popup
	uniBoxMedia.prototype.show_image = function(media_id, media_width, media_height)
	{
		width = media_width + 20;
		height = media_height + 20;
		if (width > screen.availWidth)
			width = screen.availWidth;
		if (height > screen.availHeight)
			height = screen.availHeight;
		url = "media.php5?media_id=" + media_id + "&width=" + media_width + "&height=" + media_height;
		if (this.session_name != null && this.session_id != null)
			url += "&" + this.session_name + "_id=" + session_id;
		window.open(url, "uniBoxMediaShowImage", "width=" + width + ",height=" + height + ",left=100,top=100,dependent=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
	}
}

// instantiate object
uniBoxMedia = new uniBoxMedia();
uniBoxMedia.init(opener.tinyMCE, opener.tinyMCELang);