/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack('media', 'en,de');

function TinyMCE_media_getInfo() {
	return {
		longname : 'mceUniBoxMedia',
		author : 'Media Soma',
		authorurl : 'http://www.media-soma.de',
		infourl : 'http://www.media-soma.de',
		version : '0.1'
	};
};

function TinyMCE_media_getControlHTML(control_name) {
	switch (control_name) {
		case "media":
			return '<a href="javascript:tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceMedia\');" onmousedown="return false;"><img id="{$editor_id}_media" src="{$themeurl}/images/media.gif" title="{$lang_media_desc}" width="20" height="20" class="mceButtonNormal" onmouseover="tinyMCE.switchClass(this,\'mceButtonOver\');" onmouseout="tinyMCE.restoreClass(this);" onmousedown="tinyMCE.restoreClass(this);" /></a>';
	}
	return "";
}

function TinyMCE_media_execCommand(editor_id, element, command, user_interface, value) {
	switch (command) {
		case "mceMedia":
			var template = new Array();

			template['file']   = '../../plugins/media/media.htm';
			template['width']  = 1024;
			template['height'] = 768;

			// Language specific width and height addons
			template['width']  += tinyMCE.getLang('lang_media_delta_width', 0);
			template['height'] += tinyMCE.getLang('lang_media_delta_height', 0);

			tinyMCE.openWindow(template, {editor_id : editor_id, inline : "yes"});
			return true;
			
		case "mceMediaSwitch":
			var inst = tinyMCE.getInstanceById(editor_id);
			var elm = inst.getFocusElement();
			var curtheme = tinyMCE.getAttrib(elm, 'curtheme');
			var themes = '';
			var width = 0;
			var height = 0;
			var media_id = 0;
			var media_width = 0;
			var media_height = 0;

			if (tinyMCE.getAttrib(elm, 'width'))
				width = tinyMCE.getAttrib(elm, 'width');
			if (tinyMCE.getAttrib(elm, 'height'))
				height = tinyMCE.getAttrib(elm, 'height');

			matches = tinyMCE.getAttrib(elm, 'themes').match(/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/gi);
			if (matches)
			{
				for (var i = 0; i < matches.length; ++i)
				{
					media_info = matches[i].match(/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/);
					if (value == media_info[1])
					{
						media_id = media_info[2];
						media_width = media_info[3];
						media_height = media_info[4];
					}

					if (curtheme == media_info[1])
						themes += curtheme + ': ' + media_info[2] + '|' + width + '|' + height + '; ';
					else
						themes += media_info[0];
				}
			}

			tinyMCE.setAttrib(elm, 'src', tinyMCE.baseURL + '/../media.php5?media_id=' + media_id);
			tinyMCE.setAttrib(elm, 'themes', themes);
			tinyMCE.setAttrib(elm, 'curtheme', value);
			if (media_width > 0)
				tinyMCE.setAttrib(elm, 'width', media_width);
			else
				elm.removeAttribute('width');
				
			if (media_height > 0)
				tinyMCE.setAttrib(elm, 'height', media_height);
			else
				elm.removeAttribute('height');

			tinyMCE.setAttrib(elm, 'style', tinyMCE.getAttrib(elm, 'style').replace(/(height: ?[^;]*;)|(width: ?[^;]*;)/gi, ''));
			tinyMCE.selectedInstance.repaint();
			return true;
	}
	return false;
}

function TinyMCE_media_cleanup(type, content, inst)
{
	if (type == 'submit_content_dom')
	{
		for (var n = 0; n < inst.contentWindow.document.images.length; n++)
		{
			themes = '';
			elm = inst.contentWindow.document.images[n];

			matches = this.tinyMCE.getAttrib(elm, 'themes').match(/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/gi);
			if (matches)
			{
				var cur_media_id = -1;
				var cur_media_width = -1;
				var cur_media_height = -1;
				var media_general = true;

				for (var i = 0; i < matches.length; ++i)
				{
					var media_info = matches[i].match(/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/);
					if (i != 0 && (cur_media_id != media_info[2] || cur_media_width != media_info[3] || cur_media_height != media_info[4]))
						media_general = false;
					cur_media_id = media_info[2];
					cur_media_width = media_info[3];
					cur_media_height = media_info[4];
				}
				if (media_general)
					this.tinyMCE.setAttrib(elm, 'curtheme', '__all');
			}

			curtheme = tinyMCE.getAttrib(elm, 'curtheme');
			width = 0;
			height = 0;

			if (tinyMCE.getAttrib(elm, 'width'))
				width = tinyMCE.getAttrib(elm, 'width');
			else
			{
				styles = tinyMCE.parseStyle(tinyMCE.getAttrib(elm, 'style'));
				if (styles["width"] != "undefined")
					width = styles["width"].match(/(\d)+/)[0];
			}
			if (tinyMCE.getAttrib(elm, 'height'))
				height = tinyMCE.getAttrib(elm, 'height');
			else
			{
				styles = tinyMCE.parseStyle(tinyMCE.getAttrib(elm, 'style'));
				if (styles["height"] != "undefined")
					height = styles["height"].match(/(\d)+/)[0];
			}
	
			matches = tinyMCE.getAttrib(elm, 'themes').match(/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/gi);
			if (matches)
			{
				for (var i = 0; i < matches.length; ++i)
				{
					media_info = matches[i].match(/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/);
					if ((curtheme == media_info[1] || curtheme == '__all') && width > 0 && height > 0)
						themes += media_info[1] + ': ' + media_info[2] + '|' + width + '|' + height + '; ';
					else
						themes += media_info[0];
				}
			}
			tinyMCE.setAttrib(elm, 'themes', themes);
		}
	}
	
	return content;
}