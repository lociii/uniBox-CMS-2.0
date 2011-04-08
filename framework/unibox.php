<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_unibox
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * singleton class instance
    * 
    */
    protected static $instance = null;

    /**
    * array of xsl-templates
    * 
    */
    protected $templates = array('main');
    
    protected $processed_templates = array();

    /**
    * array of xsl-templates used by content-style loaded modules 
    * 
    */
    protected $templates_content = array();

    /**
    * array of xsl-templates used by extension-style loaded modules 
    * 
    */
    protected $templates_extension = array();

    /**
    * array of html meta information
    * 
    */
    public $meta = array();

    /**
    * array of important system translations
    * 
    */
    public $translations;

    /**
    * microtime start of processing
    * 
    */
    protected $script_starttime;

    /**
    * determines if a full featured engine should be loaded or not
    * 
    */
    protected $action_short = false;

    /**
    * current extensions order
    * 
    */
    public $within_extension = false;

    /**
    * current extensions ident
    * 
    */
    protected $current_extension_ident = null;

    /**
    * editor mode
    * 
    */
    public $editor_mode = false;

	protected $extensions = array();
	protected $alias_switched = false;

    public $pdf = null;

    /**
    * returns class version
    * 
    * @return   version-number (float)
    */
    public static function get_version()
    {
        return ub_unibox::version;
    } // end get_version()

    #################################################################################################
    ### object construction
    #################################################################################################

    /**
    * returns class instance
    * 
    * @return   ub_unibox (object)
    */
    public static function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_unibox();
        return self::$instance;
    } // end get_instance()

    /**
    * framework constructor
    * starts time measurement, loads constants, initializes translations array and sets the include path
    * 
    */
    protected function __construct()
    {
        // start time measurement
        $this->script_starttime = microtime(true);

        // extend include-path
        set_include_path(get_include_path().PATH_SEPARATOR.DIR_FRAMEWORK_DATABASE);
        
        // default translations
        $this->translations = new StdClass();
        $this->translations->error = '[translation not found]';
        $this->translations->backend = '[translation not found]';
        $this->translations->help = '[translation not found]';
    } // end __construct()

    #################################################################################################
    ### main page processing
    #################################################################################################

    /**
    * initialize framework
    * load all needed objects, prepare and execute page processing
    * 
    */
    public function init()
    {
        try
        {
            // seed random number generator
            mt_srand((double) microtime(true) * 1000000);

			// get session object
			$this->session = ub_session::get_instance();

            // get db object
            $this->db = ub_db::get_instance();

            // get configuration
            $this->config = ub_config::get_instance();

            // call url_rewrite if neccessary
            if ($this->config->system->url_rewrite == 1)
                $this->url_rewrite_input();

            // create xml and xsl objects and set markers
            $this->xml = new ub_xml;
            $this->xml_init();
            $this->xsl = new ub_xsl;

            // get ubc object
            $this->ubc = new ub_ubc();

            // load session
            $this->session->load_session();

            // process content
            $this->alias_process();
        }
        catch (ub_exception $exception)
        {
            $exception->process('TRL_ERR_GENERAL_ERROR');
        }

        // display the processed page
        $this->display_page();
    }

    /**
    * initialize unibox xml
    * create system nodes and set markers
    * 
    */
    protected function xml_init()
    {
        $this->xml->add_node('unibox');
        $this->xml->set_marker('unibox');
        $this->xml->parse_node();

        $this->xml->add_node('location');
        $this->xml->set_marker('location');
        $this->xml->parse_node();

        $this->xml->add_node('content');
        $this->xml->set_marker('content');
        $this->xml->parse_node();

        $this->xml->add_node('extensions');
        $this->xml->set_marker('extensions');
        $this->xml->parse_node();

        $this->xml->add_node('messages');
        $this->xml->set_marker('messages');
        $this->xml->parse_node();

        $this->xml->add_node('dialog');
        $this->xml->set_marker('dialog');
        $this->xml->parse_node();
    }

    /**
    * reset unibox xml
    * kill all content
    * 
    */
    public function xml_reset($markers = array())
    {
        foreach ($markers as $marker)
            if ($this->xml->isset_marker($marker))
            {
                $this->xml->goto($marker);
                $this->xml->remove();
                $this->xml->add_node($marker);
                $this->xml->set_marker($marker);
                $this->xml->parse_node();
                $this->xml->restore();
            }
    }

    /**
    * processes the current alias
    * 
    * @return      success (bool)
    */
    protected function alias_process($alias = null)
    {
    	try
    	{
	        if ($alias === null)
	        {
	            // check for alias
	            $validator = ub_validator::get_instance();
	            if (!$validator->validate('GET', 'do', TYPE_STRING, CHECK_NOTEMPTY))
	            {
	                $this->url_rewrite_input($this->config->system->default_alias);
	                $validator->validate('GET', 'do', TYPE_STRING, CHECK_NOTEMPTY);
	            }
	        }
	        else
	            $this->session->env->input->do = $this->db->cleanup($alias);

	        // get processing informations for requested action
	        $sql_string  = 'SELECT
							  a.alias_group_ident,
	                          b.action_short,
	                          c.theme_ident,
	                          c.subtheme_ident,
	                          b.action_ident,
	                          b.state_init,
	                          b.action_save,
	                          c.location_show_module,
	                          c.location_show_action,
	                          c.location_show_backend,
	                          d.module_ident,
	                          d.module_actionbar_menu_id,
	                          e.string_value,
	                          f.string_value,
	                          g.string_value
	                        FROM
	                          sys_alias AS a
	                            INNER JOIN sys_actions AS b
	                              ON b.action_ident = a.action_ident
	                            INNER JOIN sys_alias_groups AS c
	                              ON c.alias_group_ident = a.alias_group_ident
	                            INNER JOIN sys_modules AS d
	                              ON d.module_ident = b.module_ident
	                            LEFT JOIN sys_translations AS e
	                              ON
	                                (
	                                e.string_ident = d.si_module_name
	                                AND
	                                e.lang_ident = \''.$this->session->lang_ident.'\'
	                                )
	                            LEFT JOIN sys_translations AS f
	                              ON
	                                (
	                                f.string_ident = b.si_action_descr
	                                AND
	                                f.lang_ident = \''.$this->session->lang_ident.'\'
	                                )
	                            LEFT JOIN sys_translations AS g
	                              ON
	                                (
	                                g.string_ident = b.si_action_help
	                                AND
	                                g.lang_ident = \''.$this->session->lang_ident.'\'
	                                )
	                        WHERE
	                          a.alias = \''.$this->session->env->input->do.'\'
	                          AND
	                          d.module_active = 1
	                          AND
	                          b.action_ident IN (\''.implode('\', \'', $this->session->get_rights()).'\')';
	        $result = $this->db->query($sql_string, 'failed to retrieve alias information for '.$this->session->env->input->do);
	        if ($result->num_rows() != 1)
	        	throw new ub_exception_general('failed to get alias information from database');
        	$data = $result->fetch_row();
        	$result->free();

            // safe alias for next fallback
            if (isset($this->session->env->alias->last))
                $last = $this->session->env->alias->last; 
            $this->session->env->alias = new StdClass();
            if (isset($last))
                $this->session->env->alias->last = $last;

			// list data
            list($this->session->env->alias->group_ident, $this->action_short, $theme_ident, $subtheme_ident, $this->session->env->system->action_ident, $this->session->env->system->state, $action_save, $this->session->env->alias->location_show_module, $this->session->env->alias->location_show_action, $this->session->env->alias->location_show_backend, $this->session->env->system->module_ident, $module_actionbar_menu_id, $module_name, $action_descr, $action_help) = $data;

            // save url getvars for next fallback
            $this->session->env->alias->url_get = $_GET;
            unset($this->session->env->alias->url_get['do']);

            $this->session->env->alias->name = $this->session->env->input->do;
            unset($this->session->env->input->do);

            // load content depending theme
            if (!$this->action_short)
                $this->set_theme($theme_ident, $subtheme_ident);

            // select alias get
            $sql_string  = 'SELECT
                              name,
                              value
                            FROM
                              sys_alias_get
                            WHERE
                              alias = \''.$this->session->env->alias->name.'\'';
            $result = $this->db->query($sql_string, 'failed to select alias getvars');
            $this->session->env->alias->get = array();
        	while (list($name, $value) = $result->fetch_row())
            	$this->session->env->alias->get[$name] = $value;
            $result->free();
	            
            foreach ($this->session->env->alias->url_get as $key => $value)
                if (!isset($this->session->env->alias->get[$key]))
                    $this->session->env->alias->get[$key] = $value;
            $this->session->env->alias->main_get = $this->session->env->alias->get;

            if (!$this->action_short)
            {
                // add unibox system information
                $this->xml->goto('unibox');
                $this->xml->add_value('alias', $this->session->env->alias->name);
                $this->xml->add_value('action_descr', $action_descr);
                $this->xml->add_value('action_ident', $this->session->env->system->action_ident);

                // set help
                $this->xml->add_node('help');
                if ($action_help !== null)
                    $this->ubc->ubc2xml($action_help);
                else
                    $this->xml->add_text('TRL_HELP_NOT_FOUND', true);
                $this->xml->parse_node();

                $this->xml->restore();

                // add location information
                if ($this->session->env->alias->location_show_backend)
                    $this->add_location('TRL_FRAMEWORK_LOCATION_BACKEND', 'unibox', true);
                if ($this->session->env->alias->location_show_module && trim($module_name) != '')
                    $this->add_location($module_name, $this->session->env->system->module_ident.'_welcome');
                if ($this->session->env->alias->location_show_action && trim($action_descr) != '')
                    $this->add_location($action_descr, $this->session->env->alias->name);
            }

    		// read extensions
    		$this->get_extensions();

	        // process pre-content extensions
	        if (!$this->action_short)
	            $this->process_extensions(1);

            // goto content node
            $this->xml->goto('content');
			$this->xml->set_attribute('type', $this->session->env->system->action_ident);

            // process content
            $this->state_handler();
            $this->xml->restore();

			// call past-content extensions
	        // process pre-content extensions
	        if (!$this->action_short && !$this->alias_switched)
	            $this->process_extensions(0);

            if (isset($this->session->env->alias->name) && $action_save)
            {
                $this->session->env->alias->last = new StdClass();
                $this->session->env->alias->last->name = $this->session->env->alias->name;
                if (isset($this->session->env->alias->url_get))
                    $this->session->env->alias->last->url_get = $this->session->env->alias->url_get;
            }
    	}
    	catch (ub_exception_general $exception)
    	{
            $this->alias_error();
    	}
    } // end process_alias()

    /**
    * alias error processing
    * thrown if check or processing of an alias failes
    * 
    */
    protected function alias_error()
    {
        // set default theme - if no theme set
        if (!isset($this->session->env->themes->current->theme_ident) || !isset($this->session->env->themes->current->subtheme_ident))
            $this->set_theme();

        // open content node and process content
        $this->xml->goto('content');
        // add location
        $this->xml->remove('help');
        $this->xml->add_node('help');
        $this->xml->add_value('component', 'TRL_HELP_NOT_FOUND', true);
        $this->xml->parse_node();
        $this->xml->goto('location');
        $this->xml->add_node('component');
        $this->xml->add_value('value', 'TRL_ERROR_MESSAGE', true);
        $this->xml->parse_node();
        // restore old position
        $this->xml->restore();
        $this->xml->restore();

        // add error message
        $msg = new ub_message(MSG_ERROR);
        $msg->add_text('TRL_ERR_FORBIDDEN');
        $msg->add_newline(2);
        $msg->add_link($this->session->env->system->default_alias, 'TRL_BACK_TO_FRONTPAGE');
        $msg->display();
    }

    /**
    * loads and processes all the alias-group depending extensions
    * 
    */
    protected function process_extensions($pre_content = 0)
    {
        if (isset($this->extensions[$pre_content]) && !empty($this->extensions[$pre_content]))
        {
            $this->xml->goto('extensions');

            // process extensions
            $this->within_extension = true;
            foreach ($this->extensions[$pre_content] as $group_ident => $group_data)
            {
            	$this->xml->add_node('group');
            	$this->xml->set_attribute('ident', $group_ident);

                foreach ($group_data as $extension_ident => $extension)
                {
                    $this->current_extension_ident = $extension_ident;
                    $this->session->env->alias->get = array();
                    $this->session->env->system->action_ident = $extension['action_ident'];
                    $this->session->env->system->state = $extension['state_init'];
                    if (isset($extension['get']))
                        foreach ($extension['get'] as $get_name => $get_value)
                            $this->session->env->alias->get[$get_name] = $get_value;

                    // add extension content node
                    $this->xml->add_node('content');
                    $this->xml->set_attribute('type', $this->session->env->system->action_ident);
                    $this->xml->set_attribute('ident', $extension_ident);
                    $this->xml->set_attribute('sort', $extension['sort']);

                    // process extension
                    $this->state_handler();

                    // close node
                    $this->xml->parse_node();
                }
                // close group node
                $this->xml->parse_node();
            }
            $this->within_extension = false;
            
            $this->xml->restore();
        }
    }

	protected function get_extensions()
	{
		if (isset($this->session->env->alias->group_ident))
		{
			// get extensions
	        $sql_string  = 'SELECT
	                          a.extension_ident,
							  a.extension_group_ident,
							  a.pre_content,
							  a.sort,
	                          b.action_ident,
	                          c.state_init,
	                          d.name,
	                          d.value
	                        FROM
	                          sys_alias_group_extensions AS a
	                            INNER JOIN sys_extensions AS b
	                              ON b.extension_ident = a.extension_ident
	                            INNER JOIN sys_actions AS c
	                              ON c.action_ident = b.action_ident
	                            LEFT JOIN sys_extensions_get AS d
	                              ON d.extension_ident = a.extension_ident
	                        WHERE
	                          a.alias_group_ident = \''.$this->session->env->alias->group_ident.'\'
	                          AND
	                          b.action_ident IN (\''.implode('\', \'', $this->session->get_rights()).'\')
							  AND
							  a.output_format_ident = \''.$this->session->output_format_ident.'\'
							ORDER BY
							  a.sort';
	        $result = $this->db->query($sql_string, 'failed to select extensions');

			// get data
	        while (list($extension_ident, $extension_group_ident, $pre_content, $sort, $action_ident, $state_init, $get_name, $get_value) = $result->fetch_row())
	        {
	            if (!isset($extensions[$extension_group_ident][$extension_ident]))
	            {
	                $this->extensions[$pre_content][$extension_group_ident][$extension_ident]['action_ident'] = $action_ident;
	                $this->extensions[$pre_content][$extension_group_ident][$extension_ident]['state_init'] = $state_init;
	                $this->extensions[$pre_content][$extension_group_ident][$extension_ident]['sort'] = $sort;
	            }
	            if (trim($get_name) != '' && trim($get_value) != '')
	                $this->extensions[$pre_content][$extension_group_ident][$extension_ident]['get'][$get_name] = $get_value;
	        }
	        $result->free();
		}
	}

    /**
    * redirects to
    *   - given alias
    *   - last processed alias
    *   - default alias
    *
    * @param        $alias              alias to be redirected to (string)
    * @param        $getvars            getvars to be loaded (array)
    */
    public function redirect($alias = null, $getvars = array())
    {
        $_GET = $_POST = array();
        if ($alias === null)
        {
            if (isset($this->session->env->alias->last->name))
            {
                if (isset($this->session->env->alias->last->url_get) && count($this->session->env->alias->last->url_get) > 0)
                    $_GET = $this->session->env->alias->last->url_get;
                $alias = $this->session->env->alias->last->name;
            }
            else
                $alias = $this->session->env->system->default_alias;
        }
        else
        {
            if (count($getvars) > 0)
                $_GET = $getvars;
            $alias = $this->url_rewrite_input($alias);
        }
        $this->switch_alias($alias);
    } // end redirect();

    /**
    * switches to the given alias keeping the given xml-nodes
    *
    * @param        $alias              alias to be redirected to (string)
    * @param        $keep_messages      keep message node (bool, default false)
    * @param		$url_vars			url vars to apply (array, default empty)
    */
    public function switch_alias($alias, $keep_messages = false, $url_vars = array())
    {
        $nodes_to_reset = array('unibox', 'location', 'dialog', 'extensions', 'content');
        if (!$keep_messages)
        	$nodes_to_reset[] = 'messages';

		if (!empty($url_vars))
			$_GET = $url_vars;

        // reset xml
        $this->xml_reset($nodes_to_reset);

        // process new content
        $this->alias_process($alias);

        // remember that we've switched the alias
        $this->alias_switched = true;
    }

    /**
    * switches the current action
    * 
    * @param        $action_ident       switch to given action ident
    * @param        $nodes_to_reset     xml nodes to keep (array)
    */
    public function switch_action($action_ident, $nodes_to_reset = array())
    {
        $default_nodes = array('content', 'extensions', 'messages'); 
        $nodes_to_reset = array_intersect($default_nodes, $nodes_to_reset);
        $nodes_to_reset[] = 'location';
        $nodes_to_reset[] = 'dialog';

        // reset xml nodes
        $this->xml_reset($nodes_to_reset);

        // check for valid action
        $sql_string  = 'SELECT
                          a.state_init,
                          a.action_save,
                          b.module_ident,
                          c.string_value,
                          d.string_value,
                          e.string_value
                        FROM
                          sys_actions AS a
                            INNER JOIN sys_modules AS b
                              ON b.module_ident = a.module_ident
                            LEFT JOIN sys_translations AS c
                              ON
                                (c.string_ident = b.si_module_name
                                AND
                                c.lang_ident = \''.$this->session->lang_ident.'\')
                            LEFT JOIN sys_translations AS d
                              ON
                                (d.string_ident = a.si_action_descr
                                AND
                                d.lang_ident = \''.$this->session->lang_ident.'\')
                            LEFT JOIN sys_translations AS e
                              ON
                                (e.string_ident = a.si_action_help
                                AND
                                e.lang_ident = \''.$this->session->lang_ident.'\')
                        WHERE
                          a.action_ident = \''.$action_ident.'\'
                          AND
                          a.action_ident IN (\''.implode('\', \'', $this->session->get_rights()).'\')';
        $result = $this->db->query($sql_string, 'failed to retrieve action information for '.$action_ident);
        if ($result->num_rows() == 1)
        {
            list($this->session->env->system->state, $this->session->env->system->action_save, $this->session->env->system->module_ident, $module_name, $action_descr, $action_help) = $result->fetch_row();
            $result->free();

            $this->session->env->system->action_ident = $action_ident;

            // fix system info
            $this->xml->goto('unibox');
            $this->xml->remove('action_descr');
            $this->xml->add_value('action_descr', $action_descr);
            $this->xml->remove('help');

            // set help
            $this->xml->add_node('help');
            if ($action_help !== null)
                $this->ubc->ubc2xml($action_help);
            else
                $this->xml->add_text('TRL_HELP_NOT_FOUND', true);
            $this->xml->parse_node();

            $this->xml->restore();

            // add location information
            if ($this->session->env->alias->location_show_backend)
                $this->add_location('TRL_FRAMEWORK_LOCATION_BACKEND', 'unibox', true);
            if ($this->session->env->alias->location_show_module && trim($module_name) != '')
                $this->add_location($module_name, $this->session->env->system->module_ident.'_welcome');
            if ($this->session->env->alias->location_show_action && trim($action_descr) != '')
                $this->add_location($action_descr, $this->session->env->alias->name);
        }
        else
        {
            $this->xml->goto('unibox');
            $this->xml->remove('help');
            $this->xml->add_node('help');
            $this->xml->add_value('component', 'TRL_HELP_NOT_FOUND', true);
            $this->xml->parse_node();
            $this->xml->restore();

            $this->xml->goto('location');
            $this->xml->add_value('component', 'TRL_ERROR_MESSAGE');
            $this->xml->restore();

            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_ACTION_NO_RIGHTS');
            $msg->display();
        }
    }

    /**
    * finite state machine
    * main processing loop - does all the state managing stuff
    * 
    */
    protected function state_handler()
    {
        $sql_string  = 'SELECT
                          a.state_next,
                          a.state_current,
                          a.state_return_value,
                          b.class_name,
                          b.function
                        FROM
                          sys_paths AS a
                            INNER JOIN sys_state_functions AS b
                              ON b.state = a.state_current
                            INNER JOIN sys_class_files AS c
                              ON c.class_name = b.class_name
                            INNER JOIN sys_modules AS d
                              ON d.module_ident = c.module_ident
                        WHERE
                          a.action_ident = \''.$this->session->env->system->action_ident.'\'
                          AND
                          d.module_active = 1
                        ORDER BY
                          a.state_current';
        $result = $this->db->query($sql_string, 'failed to get path');
        $path = new StdClass;
        $state_temp = null;
        while (list($state_next, $state_current, $state_return_value, $class_name, $function) = $result->fetch_row())
        {
            if ($state_current != $state_temp)
            {
                $path->$state_current = new StdClass;
                $path->$state_current->class_name = $class_name;
                $path->$state_current->function = $function;
                $path->$state_current->return_values = new StdClass;
                $state_temp = $state_current;
            }
            $path->$state_current->return_values->$state_return_value = $state_next;
        }
        $result->free();

        // begin path processings
        $action = $this->session->env->system->action_ident;
        while ($this->session->env->system->state != 'CONTENT_PROCESSED')
        {
            // try if state exists
            if (isset($path->{$this->session->env->system->state}))
            {
                // try if class exists or can be autoloaded
                if (class_exists($path->{$this->session->env->system->state}->class_name))
                {
                    // call state function
                    // echo 'memory size before ('.$path->{$this->session->env->system->state}->class_name.') '.memory_get_usage().'<br/>';
                    $state_function_object = call_user_func(array($path->{$this->session->env->system->state}->class_name, 'get_instance'));
                    // echo 'memory size after loading ('.$path->{$this->session->env->system->state}->class_name.') '.memory_get_usage().'<br/>';
                    if (trim($return_value = $state_function_object->{$path->{$this->session->env->system->state}->function}()) == '')
                        $return_value = 0;
					// echo 'memory size after executing ('.$path->{$this->session->env->system->state}->class_name.') '.memory_get_usage().'<br/>';
                }
                else
                    throw new ub_exception_runtime('class not defined after loading class-file: '.$path->{$this->session->env->system->state}->class_name);
            }
            else
                throw new ub_exception_runtime('no function defined or module deactivated for state: '.$this->session->env->system->state);

            // if the content isn't yet completely processed
            if ($this->session->env->system->state != 'CONTENT_PROCESSED')
            {
                // if action hasn't been switched
                if ($action == $this->session->env->system->action_ident)
                {
                    // get new state
                    if (isset($path->{$this->session->env->system->state}->return_values->$return_value))
                        $this->session->env->system->state = $path->{$this->session->env->system->state}->return_values->$return_value;
                    else
                        throw new ub_exception_runtime('path incomplete - halting at state \''.$this->session->env->system->state.'\' with return value \''.$return_value.'\'');
                }
                else
                {
                    $this->state_handler();
                    break;
                }
            }
        }
    } // end state_handler()

    #################################################################################################
    ### output processing
    #################################################################################################

    /**
    * loads all required pdf-templates and computes them
    * 
    */
    // TODO: implement pdf processing
    protected function process_pdf_output()
    {
    } // end process_pdf_output()

    /**
    * finally processes and outputs the content 
    * 
    */
    protected function display_page()
    {
        if ((!isset($this->action_short) || !$this->action_short) && !in_array($this->session->output_format_ident, array('xml', 'none')))
        {
            $this->xml->goto('unibox');
            $this->xml->add_value('html_base', $this->session->html_base);
            $this->xml->add_value('font_size', $this->session->env->themes->current->font_size + $this->session->font_size);
            $this->xml->add_value('theme_ident', $this->session->env->themes->current->theme_ident);
            $this->xml->add_value('subtheme_ident', $this->session->env->themes->current->subtheme_ident);
            $this->xml->add_value('combined_theme_ident', $this->session->env->themes->current->theme_ident.'_'.$this->session->env->themes->current->subtheme_ident);
            $this->xml->add_value('user_id', $this->session->user_id);
            $this->xml->add_value('user_name', $this->session->user_name);
            $this->xml->add_value('user_locale', $this->session->locale->ident);

            // output current time
            $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_USER);
            $time->now();
            $this->xml->add_value('datetime', $time->get_datetime());

            if (isset($this->session->env->alias->group_ident))
                $this->xml->add_value('alias_group_ident', $this->session->env->alias->group_ident);

            // build query string
            if (isset($this->session->env->alias->url_get))
            {
                $result_string = $this->session->env->alias->url_get;
                if ($this->session->uses_cookies)
                	unset($result_string[SESSION_NAME.'_id']);
                if (count($result_string) > 0)
                    $result_string =  $this->session->env->alias->name.'/'.ub_functions::key_implode('/', $result_string);
                else
                    $result_string =  $this->session->env->alias->name;
            }
            else
                $result_string =  $this->session->env->alias->name;

			// transform query string
            if (!$this->session->uses_cookies || $this->config->system->url_rewrite == 0)
            	$result_string = $this->url_rewrite_output_transform($result_string);
            $this->xml->add_value('query_string', $result_string);

            $this->xml->add_value('page_name', $this->config->system->page_name);
            // add meta tags
            $this->xml->add_node('meta');
            foreach ($this->meta AS $key => $value)
            {
                $this->xml->add_node('entry');
                $this->xml->add_value('name', $key);
                $this->xml->add_value('value', $value);
                $this->xml->parse_node();
            }
            $this->xml->parse_node();
            $this->xml->restore();
        }

        // show xml if debug mode set via post var
        if (DEBUG > 0)
        {
            $validator = ub_validator::get_instance();
            if ($validator->validate('POST', 'debug_output_xml', TYPE_STRING, CHECK_NOTEMPTY))
                $this->session->output_format_ident = 'xml';
        }

        // switch output format
        switch ($this->session->output_format_ident)
        {
            case 'none':
                $content = '';
                break;
            case 'xhtml':
                header('Content-Type: text/html; Charset=UTF-8');
                header('Cache-control: no-store, no-cache, must-revalidate');

                // transform content to xhtml output
                $content = $this->xsl->process($this->xml, true);

                // rewrite urls if no catcher method is active
                if (!$this->session->uses_cookies || $this->config->system->url_rewrite == 0)
                    $this->url_rewrite_output($content);

                // append performance info if debug level > 0
                //if (DEBUG > 0)
                    $content .= $this->get_performance_info();
                break;
            case 'pdf':
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename=unibox.pdf');
                header('Cache-control: no-store, no-cache, must-revalidate');
                $content = $this->process_pdf_output();
                header('Content-Length: '.strlen($content));
                break;
            case 'xml':
            default:
                header('Content-Type: text/xml; Charset=UTF-8');
                header('Cache-control: no-store, no-cache, must-revalidate');
                $content = $this->xml->get_tree();

                // append performance info if debug level > 0
                if (DEBUG > 0)
                    $content .= $this->get_performance_info();
                break;
        }
        die($content);
    } // end display_page()

    #################################################################################################
    ### error handling and performance measurement
    #################################################################################################

    public function log($log_type, $log_message = null, $content_id = null, $content_lang_ident = null)
    {
    	if (!isset($this->config) || !isset($this->db))
    		return false;

    	if (!$this->module_available('log'))
    		return false;

		$this->config->load_config('log');

		if (
			    (
	    		$log_type == LOG_ALTER
	    		&&
	    		isset($this->config->system->log_level_alter)
	    		&&
	    		$this->config->system->log_level_alter
	    		)
	    	||
	    		(
	    		$log_type == LOG_ERR_GENERAL
	    		&&
	    		isset($this->config->system->log_level_err_general)
	    		&&
	    		$this->config->system->log_level_err_general
	    		)
	    	||
	    		(
	    		$log_type == LOG_ERR_SECURITY
	    		&&
	    		isset($this->config->system->log_level_err_security)
	    		&&
	    		$this->config->system->log_level_err_security
	    		)
	    	||
	    		(
	    		$log_type == LOG_ERR_DB
	    		&&
	    		isset($this->config->system->log_level_err_db)
	    		&&
	    		$this->config->system->log_level_err_db
	    		)
	    	||
	    		(
	    		$log_type == LOG_ERR_RUNTIME
	    		&&
	    		isset($this->config->system->log_level_err_runtime)
	    		&&
	    		$this->config->system->log_level_err_runtime
	    		)
	    	)
		{
            $log_message = ($log_message !== null) ? '\''.$this->db->cleanup(serialize($log_message)).'\'' : 'null';
            $content_lang_ident = ($content_lang_ident !== null) ? '\''.$this->db->cleanup($content_lang_ident).'\'' : 'null';
            if ($content_id === null)
                $content_id = 'null';
            else
                $content_id = (int)$content_id;

            $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
            $time->now();
            $ip = ub_functions::get_ip();
            $hostname = gethostbyaddr($ip);
            if (strlen($hostname) > 250)
                $hostname = substr($hostname, 0, 250);
            $ip = ub_functions::encode_ip($ip);

            $sql_string  = 'INSERT INTO
                              data_log
                            SET
                              type = '.$log_type.',
                              time = \''.$time->get_datetime().'\',
                              user_id = '.$this->session->user_id.',
                              user_lang_ident = \''.$this->session->lang_ident.'\',
                              user_ip = \''.$ip.'\',
                              user_hostname = \''.$this->db->cleanup($hostname).'\',
                              action_ident = \''.(isset($this->session->env->system->action_ident) ? $this->session->env->system->action_ident : 'system startup').'\',
                              message = '.$log_message.',
                              content_id = '.$content_id.',
                              content_lang_ident = '.$content_lang_ident.',
                              url_get = '.(!empty($this->session->env->alias->url_get) ? '\''.$this->db->cleanup(serialize($this->session->env->alias->url_get)).'\'' : 'null').',
                              alias_get = '.(!empty($this->session->env->alias->get) ? '\''.$this->db->cleanup(serialize($this->session->env->alias->get)).'\'' : 'null');
            $this->db->query($sql_string, 'failed to log event');
        }
    }

    /**
    * processes (and on fatal ones displays) all types of errors
    * 
    * @param       $err_msg                language independent errormessage (string)
    * @param       $err_form               form name (string)
    * @param       $err_input_field        form input-field containing the error - only on form errors (string)
    * @param       $err_input_value        form input-fields value - only on form errors (string)
    * @param       $args                   translation arguments
    */
    public function error($err_msg = null, $err_form = null, $err_field = null, $err_value = null, $err_args = array())
    {
        // ignore error if no message was passed
        if ($err_msg === null)
            return;

        // create error object
        $error = new stdClass();
        $error->message = $err_msg;
        $error->args = $err_args;
        $error->field = $err_field;
        $error->value = $err_value;

        // check if error was invoked by form validation
        if ($err_form !== null && isset($this->session->env->form->$err_form))
        {
            $this->session->env->form->$err_form->error[] = $error;
            return;
        }

        // check if the exact error message already exists
        if (isset($this->session->env) && isset($this->session->env->error))
            foreach ($this->session->env->error as $temp_error)
                if ($temp_error->message == $error->message && $temp_error->field == $error->field && $temp_error->value == $error->value)
                    return;

        // append error-object
        $this->session->env->error[] = $error;
    } // end error()
    
    /**
    * forces unibox to display the current error information stored in the environment
    * 
    * @param       $delete_content              delete already generated content (bool)
    */
    public function display_error($delete_content = true)
    {
        // build message
        $msg = new ub_message(MSG_ERROR, $delete_content);

        end($this->session->env->error);
        $last = key($this->session->env->error);

        foreach ($this->session->env->error as $key => $error)
        {
            $msg->add_text($error->message, array($error->field, $error->value));
            if ($key != $last)
                $msg->add_newline();
        }
        $msg->display();

        // reset error array
        $this->session->env->error = array();
    } // end display_error()

    /**
    * returns performance output
    * 
    * @return       performance info (string)
    */
    protected function get_performance_info()
    {
        // calculate script runtime
        $script_endtime = microtime(true);

        $info = "\n<!--\n\t db queries: ".$this->db->get_query_count()."\n\t xslt processing time: ".sprintf('%.03f sec', $this->xsl->get_processtime())."\n\t script runtime: ".sprintf('%.03f sec', $script_endtime - $this->script_starttime);
        if (function_exists('memory_get_usage'))
            $info .= "\n\t memory usage: ".sprintf('%.03f kb', memory_get_usage() / 1024);
        if (function_exists('memory_get_peak_usage'))
            $info .= "\n\t max memory usage: ".sprintf('%.03f kb', memory_get_peak_usage() / 1024);  
        $info .= "\n-->";

        return $info;
    } // end get_performance_info()

    /**
    * adds the performance info to pdf file info
    * 
    */
    // TODO: add pdf performance info
    protected function add_pdf_performance_info()
    {
    }

    #################################################################################################
    ### url rewriter
    #################################################################################################

    /**
    * rewrites the directory-style request uri
    * 
    * @param        $url            url to be rewritten (string)
    * @return       alias
    */
    protected function url_rewrite_input($url = '')
    {
        if (trim($url) == '')
        {
            $offset = strlen(dirname($_SERVER['SCRIPT_NAME']));
            $url = substr($_SERVER['REQUEST_URI'], $offset);
        }
        if (!preg_match('/^\/index\.php5?\?do=/i', $url))
        {
            $_GET['do'] = strtok($url, '/');
            while (($var = strtok('/')) !== FALSE)
                $_GET[$var] = strtok('/');
        }
        return $_GET['do'];
    } // end url_rewrite()

    /**
    * rewrites the directory-style links etc. via url_rewrite_output_transform()
    * 
    * @param        $page           page-content reference (string)
    */
    protected function url_rewrite_output(&$page)
    {
        // save textareas
        $sections = array();
        $matches = array();
        if (preg_match_all('/<textarea[^>]*>([^<]*)<\/textarea>/ims', $page, $matches, PREG_SET_ORDER))
        {
            // generate section mark
            mt_srand((double)microtime()*1000000);
            $section_mark = 'TEXTAREA_'.substr(md5(uniqid(mt_rand())), 0, 10).'_';
            $index = 0;

            foreach ($matches AS $matches_arr)
            {
                $page = preg_replace('/'.preg_quote($matches_arr[0], '/').'/', $section_mark.$index, $page);
                $sections[$index] = $matches_arr[0];
                $index++;
            }
        }

        // rewrite urls
        $page = preg_replace('/(action|code|codebase|href|longdesc|src)=([\'"])(.*?)(\\2)/ie', '"\\1=\\2".$this->url_rewrite_output_transform("\\3")."\\4"', $page);

        // restore textareas
        foreach ($sections AS $index => $content)
            $page = preg_replace('/'.$section_mark.$index.'/', $content, $page);
    } // end url_rewrite_output()

    /**
    * rewrites the directory-style links etc.
    * 
    * @param        $url            url to be rewritten (string)
    * @param        $utf8           flag if output should be utf8 conform (bool)
    * @return       rewritten url (string)
    */
    public function url_rewrite_output_transform($url, $utf8 = true)
    {
        $sign_and = ($utf8) ? '&#38;' : '&';
        $pattern = '/(^ad\.php)|(^ad_click\.php)|(^media\.php)|(^install\/)|(^irc\/)|(^media\/)|(^themes\/)|(^tinymce\/)|(\.js$)|(^javascript:)|(^mailto:)|(^https?:\/\/)|(^#)/i';
        if (preg_match($pattern, $url))
            return $url;

        // add session id if not using cookies
        if (!$this->session->uses_cookies)
        {
            // check if an anchor is set and cut it off
            if (stristr($url, '#'))
                list($url, $anchor) = explode('#', $url);

            // add the session id
            if (substr($url, -1, 1) != '/')
                $url .= '/';
            $url .= SESSION_NAME.'_id/'.$this->session->session_id;

            // re-add the anchor
            if (isset($anchor))
                $url .= '#'.$anchor;
        }

        // check if directory-style urls are disabled and rewrite
        if ($this->config->system->url_rewrite == 0)
        {
            $file = basename($_SERVER['SCRIPT_NAME']);
            $url = $file.'?do='.strtok($url, '/');
            while (($var = strtok('/')) !== false)
            {
                if (trim($var) != '')
                    $url .= $sign_and.$var;
                $val = strtok('/');
                if ($val !== false)
                    $url .= '='.$val;
            }
        }
        return $url;
    } // url_rewrite_output_transform()

    /**
    * creates a link for use in newsletters etc.
    * 
    * @param        $url            url to be processed (string)
    * @return       rewritten url (string)
    */
    public function create_link($url, $url_absolute = true, $result_values = array())
    {
        $result_string = ub_functions::key_implode('/', $result_values);

        // pass through javascript links
        if (stristr($url, 'javascript:'))
            return $url;
        
        // fix missing '/' 
        if (substr($url, -1, 1) != '/')
            $url = $url.'/';

        $url = $url.$result_string;

        if (strtolower(substr($url, 0, 7)) == 'http://')
            return $url;
        elseif ($this->config->system->url_rewrite == URL_REWRITE_MOD)
            if ($url_absolute)
                return $this->session->html_base.$url;
            else
                return $url;
        else
            if ($url_absolute)
                return $this->session->html_base.$this->url_rewrite_output_transform($url, false);
            else
                return $this->url_rewrite_output_transform($url, false);
    }
    
    #################################################################################################
    ### template and design functions
    #################################################################################################

    /**
    * checks for the most identifying alias
    * 
    * @param        $action_ident		action ident to check alias for (string)
    * @param		$getvars			requested getvars (associative array)
    * @param		$theme_ident		requested theme ident
    * @return       alias (string)
    */
	public function identify_alias($action_ident, $getvars = array(), $theme_ident = null)
	{
		$key = md5(serialize(func_get_args()));
		if (isset($this->alias_cache[$key]))
			return $this->alias_cache[$key];

		// initialize counter
		$count = 0;
		$fields = $joins = $order = array();

		// process getvars
		foreach ($getvars as $name => $value)
		{
			$count++;
			$fields[] = 'c'.$count.'.value AS '.$name;
			$joins[] = 'INNER JOIN sys_alias_get AS c'.$count.'
						  ON
						  (
						  c'.$count.'.alias = a.alias
						  AND
						  c'.$count.'.name = \''.$this->db->cleanup($name).'\'
						  AND
						  c'.$count.'.value = \''.$this->db->cleanup($value).'\'
						  )';
			$order[] = 'c'.$count.'.value DESC';
		}

		// build sql string
		$sql_string  = 'SELECT
						  a.alias ';

		$sql_string .= 'FROM
						  sys_alias AS a ';

		// set join condition for theme
		if ($theme_ident !== null)
			$sql_string .= 'LEFT JOIN sys_alias_groups AS b
							  ON
							  (
							  b.alias_group_ident = a.alias_group_ident
							  AND
							  b.theme_ident = \''.$this->db->cleanup($theme_ident).'\'
							  )
							LEFT JOIN sys_alias_groups AS c
							  ON
							  (
							  c.alias_group_ident = a.alias_group_ident
							  AND
							  c.theme_ident = (SELECT fallback_theme_ident FROM sys_themes WHERE theme_ident = \''.$this->db->cleanup($theme_ident).'\')
							  ) ';

		// join for current alias group
		$sql_string .= 'LEFT JOIN sys_alias_groups AS d
						  ON
						  (
						  d.alias_group_ident = a.alias_group_ident
						  AND
						  d.alias_group_ident = \''.$this->session->env->alias->group_ident.'\'
						  ) ';

		// join for current theme
		if (isset($this->session->env->themes->current->theme_ident))
			$sql_string .= 'LEFT JOIN sys_alias_groups AS e
							  ON
							  (
							  e.alias_group_ident = a.alias_group_ident
							  AND
							  e.theme_ident = \''.$this->session->env->themes->current->theme_ident.'\'
							  ) ';

		// join for fallback theme
		if (isset($this->session->env->themes->fallback->theme_ident))
			$sql_string .= 'LEFT JOIN sys_alias_groups AS f
							  ON
							  (
							  f.alias_group_ident = a.alias_group_ident
							  AND
							  f.theme_ident = \''.$this->session->env->themes->fallback->theme_ident.'\'
							  ) ';

		// join getvar tables
		$sql_string .= implode(' ', $joins);
		$sql_string .= ' WHERE a.action_ident = \''.$this->db->cleanup($action_ident).'\'';

		// order result by priority
		$sql_string .= ' ORDER BY ';
		
		// 1: getvars
		if (!empty($getvars))
			$sql_string .= implode(', ', $order).', ';
		// 2: theme ident
		if ($theme_ident !== null)
			$sql_string .= 'b.theme_ident DESC,
							c.theme_ident DESC, ';
		//
		$sql_string .= ' d.alias_group_ident DESC';
		
		if (isset($this->session->env->themes->current->theme_ident))
			$sql_string .= ', e.theme_ident DESC';

		if (isset($this->session->env->themes->fallback->theme_ident))
			$sql_string .= ', f.theme_ident DESC';

		$sql_string .= ' LIMIT 0,1';
		$result = $this->db->query($sql_string, 'failed to identify alias for action \''.$action_ident.'\'');
		if ($result->num_rows() == 1)
		{
			list($alias) = $result->fetch_row();
			$this->alias_cache[$key] = $alias;

			return $alias;
		}
		elseif (DEBUG > 0)
			return null;
		else
			throw new ub_exception_general('failed to identify alias for action \''.$action_ident.'\'', 'TRL_ERR_FAILED_TO_IDENTIFY_ALIAS', array($action_ident));
	}

    /**
    * adds a new template to the loading list
    *
    * @param        $template_ident     template-ident to be added (string)
    */
    public function load_template($template_ident)
    {
    	// save extension - template assignment
        if ($this->within_extension && (!isset($this->templates_extension[$template_ident]) || !in_array($this->current_extension_ident, $this->templates_extension[$template_ident])))
            $this->templates_extension[$template_ident][] = $this->current_extension_ident;
        elseif (!$this->within_extension && !in_array($template_ident, $this->templates_content))
        	$this->templates_content[] = $template_ident;

        if (!in_array($template_ident, $this->templates))
            $this->templates[] = $template_ident;
    } // end load_template()

    /**
    * removes all templates from the loading list
    *
    */
    public function unload_templates()
    {
        if (!$this->within_extension)
            $this->templates = $this->templates_content = $this->templates_extension = array();
    } // end unload_templates()

    /**
    * returns template loading list
    *
    * @return       template loading list (array)
    */
    public function get_templates()
    {
        return $this->templates;
    } // end get_templates()

    /**
    * returns processed template loading list
    *
    * @return       processed template loading list (array)
    */
    public function get_processed_templates()
    {
        return $this->processed_templates;
    } // end get_processed_templates()

    /**
    * returns extension template loading list
    *
    * @return       extension template loading list (array)
    */
    public function get_templates_extension()
    {
        return $this->templates_extension;
    } // end get_templates_extension()

    /**
    * returns content template loading list
    *
    * @return       content template loading list (array)
    */
    public function get_templates_content()
    {
        return $this->templates_content;
    } // end get_templates_content()

    /**
    * sets an array as template loading list
    *
    * @param        $template_arr       array of template-idents to be loaded (array)
    */
    public function set_templates($template_arr = array(), $processed_template_arr = array())
    {
        $this->templates = $template_arr;
        $this->processed_templates = $processed_template_arr;
    } // end set_templates()

    /**
    * sets an array as template loading list
    *
    * @param        $template_arr       array of template-idents to be loaded (array)
    */
    public function set_templates_content($template_arr)
    {
        $this->templates_content = $template_arr;
    } // end set_templates()

    /**
    * sets an array as template loading list
    *
    * @param        $template_arr       array of template-idents to be loaded (array)
    */
    public function set_templates_extension($template_arr)
    {
        $this->templates_extension = $template_arr;
    } // end set_templates()

    /**
    * validates and sets the given page theme
    *
    * @param        $theme_ident        theme-ident to be loaded (string)
    */
    protected function set_theme($theme_ident = null, $subtheme_ident = null)
    {
        // try if a theme change is requested
        if (!empty($theme_ident) && (!isset($this->session->env->themes->current->theme_ident) || $theme_ident != $this->session->env->themes->current->theme_ident))
        {
            // try if the subtheme should change too
            if (!empty($subtheme_ident))
            {
                // try if the given theme/subtheme combination is valid
                $sql_string  = 'SELECT
                                  a.theme_ident,
                                  a.subtheme_ident,
                                  b.default_alias,
                                  b.default_subtheme_ident,
                                  b.font_size
                                FROM
                                  sys_theme_output_formats AS a
                                    INNER JOIN sys_themes AS b
                                      ON b.theme_ident = a.theme_ident
                                WHERE
                                  a.theme_ident = \''.$theme_ident.'\'
                                  AND
                                  a.subtheme_ident = \''.$subtheme_ident.'\'
                                  AND
                                  a.output_format_ident = \''.$this->session->output_format_ident.'\'';
                $result = $this->db->query($sql_string, 'failed to verify theme/subtheme combination');
                if ($result->num_rows() == 1)
                {
                    list($this->session->env->themes->current->theme_ident, $this->session->env->themes->current->subtheme_ident, $this->session->env->themes->current->default_alias, $this->session->env->themes->current->default_subtheme_ident, $this->session->env->themes->current->font_size) = $result->fetch_row();
                    $result->free();
                    $this->set_theme_fallback();
                    return;
                }
            }

            // try current subtheme for given theme
            if (isset($this->session->env->themes->current->default_subtheme_ident) && !empty($this->session->env->themes->current->default_subtheme_ident))
            {
                // try if the given theme/subtheme combination is valid
                $sql_string  = 'SELECT
                                  a.theme_ident,
                                  b.default_alias,
                                  b.default_subtheme_ident,
                                  b.font_size
                                FROM
                                  sys_theme_output_formats AS a
                                    INNER JOIN sys_themes AS b
                                      ON b.theme_ident = a.theme_ident
                                WHERE
                                  a.theme_ident = \''.$theme_ident.'\'
                                  AND
                                  a.subtheme_ident = \''.$this->session->env->themes->current->default_subtheme_ident.'\'
                                  AND
                                  a.output_format_ident = \''.$this->session->output_format_ident.'\'';
                $result = $this->db->query($sql_string, 'failed to verify theme/subtheme combination');
                if ($result->num_rows() == 1)
                {
                    list($this->session->env->themes->current->theme_ident, $default_alias, $this->session->env->themes->current->default_subtheme_ident, $this->session->env->themes->current->font_size) = $result->fetch_row();
                    $result->free();
                    if (!empty($default_alias))
                        $this->session->env->themes->current->default_alias = $default_alias;
                    $this->set_theme_fallback();
                    return;
                }
            }

            // try to switch theme and get default subtheme
            $sql_string  = 'SELECT
                              a.theme_ident,
                              a.default_subtheme_ident,
                              a.default_alias,
                              a.font_size
                            FROM
                              sys_themes AS a
                                INNER JOIN sys_theme_output_formats AS b
                                  ON
                                  (
                                  b.theme_ident = a.theme_ident
                                  AND
                                  b.subtheme_ident = a.default_subtheme_ident
                                  )
                            WHERE
                              a.theme_ident = \''.$theme_ident.'\'
                              AND
                              b.output_format_ident = \''.$this->session->output_format_ident.'\'';
            $result = $this->db->query($sql_string, 'failed to verify theme and set default subtheme');
            if ($result->num_rows() == 1)
            {
                list($this->session->env->themes->current->theme_ident, $this->session->env->themes->current->subtheme_ident, $this->session->env->themes->current->default_alias, $this->session->env->themes->current->font_size) = $result->fetch_row();
                $result->free();
                $this->session->env->themes->current->default_subtheme_ident = $this->session->env->themes->current->subtheme_ident;
                $this->set_theme_fallback();
                return;
            }
            
            // theme has failed. try to get theme and subtheme both as default
            $sql_string  = 'SELECT
                              a.default_theme_ident,
                              b.default_subtheme_ident,
                              b.default_alias,
                              b.font_size
                            FROM
                              sys_output_formats AS a
                                INNER JOIN sys_themes AS b
                                  ON b.theme_ident = a.default_theme_ident
                                INNER JOIN sys_theme_output_formats AS c
                                  ON
                                  (
                                  c.theme_ident = a.default_theme_ident
                                  AND
                                  c.subtheme_ident = b.default_subtheme_ident
                                  )
                            WHERE
                              c.output_format_ident = \''.$this->session->output_format_ident.'\'';
            $result = $this->db->query($sql_string, 'failed to set default theme and subtheme');
            if ($result->num_rows() == 1)
            {
                list($this->session->env->themes->current->theme_ident, $this->session->env->themes->current->subtheme_ident, $this->session->env->themes->current->default_alias, $this->session->env->themes->current->font_size) = $result->fetch_row();
                $result->free();
                $this->session->env->themes->current->default_subtheme_ident = $this->session->env->themes->current->subtheme_ident;
                $this->set_theme_fallback();
                return;
            }
        }
        elseif (!empty($subtheme_ident) && (!isset($this->session->env->themes->current->subtheme_ident) || $subtheme_ident != $this->session->env->themes->current->subtheme_ident))
        {
            // try to get passed subtheme for current theme
            // ignore subtheme change if not
            if (isset($this->session->env->themes->current->theme_ident))
            {
                $sql_string  = 'SELECT
                                  subtheme_ident
                                FROM
                                  sys_theme_output_formats
                                WHERE
                                  theme_ident = \''.$this->session->env->themes->current->theme_ident.'\'
                                  AND
                                  subtheme_ident = \''.$subtheme_ident.'\'
                                  AND
                                  output_format_ident = \''.$this->session->output_format_ident.'\'';
                $result = $this->db->query($sql_string, 'failed to get default theme and given subtheme');
                if ($result->num_rows() == 1)
                {
                    list($this->session->env->themes->current->subtheme_ident) = $result->fetch_row();
                    $result->free();
                    $this->set_theme_fallback();
                    return;
                }
            }
        }
        // select default theme/subtheme
        elseif (!isset($this->session->env->themes->current->theme_ident) || !isset($this->session->env->themes->current->subtheme_ident))
        {
            $sql_string  = 'SELECT
                              a.default_theme_ident,
                              b.default_subtheme_ident,
                              b.font_size
                            FROM
                              sys_output_formats AS a
                                INNER JOIN sys_themes AS b
                                  ON b.theme_ident = a.default_theme_ident
                                INNER JOIN sys_theme_output_formats AS c
                                  ON
                                  (
                                  c.output_format_ident = a.output_format_ident
                                  AND
                                  c.theme_ident = a.default_theme_ident
                                  AND
                                  c.subtheme_ident = b.default_subtheme_ident
                                  )
                            WHERE
                              a.output_format_ident = \''.$this->session->output_format_ident.'\'';
            $result = $this->db->query($sql_string, 'failed to get default theme and given subtheme');
            if ($result->num_rows() == 1)
            {
                list($this->session->env->themes->current->theme_ident, $this->session->env->themes->current->subtheme_ident, $this->session->env->themes->current->font_size) = $result->fetch_row();
                $result->free();
                $this->session->env->themes->current->default_subtheme_ident = $this->session->env->themes->current->subtheme_ident;
                $this->set_theme_fallback();
                return;
            }
        }
        if (!isset($this->session->env->themes->current->theme_ident) || !isset($this->session->env->themes->current->subtheme_ident))
        {
            // check if output format is theme depending
            $sql_string  = 'SELECT
                              filename_extension
                            FROM
                              sys_output_formats
                            WHERE
                              output_format_ident = \''.$this->session->output_format_ident.'\'';
            $result = $this->db->query($sql_string, 'failed to select if output format is theme depending');
            if ($result->num_rows() == 1)
            {
                list($filename_extension) = $result->fetch_row();
                $result->free();
                if (empty($filename_extension))
                    return;
            }
            throw new ub_exception_runtime('failed to get theme');
        }
    } // end set_theme()

    /**
    * try to find out and set a fallback theme for the current page theme
    *
    */
    protected function set_theme_fallback()
    {
        // check if output layer is not xml and thus theme depending
        if ($this->session->output_format_ident != 'xml')
        {
            $sql_string  = 'SELECT
                              a.fallback_theme_ident,
                              c.subtheme_ident,
                              d.subtheme_ident
                            FROM
                              sys_themes AS a
                                INNER JOIN sys_themes AS b
                                  ON b.theme_ident = a.fallback_theme_ident
                                LEFT JOIN sys_theme_output_formats AS c
                                  ON
                                  (
                                  c.theme_ident = a.fallback_theme_ident
                                  AND
                                  c.subtheme_ident = \''.$this->session->env->themes->current->subtheme_ident.'\'
                                  AND
                                  c.output_format_ident = \''.$this->session->output_format_ident.'\'
                                  )
                                LEFT JOIN sys_theme_output_formats AS d
                                  ON
                                  (
                                  d.theme_ident = a.fallback_theme_ident
                                  AND
                                  d.subtheme_ident = b.default_subtheme_ident
                                  AND
                                  d.output_format_ident = \''.$this->session->output_format_ident.'\'
                                  )
                            WHERE
                              a.theme_ident = \''.$this->session->env->themes->current->theme_ident.'\'';
            $result = $this->db->query($sql_string, 'failed to determine fallback theme and subtheme');
            if ($result->num_rows() == 1)
            {
                list($theme, $current_subtheme, $default_subtheme) = $result->fetch_row();
                $result->free();
                $this->session->env->themes->fallback->theme_ident = $theme;
                $this->session->env->themes->use_fallback = true;
                $this->session->env->themes->fallback->subtheme_ident = $current_subtheme;
            	$this->session->env->themes->fallback->default_subtheme_ident = $default_subtheme;
                return;
            }
            $this->session->env->themes->fallback->theme_ident = null;
            $this->session->env->themes->fallback->subtheme_ident = null;
            $this->session->env->themes->use_fallback = false;
        }
    } // end set_theme_fallback()

    #################################################################################################
    ### misc
    #################################################################################################

    /**
    * translates given string_ident 
    *
    * @param        $string             translation identifier (string)
    * @return       translation (string)
    */
    public function translate($string, $trl_args = array())
    {
        if (!isset($this->translations->$string))
        {
            $sql_string  = 'SELECT
                              string_value
                            FROM
                              sys_translations
                            WHERE
                              string_ident = \''.$string.'\'
                              AND
                              lang_ident = \''.$this->session->lang_ident.'\'';
            $result = $this->db->query($sql_string, 'failed to translate \''.$string.'\'');
            if ($result->num_rows() == 1)
            {
                list($this->translations->$string) = $result->fetch_row();
                $result->free();
            }
            else
                return $string;
        }

        // replace placeholders in string_value
        $string_value = preg_replace('/\$\$(\d+)/e', '$trl_args[\\1-1]', $this->translations->$string);

        return $string_value;
    }

    public function insert_string_ident($string_ident)
    {
        $sql_string = 'SELECT string_ident FROM sys_string_identifiers WHERE string_ident = \''.$this->db->cleanup($string_ident).'\'';
        $result = $this->db->query($sql_string, 'failed to check for string identifier');
        if ($result->num_rows() == 0)
        {
        	$result->free();
            $sql_string = 'INSERT INTO sys_string_identifiers SET string_ident = \''.$this->db->cleanup($string_ident).'\'';
            $this->db->query($sql_string, 'failed to insert string identifier');
            if ($this->db->affected_rows() == 1)
                return true;
        }
        elseif ($result->num_rows() == 1)
            return true;
        return false;
    }

    /**
     * inserts a translation
     * 
     * @param       $string_ident       translation identifier
     * @param       $translations       assoc array of translations
     * @param       $module_ident       requiring module
     * @return      status
     */
    public function insert_translation($string_ident, $translations, $module_ident = null)
    {
        // insert translations
        $empty_lang_idents = array();
        $insert = false;

		// insert string ident
        $this->insert_string_ident($string_ident);

        $sql_string =  'REPLACE INTO
                          sys_translations
                        (string_ident, lang_ident, string_value)
                        VALUES ';
        foreach ($translations as $lang_ident => $translation)
            if (!empty($translation))
            {
                $insert = true;
                $sql_string .= '(\''.$this->db->cleanup($string_ident).'\', \''.$this->db->cleanup($lang_ident).'\', \''.$this->db->cleanup(stripslashes($translation)).'\'), ';
            }
            else
                $empty_lang_idents[] = $lang_ident;

        // check if there is anything to insert
        if ($insert)
            if (!$this->db->query(substr($sql_string, 0, -2), 'failed to update/insert translation') || $this->db->affected_rows() == 0)
                return false;

        // delete empty translations
        if (count($empty_lang_idents) > 0)
        {
            $sql_string =  'DELETE FROM
                              sys_translations
                            WHERE
                              string_ident = \''.$this->db->cleanup($string_ident).'\'
                              AND
                              lang_ident IN (\''.implode('\', \'', $this->db->cleanup($empty_lang_idents)).'\')';
            $this->db->query($sql_string, 'failed to delete empty translations');
        }

        // insert dependencies
        if ($module_ident !== null && $insert)
            if (!$this->insert_translation_dependency($string_ident, $module_ident))
                return false;

        // everything ok
        return true;
    }

    /**
     * deletes a translation
     * 
     * @param       $string_ident       translation identifier
     * @param       $module_ident       unrequiring module
     * @return      status
     */
    public function delete_translation($string_ident, $module_ident)
    {
        // delete translation dependecy for passed module
        $sql_string  = 'DELETE FROM
                          sys_translation_dependencies
                        WHERE
                          string_ident = \''.$string_ident.'\'
                          AND
                          module_ident = \''.$module_ident.'\'';
        if (!$this->db->query($sql_string, 'failed to delete translation dependency'))
            return false;

        // check if there are more translation dependecies for this translation
        $sql_string  = 'SELECT
                          COUNT(*)
                        FROM
                          sys_translation_dependencies
                        WHERE
                          string_ident = \''.$string_ident.'\'';
        if (!($result = $this->db->query($sql_string, 'failed to check for other translation dependencies')) && $result->num_rows() != 1)
            return false;
        list($count) = $result->fetch_row();
        $result->free();

        if ($count != 0)
            return false;

        // delete translation
        $sql_string  = 'DELETE FROM
                          sys_translations
                        WHERE
                          string_ident = \''.$string_ident.'\'';
        if (!$this->db->query($sql_string, 'failed to delete translations'))
            return false;

        // delete string identifier
        $sql_string  = 'DELETE FROM
                          sys_string_identifiers
                        WHERE
                          string_ident = \''.$string_ident.'\'';
        if (!$this->db->query($sql_string, 'failed to delete string identifier'))
            return false;
        
        return true;
    }

    /**
     * insert_translation_dependency()
     * 
     * adds a translation dependency
     * 
     * @param   string      string_ident
     * @param   string      module ident
     * @return  bool        success
     * @access  public
     */
    function insert_translation_dependency($string_ident, $module_ident)
    {
    	// insert string ident
        $this->insert_string_ident($string_ident);

        // replace dependency
        $sql_string =  'REPLACE INTO
                          sys_translation_dependencies
                        SET
                          string_ident = \''.$string_ident.'\',
                          module_ident = \''.$module_ident.'\'';
        return ($this->db->query($sql_string, 'error while inserting/updating dependency: '.$string_ident.' - '.$module_ident) && $this->db->affected_rows() > 0);
    } // end insert_translation_dependency()

    public function set_content_title($text, $trl_args = array(), $translate = true)
    {
        $this->xml->goto('unibox');
        $this->xml->add_value('content_title', $text, $translate, $trl_args);
        $this->xml->restore();
    }

    public function add_location($si_name, $url = null, $translate = false, $trl_args = array())
    {
        $this->xml->goto('location');
        $this->xml->add_node('component');
        $this->xml->add_value('value', $si_name, $translate, $trl_args);
        if ($url !== null)
            $this->xml->add_value('url', $url);
        $this->xml->parse_node();
        $this->xml->restore();
    }

	public function clear_location()
	{
		$this->xml->goto('root');
		$this->xml->remove('location');
		$this->xml->add_node('location');
		$this->xml->set_marker('location');
		$this->xml->parse_node();
		$this->xml->restore();
	}

    public function module_available($module_ident)
    {
        if (!isset($this->db))
            return false;

        $sql_string =  'SELECT
                          module_ident
                        FROM 
                          sys_modules
                        WHERE
                          module_ident = \''.$module_ident.'\'
                          AND
                          module_active = 1';
        $result = $this->db->query($sql_string, 'failed to check module availability');
        $num_rows = $result->num_rows();
        $result->free();
        return ($num_rows > 0);
    }
    
    public function resort_column($sql_string, $sort_offset = 1)
    {
        // extract table name from sql string
        $matches = array();
        if (!preg_match('/FROM\s+([a-z_]+)/', $sql_string, $matches))
            return false;
        $table = $matches[1];

        // get primary key
        if (!$primary_key = $this->db->tools->get_primary_key($table))
            return false;

        // modifiy the query to include the primary key
        $sql_string = preg_replace('/\s+FROM/mis', ', '.implode(', ', $primary_key).' FROM', $sql_string);

        // execute given sql query
        $result = $this->db->query($sql_string, 'invalid sql string for resort_column()');

        // if there is nothing to do, return true
        if ($result->num_rows() < 1)
            return true;

        // save column order without the primary key
        $columns = array_slice(array_keys($result->fetch_row(MYSQL_ASSOC)), 0, -count($primary_key));
        $key_index = count($columns);

        // rewind result set
        $result->goto(0);

        // loop through result set
        $keys = array();
        while ($row = $result->fetch_row(MYSQL_ASSOC))
        {
            // save primary key
            foreach ($primary_key as $column)
                $key[$column] = $row[$column];

            // build arguments array for array_multisort
            foreach ($columns as $index => $column)
                $args[$index][] = $row[$column];

            $keys[] = $key;
        }
        $result->free();

        // resort array
        $keys_sort = array_keys($keys);
        $args[] = &$keys_sort;
        call_user_func_array('array_multisort', $args);
        
        // loop through sorted array and update each row
        foreach ($args[$key_index] as $sort_value => $key)
        {
        	$key = $keys[$key];
            // shift sort by sort offset
            $sort_value += $sort_offset;
            
            // build where string from key array
            $where_str = '1';
            foreach ($key as $column => $value)
                $where_str .= ' AND '.$column.' = \''.$value.'\'';

            // update row
            $sql_string =  'UPDATE
                              '.$table.'
                            SET
                              '.$columns[0].' = \''.$sort_value.'\'
                            WHERE
                              '.$where_str;
            if (!$this->db->query($sql_string, 'failed to update row with new sort value'))
                return false;
        }
        
        // everything seems ok
        return true;
    }

} // end class ub_unibox

?>