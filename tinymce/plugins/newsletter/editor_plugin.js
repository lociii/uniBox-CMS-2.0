/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack('newsletter','en,tr,de,sv,zh_cn,cs,fa,fr_ca,fr,pl,pt_br,nl,he,nb,ru,ru_KOI8-R,ru_UTF-8,nn,cy,es,is,zh_tw,zh_tw_utf8,sk,da');

function TinyMCE_newsletter_getInfo() {
	return {
		longname : 'mceUniBoxNewsletter',
		author : 'Media Soma',
		authorurl : 'http://www.media-soma.de',
		infourl : 'http://www.media-soma.de',
		version : '0.1'
	};
};

/*
function TinyMCE_newsletter_getControlHTML(control_name) {
	switch (control_name) {
		case "newsletter":
			return '<a href="javascript:tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceNewsletter\');" onmousedown="return false;"><img id="{$editor_id}_newsletter" src="{$themeurl}/images/media.gif" title="" width="20" height="20" class="mceButtonNormal" onmouseover="tinyMCE.switchClass(this,\'mceButtonOver\');" onmouseout="tinyMCE.restoreClass(this);" onmousedown="tinyMCE.restoreClass(this);" /></a>';
	}
	return "";
}
*/

function TinyMCE_newsletter_execCommand(editor_id, element, command, user_interface, value) {
	switch (command) {
		case "mceNewsletter":
			var template = new Array();

			template['file']   = '../../plugins/newsletter/newsletter.htm';
			template['width']  = 500;
			template['height'] = 365;

			// Language specific width and height addons
			template['width']  += tinyMCE.getLang('lang_newsletter_delta_width', 0);
			template['height'] += tinyMCE.getLang('lang_newsletter_delta_height', 0);

			tinyMCE.openWindow(template, {editor_id : editor_id, inline : "yes", scrollbars : "yes"});
			return true;
	}
	return false;
}
