<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       14.03.2005  jn/pr   1st release\n
* 0.11      05.04.2005	pr		fixed add_option_sql() to use *_optgroup functions\n
* 0.12      05.04.2005	pr		added commentsn\n
* 0.13      05.04.2005	pr		changed location of session- and db-references\n
* 0.14      06.04.2005  jn      changed template loading\n
* 0.2       12.06.2005	pr		changed to form specific data storage\n
* 0.21      14.07.2005  jn      cleaned sth up\n
* 0.22		23.01.2006	pr		added import_form_spec-function\n
* 0.23      23.02.2006  jn      custom destination directory for file handling
* 0.24		14.07.2006	pr		added comments, field disabling, minor fixes
*
*/

class ub_form_creator
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

	/**
	 * $unibox
	 * 
	 * holds the framework instance
	 * 
	 * @access	protected
	 */
	protected $unibox;

    /**
    * unibox framework object
    * 
    */
	protected static $instance = null;

    /**
    * all processed editors names to replace texareas
    * 
    */
    protected $editors = array();

	/**
	* holds a reference to the current element
	* 
	*/
	protected $current_element;

	/**
	* name of current multilanguage element
	* 
	*/
	protected $current_ml_element_name;

	/**
    * holds the current submit button index
	* 
	*/
	protected $submit_index = 0;

	/**
    * indicates if descriptions/labels are to be translated
    *
    */
	protected $translate = true;

    /**
    * indicates if the required-text has already been set
    * 
    */
    protected $required_set = false;

    protected $editor;

    protected $labels = array();
    
    protected $ml_elements = array();

    /**
    * returns class version
    * 
    * @return   float       version-number
    */
    public static function get_version()
    {
        return ub_form_creator::version;
    } // end get_version()

	/**
	* class contructor function
	* 
	*/
	protected function __construct()
	{
		$this->unibox = ub_unibox::get_instance();
	} // end __construct()

	/**
	* returns class instance
	* 
	*/
	public static function get_instance()
	{
		if (self::$instance === null)
			self::$instance = new ub_form_creator;
		return self::$instance;
	} // end get_instance()

	/**
	* sets the translation flag
	* 
	* @param       $value          translation flag (bool)
	*/
	public function set_translation($value)
	{
		$this->translate = $value;
	} // end set_translation()

	public function set_current_form($name)
	{
		if (!isset($this->unibox->session->env->form->$name->spec))
			$this->create_form_spec($name);
		$this->current_form = &$this->unibox->session->env->form->$name;
	}

	public function form_defined($name)
	{
		return isset($this->unibox->session->env->form->$name->spec);
	}

	protected function create_form_spec($name)
	{
		$this->unibox->session->env->form->$name->spec->name = $name;
		$this->unibox->session->env->form->$name->spec->restore = false;
		$this->unibox->session->env->form->$name->spec->failed = false;
		$this->unibox->session->env->form->$name->spec->secure = false;
		$this->unibox->session->env->form->$name->spec->elements = new stdClass;
        $this->unibox->session->env->form->$name->spec->destructor = new stdClass;
        $this->unibox->session->env->form->$name->error = array();
	}
	
	/**
	* initializes a new form
	* 
	* @param       $name           form name (string)
	* @param       $action         action url (string)
	* @param       $enctype        encoding (string)
	* @param       $method         method (string)
	*/
	public function begin_form($name, $action, $encoding = '', $method = 'POST')
	{
        // load template
        $this->unibox->load_template('shared_form');
        
		$this->unibox->xml->add_node('form');
        $this->unibox->xml->set_marker('current_form');
		$this->unibox->xml->set_attribute('name', $name);
		$this->unibox->xml->set_attribute('action', $action);
		$this->unibox->xml->set_attribute('encoding', $encoding);
		$this->unibox->xml->set_attribute('method', strtolower($method));

		if (!isset($this->unibox->session->env->form->$name->spec))
			$this->create_form_spec($name);

		$this->current_form = &$this->unibox->session->env->form->$name;
		$this->current_form->spec->method = $method;

        if (DEBUG == 3)
        {
            $this->begin_fieldset('TRL_DEBUG');
            $this->checkbox('debug_output_xml', 'TRL_DEBUG_OUTPUT_XML');
            $this->end_fieldset();
        }
	} // end begin_form()

	/**
    * finishes current form
	* 
	*/
	public function end_form()
	{
        // add form validation hash
        $this->current_form->spec->hash = md5(uniqid(mt_rand()));
        
        $restore = $this->current_form->spec->restore;
        $this->current_form->spec->restore = false;
        $this->hidden('form_validation_hash_'.$this->current_form->spec->name, $this->current_form->spec->hash);
        $this->current_form->spec->restore = $restore;
        
        // translate field descriptions
        $sql_string  = 'SELECT
                          string_ident,
                          string_value
                        FROM
                          sys_translations
                        WHERE
                          string_ident IN (\''.implode('\', \'', $this->labels).'\')
                          AND
                          lang_ident = \''.$this->unibox->session->lang_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select element labels');
        while (list($string_ident, $string_value) = $result->fetch_row())
            $this->labels[$string_ident] = $string_value;
        $result->free();

        foreach ($this->current_form->spec->elements as $name => $element)
        {
            if (isset($element->label) && isset($this->labels[$element->label]))
                $this->current_form->spec->elements->$name->label = $this->labels[$element->label];
            if (isset($element->label_multilang) && isset($this->labels[$element->label_multilang]))
                $this->current_form->spec->elements->$name->label_multilang = $this->labels[$element->label_multilang];
        }

        // transform editors        
        if (count($this->editors) > 0)
        {
        	$this->editor = new StdClass();
	        $this->editor->plugins = 'table,acronym,media,newsletter,advhr,advlink,emotions,insertdatetime,preview,zoom,flash,searchreplace,print,paste,directionality,fullscreen,noneditable,contextmenu';
	        $this->editor->buttons1 = 'bold,italic,underline,strikethrough,separator,insertdate,inserttime,separator,emotions,separator,acronym';
	        $this->editor->buttons2 = 'cut,copy,paste,pastetext,pasteword,separator,search,replace,separator,bullist,numlist,separator,undo,redo,separator,advhr,separator,link,unlink,anchor';
	        
	        // check for media library and newsletter
			if ($this->unibox->module_available('media'))
				$this->editor->buttons2 .= ',image';
			if ($this->unibox->module_available('newsletter'))
				$this->editor->buttons2 .= ',newsletter';

	        $this->editor->buttons2 .= ',cleanup,help,code';
        	$this->editor->buttons3 = 'tablecontrols,separator,removeformat,visualaid,separator,sub,sup,separator,charmap,iespell,flash,separator,print,separator,fullscreen';

            $this->unibox->xml->add_node('input');
            $this->unibox->xml->set_attribute('type', 'editors_show');
            $this->unibox->xml->add_value('language', $this->unibox->session->lang_ident);
            $this->unibox->xml->add_value('editor_dir', DIR_EDITOR);
            $this->unibox->xml->add_value('plugins', $this->editor->plugins);
            $this->unibox->xml->add_value('buttons1', $this->editor->buttons1);
            $this->unibox->xml->add_value('buttons2', $this->editor->buttons2);
            $this->unibox->xml->add_value('buttons3', $this->editor->buttons3);
            foreach ($this->editors as $editor)
                $this->unibox->xml->add_value('editor', $editor);

			$sql_string  = 'SELECT
							  a.subtheme_ident,
							  a.theme_ident,
							  c.string_value,
							  d.string_value
							FROM
							  sys_subthemes AS a
							  LEFT JOIN sys_themes AS b
								ON b.theme_ident = a.theme_ident
							  LEFT JOIN sys_translations AS c
								ON c.string_ident = a.si_subtheme_descr
							  LEFT JOIN sys_translations AS d
								ON d.string_ident = b.si_theme_descr
							WHERE
							  (c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								OR
							  c.lang_ident IS NULL)
							  AND
							  (d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								OR
							  d.lang_ident IS NULL)
							ORDER BY
							  d.string_value, c.string_value';
			$result = $this->unibox->db->query($sql_string, 'failed to select themes');
			if ($result->num_rows() > 0)
        	{
				while (list($subtheme_ident, $theme_ident, $subtheme_descr, $theme_descr) = $result->fetch_row())
				{
					$this->unibox->xml->add_node('theme');
					$this->unibox->xml->add_value('ident', $theme_ident);
					$this->unibox->xml->add_value('descr', $theme_descr);
					$this->unibox->xml->add_node('subtheme');
					$this->unibox->xml->add_value('ident', $subtheme_ident);
					$this->unibox->xml->add_value('descr', $subtheme_descr);
					$this->unibox->xml->parse_node();
					$this->unibox->xml->parse_node();
				}
				$result->free();
        	}
            $this->unibox->xml->parse_node();
        }

		if ($this->current_form->spec->restore)
		{
			// display all errors for this form
			foreach ($this->current_form->error as $err)
            {
            	$field = isset($this->ml_elements[$err->field]) ? reset($this->ml_elements[$err->field]) : $err->field;
            	
				if (isset($err->args) && is_array($err->args))
					array_unshift($err->args, $this->current_form->spec->elements->$field->label);
				else
					$err->args = array($this->current_form->spec->elements->$field->label);

            	if (isset($this->current_form->spec->elements->$field->label_multilang))
        			array_unshift($err->args, $this->current_form->spec->elements->$field->label_multilang);

				$this->unibox->xml->add_value('error', $err->message, true, $err->args);
            }

			$this->current_form->error = array();
			$this->current_form->spec->restore = false;
			$this->current_form->spec->failed = false;
		}
		while ($this->unibox->xml->get_tagname() != 'form')
			$this->unibox->xml->parse_node();
		$this->unibox->xml->parse_node();
		
		// fix selects ...
		foreach ($this->current_form->spec->elements as $name => $element)
		{
			if ($element->input_type == INPUT_SELECT)
			{
				if (ub_validator::has_condition($element, CHECK_NOTEMPTY))
				{
				    if (isset($element->options) && $element->show_please_select && count($element->options) == 2)
					{
						$this->unibox->xml->goto('select_'.$name);
						$this->unibox->xml->remove('input');
						$this->unibox->xml->restore();
						unset($element->options[0]);
					}
					elseif (!isset($element->options) || (isset($element->options) && (($element->show_please_select && count($element->options) == 1) || count($element->options) == 0)))
					{
						// add comment
						$this->unibox->xml->goto('select_'.$name);
						$this->unibox->xml->set_attribute('disabled', 1);
						$this->unibox->xml->add_node('comment');
						$this->unibox->xml->add_value('value', 'TRL_NO_OPTIONS', true);
						$this->unibox->xml->parse_node();
						$this->unibox->xml->restore();

						// show error message
						$msg = new ub_message(MSG_ERROR, false);
						$msg->add_text('TRL_ERR_REQUIRED_FIELD_CONTAINS_NO_DATA', array($element->label));
						$msg->display();
						
						// remove submit buttons
						foreach ($this->current_form->spec->submit_indices as $ident)
						{
							$this->unibox->xml->goto('submit_'.$ident);
							$this->unibox->xml->add_value('disabled', 1);
							$this->unibox->xml->restore();
						}
						$this->current_form->spec->submit_indices = array();
					}
				}
				else
				{
					if (!isset($element->options) || (isset($element->options) && (($element->show_please_select && count($element->options) == 1) || count($element->options) == 0)))
					{
						$this->unibox->xml->goto('select_'.$name);
						$this->unibox->xml->set_attribute('disabled', 1);
						$this->unibox->xml->add_node('comment');
						$this->unibox->xml->add_value('value', 'TRL_NO_OPTIONS', true);
						$this->unibox->xml->parse_node();
						$this->unibox->xml->restore();
					}
				}
			}
            elseif ($element->input_type == INPUT_CHECKBOX_MULTI && ub_validator::has_condition($element, CHECK_NOTEMPTY) && isset($element->options) && count($element->options) == 1)
            {
                $this->unibox->xml->goto('checkbox_multi_'.$name);
                $options = $this->unibox->xml->get_child_nodes();
                $option_values = $options->item(0)->childNodes;
                foreach ($option_values as $key => $option_value)
                    if ($option_value->nodeName == 'selected')
                        $options->item(0)->removeChild($option_value);
                $this->unibox->xml->restore();
                
                $this->unibox->xml->goto('checkbox_multi_first_'.$name);
                $this->unibox->xml->add_value('selected', 1);
                $this->unibox->xml->restore();
            }
		}
	} // end end_form()

    public function register_with_dialog()
    {
    	if (ub_dialog::instance_exists())
    	{
    		$dialog = ub_dialog::get_instance();
    		$dialog->register_form($this->current_form->spec->name);
    	}
    }

    /**
    * sets the destructor function for the current form
    * 
    * @param        $class          class name
    * @param        $function       function name
    */
    public function set_destructor($class, $function)
    {
	    $this->current_form->spec->destructor->class = $class;
	    $this->current_form->spec->destructor->function = $function;
    } 

	/**
	* begins a new buttonset
	* 
    * @param        $class          css class (string)
	*/
    public function begin_buttonset($indent = true)
    {
        $this->unibox->xml->add_node('input');
        $this->unibox->xml->set_attribute('type', 'buttonset');
        if (!$indent)
        	$this->unibox->xml->set_attribute('class', 'buttons_no_indent');
        else
        {
        	$this->unibox->xml->goto('last_child');
        	$type = $this->unibox->xml->get_attribute('type');
			$this->unibox->xml->restore();

        	if (in_array($type, array('fieldset', 'radio', 'text_multilang', 'textarea_multilang', 'checkbox_multi')))
        		$this->unibox->xml->set_attribute('class', 'buttons_indent_fieldset');
        	else
        		$this->unibox->xml->set_attribute('class', 'buttons_indent_no_fieldset');
        }
    } // end begin_buttonset()

	/**
    * finishes current buttonset
	* 
    */
	public function end_buttonset()
	{
		$this->unibox->xml->parse_node();
	} // end end_buttonset()
	
	/**
    * begins a new fieldset
    * 
    * @param        $si_descr           fieldset description (string)
    */
	public function begin_fieldset($si_descr, $args = array())
	{
		$this->unibox->xml->add_node('input');
        $this->unibox->xml->set_attribute('type', 'fieldset');
		$this->unibox->xml->set_attribute('descr', $si_descr, $this->translate, $args);
	} // end begin_fieldset()

	/**
    * finishes current fieldset
    * 
    */
	public function end_fieldset()
	{
		$this->unibox->xml->parse_node();
	} // end end_fieldset()
    
	/**
    * adds a new text field
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $value              default value (string)
    * @param        $width              field size (int)
    */
	public function text($name, $si_label = '', $value = '', $width = 0, $si_label_multilang = null)
	{
		if ($this->current_form->spec->restore)
			$value = $this->get_value($name, $value);
		
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_TEXT);
		$this->unibox->xml->add_value('name', $name);
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		if ($width != 0)
			$this->unibox->xml->add_value('width', $width);
		$this->unibox->xml->add_value('value', $value);
        $this->unibox->xml->add_value('error', $this->has_failed($name));
		$this->unibox->xml->set_marker($name);
		$this->unibox->xml->parse_node();
		
		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_TEXT;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;

		if ($si_label_multilang !== null)
		{
	        $this->current_form->spec->elements->$name->label_multilang = $si_label_multilang;
	        $this->labels[] = $this->current_form->spec->elements->$name->label_multilang;
		}
		$this->current_element = &$this->current_form->spec->elements->$name;
	} // end text()

	/**
    * adds a new color field
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $value              default value (string)
    */
	public function color($name, $si_label = '', $value = '')
	{
		$this->unibox->load_template('shared_form_colorpicker');
		
		if ($this->current_form->spec->restore)
			$value = $this->get_value($name, $value);
		
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_COLOR);
		$this->unibox->xml->add_value('name', $name);
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		$this->unibox->xml->add_value('value', $value);
        $this->unibox->xml->add_value('error', $this->has_failed($name));
		$this->unibox->xml->set_marker($name);
		$this->unibox->xml->parse_node();
		
		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_COLOR;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;

		$this->current_element = &$this->current_form->spec->elements->$name;
	} // end text()

    /**
    * adds a new password field
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $value              default value (string)
    * @param        $width              field size (int)
    */
    public function password($name, $si_label, $value = '', $width = 0)
    {
        // does it make sense to restore password fields?
        /*
        if ($this->current_form->spec->restore)
        	$value = $this->get_value($name, $value);
        */
        
        $this->unibox->xml->add_node('input');
        $this->unibox->xml->set_attribute('type', INPUT_PASSWORD);
        $this->unibox->xml->add_value('name', $name);
        $this->unibox->xml->add_value('label', $si_label, $this->translate);
        if ($width != 0)
        	$this->unibox->xml->add_value('width', $width);
        $this->unibox->xml->add_value('value', $value);
        $this->unibox->xml->add_value('error', $this->has_failed($name));
        $this->unibox->xml->parse_node();
        
        $this->current_form->spec->elements->$name = new stdClass;
        $this->current_form->spec->elements->$name->input_type = INPUT_TEXT;
        $this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;
        $this->current_element = &$this->current_form->spec->elements->$name;
    } // end password()

    /**
    * adds a new text field (for all languages)
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $width              field size (int)
    */
    public function text_multilanguage($name, $si_label, $width = 0)
    {
        $this->begin_fieldset($si_label);
        $this->unibox->xml->set_attribute('error', $this->has_failed($name));
		$this->unibox->xml->set_marker($name);
		$sql_string  = 'SELECT
                          a.lang_ident,
                          a.si_lang_descr
                        FROM sys_languages AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_lang_descr
						WHERE
						  a.lang_active = 1
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						ORDER BY
						  b.string_value ASC';
        $result = $this->unibox->db->query($sql_string, 'failed to get all languages for multilanguage textfield');
        if ($result->num_rows() > 0)
        {
	        while (list($lang_ident, $si_lang_descr) = $result->fetch_row())
	        {
	            $this->text('multilang_'.$lang_ident.'_'.$name, $si_lang_descr, '', $width, $si_label);
	            $this->ml_elements[$name][] = 'multilang_'.$lang_ident.'_'.$name;
	        }
	        $result->free();
        }

        $this->end_fieldset();
        $this->current_ml_element_name = $name;
    } // end text()

	/**
    * adds a new textarea
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $value              default value (mixed)
    * @param        $width              number of columns (int)
    * @param        $height             number of rows (int)
    */
	public function textarea($name, $si_label, $value = '', $width = 0, $height = 0, $si_label_multilang = null)
	{
		if ($this->current_form->spec->restore)
			$value = $this->get_value($name, $value);
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_TEXTAREA);
		$this->unibox->xml->add_value('name', $name);
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		if ($width != 0)
			$this->unibox->xml->add_value('width', $width);
		if ($height != 0)
			$this->unibox->xml->add_value('height', $height);
		$this->unibox->xml->add_value('value', $value);
        $this->unibox->xml->add_value('error', $this->has_failed($name));
		$this->unibox->xml->set_marker($name);
		$this->unibox->xml->parse_node();

		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_TEXTAREA;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;

		if ($si_label_multilang !== null)
		{
	        $this->current_form->spec->elements->$name->label_multilang = $si_label_multilang;
	        $this->labels[] = $this->current_form->spec->elements->$name->label_multilang;
		}
		$this->current_element = &$this->current_form->spec->elements->$name;
	} // end textarea()

    /**
    * adds a new textarea (for all languages)
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $width              number of columns (int)
    * @param        $height             number of rows (int)
    */
    public function textarea_multilanguage($name, $si_label, $width = 0, $height = 0)
    {
        $this->begin_fieldset($si_label);
        $this->unibox->xml->set_attribute('error', $this->has_failed($name));
		$this->unibox->xml->set_marker($name);
		$sql_string  = 'SELECT
                          a.lang_ident,
                          a.si_lang_descr
                        FROM sys_languages AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_lang_descr
						WHERE
						  a.lang_active = 1
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						ORDER BY
						  b.string_value ASC';
        $result = $this->unibox->db->query($sql_string, 'failed to get all languages for multilanguage textarea');
        if ($result->num_rows() > 0)
        {
	        while (list($lang_ident, $si_lang_descr) = $result->fetch_row())
	        {
	            $this->textarea('multilang_'.$lang_ident.'_'.$name, $si_lang_descr, '', $width, $height, $si_label);
	            $this->ml_elements[$name][] = 'multilang_'.$lang_ident.'_'.$name;
	        }
	        $result->free();
        }
        $this->end_fieldset();

        $this->current_ml_element_name = $name;
    } // end text()

    /**
    * adds a new editor (js-replaced textarea)
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $value              default value (string)
    * @param        $width              number of columns (int)
    * @param        $height             number of rows (int)
    */
	public function editor($name, $si_label, $value = '', $height = 0, $si_label_multilang = null)
	{
        // TODO: browsercheck (don't show editor on browsers other than mozilla or ie)
        $this->unibox->editor_mode = true;

		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_EDITOR);
		$this->unibox->xml->add_value('name', $name);
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		if ($height != 0)
			$this->unibox->xml->add_value('height', $height);

		// transform ub-code to html
		$ubc = new ub_ubc();
		$validator = ub_validator::get_instance();
		if ($validator->form_sent($this->current_form->spec->name) && $this->current_form->spec->restore)
			$value = $this->get_value($name, $value);
		elseif($this->current_form->spec->restore)
			$value = $ubc->ubc2html($this->get_value($name, $value), null, null, array('editor'));
		else
			$value = $ubc->ubc2html($value, null, null, array('editor'));

		//$this->unibox->xml->add_value('value', preg_replace("/\r|\n/", '', $value));
        $this->unibox->xml->add_value('value', $value);
        $this->unibox->xml->add_value('error', $this->has_failed($name));
		$this->unibox->xml->set_marker($name);
		$this->unibox->xml->parse_node();

		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_EDITOR;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;
		$this->current_element = &$this->current_form->spec->elements->$name;
        $this->editors[] = $name;

		if ($si_label_multilang !== null)
		{
	        $this->current_form->spec->elements->$name->label_multilang = $si_label_multilang;
	        $this->labels[] = $this->current_form->spec->elements->$name->label_multilang;
		}

        $this->unibox->editor_mode = false;
	} // end editor()

    /**
    * adds a new editor for xml cached data (js-replaced textarea)
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $value              default value XML (string)
    * @param        $width              number of columns (int)
    * @param        $height             number of rows (int)
    */
	public function editor_xml($name, $si_label, $value = '', $height = 0, $si_label_multilang = null)
	{
        // TODO: browsercheck (don't show editor on browsers other than mozilla or ie)
        $this->unibox->editor_mode = true;

		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_EDITOR);
		$this->unibox->xml->add_value('name', $name);
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		if ($height != 0)
			$this->unibox->xml->add_value('height', $height);

		// transform ub-code to html
		$ubc = new ub_ubc();
		$validator = ub_validator::get_instance();
		if ($validator->form_sent($this->current_form->spec->name) && $this->current_form->spec->restore)
			$value = $this->get_value($name, $value);
		elseif($this->current_form->spec->restore)
			$value = $ubc->xml2html($this->get_value($name, $value), null, null, array('editor'));
		else
			$value = $ubc->xml2html($value, null, null, array('editor'));

		//$this->unibox->xml->add_value('value', preg_replace("/\r|\n/", '', $value));
        $this->unibox->xml->add_value('value', $value);
        $this->unibox->xml->add_value('error', $this->has_failed($name));
		$this->unibox->xml->set_marker($name);
		$this->unibox->xml->parse_node();

		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_EDITOR;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;
		$this->current_element = &$this->current_form->spec->elements->$name;
        $this->editors[] = $name;

		if ($si_label_multilang !== null)
		{
	        $this->current_form->spec->elements->$name->label_multilang = $si_label_multilang;
	        $this->labels[] = $this->current_form->spec->elements->$name->label_multilang;
		}

        $this->unibox->editor_mode = false;
	} // end editor()

    /**
    * adds a new editor (for all languages)
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $width              number of columns (int)
    * @param        $height             number of rows (int)
    */
    public function editor_multilanguage($name, $si_label, $height = 0)
    {
        $this->begin_fieldset($si_label);
        $this->unibox->xml->set_attribute('error', $this->has_failed($name));
		$this->unibox->xml->set_marker($name);
		$sql_string  = 'SELECT
                          a.lang_ident,
                          a.si_lang_descr
                        FROM sys_languages AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_lang_descr
						WHERE
						  a.lang_active = 1
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						ORDER BY
						  b.string_value ASC';
        $result = $this->unibox->db->query($sql_string, 'failed to get all languages for multilanguage textarea');
        if ($result->num_rows() > 0)
        {
	        while (list($lang_ident, $si_lang_descr) = $result->fetch_row())
	        {
	            $this->editor('multilang_'.$lang_ident.'_'.$name, $si_lang_descr, '', $height, $si_label);
	            $this->ml_elements[$name][] = 'multilang_'.$lang_ident.'_'.$name;
	        }
	        $result->free();
        }
        $this->end_fieldset();

        $this->current_ml_element_name = $name;
    } // end text()

    public function editor_set_plugins($text)
    {
        $this->editor->plugins = $text;
    }

    public function editor_set_buttons1($text)
    {
        $this->editor->buttons1 = $text;
    }

    public function editor_set_buttons2($text)
    {
        $this->editor->buttons2 = $text;
    }

    public function editor_set_buttons3($text)
    {
        $this->editor->buttons3 = $text;
    }

	/**
    * adds a new checkbox
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    * @param        $checked            checked (bool)
    */
	public function checkbox($name, $si_label, $checked = 0)
	{
		if ($this->current_form->spec->restore)
			$checked = $this->get_value($name, $checked);
		
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_CHECKBOX);
		$this->unibox->xml->add_value('name', $name);
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		$this->unibox->xml->add_value('checked', $checked);
		$this->unibox->xml->add_value('error', $this->has_failed($name));
		$this->unibox->xml->parse_node();

		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_CHECKBOX;
		$this->current_form->spec->elements->$name->value_type = TYPE_INTEGER;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;
		$this->current_element = &$this->current_form->spec->elements->$name;
	} // end checkbox()

	/**
    * begins a new checkbox-set
    * 
    * @param        $name               field name (string)
    * @param        $si_descr           field label (string)
    */
	public function begin_checkbox($name, $si_label)
	{
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_CHECKBOX_MULTI);
		$this->unibox->xml->set_attribute('name', $name);
		$this->unibox->xml->set_attribute('descr', $si_label, $this->translate);
        $this->unibox->xml->set_attribute('error', $this->has_failed($name));
        $this->unibox->xml->set_marker('checkbox_multi_'.$name);

		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_CHECKBOX_MULTI;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;
		$this->current_element = &$this->current_form->spec->elements->$name;
	} // end begin_radio()

	/**
    * finishes current checkbox-set
    * 
    */
	public function end_checkbox()
	{
		$this->unibox->xml->parse_node();
	} // end end_radio()

	/**
    * begins a new radio-buttonset
    * 
    * @param        $name               field name (string)
    * @param        $si_descr           field label (string)
    */
	public function begin_radio($name, $si_label)
	{
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_RADIO);
		$this->unibox->xml->set_attribute('name', $name);
		$this->unibox->xml->set_attribute('descr', $si_label, $this->translate);
        $this->unibox->xml->set_attribute('error', $this->has_failed($name));

		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_RADIO;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;
		$this->current_element = &$this->current_form->spec->elements->$name;
	} // end begin_radio()

	/**
    * finishes current radio-buttonset
    * 
    */
	public function end_radio()
	{
		$this->unibox->xml->parse_node();
	} // end end_radio()

	/**
    * begins a new select field
    * 
    * @param        $name               field name (string)
    * @param        $si_descr           field label (string)
    * @param        $show_please_select show 'please select' as first entry (bool)
    * @param        $size               field size
    */
	public function begin_select($name, $si_label, $show_please_select = true, $size = 0)
	{
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_marker('select_'.$name);
		$this->unibox->xml->set_attribute('type', INPUT_SELECT);
		$this->unibox->xml->set_attribute('name', $name);
		$this->unibox->xml->set_attribute('descr', $si_label, $this->translate);
        if ($size != 0)
        	$this->unibox->xml->add_value('size', $size);
        $this->unibox->xml->set_attribute('error', $this->has_failed($name));

		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_SELECT;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;
		$this->current_form->spec->elements->$name->show_please_select = $show_please_select;
		$this->current_element = &$this->current_form->spec->elements->$name;

		if ($show_please_select)
			$this->add_option('', 'TRL_FORM_PLEASE_SELECT');
	} // end begin_select()

	/**
    * 
    * @param        $name               field name (string)
    * @param        $si_descr           field label (string)
    * @param        $show_please_select show 'please select' as first entry (bool)
    * @param        $size               field size
    */

	/**
    * finishes current select field
    * 
    */
	public function end_select()
	{
		$this->unibox->xml->parse_node();
	} // end end_select()

	/**
    * adds a new option to current radio-buttonset/select field
    * 
    * @param        $value              option (string)
    * @param        $si_label           option label (string)
    * @param        $selected           flag if option is selected (bool)
    * @param        $called_by_set_sql  flag if set via add_option_sql() (bool)
    */
	public function add_option($value, $si_label, $selected = 0, $called_by_set_sql = false)
	{
		// make $value a string
		$value = (string)$value;
		
		if ($this->current_form->spec->restore && !$called_by_set_sql)
		{
            if ($this->unibox->xml->get_attribute('type') == 'optgroup')
                $name = $this->unibox->xml->get_attribute('name', 2);
            else
                $name = $this->unibox->xml->get_attribute('name');
			$selected_value = $this->get_value($name, $selected);
			$selected = (is_array($selected_value)) ? in_array($value, $selected_value, true) : $value === $selected_value;
		}

        $node_type = $this->unibox->xml->get_attribute('type');
        $node_name = $this->unibox->xml->get_attribute('name');

		$this->unibox->xml->add_node('input');
        $this->unibox->xml->set_attribute('type', 'option');
		$this->unibox->xml->add_value('value', $value);
		$this->unibox->xml->add_value('label', $si_label, ($this->translate && !$called_by_set_sql));
		$this->unibox->xml->add_value('selected', $selected);
        if (!isset($this->current_element->options))
            $count = 0;
        else
            $count = count($this->current_element->options);

        if ($node_type == 'checkbox_multi' && $count == 0)
            $this->unibox->xml->set_marker('checkbox_multi_first_'.$node_name);

        $this->unibox->xml->add_value('count', $count);
		$this->unibox->xml->parse_node();

		$this->current_element->options[] = $value;
	} // end add_option()

	/**
    * begins a new optgroup
    * 
    * @param        $si_label           optgroup label (string)
    */
    public function begin_optgroup($si_label)
    {
        $this->unibox->xml->add_node('input');
        $this->unibox->xml->set_attribute('type', 'optgroup');
        $this->unibox->xml->set_attribute('descr', $si_label, $this->translate);
    } // end begin_optgroup()

    /**
    * finishes current optgroup
    * 
    */
    public function end_optgroup()
    {
        $this->unibox->xml->parse_node();
    } // end end_optgroup()

	/**
    * adds options to current radio-buttonset/select field
    * using the provided sql-query
    * 
    * @param        $sql_string         sql query (string)
    * @param        $selected_value     selected value (mixed)
    */
	public function add_option_sql($sql_string, $selected_value = '', $parse_sql_string = false)
	{
		$current_row = array();

        if ($this->current_form->spec->restore)
        {
            $name = $this->unibox->xml->get_attribute('name');
            $selected_value = $this->get_value($name, $selected_value);
        }

		$result = $this->unibox->db->query($sql_string, 'invalid option sql string', $parse_sql_string);
		if ($result->num_rows() > 0)
		{
			while ($row = $result->fetch_row())
			{
				// save primary dataset information, select opt-groups
				$row_value = $row[0];
				$row_descr = $row[1];
				$row = array_reverse(array_slice($row, 2));
				
				// check for first run
				if (count($current_row) > 0)
				{
					// loop through groups
					foreach ($row as $key => $value)
					{
						// check if current group changed
						if ($value != $current_row[$key])
						{
							// close all groups below the changing one
							for ($i = 0; $i <= $key; $i++)
							{
								$this->end_optgroup();
								$current_row[$i] = $row[$i];
							}
							
							// re-open groups
							for ($i = $key; $i >= 0; $i--)
								$this->begin_optgroup($row[$i]);
							break;
						}
					}
				}
				else
				{
					// initialize $current_row and open groups
					foreach ($row as $key => $value)
					{
						$this->begin_optgroup($value);
						$current_row[$key] = $row[$key];
					}
				}
				
			// add primary dataset information
			$selected = (is_array($selected_value)) ? in_array($row_value, $selected_value) : $row_value == $selected_value;
			$this->add_option($row_value, $row_descr, $selected, true);
			}
			$result->free();
		}
		
		while ($this->unibox->xml->get_attribute('type') == 'optgroup')
			$this->end_optgroup();

	} // end add_option_sql()

	/**
    * adds a new file opload field
    * 
    * @param        $name               field name (string)
    * @param        $si_label           field label (string)
    */
	public function file($name, $si_label, $dest_dir)
	{
        $this->unibox->xml->goto('current_form');
		$this->unibox->xml->set_attribute('encoding', 'multipart/form-data');
        $this->unibox->xml->restore();

		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_FILE);
		$this->unibox->xml->add_value('name', $name);
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		$this->unibox->xml->add_value('error', $this->has_failed($name));
		$this->unibox->xml->parse_node();

		$this->current_form->spec->elements->$name = new stdClass;
		$this->current_form->spec->elements->$name->input_type = INPUT_FILE;
		$this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->dest_dir = $dest_dir;
        $this->current_form->spec->elements->$name->label = $si_label;
        $this->labels[] = $this->current_form->spec->elements->$name->label;
		$this->current_element = &$this->current_form->spec->elements->$name;

		if ($size = ini_get('upload_max_filesize'))
		{
			$size = ub_functions::sh2bt($size);
			$this->set_condition(CHECK_FILE_SIZE, $size);
			$size = ub_functions::bt2hr($size, 2);
			$translate = $this->translate;
			$this->translate = false;
			$this->comment('TRL_MAX_FILE_SIZE', $size['size'].' '.$this->unibox->translate($size['unit']));
			$this->translate = $translate;
		}
		elseif ($size = ini_get('post_max_size'))
		{
			$size = ub_functions::sh2bt($size);
			$this->set_condition(CHECK_FILE_SIZE, $size);
			$size = ub_functions::bt2hr($size, 2);
			$translate = $this->translate;
			$this->translate = false;
			$this->comment('TRL_MAX_FILE_SIZE', $size['size'].' '.$this->unibox->translate($size['unit']));
			$this->translate = $translate;
		}
	} // end file()
	
	/**
    * adds a new submit button
    * 
    * @param        $si_label           button label (string)
    * @param        $id                 submit button id (int)
    */
	public function submit($si_label, $ident = null, $onclick = null)
	{
		if ($ident === null)
			$ident = $this->submit_index;
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_marker('submit_'.$ident);
		$this->unibox->xml->set_attribute('type', INPUT_SUBMIT);
		$this->unibox->xml->add_value('name', $this->current_form->spec->name.'_submit_'.$ident);
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		if ($onclick !== null)
			$this->unibox->xml->add_value('onclick', $onclick);
		$this->unibox->xml->parse_node();
		
		if (!isset($this->current_form->spec->submit_indices) || (isset($this->current_form->spec->submit_indices) && !in_array($ident, $this->current_form->spec->submit_indices)))
			$this->current_form->spec->submit_indices[] = $ident;
		$this->submit_index++;
	} // end submit()

	/**
    * adds a new cancel button
    * 
    * @param        $si_label           button label (string)
    * @param        $alias              alias to be redirected on canceling the form (string)
    */
	public function cancel($si_label, $alias, $onclick = null)
	{
		$this->unibox->xml->add_node('input');
		$this->unibox->xml->set_attribute('type', INPUT_CANCEL);
		$this->unibox->xml->add_value('name', $this->current_form->spec->name.'_cancel');
		$this->unibox->xml->add_value('label', $si_label, $this->translate);
		if ($onclick !== null)
			$this->unibox->xml->add_value('onclick', $onclick);
		$this->unibox->xml->parse_node();
		
		$this->current_form->spec->redirect_url = $alias;
	} // end cancel()

    /**
    * adds a new hidden field
    * 
    * @param        $name               field name (string)
    * @param        $value              default value (mixed)
    */
    public function hidden($name, $value = '')
    {
		if ($this->current_form->spec->restore)
			$value = $this->get_value($name, $value);

        $this->unibox->xml->add_node('input');
        $this->unibox->xml->set_attribute('type', INPUT_HIDDEN);
        $this->unibox->xml->set_attribute('name', $name);
        $this->unibox->xml->add_value('value', $value);
        $this->unibox->xml->parse_node();

        $this->current_form->spec->elements->$name = new stdClass;
        $this->current_form->spec->elements->$name->input_type = INPUT_HIDDEN;
        $this->current_form->spec->elements->$name->value_type = TYPE_STRING;
        $this->current_form->spec->elements->$name->value = $value;
        $this->current_element = &$this->current_form->spec->elements->$name;
    } // end hidden()

    /**
    * adds plain text between form fields
    * 
    * @param        $value              ubc formatted text (string)
    */
    public function plaintext($value, $translate = false, $trl_args = array())
    {
        if ($translate)
            $value = $this->unibox->translate($value, $trl_args);
        $this->unibox->xml->add_node('input');
        $this->unibox->xml->set_attribute('type', INPUT_PLAINTEXT);
        $ubc = new ub_ubc();
        $ubc->ubc2xml($value);
        $this->unibox->xml->parse_node();
    }

	/**
	* adds a new line
	* 
    * @param        $count                  number of new lines to add (int)
	*/
	public function newline($count = 1)
	{
		for ($i = 0; $i < $count; $i++)
		{
			$this->unibox->xml->add_node('input');
			$this->unibox->xml->set_attribute('type', 'newline');
			$this->unibox->xml->parse_node();
		}
	} // end newline()

	public function comment($label, $value, $trl_args_label = array(), $trl_args_value = array(), $keep_old_comment = false)
	{
		$this->unibox->xml->goto('last_child');
		if (!$keep_old_comment)
			$this->unibox->xml->remove('comment');
		$this->unibox->xml->set_marker('last_child_bck');
		$this->unibox->xml->add_node('comment');
		$this->unibox->xml->add_value('label', $label, true, $trl_args_label);
		$this->unibox->xml->add_value('value', $value, $this->translate, $trl_args_value);
		$this->unibox->xml->parse_node();
		$this->unibox->xml->restore();
		$this->unibox->xml->goto('last_child_bck');
		$this->unibox->xml->set_marker('last_child');
		$this->unibox->xml->restore();
	}
	
	/**
    * sets the expected datatype for the current element
    * expects an error message that is displayed if datatype specification is
    * not met
    * 
    * @param        $type               datatype constant (int)
    * @param        $si_err_msg         error message (string)
    */
	public function set_type($type, $si_err_msg = '')
	{
		$this->current_element->value_type = $type;

        if ($type == TYPE_DATE)
        {
            $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_USER);
            $time->now();
			$translate = $this->translate;
			$this->translate = false;
            $this->comment('TRL_COMMENT_EXAMPLE', $time->get_date());
            $this->translate = $translate;
        }
        elseif ($type == TYPE_TIME)
        {
            $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_USER);
            $time->now();
			$translate = $this->translate;
			$this->translate = false;
            $this->comment('TRL_COMMENT_EXAMPLE', $time->get_time());
            $this->translate = $translate;
        }

        $this->set_condition(CHECK_TYPE, null, $si_err_msg);
	} // end set_type()

	/**
    * sets a validation condition for the current multilanguage element
    * 
    * @param        $type               validation type constant (int)
    * @param        $si_err_msg         error message (string)
    * @param        $args               additional validation arguments (mixed)
    */
	public function set_condition_multilanguage($type, $args = null, $si_err_msg = null)
	{
		$cur_element = $this->current_element;
		if ($type == CHECK_MULTILANG && $this->unibox->config->system->form_mark_required_fields)
		{
			$this->unibox->xml->goto($this->current_ml_element_name);
			$this->unibox->xml->set_attribute('required', 1);
			$this->unibox->xml->restore();
		}
		
		foreach ($this->current_form->spec->elements as $ident => $element)
		{
            if (stristr($ident, 'multilang'))
            {
                list($foo, $lang_ident, $ml_ident) = explode('_', $ident, 3);
				if ($ml_ident == $this->current_ml_element_name)
				{
					$this->unibox->xml->goto($ident);
					$this->unibox->xml->set_marker('last_child');
					$this->unibox->xml->restore();
					$this->current_element = &$element;
					$this->set_condition($type, $args, $si_err_msg);
				}
            }
		}
		$this->current_element = $cur_element;
	} // end set_condition_multilanguage()
	
	/**
    * sets a validation condition for the current element
    * 
    * @param        $type               validation type constant (int)
    * @param        $si_err_msg         error message (string)
    * @param        $args               additional validation arguments (mixed)
    */
	public function set_condition($type, $args = null, $si_err_msg = null)
	{
		if ($type == CHECK_NOTEMPTY && $this->unibox->config->system->form_mark_required_fields)
		{
            if (!$this->required_set)
            {
                $this->unibox->xml->goto('current_form');
                $this->unibox->xml->set_attribute('info_require', '1');
                $this->unibox->xml->restore();
                $this->required_set = true;
            }
			if (!ub_validator::has_condition($this->current_element, CHECK_MULTILANG))
			{
				$this->unibox->xml->goto('last_child');
				if ($this->current_element->input_type == INPUT_RADIO || $this->current_element->input_type == INPUT_SELECT || $this->current_element->input_type == INPUT_CHECKBOX_MULTI)
					$this->unibox->xml->set_attribute('required', 1);
				else
					$this->unibox->xml->add_value('required', 1);
				$this->unibox->xml->restore();
			}
		}

		switch ($this->current_element->input_type)
		{
			case INPUT_TEXT:
			case INPUT_TEXTAREA:
				if ($type == CHECK_INRANGE)
				{
					if ($this->current_element->value_type == TYPE_STRING)
					{
						$this->unibox->xml->goto('last_child');
						$this->unibox->xml->add_value('maxlength', $args[1]);
						$this->unibox->xml->restore();
					}
					elseif ($this->current_element->value_type == TYPE_INTEGER)
					{
						$this->unibox->xml->goto('last_child');
						$this->unibox->xml->add_value('maxlength', strlen($args[1]));
						$this->unibox->xml->restore();
					}
				}
                if ($type == CHECK_URL)
                {
					$translate = $this->translate;
					$this->translate = false;
                    $this->comment('TRL_ALLOWED_PROTOCOLS', implode(', ', $args));
                    $this->translate = $translate;
                }
				break;
            case INPUT_PASSWORD:
                if ($type == CHECK_INRANGE)
                {
                    if ($this->current_element->value_type == TYPE_STRING)
                    {
                        $this->unibox->xml->goto('last_child');
                        $this->unibox->xml->add_value('maxlength', $args[1]);
                        $this->unibox->xml->restore();
                    }
                    elseif ($this->current_element->value_type == TYPE_INTEGER)
                    {
                        $this->unibox->xml->goto('last_child');
                        $this->unibox->xml->add_value('maxlength', strlen($args[1]));
                        $this->unibox->xml->restore();
                    }
                }
                break;
			case INPUT_FILE:
				if ($type == CHECK_FILE_SIZE)
				{
					$this->unibox->xml->goto('last_child');
					if (($old_length = $this->unibox->xml->get_attribute('maxlength')) && $old_length < $args)
						$this->unibox->xml->restore();
					else
					{
						$this->unibox->xml->set_attribute('maxlength', $args);
						$this->unibox->xml->restore();

						$args = ub_functions::bt2hr($args, 2);
						$translate = $this->translate;
						$this->translate = false;
						$this->comment('TRL_MAX_FILE_SIZE', $args['size'].' '.$this->unibox->translate($args['unit']));
						$this->translate = $translate;
					}
				}
                elseif ($type == CHECK_FILE_EXTENSION)
                {
                    $this->unibox->xml->goto('last_child');
                    $this->unibox->xml->add_value('accept', implode(', ', $args));
                    $this->unibox->xml->restore();  

                    // build where statement
                    $mime_types_where = $mime_types = $mime_subtypes = array();
                    foreach ($args as $mime_combined)
                    {
                        list($mime_type, $mime_subtype) = explode('/', $mime_combined);
                        $mime_types_where[] = '(mime_type = \''.$this->unibox->db->cleanup($mime_type).'\' AND mime_subtype = \''.$this->unibox->db->cleanup(($mime_subtype == '*') ? '%' : $mime_subtype).'\')';
                        $mime_types[] = $mime_type;
                        $mime_subtypes[] = $mime_subtype;
                    }
                    $mime_types_where = implode(' OR ', $mime_types_where);

                    // select file extensions of known mime types
                    $sql_string  = 'SELECT
                                      mime_type,
                                      mime_subtype,
                                      mime_file_extension
                                    FROM
                                      sys_mime_types
                                    WHERE
                                      '.$mime_types_where;
                    $result = $this->unibox->db->query($sql_string, 'failed to select mime types');
                    if ($result->num_rows() > 0)
                    {
                        $allowed_file_extensions = array();
                        while (list($mime_type, $mime_subtype, $mime_file_extension) = $result->fetch_row())
                        {
                            $allowed_file_extensions[] = $mime_file_extension;
                            foreach ($mime_types as $key => $mime_type_temp)
                                if ($mime_type_temp == $mime_type && ($mime_subtypes[$key] == $mime_subtype || $mime_subtypes[$key] == '*'))
                                    unset($mime_types[$key], $mime_subtypes[$key]);
                        }
                        $result->free();

                        // add file extension
						$translate = $this->translate;
						$this->translate = false;
                        $this->comment('TRL_ALLOWED_FILE_EXTENSIONS', implode(', ', $allowed_file_extensions), array(), array(), true);
                        $this->translate = $translate;
                        
                        // add not found mime types
                        if (!empty($mime_types))
                        {
                            foreach ($mime_types as $key => $mime_type)
                                $mime_types[$key] = $mime_type.'/'.$mime_subtypes[$key];
							$translate = $this->translate;
							$this->translate = false;
                            $this->comment('TRL_ALLOWED_ADDITIONAL_MIME_TYPES', implode(', ', $mime_types));
                            $this->translate = $translate;
                        }
                    }
                    else
                    {
                    	$translate = $this->translate;
						$this->translate = false;
                        $this->comment('TRL_ALLOWED_MIME_TYPES', implode(', ', $args));
						$this->translate = $translate;
                    }
                }
				break;
		}
								
		$this->current_element->conditions[] = new stdClass;
		end($this->current_element->conditions);
		$this->current_element->conditions[key($this->current_element->conditions)]->type = $type;
		$this->current_element->conditions[key($this->current_element->conditions)]->message = $si_err_msg;
		$this->current_element->conditions[key($this->current_element->conditions)]->arguments = $args;
	} // end set_condition()

	public function set_disabled()
	{
		$this->unibox->xml->goto('last_child');
		if ($this->current_element->input_type == INPUT_RADIO || $this->current_element->input_type == INPUT_SELECT || $this->current_element->input_type == INPUT_CHECKBOX_MULTI)
			$this->unibox->xml->set_attribute('disabled', 1);
		else
			$this->unibox->xml->add_value('disabled', 1);
		$this->unibox->xml->restore();
	}

	public function set_help($si_help, $trl_args = array())
	{
		$this->unibox->xml->goto('last_child');
		if ($this->current_element->input_type == INPUT_RADIO || $this->current_element->input_type == INPUT_SELECT || $this->current_element->input_type == INPUT_CHECKBOX_MULTI)
			$this->unibox->xml->set_attribute('help', $si_help, $this->translate, $trl_args);
		else
			$this->unibox->xml->add_value('help', $si_help, $this->translate, $trl_args);
		$this->unibox->xml->restore();
	}
	
    /**
    * adds a callback to the current form field
    * that is executed before any conditions are tested
    * 
    * @param        $info               array [class, function] or function name
    * @param        $args               additional validation arguments (mixed)
    * @param        $return_type        return type (1 = return, 2 = reference, 3 = none) (int)
    * @return       array of first resultrow
    */
    public function set_callback($callback, $args = CALLBACK_PLACEHOLDER, $return_type = 1)
    {
        $this->current_element->callbacks[] = new stdClass;
        end($this->current_element->callbacks);
        if (is_array($callback))
        {
            $this->current_element->callbacks[key($this->current_element->callbacks)]->class = $callback[0];
            $this->current_element->callbacks[key($this->current_element->callbacks)]->function = $callback[1];
        }
        else
            $this->current_element->callbacks[key($this->current_element->callbacks)]->function = $callback;
        $this->current_element->callbacks[key($this->current_element->callbacks)]->arguments = $args;
        $this->current_element->callbacks[key($this->current_element->callbacks)]->return_type = $return_type;
    }

    /**
    * fill form with data from database (e.g. editing datasets)
    * 
    * @param        $sql_string         sql query selecting the data (string)
    * @param        $multilang          array of fields that contain multilanguage data (array)
    * @param		$overwrite_existing_values
    * @param		$parse_sql_string
    * @return       array of first resultrow
    */
    public function set_values($sql_string, $multilang = array(), $overwrite_existing_values = false, $parse_sql_string = false)
    {
        $result = $this->unibox->db->query($sql_string, 'failed to fill form', $parse_sql_string);
        if ($result->num_rows() > 0)
        {
            $array = $result->fetch_row(MYSQL_ASSOC);
            $result->free();
            $validator = ub_validator::get_instance();
            if (!$validator->form_sent($this->current_form->spec->name))
            {
	            foreach ($array as $key => $value)
	            {
	            	if ($overwrite_existing_values || !isset($this->current_form->data->$key) || !$this->current_form->spec->failed)
	            	{
		                if (in_array($key, $multilang))
		                {
		                    $sql_string  = 'SELECT
		                                      lang_ident,
		                                      string_value
		                                    FROM
		                                      sys_translations
		                                    WHERE
		                                      string_ident = \''.$value.'\'';
		                    $result = $this->unibox->db->query($sql_string, 'failed to fill multilanguage: '.$key);
		                    $ml_array = array();
		                    if ($result->num_rows() > 0)
		                    {
		                    	while (list($lang_ident, $string_value) = $result->fetch_row())
		                        	$ml_array[$lang_ident] = $string_value;
		                       	$result->free();
		                    }
		                    $this->current_form->data->$key = $ml_array;
		                }
		                else
		                    $this->current_form->data->$key = $value;
	            	}
	            }
	            $this->current_form->spec->restore = true;
            }
			return $array;
        }
    } // end set_values()

    /**
    * fill form with data from array (e.g. editing datasets)
    * 
    * @param        $array              associative array of values (array)
    * @param        $multilang          array of fields that contain multilanguage data (array)
    * @param        $overwrite_existing_values  overwrite already saved information?
    */
    public function set_values_array($array = array(), $multilang = array(), $overwrite_existing_values = false)
    {
        if (count($array) > 0)
        {
            $validator = ub_validator::get_instance();
            if (!$validator->form_sent($this->current_form->spec->name))
            {
                foreach ($array as $key => $value)
                {
                    if ($overwrite_existing_values || !isset($this->current_form->data->$key) || !$this->current_form->spec->failed)
                    {
                        if (in_array($key, $multilang))
                        {
		                    $sql_string  = 'SELECT
		                                      lang_ident,
		                                      string_value
		                                    FROM
		                                      sys_translations
		                                    WHERE
		                                      string_ident = \''.$value.'\'';
		                    $result = $this->unibox->db->query($sql_string, 'failed to fill multilanguage: '.$key);
		                    $ml_array = array();
		                    if ($result->num_rows() > 0)
		                    {
		                    	while (list($lang_ident, $string_value) = $result->fetch_row())
		                        	$ml_array[$lang_ident] = $string_value;
		                       	$result->free();
		                    }
		                    $this->current_form->data->$key = $ml_array;
                        }
                        else
                            $this->current_form->data->$key = $value;
                    }
                }
                $this->current_form->spec->restore = true;
            }
        }
    } // end set_values()

	/**
	 * marks that the form should be restored
	 * 
	 * @param		$restore				restore status (bool)
	 */
	public function set_restore($flag)
	{
		$this->current_form->spec->restore = $flag;
	} // end set_failed()

	/**
	 * marks that the form failed validation
	 * 
	 * @param		$failed				failure status (bool)
	 */
	public function set_failed($flag)
	{
		$this->current_form->spec->failed = $flag;
	} // end set_failed()
	
	/**
    * retrieves a form value from either form->data or the error object
    * 
    * @param        $field        input field name (string)
    * @return       input value (string)
    */
	protected function get_value($field, $default_value = '')
	{
		foreach ($this->current_form->error as $err)
		{
			if ($err->field == $field)
				return ub_functions::unescape($err->value);
		}

		if (stristr($field, 'multilang'))
		{
			list($foo, $lang_ident, $ident) = explode('_', $field, 3);
			$arr = &$this->current_form->data->$ident;
			return isset($arr[$lang_ident]) ? ub_functions::unescape($arr[$lang_ident]) : $default_value;
		}
		else
		{
			if (isset($this->current_form->data->$field))
				return ub_functions::unescape($this->current_form->data->$field);
			else
				return $default_value;
		}
	} // end get_value()
	
	/**
    * returns if the validation of the specified input field has failed
    * 
    * @param        $field        input field name (string)
    * @return       result (bool)
    */
	protected function has_failed($field)
	{
		if (isset($this->current_form->spec->restore))
		{
			foreach ($this->current_form->error as $err)
				if ($err->field == $field)
					return true;
		}
		return false;
	} // end has_failed()
	
	/**
    * resets spec/data in environment
    * 
    * @param        $name               form name (string)
    */
	public static function reset($name = null, $call_destructor = true, $delete_dialog_associated_forms = true)
	{
		$unibox = ub_unibox::get_instance();

		// if no form name is given, reset all forms
		if ($name === null)
			foreach ($unibox->session->env->form as $form)
			{
				if (isset($form->spec) && isset($form->spec->name))
	            	self::destroy($form->spec->name, $call_destructor);
			}
		else
		{
			// check if we're in a dialog
			if (ub_dialog::instance_exists() && $delete_dialog_associated_forms)
			{
				// check if the canceled form is part of the dialog
				$dialog = ub_dialog::get_instance();
				if (in_array($name, $dialog->get_forms()))
				{
					// if so, destroy all forms that are associated with the dialog
					foreach ($dialog->get_forms() as $form)
						self::destroy($form, $call_destructor);
						
					// and reset the dialog
					$dialog->reset();
				}
			}
			else
				self::destroy($name, $call_destructor);
		}
	} // end reset()
	
	/*
	 * destroy()
	 * 
	 * calls the form destructor and deletes all saved form information
	 * 
	 * @param		$name			form to be destroyed
	 * 
	 */
	protected static function destroy($name, $call_destructor = true)
	{
		$unibox = ub_unibox::get_instance();

        $validator = ub_validator::get_instance();
        $validator->reset_cache($name);

		// if no form exists, there is nothing to destroy
		if (!isset($unibox->session->env->form->$name))
			return;
			
		// check if form spec exists
		if (isset($unibox->session->env->form->$name->spec))
		{
	        // delete temporary files from file upload fields
	        foreach ($unibox->session->env->form->$name->spec->elements as $ident => $element)
	        {
	            if ($element->input_type == INPUT_FILE && isset($unibox->session->env->form->$name->data->$ident))
	            {
	                $element_data = $unibox->session->env->form->$name->data->$ident;
	                if (file_exists($element->dest_dir.DIRECTORY_SEPARATOR.$element_data['tmp_name']) && is_writable($element->dest_dir.DIRECTORY_SEPARATOR.$element_data['tmp_name']))
	                    unlink($element->dest_dir.DIRECTORY_SEPARATOR.$element_data['tmp_name']);
	            }
	        }
	
			// call the form destructor
	        if ($call_destructor && isset($unibox->session->env->form->$name->spec->destructor) && isset($unibox->session->env->form->$name->spec->destructor->class) && isset($unibox->session->env->form->$name->spec->destructor->function))
	            call_user_func(array($unibox->session->env->form->$name->spec->destructor->class, $unibox->session->env->form->$name->spec->destructor->function));
		}
		
        // delete all form data
		unset($unibox->session->env->form->$name);
	}
	
	public function import_form_spec($form_spec)
	{
		$elements = @unserialize($form_spec);
        if (is_array($elements))
			foreach ($elements as $element)
				call_user_func_array(array(&$this, $element->function), $element->args);
	} // end import_form_spec()
	
	public function secure()
	{
		$data = array();
		// TODO: select both dataset groups as UNION

		// get both groups
		$sql_string  = 'SELECT
						  a.antispam_group_ident,
						  d.string_value
						FROM
						  sys_antispam AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
							INNER JOIN sys_antispam_groups AS c
							  ON c.antispam_group_ident = a.antispam_group_ident
							INNER JOIN sys_translations AS d
							  ON
							  (
							  d.string_ident = c.si_group_name
							  AND
							  d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						GROUP BY
						  a.antispam_group_ident
						HAVING
						  COUNT(*) > 3
						ORDER BY
						  RAND()
						LIMIT
						  0, 2';
		$result = $this->unibox->db->query($sql_string, 'failed to select antispam groups');
		if ($result->num_rows() != 2)
			throw new ub_exception_general('failed to select antispam groups');

		// get wrong entry
		list($group_ident, $group_name_invalid) = $result->fetch_row();
		$sql_string  = 'SELECT
						  antispam_ident,
						  si_name
						FROM
						  sys_antispam
						WHERE
						  antispam_group_ident = \''.$group_ident.'\'
						ORDER BY
						  RAND()
						LIMIT
						  0, 1';
		$result_data = $this->unibox->db->query($sql_string, 'failed to select wrong dataset for antispam');
		if ($result_data->num_rows() != 1)
			throw new ub_exception_general('failed to select invalid dataset for antispam');
		list($valid_item, $name) = $result_data->fetch_row();
		$data[] = array($valid_item, $name);

		// get right entries
		list($group_ident, $group_name_valid) = $result->fetch_row();
		$sql_string  = 'SELECT
						  antispam_ident,
						  si_name
						FROM
						  sys_antispam
						WHERE
						  antispam_group_ident = \''.$group_ident.'\'
						ORDER BY
						  RAND()
						LIMIT
						  0, 4';
		$result_data = $this->unibox->db->query($sql_string, 'failed to select valid datasets for antispam');
		if ($result_data->num_rows() != 4)
			throw new ub_exception_general('failed to select valid datasets for antispam');
		while (list($value, $name) = $result_data->fetch_row())
			$data[] = array($value, $name);

		// shuffle datasets
		shuffle($data);

		$restore = $this->current_form->spec->restore;
        $this->current_form->spec->restore = false;
		$this->begin_fieldset('TRL_ANTISPAM');
		$this->plaintext('TRL_ANTISPAM_SELECT_INVALID', true, array($group_name_valid, $group_name_invalid));
		$this->plaintext('[br /]');
		$this->begin_select($this->current_form->spec->name.'_form_antispam', 'TRL_ANTISPAM');
		foreach ($data as $item)
			$this->add_option($item[0], $item[1]);
		$this->end_select();
		$this->set_condition(CHECK_NOTEMPTY, null, 'TRL_ERROR_ANTISPAM');
		$this->set_condition(CHECK_INSET, array($valid_item), 'TRL_ERROR_ANTISPAM');
		$this->end_fieldset();

		$this->current_form->spec->restore = $restore;
	}

} // end class ub_form_creator

?>