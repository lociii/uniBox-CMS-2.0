// show full image in popup
function show_image(media_id, width, height, session_name, session_id)
{
	isMSIE = (navigator.appName == "Microsoft Internet Explorer");
	screen_width = width + 20;
	screen_height = height + 20;

	padding_top = 50;
	padding_left = 50;

	if (isMSIE)
	{
		screen_width += 20;
		screen_height += 10;
	}

	if (screen_width > screen.availWidth)
		screen_width = screen.availWidth - padding_top;
	if (screen_height > screen.availHeight)
		screen_height = screen.availHeight - padding_left;
	if (session_name != null && session_id != null)
		url = window.document.getElementsByTagName('base')[0].href + "media.php5?media_id=" + media_id + "&width=" + width + "&height=" + height + "&" + session_name + "_id=" + session_id;
	else
		url = window.document.getElementsByTagName('base')[0].href + "media.php5?media_id=" + media_id + "&width=" + width + "&height=" + height
	window.open(url , "uniBoxMediaShowImage", "width=" + screen_width + ",height=" + screen_height + ",left=" + padding_left + ",top=" + padding_top + ",dependent=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

// set external links to open in new windows
function external_links()
{
	if (!document.getElementsByTagName)
	 	return;
	var anchors = document.getElementsByTagName("a");
	for (var i=0; i<anchors.length; i++)
	{
		var anchor = anchors[i];
		if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "external")
			anchor.target = "_blank";
	}
}

function administration_check_all(ident)
{
	form = document.getElementById('administration_' + ident);
	if (form)
		if (isNaN(form.elements['administration_checkbox[]'].length))
			form.elements['administration_checkbox[]'].checked = true;
		else
			for (i = 0; i < form.elements['administration_checkbox[]'].length; i++)
				form.elements['administration_checkbox[]'][i].checked = true;
}

function administration_uncheck_all(ident)
{
	form = document.getElementById('administration_' + ident);
	if (form)
		if (isNaN(form.elements['administration_checkbox[]'].length))
			form.elements['administration_checkbox[]'].checked = false;
		else
			for (i = 0; i < form.elements['administration_checkbox[]'].length; i++)
				form.elements['administration_checkbox[]'][i].checked = false;
}
