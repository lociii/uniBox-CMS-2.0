var url = tinyMCE.getParam("flash_external_list_url");
if (url != null) {
	// Fix relative
	if (url.charAt(0) != '/' && url.indexOf('://') == -1)
		url = tinyMCE.documentBasePath + "/" + url;

	document.write('<sc'+'ript language="javascript" type="text/javascript" src="' + url + '"></sc'+'ript>');
}

function init() {
	tinyMCEPopup.resizeToInnerSize();

	document.getElementById("filebrowsercontainer").innerHTML = getBrowserHTML('filebrowser','file','flash','flash');

	// Image list outsrc
	var html = getFlashListHTML('filebrowser','file','flash','flash');
	if (html == "")
		document.getElementById("linklistrow").style.display = 'none';
	else
		document.getElementById("linklistcontainer").innerHTML = html;

	var formObj = document.forms[0];
	var swffile   = tinyMCE.getWindowArg('swffile');
	var swfwidth  = '' + tinyMCE.getWindowArg('swfwidth');
	var swfheight = '' + tinyMCE.getWindowArg('swfheight');

// BEGIN ADD BY MEDIA SOMA
	if (swffile.substring(0, 6) == '../../')
		swffile = swffile.substring(6, swffile.length);
// END ADD BY MEDIA SOMA

	if (swfwidth.indexOf('%')!=-1) {
		formObj.width2.value = "%";
		formObj.width.value  = swfwidth.substring(0,swfwidth.length-1);
	} else {
		formObj.width2.value = "px";
		formObj.width.value  = swfwidth;
	}

	if (swfheight.indexOf('%')!=-1) {
		formObj.height2.value = "%";
		formObj.height.value  = swfheight.substring(0,swfheight.length-1);
	} else {
		formObj.height2.value = "px";
		formObj.height.value  = swfheight;
	}

	formObj.file.value = swffile;
	formObj.insert.value = tinyMCE.getLang('lang_' + tinyMCE.getWindowArg('action'), 'Insert', true);

	selectByValue(formObj, 'linklist', swffile);

	// Handle file browser
	if (isVisible('filebrowser'))
		document.getElementById('file').style.width = '230px';

	// Auto select flash in list
/* BEGIN CUT BY MEDIA SOMA
	if (typeof(tinyMCEFlashList) != "undefined" && tinyMCEFlashList.length > 0) {
*/
// BEGIN ADD BY MEDIA SOMA
	if (typeof(module_media) != "undefined" && module_media.length > 0) {
// END ADD BY MEDIA SOMA
		for (var i=0; i<formObj.linklist.length; i++) {
			if (formObj.linklist.options[i].value == tinyMCE.getWindowArg('swffile'))
				formObj.linklist.options[i].selected = true;
		}
	}
}

function getFlashListHTML() {
	if (typeof(module_media) != "undefined" && module_media.length > 0) {
		var html = "";

		html += '<select id="linklist" name="linklist" style="width: 250px" onfocus="tinyMCE.addSelectAccessibility(event, this, window);" onchange="this.form.file.value=this.options[this.selectedIndex].value;">';
		html += '<option value="">---</option>';

		var current_category = '';
		for (var j=0; j<module_media.length; j++)
		{
			if (current_category != module_media[j][0])
			{
				if (typeof(current_category) != "undefined")
					html += '</optgroup>';
				html += '<optgroup label="' + module_media[j][0] + '">';
				current_category = module_media[j][0];
			}

			html += '<option value="' + module_media[j][2] + '">' + module_media[j][1] + '</option>';
		}

		html += '</optgroup></select>';

		return html;
	}

	return "";
}

function insertFlash() {
	var formObj = document.forms[0];
	var html      = '';
	var file      = formObj.file.value;
	var width     = formObj.width.value;
	var height    = formObj.height.value;
	if (formObj.width2.value=='%') {
		width = width + '%';
	}
	if (formObj.height2.value=='%') {
		height = height + '%';
	}

	if (width == "")
		width = 100;

	if (height == "")
		height = 100;

	html += ''
		+ '<img src="' + (tinyMCE.getParam("theme_href") + "/images/spacer.gif") + '" mce_src="' + (tinyMCE.getParam("theme_href") + "/images/spacer.gif") + '" '
		+ 'width="' + width + '" height="' + height + '" '
		+ 'border="0" alt="' + file + '" title="' + file + '" class="mceItemFlash" />';

	tinyMCEPopup.execCommand("mceInsertContent", true, html);
	tinyMCE.selectedInstance.repaint();

	tinyMCEPopup.close();
}
