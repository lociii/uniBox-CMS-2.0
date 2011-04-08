function administration_check_all(ident)
{
	set_checked_value('administration_' + ident, 'administration_checkbox', true);
}

function administration_uncheck_all(ident)
{
	set_checked_value('administration_' + ident, 'administration_checkbox', false);
}

var openNodes = new Object();
var idents = new Object();
var administrations = new Array();

function administration_toggle(theme_base, ident, address, collapse)
{
    isMSIE = (navigator.appName == "Microsoft Internet Explorer");
	
	var i = 1;
	while (true)
	{
		dataset = document.getElementById('dataset_' + ident + '_' + address + '_' + i);
		if (!dataset)
			break;

		if (dataset.style.display == 'none' && !collapse)
		{
			dataset.removeAttribute('style');
			openNodes[idents[ident + '_' + address]] = 1;
			
			if (document.getElementById('dataset_' + ident + '_' + address + '_' + i + '_1') && openNodes[idents[ident + '_' + address + '_' + i]] == 1)
				administration_toggle(theme_base, ident, address + '_' + i, false);
		}
		else
		{
			if (collapse && dataset.style.display != 'none')
				openNodes[idents[ident + '_' + address]] = 1;
			else
				openNodes[idents[ident + '_' + address]] = 0;
			dataset.style.display = 'none';
			
			if (document.getElementById('dataset_' + ident + '_' + address + '_' + i + '_1'))
				administration_toggle(theme_base, ident, address + '_' + i, true);
		}
		i++;
	}

	nesting = document.getElementById('nesting_' + ident + '_' + address);
	if (openNodes[idents[ident + '_' + address]] == 1)
		nesting.setAttribute('src', theme_base + '/media/images/collapse.gif');
	else
		nesting.setAttribute('src', theme_base + '/media/images/expand.gif');

	administration_recolor(ident);
	
	if (isMSIE)
	{
		document.getElementById('content').style.display = 'none';
		document.getElementById('content').style.display = 'block';
	}
}

function administration_recolor(ident)
{
	table = document.getElementById('administration_table_' + ident);
	rows = table.getElementsByTagName('tr');

	colorState = 0;
	for (i = 0; i < rows.length; i++)
		if (rows[i].style.display != 'none')
		{
			if (rows[i].className.search('highlight_error') != -1)
				rows[i].className = 'highlight_error';
			else
				rows[i].className = '';

			if (colorState % 2 == 0)
				rows[i].className += ' highlight_dark';
			else
				rows[i].className += ' highlight_light';
			colorState++;
		}
}

function administration_save_state()
{
	base = document.getElementsByTagName('base')[0].href;
	if (base.search('http://') != -1)
		offset = 7;
	else if (base.search('https://') != -1)
		offset = 8;
	else
		offset = 0;

	pathPos = base.indexOf('/', offset);
	domain = base.substring(offset, pathPos);
	path = base.substr(pathPos);

	if (path.charAt(path.length - 1) == '/')
		path = path.substr(0, path.length - 1);

	var cookieStr = '@';
	for (var ident in openNodes)
		cookieStr += ident + '=' + openNodes[ident] + ':';

	if (cookieStr != '@')
		document.cookie = cookieStr + ';path=' + path + ';domain=' + domain;
}

function administrations_init(theme_base)
{
	cookieStr = document.cookie;
	cookies = cookieStr.split(';');

	for (i = 0; i < cookies.length; i++)
	{
		cookie = cookies[i];
		if (cookie.charAt(0) == " ")
			cookie = cookie.substr(1);

		if (cookie.charAt(0) == '@')
		{
			var x = 1;
			cookie = cookie.substr(1);

			while (cookie.charAt(0) != ';' && x < 1000)
			{
				posEqual = cookie.indexOf('=');
				posColon = cookie.indexOf(':');

				ident = cookie.substring(0, posEqual);
				value = cookie.substring(posEqual + 1, posColon);
				cookie = cookie.substr(posColon + 1);

				openNodes[ident] = value;
				x++;
			}
		}
	}

	for (var index in administrations)
	{
		var ident = administrations[index];
		var i = 1;

		while (true)
		{
			dataset = document.getElementById('dataset_' + ident + '_' + i);
			if (!dataset)
				break;

			administration_init(theme_base, ident, i, false);
			i++;
		}
	}

	if (navigator.appName == "Microsoft Internet Explorer")
	{
		document.getElementById('content').style.display = 'none';
		document.getElementById('content').style.display = 'block';
	}
}

function administration_init(theme_base, ident, address, collapse)
{
	nesting = document.getElementById('nesting_' + ident + '_' + address);
	if (openNodes[idents[ident + '_' + address]] == 1 && nesting)
		nesting.setAttribute('src', theme_base + '/media/images/collapse.gif');

	var i = 1;
	while (true)
	{
		dataset = document.getElementById('dataset_' + ident + '_' + address + '_' + i);
		if (!dataset)
			break;

		if (openNodes[idents[ident + '_' + address]] == undefined || openNodes[idents[ident + '_' + address]] == 0 || collapse)
			dataset.style.display = 'none';

		if (document.getElementById('dataset_' + ident + '_' + address + '_' + i + '_1'))
			administration_init(theme_base, ident, address + '_' + i, openNodes[idents[ident + '_' + address]] == 0);
		
		i++;
	}
	
	administration_recolor(ident);
}