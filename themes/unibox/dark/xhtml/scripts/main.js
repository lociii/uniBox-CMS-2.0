function toggle_instanthelp(theme_base, text_show, text_vanish)
{
    isMSIE = (navigator.appName == "Microsoft Internet Explorer");
    element_help = document.getElementById("page_help");
    element_main = document.getElementById("page_main");
    element_main_inner = document.getElementById("page_main_inner");
    element_image = document.getElementById("image_toggle_instanthelp");
    element_tr = document.getElementById("main_corner_tr");
    element_br = document.getElementById("main_corner_br");
    if (element_image)
    {
        if (element_help.style.display == 'none')
        {
            element_help.style.display = 'block';
            element_main.style.marginRight = '17em';
            element_main_inner.style.marginRight = '8px';
            element_image.setAttribute('src', theme_base + '/media/images/instanthelp_vanish.gif');
            element_image.setAttribute('alt', text_vanish);
            element_image.setAttribute('title', text_vanish);
            if (isMSIE)
            {
                element_tr.style.right = '0px';
                element_br.style.right = '0px';
            }
        }
        else
        {
            element_help.style.display = 'none';
            element_main.style.marginRight = '0em';
            element_main_inner.style.marginRight = '0px';
            element_image.setAttribute('src', theme_base + '/media/images/instanthelp_show.gif');
            element_image.setAttribute('alt', text_show);
            element_image.setAttribute('title', text_show);
            if (isMSIE)
            {
                element_tr.style.right = '-1px';
                element_br.style.right = '-1px';
            }
        }
    }
    else
    {
        element_help.style.display = 'none';
        element_main.style.marginRight = '0em';
        element_main_inner.style.marginRight = '0px';
        if (isMSIE)
        {
            element_tr.style.right = '-1px';
            element_br.style.right = '-1px';
        }
    }
}

function toggle_sessioninfo()
{
    isMSIE = (navigator.appName == "Microsoft Internet Explorer");
    element_sessioninfo = document.getElementById("box_session_info");
    if (element_sessioninfo.style.display == 'none')
        element_sessioninfo.style.display = 'block';
    else
        element_sessioninfo.style.display = 'none';
}

//show full image in popup
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

function set_checked_value(form_name, field_name, value)
{
	form = document.getElementById(form_name);
	field_name = field_name + '[]';
	if (form)
		if (isNaN(form.elements[field_name].length))
			form.elements[field_name].checked = value;
		else
			for (i = 0; i < form.elements[field_name].length; i++)
				form.elements[field_name][i].checked = value;
}

function administration_check_all(ident)
{
	set_checked_value('administration_' + ident, 'administration_checkbox', true);
}

function administration_uncheck_all(ident)
{
	set_checked_value('administration_' + ident, 'administration_checkbox', false);
}

function administration_toggle(theme_base, ident, address, collapse)
{
    isMSIE = (navigator.appName == "Microsoft Internet Explorer");

	i = 1;
	while (true)
	{
		dataset = document.getElementById('dataset_' + ident + '_' + address + '_' + i);
		
		if (!dataset)
			break;
			
		if (dataset.style.display == 'none' && !collapse)
			dataset.removeAttribute('style');
		else
		{
			dataset.style.display = 'none';
			if (document.getElementById('dataset_' + ident + '_' + address + '_' + i +'_1'))
				administration_toggle(theme_base, ident, address + '_' + i, true);
		}
			
		i++;
		last_dataset = dataset;
	}

	nesting = document.getElementById('nesting_' + ident + '_' + address);
	if (last_dataset)
	{
		if (last_dataset.style.display == 'none')
			nesting.setAttribute('src', theme_base + '/media/images/expand.gif');
		else
			nesting.setAttribute('src', theme_base + '/media/images/collapse.gif');
	}
	else
		nesting.setAttribute('src', theme_base + '/media/images/expand.gif');

	if (isMSIE)
	{
		document.getElementById('content').style.display = 'none';
		document.getElementById('content').style.display = 'block';
	}
}

function set_color(form_name, field_name, value)
{
	if (value.match(/^([a-fA-F0-9]{2})([a-fA-F0-9]{2})([a-fA-F0-9]{2})$|^([a-fA-F0-9]{1})([a-fA-F0-9]{1})([a-fA-F0-9]{1})$/))
	{
		document.getElementById(form_name).elements[field_name].value = value;
		document.getElementById('color_preview_' + field_name).style.backgroundColor = '#' + value;
		document.getElementById('color_preview_' + field_name).innerHTML = '';
	}
	else
	{
		document.getElementById('color_preview_' + field_name).style.backgroundColor = '#FFFFFF';
		document.getElementById('color_preview_' + field_name).innerHTML = '?';
	}
}

function toggle_color_picker(theme_base, text_show, text_vanish)
{
    isMSIE = (navigator.appName == "Microsoft Internet Explorer");
    element_image = document.getElementById("image_toggle_color_picker");
    element_picker = document.getElementById("color_picker");
    if (element_picker)
    {
        if (element_picker.style.display == 'none')
        {
        	element_picker.style.display = 'block';
            element_image.setAttribute('src', theme_base + '/media/images/instanthelp_vanish.gif');
            element_image.setAttribute('alt', text_vanish);
            element_image.setAttribute('title', text_vanish);
        }
        else
        {
        	element_picker.style.display = 'none';
            element_image.setAttribute('src', theme_base + '/media/images/instanthelp_show.gif');
            element_image.setAttribute('alt', text_show);
            element_image.setAttribute('title', text_show);
        }
    }
}