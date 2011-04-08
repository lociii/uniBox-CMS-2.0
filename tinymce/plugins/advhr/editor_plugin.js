/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack('advhr', 'en,tr,de,sv,zh_cn,cs,fa,fr_ca,fr,pl,pt_br,nl,da,he,nb,hu,ru,ru_KOI8-R,ru_UTF-8,nn,fi,es,cy,is,zh_tw,zh_tw_utf8,sk');

function TinyMCE_advhr_getInfo() {
	return {
		longname : 'Advanced HR',
		author : 'Moxiecode Systems',
		authorurl : 'http://tinymce.moxiecode.com',
		infourl : 'http://tinymce.moxiecode.com/tinymce/docs/plugin_advhr.html',
		version : tinyMCE.majorVersion + "." + tinyMCE.minorVersion
	};
};

function TinyMCE_advhr_getControlHTML(control_name) {
    switch (control_name) {
        case "advhr":
			var cmd = 'tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceAdvancedHr\');return false;';
            return '<a href="javascript:' + cmd + '" onclick="' + cmd + '" target="_self" onmousedown="return false;"><img id="{$editor_id}_advhr" src="{$pluginurl}/images/advhr.gif" title="{$lang_insert_advhr_desc}" width="20" height="20" class="mceButtonNormal" onmouseover="tinyMCE.switchClass(this,\'mceButtonOver\');" onmouseout="tinyMCE.restoreClass(this);" onmousedown="tinyMCE.restoreAndSwitchClass(this,\'mceButtonDown\');" /></a>';
    }

    return "";
}

/**
 * Executes the mceAdvanceHr command.
 */
function TinyMCE_advhr_execCommand(editor_id, element, command, user_interface, value) {
    // Handle commands
    switch (command) {
        case "mceAdvancedHr":
            tinyMCE.execCommand("mceInsertContent", true, '<hr class="ubc_separator" />');
       return true;
   }
   // Pass to next handler in chain
   return false;
}