<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_administration_ng
{
    /**
    * class version
    */
    const version = '0.1.0';

    /**
    * unibox framework instance
    */
    protected $unibox;

    /**
    * sort ident
    */
    protected $ident = null;

    /**
    * label
    */
    protected $label = null;

	/**
	 * array of class instances
	 */
	private static $instances = array();

    /**
     * reference to administration object in environment
     */
    protected $administration = null;

	/**
	 * all allowed actions for dataset
	 */
    protected $allowed_actions = array();
    
    /**
     * array of options for multiple datasets
     */
    protected $multi_options = array();
    
    /**
     * additional links
     */
    protected $links = array();
    
    /**
     * description for multiple datasets
     */
    protected $multi_descr = null;

    /**
     * flag indicating if a checkbox column needs to be added
     */
    protected $checkbox_column = false;

	/**
	 * indicates if there is more than one nesting level
	 */
	protected $nested_data = false;
    
    /**
     * flag indicating if the administration was just created
     */
    protected $new = false;
	
	/**
	 * reference to data root node
	 */
	protected $root;
	
	/**
	 * reference to current data node
	 */
	protected $current_base;
    
	/**
	 * maximum number of options
	 */
	protected $max_options = 0;
	
	/**
	 * maximum number of icons
	 */
	protected $max_icons = 0;

    /**
     * fallback alias
     */
    protected $fallback = null;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_administration_ng::version;
    } // end get_version()

    /**
    * class constructor
    * 
    * @param        $ident              sort ident
    */
    protected function __construct($ident, $label = null)
    {
        $this->unibox = ub_unibox::get_instance();
        $this->ident = $ident;
        $this->label = $label;

        if (!isset($this->unibox->session->env->administration->$ident))
        {
        	$this->unibox->session->env->administration->$ident = new StdClass();
            $this->unibox->session->env->administration->$ident->valid = array();
            $this->unibox->session->env->administration->$ident->invalid = array();
            $this->unibox->session->env->administration->$ident->sort = new StdClass();
        	$this->unibox->session->env->administration->$ident->sort->ident = $ident;
        	$this->unibox->session->env->administration->$ident->sort->fields = array();
        	$this->unibox->session->env->administration->$ident->sort->history = array();
            $this->new = true;
        }

		// create root data node
		$this->root->parent = null;
		$this->root->children = array();
		$this->root->data = array();
		$this->root->dataset_index = 0;
		$this->root->data_index = 0;
		$this->root->static_count = 0;
		$this->current_base = $this->root;

        $this->administration = &$this->unibox->session->env->administration->$ident;
    }

    /**
    * returns class instance
    * 
    * @return       ub_administration_ng (object)
    */
    public static function get_instance($ident, $label = null)
    {
        $unibox = ub_unibox::get_instance();
        if (!isset($unibox->session->env->administration->$ident) || !isset(self::$instances[$ident]))
            self::$instances[$ident] = new ub_administration_ng($ident, $label);
        return self::$instances[$ident];
    }

	#################################################################################################
	### sort functions and processing
	#################################################################################################

    /**
    * add sortable field
    * 
    * @param        $si_name            field description (string)
    * @param        $db_field           assigned database field (string)
    * @param        $width              column width (int)
    * @param        $sort               default sort (string)
    */
    public function add_field($si_name, $db_field, $width = 0, $sort = SORT_ASC)
    {
    	$ident = substr(md5($this->ident.$si_name), 0, 10);
    	if (!isset($this->administration->sort->fields[$ident]))
    	{
	        $this->administration->sort->fields[$ident] = new StdClass();
	        $this->administration->sort->fields[$ident]->ident = $ident;
	        $this->administration->sort->fields[$ident]->si_name = $si_name;
	        $this->administration->sort->fields[$ident]->width = $width;
	        $this->administration->sort->fields[$ident]->sort = $sort;
            $this->administration->sort->fields[$ident]->db_field = $db_field;

	        if (!in_array($ident, $this->administration->sort->history))
	        	$this->administration->sort->history[] = $ident;
    	}
    }

	/**
	 * set initial sort column
	 * 
	 * @param		$si_name			string identifier of respective column
	 */
    public function sort_by($db_field)
    {
    	if ($this->new)
	    	foreach ($this->administration->sort->fields as $ident => $field)
	    		if ($field->db_field == $db_field)
		        {
		            // delete if already in history
		            if (($key = array_search($ident, $this->administration->sort->history)) !== false)
		                unset($this->administration->sort->history[$key]);
		
		            // re-add to history
		            array_unshift($this->administration->sort->history, $ident);
		            return;
		        }
    }

    /**
    * process sort
    */
    protected function process_sort()
    {
        // validate sort-change and process it
        $validator = ub_validator::get_instance();
        if ($validator->validate('GET', 'sort', TYPE_STRING, CHECK_INSET, null, array_keys($this->administration->sort->fields)))
        {
            // process sort order
            if ($this->administration->sort->history[0] == $this->unibox->session->env->input->sort)
            	if ($validator->validate('GET', 'order', TYPE_STRING, CHECK_INSET, null, array('ASC', 'DESC')))
            		$this->administration->sort->fields[$this->unibox->session->env->input->sort]->sort = ($this->unibox->session->env->input->order == 'ASC') ? SORT_ASC : SORT_DESC;
            	else
	                if ($this->administration->sort->fields[$this->unibox->session->env->input->sort]->sort == SORT_ASC)
	                    $this->administration->sort->fields[$this->unibox->session->env->input->sort]->sort = SORT_DESC;
	                else
	                    $this->administration->sort->fields[$this->unibox->session->env->input->sort]->sort = SORT_ASC;

            // delete if already in history
            if (($key = array_search($this->unibox->session->env->input->sort, $this->administration->sort->history)) !== false)
                unset($this->administration->sort->history[$key]);
            
            // re-add to history
            array_unshift($this->administration->sort->history, $this->unibox->session->env->input->sort);
        }
        
        // re-index array to prevent signed integer overflow
		$this->administration->sort->history = array_slice($this->administration->sort->history, 0, count($this->administration->sort->fields), false);
    }

	#################################################################################################
	### dataset handling
	#################################################################################################

	/**
	 * begins a new dataset
	 * 
	 * @param		$show_checkbox			indicates if there should be a checkbox displayed for this dataset
	 * @param		$static					indicated if this dataset is static and therefore not sortable
	 */
    public function begin_dataset($show_checkbox = true, $static = false)
    {
		$dataset = new stdClass();
		$dataset->parent = $this->current_base;
		$dataset->children = array();
		$dataset->data = array();
		$dataset->dataset_index = 0;
		$dataset->data_index = 0;
		$dataset->static_count = 0;
		$dataset->idents = array();
		$dataset->options = array();
		$dataset->icons = array();
		$dataset->descr = null;
		$dataset->checkbox = $show_checkbox;
		$dataset->static = $static;
		
		// increment static_count if the dataset is static
		if ($static)
			$this->current_base->static_count++;

		// check if we got more than one nesting level
		if ($this->current_base !== $this->root)
			$this->nested_data = true;

		$this->current_base->children[$this->current_base->dataset_index] = $dataset;
		$this->current_base->dataset_index++;
		$this->current_base = $dataset;
    }
    
    /**
    * close current dataset and move one node up
    */
    public function end_dataset()
    {
		$this->current_base = $this->current_base->parent;
    }

    /**
    * adds new data to the current dataset
    */
    public function add_data($value, $translate = false, $sort_value = null, $link = null, $onclick = null, $ident = null, $type = XML_ATTRIBUTE)
    {
		if ($this->current_base === $this->root)
            throw new ub_exception_runtime('administration: root node cannot hold any data, begin dataset first');

		$data = new stdClass();
		$data->value = $value;
		$data->translate = $translate;
		$data->sort_value = $sort_value;
		$data->link = $link;
		$data->onclick = $onclick;
		$data->ident = $ident;
		$data->type = $type;
		
		$this->current_base->data[$this->current_base->data_index] = $data;
		$this->current_base->data_index++;
    }

    /**
    * adds an administration option to the current dataset
    * 
    * @param        $image          image filename (string)
    * @param        $text           the image's alternative text (string)
    * @param        $link           the options alias (string)
    * @param		$onclick		onclick action (string)
    */
    public function add_option($image, $text, $link = null, $onclick = null, $rewrite = true)
    {
    	$option = new stdClass();
    	$option->image = $image;
    	$option->text = $text;
    	$option->link = $link;
    	$option->onclick = $onclick;
    	$option->rewrite = $rewrite;
    	
    	$this->current_base->options[] = $option;
    	
		// set max options
		if (($num_options = count($this->current_base->options)) > $this->max_options)
			$this->max_options = $num_options;

        // add link to allowed actions
        if ($link !== null)
            if (!in_array($link, $this->allowed_actions))
                $this->allowed_actions[] = $link;
    }

	/**
	 * adds an icon to the current dataset
	 * 
	 * @param		$image			image filename (string)
	 * @param		$text			image's alternative text (string)
	 */
	public function add_icon($image, $text, $trl_args = array())
	{
		$icon = new stdClass();
		$icon->image = $image;
		$icon->text = $text;
		$icon->trl_args = $trl_args;
		
		$this->current_base->icons[] = $icon;
		
		// set max icons
		if (($num_icons = count($this->current_base->icons)) > $this->max_icons)
			$this->max_icons = $num_icons;
	}

	/**
	 * adds an dataset indentifier to the current dataset
	 * 
	 * @param		$ident			identifier's name
	 * @param		$value			identifier's value
	 */
    public function add_dataset_ident($ident, $value)
    {
        $this->current_base->idents[$ident] = $value;
    }

	/**
	 * sets the description for the current dataset
	 * 
	 * @param		$descr			description (string)
	 */
    public function set_dataset_descr($descr)
    {
        $this->current_base->descr = $descr;
    }

	#################################################################################################
	### global settings and processing
	#################################################################################################

	/**
	 * adds a global administrative option applicable to multiple datasets
	 * 
     * @param        $image          image filename (string)
     * @param        $text           the image's alternative text (string)
     * @param        $link           the options alias (string)
	 */
    public function add_multi_option($image, $text, $link)
    {
        if (in_array($link, $this->allowed_actions))
        {
        	$option = new stdClass();
        	$option->image = $image;
        	$option->text = $text;
        	$option->link = $link;
        	
            $this->multi_options[] = $option;
        }
    }
    
	/**
	 * sets the global description for multiple datasets
	 * 
	 * @param		$descr			description (string)
	 */
    public function set_multi_descr($descr)
    {
        $this->multi_descr = $descr;
    }

	/**
	 * adds an additional link to the administration
	 * 
	 * @param		$alias			link alias (string)
	 * @param		$text			link text (string)
	 */
    public function add_link($alias, $text)
    {
    	$link = new stdClass();
    	$link->alias = $alias;
    	$link->text = $text;
    	
        $this->links[] = $link;
    }

    /**
    * insert sort table header into xml
    */
    protected function insert_table_header($administration_link)
    {
        $this->unibox->xml->add_node('table_header');
        $this->unibox->xml->add_value('alias', $administration_link);

        if ($this->checkbox_column)
            $this->unibox->xml->add_value('checkbox_column', 1);

        for ($i = 0; $i < $this->max_icons; $i++)
            $this->unibox->xml->add_value('icon_column');

        foreach ($this->administration->sort->fields as $ident => $field)
        {
            $this->unibox->xml->add_node('column');
            $this->unibox->xml->add_value('name', $field->si_name, true);
            $this->unibox->xml->add_value('ident', $ident);
            if ($field->sort == SORT_ASC)
                $this->unibox->xml->add_value('sort', 'ASC');
            else
                $this->unibox->xml->add_value('sort', 'DESC');
            $this->unibox->xml->add_value('width', $field->width);
            
            if ($field->sort == SORT_ASC)
            {
                if ($ident == $this->administration->sort->history[0])
                    $this->unibox->xml->add_value('image', 'arrow_up_active');
                else
                    $this->unibox->xml->add_value('image', 'arrow_up');
            }
            else
            {
                if ($ident == $this->administration->sort->history[0])
                    $this->unibox->xml->add_value('image', 'arrow_down_active');
                else
                    $this->unibox->xml->add_value('image', 'arrow_down');
            }
            $this->unibox->xml->parse_node();
        }
        
        for ($i = 0; $i < $this->max_options; $i++)
            $this->unibox->xml->add_value('option_column');

        $this->unibox->xml->parse_node();
    }

	public function process_sql($sql_string, $count_per_page = 25)
	{
        // process sort information
        $this->process_sort();

        $pagebrowser = ub_pagebrowser::get_instance($this->ident);
        $pagebrowser->process($sql_string, $count_per_page);

        // add sort
		foreach ($this->administration->sort->history as $ident)
        {
        	// set current sort field
        	$sort = $this->administration->sort->fields[$ident];

        	// loop through columns
			$args[] = ' '.$sort->db_field.' '.($sort->sort === SORT_ASC ? 'ASC' : 'DESC');
        }
        if (!empty($args))
        	$sql_string .= ' ORDER BY '.implode(', ', $args);

        // add limit
        if ($limit = $pagebrowser->get_limit())
        	$sql_string .= ' LIMIT '.$pagebrowser->get_limit();

        return $this->unibox->db->query($sql_string, 'failed to get administration data for \''.$this->ident.'\'');
	}

	/**
	 * main data processing function
	 * 
	 * @param		$base			base node (reference)
	 */
	protected function process_data($base)
	{
		// create blank dummy data for array-patching
		$data = new stdClass();
		$data->value = '';
		$data->translate = false;
		$data->sort_value = null;
		$data->link = null;
		$data->onclick = null;
		$data->ident = null;
		$data->type = XML_ATTRIBUTE;

		// get column count
		$column_count = count($this->administration->sort->fields);

		// patch data arrays and transform row-representation to column-representation
		foreach ($base->children as $dataset_index => $child)
		{
			$child->data = array_pad($child->data, $column_count, $data);
			
			foreach ($child->data as $data_index => $data)
				$columns[$data_index][$dataset_index] = $data;
		}

		// show all datasets
		foreach ($base->children as $dataset)
			$this->show_dataset($dataset);
	}

	/**
	 * inserts a dataset into xml
	 * 
	 * @param		$dataset			reference to dataset
	 */
	protected function show_dataset($dataset)
	{
	   	// begin dataset node
        $this->unibox->xml->add_node('dataset');
        $this->unibox->xml->set_attribute('ident', ub_functions::key_implode('/', $dataset->idents));
        
        // check if dataset is marked as invalid
        if (in_array($dataset->idents, $this->administration->invalid))
            $this->unibox->xml->set_attribute('status', '-1');
        elseif (in_array($dataset->idents, $this->administration->valid))
            $this->unibox->xml->set_attribute('status', '1');
        else
            $this->unibox->xml->set_attribute('status', '0');

		// check if a checkbox should be displayed
        if ($dataset->checkbox)
        {
            $this->checkbox_column = true;
            $this->unibox->xml->add_value('checkbox', '1');
        }

		// process icons
        foreach ($dataset->icons as $icon)
        {
            $this->unibox->xml->add_node('icon');
            $this->unibox->xml->add_value('image', $icon->image);
            $this->unibox->xml->add_value('text', $icon->text, true, $icon->trl_args);
            $this->unibox->xml->parse_node();
        }

		// pad icons to the right
		if (($icon_count = count($dataset->icons)) < $this->max_icons)
			for ($i = 0; $i < ($this->max_icons - $icon_count); $i++)
				$this->unibox->xml->add_value('icon', '');

		// process data
        foreach ($dataset->data as $data)
        {
        	// check if a custom node/attribute is required
        	if ($data->ident !== null)
        		if ($data->type == XML_ATTRIBUTE)
        		{
        			$this->unibox->xml->add_node('data');
        			$this->unibox->xml->set_attribute('ident', $data->ident);
        		}
        		else
        			$this->unibox->xml->add_node($data->ident);
        	else
                $this->unibox->xml->add_node('data');

			// set link if required
			if ($data->link !== null)
				$this->unibox->xml->add_value('link', $data->link);

			// set onclick if required
			if ($data->onclick !== null)
				$this->unibox->xml->add_value('onclick', $data->onclick);

			// add value and close data node
			$this->unibox->xml->add_value('value', $data->value, $data->translate);
			$this->unibox->xml->parse_node();
        }
        
    	// pad options to the left
		if (($option_count = count($dataset->options)) < $this->max_options)
			for ($i = 0; $i < ($this->max_options - $option_count); $i++)
				$this->unibox->xml->add_value('option', '');
    	
    	// process options
        foreach ($dataset->options as $option)
        {
            $this->unibox->xml->add_node('option');
            $this->unibox->xml->add_value('image', $option->image);
            $this->unibox->xml->add_value('text', $option->text, true, $dataset->descr !== null ? !is_array($dataset->descr) ? array($dataset->descr) : $dataset->descr : array());
            
            $args_array = $dataset->idents;
            $args_array['administration_fallback'] = $this->unibox->session->env->alias->name;
            if ($option->link !== null)
            	if ($option->rewrite)
            		$link = $this->unibox->create_link($option->link, false, $args_array);
        		else
        			$link = $option->link;
        	else
        		$link = '';
            $this->unibox->xml->add_value('link', $link);
            $this->unibox->xml->add_value('onclick', $option->onclick);
            $this->unibox->xml->parse_node();
        }
        
        // process children of current node
        if (!empty($dataset->children))
            $this->process_data($dataset);
        
        // close dataset node
        $this->unibox->xml->parse_node();
	}
	
    /**
    * show administration
    */
    public function show($administration_link = null, $msg_no_data = null)
    {
    	// check if administration link was passed, if not use current alias
    	if ($administration_link === null)
    		$administration_link = $this->unibox->session->env->alias->name;

		// check if there is any data
        if (empty($this->root->children))
        {
            $msg = new ub_message(MSG_INFO, false);
            $msg->add_text(($msg_no_data === null) ? 'TRL_NO_DATA_FOR_FILTER' : $msg_no_data);
            $msg->display();
            return;
        }

		// begin administration node
        $this->unibox->xml->add_node('administration');
        $this->unibox->xml->set_attribute('ident', $this->ident);
        if (!is_null($this->label))
        	$this->unibox->xml->set_attribute('label', $this->label, true);
        $this->unibox->xml->set_attribute('fallback', $this->unibox->session->env->alias->name);
        $this->unibox->xml->set_attribute('nested_data', $this->nested_data);

        // show additional links if there are any
        if (!empty($this->links))
        {
            $this->unibox->xml->add_node('links');
            foreach ($this->links as $link)
            {
                $this->unibox->xml->add_node('link');
                $this->unibox->xml->add_value('alias', $link->alias);
                $this->unibox->xml->add_value('text', $link->text, true);
                $this->unibox->xml->parse_node();
            }
            $this->unibox->xml->parse_node();
        }

		// check if we have a pagebrowser
        if (isset($this->unibox->session->env->pagebrowser->{$this->ident}))
        {
            $pagebrowser = ub_pagebrowser::get_instance($this->ident);
	        $pagebrowser->show($administration_link);
        }

        // reset checkbox column flag
        $this->checkbox_column = false;

		// begin datasets node
        $this->unibox->xml->add_node('datasets');

		// process data
		$this->process_data($this->root);

        // close datasets node
        $this->unibox->xml->parse_node();

        // insert table header
        $this->insert_table_header($administration_link);

        // show multi options
        $this->unibox->xml->add_node('multi_options');
        foreach ($this->multi_options as $option)
        {
            $this->unibox->xml->add_node('option');
            $this->unibox->xml->add_value('image', $option->image);
            $this->unibox->xml->add_value('text', $option->text, true, ($this->multi_descr !== null) ? array($this->unibox->translate($this->multi_descr)) : array());
            $this->unibox->xml->add_value('link', $option->link);
            $this->unibox->xml->parse_node();
        }
        $this->unibox->xml->parse_node();

        // close adminstration node
        $this->unibox->xml->parse_node();

        // reset valid/invalid datasets
        $this->administration->valid = array();
        $this->administration->invalid = array();
    }

    public function reset()
    {
    	// reset administration environment
        $this->administration = new StdClass();
        $this->administration->valid = array();
        $this->administration->invalid = array();
        $this->administration->sort = new StdClass();
        $this->administration->sort->ident = $this->ident;
        $this->administration->sort->fields = array();
        $this->administration->sort->history = array();
        
        // create root data node
		$this->root->parent = null;
		$this->root->children = array();
		$this->root->data = array();
		$this->root->dataset_index = 0;
		$this->root->data_index = 0;
		$this->root->static_count = 0;
		$this->current_base = $this->root;

		// reset global parameters
        $this->allowed_actions = array();
        $this->multi_options = array();
        $this->links = array();
        $this->multi_descr = null;
        $this->checkbox_column = false;
        $this->nesting_column = false;
        $this->new = true;
        $this->max_options = 0;
        $this->max_icons = 0;
    }
}

?>