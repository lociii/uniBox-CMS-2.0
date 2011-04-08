function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function insertEmotion(code) {
	tinyMCE.execCommand('mceInsertContent', false, code);
	tinyMCEPopup.close();
}
