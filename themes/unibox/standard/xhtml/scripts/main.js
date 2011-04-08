function unibox_js()
{
	this.version = '0.1';

	// holds all window onload events
	this.load_events = new Array();
	this.load_events_args = new Array();

	// holds all window unonload events
	this.unload_events = new Array();
	this.unload_events_args = new Array();

	// add window onload event
	unibox_js.prototype.add_load_event = function(function_name, args)
	{
		if (eval('window.' + function_name))
		{
			this.load_events[this.load_events.length] = function_name;
			if (args != null)
				this.load_events_args[function_name] = args;
		}
	}

	// add window unonload event
	unibox_js.prototype.add_unload_event = function(function_name, args)
	{
		if (eval('window.' + function_name))
		{
			this.unload_events[this.unload_events.length] = function_name;
			if (args != null)
				this.unload_events_args[function_name] = args;
		}
	}

	// process window onload events
	unibox_js.prototype.process_load_events = function()
	{
		for (load_events_counter = 0; load_events_counter < this.load_events.length; load_events_counter++)
			if (this.load_events_args[this.load_events[load_events_counter]] != null)
				eval(this.load_events[load_events_counter] + '(this.load_events_args[this.load_events[load_events_counter]])');
			else
				eval(this.load_events[load_events_counter] + '()');
	}

	// process window unonload events
	unibox_js.prototype.process_unload_events = function()
	{
		for (unload_events_counter = 0; unload_events_counter < this.unload_events.length; unload_events_counter++)
			if (this.unload_events_args[this.unload_events[unload_events_counter]] != null)
				eval(this.unload_events[unload_events_counter] + '(this.unload_events_args[this.unload_events[unload_events_counter]])');
			else
				eval(this.unload_events[unload_events_counter] + '()');
	}

	// add css class to object
	unibox_js.prototype.add_css_class = function(element, class_name)
	{
		removeClassName(element, class_name);
		element.class_name = (element.class_name + " " + class_name).trim();
	}

	// remove css class from object
	unibox_js.prototype.remove_css_class = function(element, class_name)
	{
		element.class_name = element.class_name.replace(class_name, '').trim();
	}
}

String.prototype.trim = function()
{
	return this.replace(/^\s+|\s+$/, '');
}

top.unibox_js = new unibox_js();

window.onload = function()
{
	top.unibox_js.process_load_events();
}

window.onunload = function()
{
	top.unibox_js.process_unload_events();
}

//set external links to open in new windows
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
top.unibox_js.add_load_event('external_links');

function toggle_sessioninfo()
{
    isMSIE = (navigator.appName == "Microsoft Internet Explorer");
    if (element_sessioninfo = document.getElementById("box_session_info"))
    {
	    if (element_sessioninfo.style.display == 'none')
	        element_sessioninfo.style.display = 'block';
	    else
	        element_sessioninfo.style.display = 'none';
    }
}
top.unibox_js.add_load_event('toggle_sessioninfo');

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