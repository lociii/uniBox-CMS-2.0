/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack('acronym', 'en,de');

function TinyMCE_acronym_getInfo() {
	return {
		longname : 'Acronym',
		author : 'Media Soma',
		authorurl : 'http://www.media-soma.de',
		infourl : 'http://www.media-soma.de/',
		version : '0.1'
	};
};

function TinyMCE_acronym_getControlHTML(control_name) {
	switch (control_name) {
		case "acronym":
			var return_value = '<a href="javascript:tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceAcronym\');" onmousedown="return false;"><img id="{$editor_id}_acronym" src="{$themeurl}/images/acronym.gif" title="{$lang_acronym_desc}" width="20" height="20" class="mceButtonNormal" onmouseover="tinyMCE.switchClass(this,\'mceButtonOver\');" onmouseout="tinyMCE.restoreClass(this);" onmousedown="tinyMCE.restoreClass(this);" /></a>';
			return_value = return_value + '<a href="javascript:tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mceAcronymDelete\');" onmousedown="return false;"><img id="{$editor_id}_acronym_delete" src="{$themeurl}/images/acronym.gif" title="{$lang_acronym_delete_desc}" width="20" height="20" class="mceButtonNormal" onmouseover="tinyMCE.switchClass(this,\'mceButtonOver\');" onmouseout="tinyMCE.restoreClass(this);" onmousedown="tinyMCE.restoreClass(this);" /></a>';
			return return_value;
	}

	return "";
}

function TinyMCE_acronym_execCommand(editor_id, element, command, user_interface, value) {
	switch (command) {
		case "mceAcronym":
			var template = new Array();
			var anySelection = false;
			var inst = tinyMCE.getInstanceById(editor_id);
			var focusElm = inst.getFocusElement();

			if (tinyMCE.selectedElement)
				anySelection = (tinyMCE.selectedElement.nodeName.toLowerCase() == "acronym") || (selectedText && selectedText.length > 0);

			if (anySelection || (focusElm != null && focusElm.nodeName == "ACRONYM")) {
				var template = new Array();

				template['file']   = '../../plugins/acronym/acronym.htm';
				template['width']  = 480;
				template['height'] = 400;

				// Language specific width and height addons
				template['width']  += tinyMCE.getLang('lang_acronym_delta_width', 0);
				template['height'] += tinyMCE.getLang('lang_acronym_delta_height', 0);

				tinyMCE.openWindow(template, {editor_id : editor_id, inline : "yes"});
			}

			return true;
		case "mceAcronymDelete":
			var inst = tinyMCE.getInstanceById(editor_id);
			var focusElm = tinyMCE.getParentElement(inst.getFocusElement(), "acronym");
			if (focusElm != null && focusElm.nodeName == "ACRONYM") {
				tinyMCE.execCommand("mceBeginUndoLevel");
				var textNode = document.createTextNode(focusElm.innerHTML);
				focusElm.parentNode.insertBefore(textNode, focusElm);
				focusElm.parentNode.removeChild(focusElm);
				tinyMCE.execCommand("mceEndUndoLevel");
			}
			
			return true;
	}

	return false;
}

function TinyMCE_acronym_handleNodeChange(editor_id, node, undo_index, undo_levels, visual_aid, any_selection) {
	tinyMCE.switchClassSticky(editor_id + '_acronym', 'mceButtonDisabled', true);
	tinyMCE.switchClassSticky(editor_id + '_acronym_delete', 'mceButtonDisabled', true);

	if (node == null)
		return;

	if (any_selection)
		tinyMCE.switchClassSticky(editor_id + '_acronym', 'mceButtonNormal', false);

	do {
		if (node.nodeName == "ACRONYM") {
			tinyMCE.switchClassSticky(editor_id + '_acronym', 'mceButtonSelected', false);
			tinyMCE.switchClassSticky(editor_id + '_acronym_delete', 'mceButtonNormal', false);
		}
	} while ((node = node.parentNode));

	return true;
}
