
function uniBoxNewsletter()
{
	this.version = '0.1';

	uniBoxNewsletter.prototype.init = function(tinyMCE)
	{
		this.tinyMCE = tinyMCE;

	}

	// resize and move window
	uniBoxNewsletter.prototype.move = function()
	{
		window.moveTo((screen.width - 500) / 2, (screen.height - 365) / 2);
	}
	
	uniBoxNewsletter.prototype.checkForSelection = function()
	{
		if (tinyMCE.selectedInstance)
		{
			var inst = tinyMCE.selectedInstance;
			var selectedText = inst.getSelectedText();
		
			if (this.tinyMCE.selectedElement && selectedText && selectedText.length > 0)
			{
				elem = document.getElementById('replacement_unsubscribe_link');
				elem.checked = true;
				elem = document.getElementById('unsubscribe_link_type_link');
				elem.disabled = false;
				elem.checked = true;
			}
			else
				elem = document.getElementById('unsubscribe_link_type_url').checked = true;
		}
	}
	
	uniBoxNewsletter.prototype.loadContentDialog = function()
	{
		window.location.href = '../../plugins/newsletter/insert_content.htm';
		window.resizeTo(700, 500);
		window.moveTo((screen.width - 700) / 2, (screen.height - 500) / 2);
	}
	
	uniBoxNewsletter.prototype.insert = function()
	{
		var elem = document.forms[0].elements.replacement;
		var replacement = this.getRadioValue(elem);
		switch (replacement)
		{
			case "title":
			case "firstname":
			case "lastname":
			case "email":
				this.insertHTML('[replace module="__static" content="' + replacement + '"/]');
				return;
				
			case "unsubscribe_link":
				var elem = document.forms[0].elements.unsubscribe_link_type;
				var type = this.getRadioValue(elem);
				if (type)
				{
					if (type == 'link')
					{
						if (this.tinyMCE.isSafari)
							this.tinyMCE.execCommand("mceInsertContent", false, '<a href="[replace module=\'__static\' content=\'unsubscribe_link\'/]">' + inst.getSelectedHTML() + '</a>');
						else
							tinyMCE.execCommand("createlink", false, "[replace module=\'__static\' content=\'unsubscribe_link\'/]");
						
						window.close();
						return;
					}
					else
					{
						this.insertHTML('[replace module="__static" content="unsubscribe_link"/]');
						return;
					}
				}
				else
					alert(tinyMCELang['lang_newsletter_no_linktype_selected']);
				break;
				
			case "content":
				this.loadContentDialog();
				break;
				
			default:
				alert(tinyMCELang['lang_newsletter_no_replacement_selected']);
		}
	}

	uniBoxNewsletter.prototype.insertHTML = function(html)
	{
		this.tinyMCE.execCommand("mceInsertContent", false, html);
		window.close();
	}
	
	uniBoxNewsletter.prototype.getRadioValue = function(elem)
	{
		if (!elem)
			return 'undefined';

		for (i = 0; i < elem.length; i++)
			if (elem[i].checked)
				return elem[i].value;
		
		return false;
	}
}

// instantiate object
uniBoxNewsletter = new uniBoxNewsletter();
uniBoxNewsletter.init(opener.tinyMCE);