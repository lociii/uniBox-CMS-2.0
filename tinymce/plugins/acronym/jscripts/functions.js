/* Functions for the advlink plugin popup */

function preinit() {
	// Initialize
	tinyMCE.setWindowArg('mce_windowresize', false);
}

function init() {
	tinyMCEPopup.resizeToInnerSize();

	var formObj = document.forms[0];
	var inst = tinyMCE.getInstanceById(tinyMCE.getWindowArg('editor_id'));
	var elm = inst.getFocusElement();
	var action = "insert";

	elm = tinyMCE.getParentElement(elm, "acronym");
	if (elm != null && elm.nodeName == "ACRONYM")
		action = "update";

	formObj.insert.value = tinyMCE.getLang('lang_' + action, 'Insert', true); 

	if (action == "update") {
		// Setup form data
		setFormValue('title', tinyMCE.getAttrib(elm, 'title'));
		setFormValue('lang', tinyMCE.getAttrib(elm, 'lang'));
	}

	window.focus();
}

function setFormValue(name, value) {
	document.forms[0].elements[name].value = value;
}

function setAttrib(elm, attrib, value) {
	var formObj = document.forms[0];
	var valueElm = formObj.elements[attrib.toLowerCase()];

	if (typeof(value) == "undefined" || value == null) {
		value = "";

		if (valueElm)
			value = valueElm.value;
	}

	if (value != "") {
		elm.setAttribute(attrib.toLowerCase(), value);

		if (attrib == "style")
			attrib = "style.cssText";

		if (attrib == "href")
			elm.setAttribute("mce_real_href", value);

		if (attrib.substring(0, 2) == 'on')
			value = 'return true;' + value;

		if (attrib == "class")
			attrib = "className";

		eval('elm.' + attrib + "=value;");
	} else
		elm.removeAttribute(attrib);
}

function insertAction() {
	var inst = tinyMCE.getInstanceById(tinyMCE.getWindowArg('editor_id'));
	var elm = inst.getFocusElement();

	elm = tinyMCE.getParentElement(elm, "acronym");

	tinyMCEPopup.execCommand("mceBeginUndoLevel");

	// Create new acronym element
	if (elm == null) {
		// href attribute gets set to find the element again
		if (tinyMCE.isSafari)
			tinyMCEPopup.execCommand("mceInsertContent", false, '<acronym href="#mce_temp_href#">' + inst.getSelectedHTML() + '</acronym>');
		else
		{
			// we're inserting an anchor as long as the browsers cannot insert acronyms
			tinyMCEPopup.execCommand("createlink", false, "#mce_temp_href#");
			// change anchor to acronym and re-read th whole content
			content = inst.getBody().innerHTML;
			content = content.replace(/(.*)<a href="#mce_temp_href#">(.*?)<\/a>(.*)/i, "$1<acronym href=\"#mce_temp_href#\">$2</acronym>$3");
			inst.getBody().innerHTML = content;
		}

		var elementArray = tinyMCE.getElementsByAttributeValue(inst.getBody(), "acronym", "href", "#mce_temp_href#");

		for (var i=0; i<elementArray.length; i++) {
			var elm = elementArray[i];

			// remove href attribute
			elm.removeAttribute("href");

			// Move cursor behind the new anchor
			if (tinyMCE.isGecko) {
				var sp = inst.getDoc().createTextNode(" ");

				if (elm.nextSibling)
					elm.parentNode.insertBefore(sp, elm.nextSibling);
				else
					elm.parentNode.appendChild(sp);

				// Set range after link
				var rng = inst.getDoc().createRange();
				rng.setStartAfter(elm);
				rng.setEndAfter(elm);

				// Update selection
				var sel = inst.getSel();
				sel.removeAllRanges();
				sel.addRange(rng);
			}

			setAllAttribs(elm);
		}
	} else
		setAllAttribs(elm);

	tinyMCEPopup.execCommand("mceEndUndoLevel");
	tinyMCEPopup.close();
}

function setAllAttribs(elm) {
	var formObj = document.forms[0];

	setAttrib(elm, 'title');
	setAttrib(elm, 'lang');

	// Refresh in old MSIE
	if (tinyMCE.isMSIE5)
		elm.outerHTML = elm.outerHTML;
}

// While loading
preinit();
