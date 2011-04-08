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

function toggle_color_picker(ident, theme_base, text_show, text_vanish)
{
    isMSIE = (navigator.appName == 'Microsoft Internet Explorer');
    element_image = document.getElementById('toggle_color_picker_' + ident);
    element_picker = document.getElementById('color_picker_' + ident);
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