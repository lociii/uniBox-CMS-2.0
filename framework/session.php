<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_session
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * unibox framework object
    * 
    */
    protected $unibox;

    /**
    * singleton class instance
    * 
    */
    protected static $instance = null;

    /**
    * fsm environment object
    * 
    */
    public $env;

    /**
    * variable handler object
    * 
    */
    public $var;

    /**
    * session id
    * 
    */
    public $session_id;

    /**
    * indicates if the session is using cookies
    * 
    */
    public $uses_cookies = false;

    /**
    * user id saved in session\n
    * set to -1 (anonymous) if not logged in
    * 
    */
    public $user_id = 1;

    /**
    * user id saved in session\n
    * set to 'Anonymous' by default if not logged in
    * 
    */
    public $user_name = 'Anonymous';

    /**
    * the users groups ids saved in session\n
    * set to empty array if not logged in
    * 
    */
    public $group_ids = array();

    /**
    * the users language ident
    * 
    */
    public $lang_ident;

    /**
    * the users country ident
    * 
    */
    public $country_ident;

    /**
    * the selected output format
    * 
    */
    public $output_format_ident;

    /**
    * the user-selected font-size
    * 
    */
    public $font_size;

    /**
    * getvars to be saved in session
    * 
    */
    public $getvars = array();

    /**
    * the last alias processed for the current user
    * 
    */
    public $last_alias;

    /**
    * indicates if the session was created this time
    * 
    */
    public $session_new = false;

    /**
    * array of media id's allowed to retrieve
    * 
    */
    public $access = null;

    /**
    * the user's rights
    * 
    */
    protected $rights = array();

    /**
    * allowed categories for actions
    * 
    */
    protected $rights_categories = array();

    /**
    * allowed actions for categories
    * 
    */
    protected $rights_for_category = array();

    /**
    * indicate if output format change is only valid for current call
    * 
    */
    protected $output_format_change_temporary = false;

    /**
    * indicate if lang ident change is only valid for current call
    * 
    */
    protected $lang_ident_change_temporary = false;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_session::version;
    }

    /**
    * load session - initalizes the saved session or calls the session creator
    *
    */
    public function load_session()
    {
        // load framwork
        $this->unibox = ub_unibox::get_instance();
        
        // get storing engine
        $this->store = ub_session_store::get_instance();

        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
        $time->now();
        $time->substract($this->unibox->config->system->session_timeout);
		$time = $time->get_datetime();

		// lottery for session cleanup
		if ($this->unibox->config->system->session_lottery == 1 || ($this->unibox->config->system->session_lottery > 0 && mt_rand(1, $this->unibox->config->system->session_lottery) == $this->unibox->config->system->session_lottery))
        	$this->store->destroy_expired_sessions($time);

        // check if session-id has been passed
        // decide what way it has been passed - set cookie-flag if passed by cookie
        if (isset($_COOKIE[SESSION_NAME.'_id']))
        {
            $temp_session_id = $_COOKIE[SESSION_NAME.'_id'];
            $this->uses_cookies = true;
        }
        elseif (isset($_POST[SESSION_NAME.'_id']))
            $temp_session_id = $_POST[SESSION_NAME.'_id'];
        elseif (isset($_GET[SESSION_NAME.'_id']))
            $temp_session_id = $_GET[SESSION_NAME.'_id'];

        // check if a session-id was found
        if (isset($temp_session_id) && strlen($temp_session_id) == 32)
        {
            $temp_session_id = $this->unibox->db->cleanup($temp_session_id);

            if ($data = $this->store->get_session($temp_session_id, $time))
            {
                list($this->session_id, $this->user_id, $this->user_name, $this->group_ids, $this->lang_ident, $this->locale->ident, $this->output_format_ident, $this->font_size, $this->timezone, $this->getvars, $this->access, $this->rights, $this->rights_categories, $this->rights_for_category, $this->var, $environment_system, $this->locale->windows_ident, $this->locale->date_format, $this->locale->time_format, $this->locale->datetime_format) = $data;

                // stop if the user is logged in without cookies
                if (!$this->uses_cookies && $this->user_id != 1)
                {
                    $this->logout();
                    throw new ub_exception_security('user was logged in without cookies');
                    return;
                }

                // session is valid - update timestamps
                $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
                $time->now();
                $this->session_time = $time->get_datetime();

                // unserialize rights, content rights and groups
                if (
                        (($this->rights === null) || !is_array($this->rights = unserialize($this->rights)))
                        ||
                        (($this->rights_categories === null) || !is_array($this->rights_categories = unserialize($this->rights_categories)))
                        ||
                        (($this->rights_for_category === null) || !is_array($this->rights_for_category = unserialize($this->rights_for_category)))
                        ||
                        (($this->group_ids === null) || !is_array($this->group_ids = unserialize($this->group_ids)))
                    )
                    $this->set_rights();

                // restore session variable handler if it's an instance of ub_variable_handler
                // else create a new one
                if (is_null($this->var) || !(($this->var = unserialize($this->var)) instanceof ub_variable_handler))
                    $this->var = new ub_variable_handler();

                // create new environment
                $this->env = new ub_environment();
                // restore system part of environment
                if (!is_null($environment_system) && ($environment_system = unserialize($environment_system)) && is_array($environment_system))
                    foreach ($environment_system as $key => $value)
                    	$this->env->$key = $value;

                // restore get-vars if they have been saved
                if (is_null($this->getvars) || !($this->getvars = unserialize($this->getvars)) || !is_array($this->getvars) || count($this->getvars) == 0)
                    unset($this->getvars);

                // restore media access array
                if (is_null($this->access) || !(($this->access = unserialize($this->access)) instanceof StdClass))
                    $this->access = new StdClass(); 

                $this->process_display_type();
                return;
            }
        }
       $this->begin_session();
    }

    /**
    * returns class instance
    * 
    * @return       ub_session (object)
    */
    public static function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_session;
        return self::$instance;
    }

    /**
    * returns action_idents for given category or all allowed action_idents
    * 
    * @param        $category_id            category id (integer)
    * @return       result (array/bool)
    */
    public function get_rights($category_id = null)
    {
        if ($category_id != null)
            if (isset($this->rights_for_category[$category_id]))
                return $this->rights_for_category[$category_id];
            else
                return false;
        else
            return $this->rights;
    }

    /**
    * returns general right indicator if category has not passed\n
    * returns right indicator for given category if passed
    * 
    * @param        $action_ident           action ident (string)
    * @param        $category_id            category id (integer, optional)
    * @return       result (array/bool)
    */
    public function has_right($action_ident = '', $category_id = null)
    {
        if ($category_id == null)
            return in_array($action_ident, $this->rights);
        elseif (isset($this->rights_for_category[$category_id]))
            return in_array($action_ident, $this->rights_for_category[$category_id]);
        else
            return false;
    }

    /**
    * returns all categories where the given actions are allowed
    * 
    * @param        func_get_args()             action idents (multiple of string)
    * @return       result (array/bool)
    */
    public function get_allowed_categories()
    {
        $action_idents = array_unique(func_get_args());
        $allowed_categories = array(0);

        foreach ($this->rights_for_category as $category_id => $category_rights)
        	foreach ($action_idents as $action_ident)
			    if (in_array($action_ident, $category_rights))
			        $allowed_categories[] = $category_id;

        return array_unique($allowed_categories);
    }

    /**
    * logout current user and destroy data-cookie
    *
    */
    function logout()
    {
        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
        $time->now();
		$time = $time->get_datetime();

		$this->user_name = $this->store->logout($time, $this->user_id);

	    $this->user_id = 1;
        $this->rights = $this->rights_categories = $this->rights_for_category = array();
	    $this->set_rights();

	    // delete datacookie
	    if (isset($_COOKIE[SESSION_NAME.'_data']))
        {
	        $cookiedata = $_COOKIE[SESSION_NAME.'_data'];
	        $this->set_cookie(SESSION_NAME.'_data', '', time() - 3600, $this->unibox->config->system->path, '.'.$this->unibox->config->system->host);
        }
    } // end logout

    /**
    * output format change handler
    * 
    */
    protected function process_display_type()
    {
        // initialize variables
        $validator = ub_validator::get_instance();

		// get data
		$subdomains = substr($_SERVER['HTTP_HOST'], 0, strpos($_SERVER['HTTP_HOST'], $this->unibox->config->system->host));
		if (!empty($subdomains))
		{
			$subdomains = substr($subdomains, 0, strlen($subdomains) - 1);
			$subdomains = $this->unibox->db->cleanup(explode('.', $subdomains));
		}
		else
			$subdomains = array();

		// TODO: match protocol in another way
		if (isset($_SERVER['HTTPS']))
			$protocol = 'https';
		else
			$protocol = 'http';

        // check for output format switch via url
		if ($validator->validate('GET', 'set_output_format_ident', TYPE_STRING, CHECK_ISSET))
		{
	        $sql_string  = 'SELECT
	                          output_format_ident
	                        FROM
	                          sys_output_formats
	                        WHERE
	                          output_format_active = 1';
	        if ($validator->validate('GET', 'set_output_format_ident', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
	        {
	            $this->output_format_ident_last = $this->output_format_ident;
	            $this->output_format_ident = $this->unibox->session->env->input->output_format_ident;
	            $this->output_format_change_temporary = true;
	        }
		}

		if ($validator->validate('GET', 'set_lang_ident', TYPE_STRING, CHECK_ISSET))
		{
	        // check for language switch via url
	        $sql_string  = 'SELECT
	                          lang_ident
	                        FROM
	                          sys_languages
	                        WHERE
	                          lang_active = 1';
	        if ($validator->validate('GET', 'set_lang_ident', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
	        {
	            $this->lang_ident_last = $this->lang_ident;
	            $this->lang_ident = $this->unibox->session->env->input->lang_ident;
	            $this->lang_ident_change_temporary = true;
	        }
		}

		if (!isset($this->env->system->subdomains) || $subdomains != $this->env->system->subdomains)
		{
			// save subdomains
			$this->env->system->subdomains = $subdomains;
	
	        // check for output format change via subdomain
	        if (!$this->output_format_change_temporary && !ub_functions::array_empty($subdomains))
			{
		        $sql_string  = 'SELECT
		                          a.output_format_ident
		                        FROM
		                          sys_subdomains AS a
		                            INNER JOIN sys_output_formats AS b
		                              ON b.output_format_ident = a.output_format_ident
		                        WHERE
		                          a.subdomain IN (\''.implode('\', \'', $subdomains).'\')
		                          AND
		                          a.active = 1
		                          AND
		                          b.output_format_active = 1';
				$this->unibox->db->limit_query(0, 1);
		        $result = $this->unibox->db->query($sql_string, 'failed to select output format by subdomain');
		        if ($result->num_rows() == 1)
		            list($this->output_format_ident) = $result->fetch_row();
				$result->free();
			}

	        // choose language via subdomain
	        if (!$this->lang_ident_change_temporary && $this->unibox->config->system->url_include_language && !ub_functions::array_empty($subdomains))
	        {
		        $sql_string  = 'SELECT
								  lang_ident
								FROM
								  sys_languages
								WHERE
								  lang_ident IN (\''.implode('\', \'', $subdomains).'\')
								  AND
								  lang_active = 1';
				$this->unibox->db->limit_query(0, 1);
		        $result = $this->unibox->db->query($sql_string, 'failed to select language by subdomain');
		        if ($result->num_rows() == 1)
	            {
		            list($this->lang_ident) = $result->fetch_row();
		            $result->free();
	                unset($subdomains[array_search($this->lang_ident, $subdomains)]);
	            }
	        }
		}

        if ($this->lang_ident_change_temporary)
            $lang_ident = $this->lang_ident_last;
        else
            $lang_ident = $this->lang_ident;

        if ($this->unibox->config->system->url_include_language == 1 && !in_array($lang_ident, $subdomains))
        	$subdomains[] = $lang_ident;

		// set html base
		if (!empty($subdomains))
        	$this->html_base = $protocol.'://'.implode('.', $subdomains).'.'.$this->unibox->config->system->host.$this->unibox->config->system->path;
        else
        	$this->html_base = $protocol.'://'.$this->unibox->config->system->host.$this->unibox->config->system->path;
    }

    /**
    * grant an additional right
    * 
    */
    public function add_right($action_ident)
    {
        $sql_string  = 'SELECT
                          COUNT(*)
                        FROM
                          sys_actions
                        WHERE
                          action_ident = \''.$action_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'error while getting the user\'s non-content rights');
        list($count) = $result->fetch_row();
        $result->free();
        if ($count == 1)
        {
        	$this->unibox->session->rights[] = $action_ident;
            return $this->store->add_right($action_ident);
        }
        return false;
    }

	public function set_rights()
	{
        // set all containers to empty arrays and reset rights
        $category_allow_action_idents = $category_deny_action_idents = $action_ident_allow_categories = $action_ident_deny_categories = $action_idents_allow = $action_idents_deny = $this->rights = $this->rights_for_category = $this->rights_categories = array();

        // add anonymous rights to EVERY session
        $this->group_ids = array($this->unibox->config->system->anonymous_default_group);

        // get all of the users groups (direct and inherited)
        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
        $time->now();
        $sql_string  = 'SELECT DISTINCT
                          group_id
                        FROM
                          sys_user_groups
                        WHERE
                          user_id = '.$this->user_id.'
                          AND
                          (
                          begin IS NULL
                          OR
                          begin <= \''.$time->get_datetime().'\'
                          )
                          AND
                          (
                          end IS NULL
                          OR
                          end >= \''.$time->get_datetime().'\'
                          )';
        $result = $this->unibox->db->query($sql_string, 'error while getting the user\'s groups');
        if ($result->num_rows() > 0)
            while (list($group_id) = $result->fetch_row())
            {
                $this->group_ids[] = (int)$group_id;
                $this->get_inherited_groups($group_id, $this->group_ids);
            }
        $result->free();

        // make array unique
        $this->group_ids = array_unique($this->group_ids);

		// read all rights
		foreach ($this->group_ids as $group_id)
		{
			list($temp_category_allow_action_idents, $temp_category_deny_action_idents, $temp_action_ident_allow_categories, $temp_action_ident_deny_categories, $temp_action_idents_allow, $temp_action_idents_deny) = $this->read_group_rights($group_id);

			// merge category -> actions (GRANTED)
			foreach ($temp_category_allow_action_idents as $category_id => $action_idents)
			{
				if (isset($category_allow_action_idents[$category_id]))
				{
					foreach ($action_idents as $action_ident)
						if (!in_array($action_ident, $category_allow_action_idents[$category_id]))
							$category_allow_action_idents[$category_id][] = $action_ident;
				}
				else
					$category_allow_action_idents[$category_id] = $action_idents;
			}

			// merge category -> actions (DENIED)
			foreach ($temp_category_deny_action_idents as $category_id => $action_idents)
			{
				if (isset($category_deny_action_idents[$category_id]))
				{
					foreach ($action_idents as $action_ident)
						if (!in_array($action_ident, $category_deny_action_idents[$category_id]))
							$category_deny_action_idents[$category_id][] = $action_ident;
				}
				else
					$category_deny_action_idents[$category_id] = $action_idents;
			}

			// merge action -> categories (GRANTED)
			foreach ($temp_action_ident_allow_categories as $action_ident => $categories)
			{
				if (isset($action_ident_allow_categories[$action_ident]))
				{
					foreach ($categories as $category_id)
						if (!in_array($category_id, $action_ident_allow_categories[$action_ident]))
							$action_ident_allow_categories[$action_ident][] = $category_id;
				}
				else
					$action_ident_allow_categories[$action_ident] = $categories;
			}

			// merge action -> categories (DENIED)
			foreach ($temp_action_ident_deny_categories as $action_ident => $categories)
			{
				if (isset($action_ident_deny_categories[$action_ident]))
				{
					foreach ($categories as $category_id)
						if (!in_array($category_id, $action_ident_deny_categories[$action_ident]))
							$action_ident_deny_categories[$action_ident][] = $category_id;
				}
				else
					$action_ident_deny_categories[$action_ident] = $categories;
			}

			// merge actions (GRANTED)
			foreach ($temp_action_idents_allow as $action_ident)
				if (!in_array($action_ident, $action_idents_allow))
					$action_idents_allow[] = $action_ident;

			// merge actions (DENIED)
			foreach ($temp_action_idents_deny as $action_ident)
				if (!in_array($action_ident, $action_idents_deny))
					$action_idents_deny[] = $action_ident;
		}

        // remove denied category -> actions
        foreach ($category_deny_action_idents as $category_id => $action_idents)
            foreach ($action_idents as $action_ident)
                unset($category_allow_action_idents[$category_id][array_search($category_allow_action_idents[$category_id], $action_ident)]);
		$this->rights_for_category = $category_allow_action_idents;

        // remove denied action -> categories
        foreach ($action_ident_deny_categories as $action_ident => $categories)
            foreach ($categories as $category_id)
                unset($action_ident_allow_categories[$action_ident][array_search($action_ident_allow_categories[$action_ident], $category_id)]);
		$this->rights_categories = $action_ident_allow_categories;

		// add all action idents allowed via categories to generally allowed actions
		foreach ($action_ident_allow_categories as $action_ident => $categories)
			$this->rights[] = $action_ident;

		// remove forbidden non-category-rights
		$action_idents_allow = array_diff($action_idents_allow, $action_idents_deny);

        // merge category- and non-category-rights
        $this->rights = array_unique(array_merge($this->rights, $action_idents_allow));

		// get inherited rights
		$this->add_inherited_actions();

		return $this->store->set_rights($this->session_id, $this->rights, $this->rights_for_category, $this->rights_categories, $this->group_ids);
	}

	public function read_group_rights($group_id)
	{
		$category_allow_bitmasks = array();
		$category_deny_bitmasks = array();
		$category_allow_action_idents = array();
		$category_deny_action_idents = array();
		$action_ident_allow_categories = array();
		$action_ident_deny_categories = array();

		$action_idents_allow = array();
		$action_idents_deny = array();

		// select category rights
        $sql_string  = 'SELECT
                          a.category_id,
                          a.bit_allow,
                          a.bit_deny
                        FROM
                          sys_group_rights_content AS a
                            INNER JOIN sys_categories AS b
                              ON b.category_id = a.category_id
                            INNER JOIN sys_modules AS c
                              ON c.module_ident = b.module_ident
                        WHERE
                          c.module_active = 1
                          AND
                          group_id = '.$group_id;
        $result = $this->unibox->db->query($sql_string, 'error while getting group rights');
        if ($result->num_rows() > 0)
            while (list($category_id, $bit_allow, $bit_deny) = $result->fetch_row())
            {
                // fill allow
                if (!isset($category_allow_bitmasks[$category_id]))
                    $category_allow_bitmasks[$category_id] = ub_functions::get_bit_components($bit_allow);
                else
                    $category_allow_bitmasks[$category_id] = array_merge($category_allow_bitmasks[$category_id], ub_functions::get_bit_components($bit_allow));

                // fill deny
                if (!isset($category_deny_bitmasks[$category_id]))
                    $category_deny_bitmasks[$category_id] = ub_functions::get_bit_components($bit_deny);
                else
                    $category_deny_bitmasks[$category_id] = array_merge($category_deny_bitmasks[$category_id], ub_functions::get_bit_components($bit_deny));
            }
        $result->free();

		// build condition to get bit-assigned action idents (GRANTED)
		$where = array();
		foreach ($category_allow_bitmasks as $category_id => $bit_array)
			if (!empty($bit_array))
				$where[] = 'b.category_id = \''.$category_id.'\' AND a.bitmask IN ('.implode(', ', $bit_array).')';

		// get bit-assigned action idents (GRANTED)
		if (!empty($where))
		{
	        $sql_string  = 'SELECT DISTINCT
	                          a.action_ident,
							  b.category_id
	                        FROM
	                          sys_actions AS a
	                          INNER JOIN sys_categories AS b
	                            ON b.module_ident = a.module_ident
	                        WHERE
	                          ('.implode(') OR (', $where).')
	                        ORDER BY
	                          a.action_ident';
	        $result = $this->unibox->db->query($sql_string, '');
	        while (list($action_ident, $category_id) = $result->fetch_row())
	        {
	        	$category_allow_action_idents[$category_id][] = $action_ident;
                if (!isset($action_ident_allow_categories[$action_ident]))
                    $action_ident_allow_categories[$action_ident] = array();
                if (!in_array($category_id, $action_ident_allow_categories[$action_ident]))
                    $action_ident_allow_categories[$action_ident][] = $category_id;
                $action_idents_allow[] = $action_ident;
	        }
	        $result->free();
		}

		// build condition to get bit-assigned action idents (DENIED)
		$where = array();
		foreach ($category_deny_bitmasks as $category_id => $bit_array)
			if (!empty($bit_array))
				$where[] = 'b.category_id = \''.$category_id.'\' AND a.bitmask IN ('.implode(', ', $bit_array).')';

		// get bit-assigned action idents (DENIED)
		if (!empty($where))
		{
	        $sql_string  = 'SELECT DISTINCT
	                          a.action_ident,
							  b.category_id
	                        FROM
	                          sys_actions AS a
	                          INNER JOIN sys_categories AS b
	                            ON b.module_ident = a.module_ident
	                        WHERE
	                          ('.implode(') OR (', $where).')
	                        ORDER BY
	                          a.action_ident';
	        $result = $this->unibox->db->query($sql_string, '');
	        while (list($action_ident, $category_id) = $result->fetch_row())
	        {
	        	$category_deny_action_idents[$category_id][] = $action_ident;
                if (!isset($action_ident_deny_categories[$action_ident]))
                    $action_ident_deny_categories[$action_ident] = array();
                if (!in_array($category_id, $action_ident_deny_categories[$action_ident]))
                    $action_ident_deny_categories[$action_ident][] = $category_id;
	        }
	        $result->free();
		}

		// get non-category rights
        $sql_string  = 'SELECT
                          a.action_ident,
                          a.flag
                        FROM sys_group_rights AS a
                          INNER JOIN sys_actions AS b
                            ON b.action_ident = a.action_ident
                          INNER JOIN sys_modules AS c
                            ON c.module_ident = b.module_ident
                        WHERE
                          a.group_id = '.$group_id.'
                          AND
                          c.module_active = 1';
        $result = $this->unibox->db->query($sql_string, 'error while getting the group\'s non-content rights');
        while (list($action_ident, $flag) = $result->fetch_row())
            if ($flag == 1)
                $action_idents_allow[] = $action_ident;
            else
                $action_idents_deny[] = $action_ident;
        $result->free();

		// make allowed actions unqiue
		$action_idents_allow = array_unique($action_idents_allow);

		return array($category_allow_action_idents, $category_deny_action_idents, $action_ident_allow_categories, $action_ident_deny_categories, $action_idents_allow, $action_idents_deny);
	}

    /**
    * adds all inheritated actions to the rights arrays
    * 
    */
    protected function add_inherited_actions()
    {
    	$this->action_tree = array();
    	
        $sql_string  = 'SELECT
						  inherits_from_action_ident,
                          action_ident
                        FROM
                          sys_action_inheritance';
        $result = $this->unibox->db->query($sql_string, 'error while getting inherited actions');
        while (list($action_ident_from, $action_ident_to) = $result->fetch_row())
        	$this->action_tree[$action_ident_from][] = $action_ident_to;
        $result->free();

		foreach ($this->rights as $action_ident)
			$this->add_inherited_actions_traverse($action_ident);
    }

    /**
    * adds all inheritated actions to the rights arrays
    * 
    * @param        $action_ident           action ident (string)
    */
	protected function add_inherited_actions_traverse($action_ident)
	{
		if (isset($this->action_tree[$action_ident]))
		{
			foreach ($this->action_tree[$action_ident] as $right)
				if (!in_array($right, $this->rights))
				{
					$this->rights[] = $right;
					$this->add_inherited_actions_traverse($right);
				}
		}
	}

    /**
    * set locale
    * 
    */
	public function set_locale($locale_ident)
	{
        // choose locale
        $sql_string  = 'SELECT
                          locale_windows,
                          date_format,
                          time_format,
                          datetime_format
                        FROM
                          sys_locales
                        WHERE
                          locale = \''.$this->locale->ident.'\'';
        $this->unibox->db->limit_query(0, 1);
        $result = $this->unibox->db->query($sql_string, 'failed to select locale data');
        if ($result->num_rows() == 1)
        {
            list($this->locale->windows_ident, $this->locale->date_format, $this->locale->time_format, $this->locale->datetime_format) = $result->fetch_row();
        	$result->free();
        	return true;
        }
        return false;
	}

    /**
    * adds all inheriting groups to the array
    * 
    * @param        $base_group				group to be inherited from (int)
    * @param        $groups                 list of groups - passed by reference (array)
    */
    public function get_inheriting_groups($base_group, &$groups)
    {
        $sql_string  = 'SELECT 
                          group_id
                        FROM
                          sys_group_inheritance
                        WHERE
                          inherits_from_group_id = '.$base_group;
        $result = $this->unibox->db->query($sql_string, 'error while getting inheriting groups of \''.$base_group.'\'');
        while (list($inheriting_group_id) = $result->fetch_row())
        {
            $groups[] = $inheriting_group_id;
            $this->get_inheriting_groups($inheriting_group_id, $groups);
        }
    }

    /**
    * adds all inheritated actions to the rights arrays
    * 
    */
    public function get_inherited_groups($group_id, &$groups)
    {
    	$this->action_tree = array();
    	
        $sql_string  = 'SELECT 
                          inherits_from_group_id,
						  group_id
                        FROM
                          sys_group_inheritance';
        $result = $this->unibox->db->query($sql_string, 'error while getting inherited groups');
        while (list($group_id_from, $group_id_to) = $result->fetch_row())
        	$group_tree[$group_id_to] = $group_id_from;
        $result->free();

		$this->get_inherited_groups_traverse($group_id, $groups, $group_tree);
    }

    /**
    * adds all inheritated actions to the rights arrays
    * 
    * @param        $action_ident           action ident (string)
    */
	protected function get_inherited_groups_traverse($group_id, &$groups, &$group_tree)
	{
		if (isset($group_tree[$group_id]))
		{
			if (!in_array($group_tree[$group_id], $groups))
				$groups[] = (int)$group_tree[$group_id];
			$this->get_inherited_groups_traverse($group_tree[$group_id], $groups, $group_tree);
		}
	}

    /**
    * start a new session
    * 
    */
    protected function begin_session()
    {
        // generate new session-id
        $this->session_id = $this->store->get_session_id();

        // fill object-variables with standard values for anonymous users
        $this->access = new StdClass();
        $this->session_new = true;
        $this->var = new ub_variable_handler();
        $this->env = new ub_environment();

        // get default user's values
        $sql_string  = 'SELECT
                          user_id,
                          user_name,
                          user_locale,
                          user_lang_ident,
                          user_output_format_ident,
                          user_font_size,
                          user_timezone
                        FROM
                          sys_users
                        WHERE
                          user_id = 1';
        $result = $this->unibox->db->query($sql_string, 'failed to select default user\'s values');
        if ($result->num_rows() == 1)
        {
            list($this->user_id, $this->user_name, $this->locale->ident, $this->lang_ident, $this->output_format_ident, $this->font_size, $this->timezone) = $result->fetch_row();
            $result->free();
			$this->set_locale($this->locale->ident);
        }
        else
            throw new ub_exception_runtime('failed to select default user\'s values');

        // time
        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
        $time->now();
        $this->session_time = $time->get_datetime();

        // add new session to database
        $this->store->begin_session();
        $this->set_cookie(SESSION_NAME.'_id', $this->session_id, 0, $this->unibox->config->system->path, '.'.$this->unibox->config->system->host);

        // check for datacookie
        if (isset($_COOKIE[SESSION_NAME.'_data']))
        {
            $cookiedata = unserialize(stripslashes($_COOKIE[SESSION_NAME.'_data']));
            if (isset($cookiedata['autologinid']) && strlen($cookiedata['autologinid']) == 40)
            {
                $sql_string  = 'SELECT
                                  user_email
                                FROM
                                  sys_users
                                WHERE
                                  user_id = \''.$this->unibox->db->cleanup($cookiedata['userid']).'\'';
                $result = $this->unibox->db->query($sql_string);
                if ($result->num_rows() == 1)
                {
                    list($user_email) = $result->fetch_row();
                    $result->free();
                    $this->auth($user_email, $cookiedata['autologinid'], true, true);
                }
            }
        }

        // process display type and set user rights
        $this->process_display_type();
        $this->set_rights();
    }

    /**
    * authenticate user
    * 
    * @param        $user_email             email (string)
    * @param        $user_password          password (string)
    * @param        $autologin              autologin (bool)
    * @param        $autocall               autocall (bool)
    * @return       result (bool)
    */
    public function auth($user_email, $user_password, $autologin = false, $autocall = false)
    {
        if ($this->uses_cookies || $autocall)
        {
            $sql_string  = 'SELECT
                              user_id,
                              user_name,
                              user_locked,
                              user_active,
                              user_password,
                              user_lang_ident,
                              user_locale,
                              user_output_format_ident,
                              user_font_size,
                              user_timezone,
                              user_failed_logins,
                              variables,
                              environment_administration,
                              environment_dialog,
                              environment_form,
                              environment_pagebrowser,
                              environment_preselect,
                              environment_replace,
                              environment_stack
                            FROM
                              sys_users
                            WHERE
                              user_email = \''.$this->unibox->db->cleanup($user_email).'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select user data for session auth');
            if ($result->num_rows() == 1)
            {
                list($user_id, $user_name, $user_locked, $user_active, $user_password_db, $user_lang_ident, $user_locale, $user_output_format_ident, $user_font_size, $user_timezone, $user_failed_logins, $variables, $environment['administration'], $environment['dialog'], $environment['form'], $environment['pagebrowser'], $environment['preselect'], $environment['replace'], $environment['stack']) = $result->fetch_row();
                $result->free();

                if (!$user_active)
                    return LOGIN_DISABLED;

                if ($user_locked)
                    return LOGIN_LOCKED;

                // INFO: added compatibility to older unibox user tables
                // check for encryption of db password (md5 => 32 chars, sha1 => 40 chars)
                if (strlen($user_password_db) == 32)
                    $crypt = 'md5';
                else
                    $crypt = 'sha1';
                // determine login type
                $user_password_encrypted = ($autocall) ? $user_password : $crypt($user_password);

                if ($user_password_db === $user_password_encrypted)
                {
                    // INFO: added compatibility to older unibox user tables
                    // update old md5 password to sha1
                    if (!$autocall && $crypt == 'md5')
                    {
                        $user_password_db = sha1($user_password);
                        $sql_string  = 'UPDATE
                                          sys_users
                                        SET
                                          user_password = \''.$user_password_db.'\'
                                        WHERE
                                          user_id = '.$user_id;
                        $this->unibox->db->query($sql_string, 'failed to update encrypted password to sha1');
                    }
                    unset($user_password);

                    $cookiedata['userid'] = $user_id;
                    $cookiedata['autologinid'] = ($autologin) ? $user_password_db : '';
                    $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
                    $time->now();
                    $this->session_time = $time->get_datetime();
                    $this->set_cookie(SESSION_NAME.'_data', serialize($cookiedata), time() + $this->unibox->config->system->session_datacookie_ttl * 86400, $this->unibox->config->system->path, '.'.$this->unibox->config->system->host);

                    // log the user in
                    $this->user_id = $user_id;
                    $this->user_name = $user_name;

                    if (!empty($user_lang_ident))
                        $this->lang_ident = $user_lang_ident;
                    if (!empty($user_locale))
                    {
                        $this->locale->ident = $user_locale;
                        $this->set_locale($this->locale->ident);
                    }
                    if (!empty($user_timezone))
                        $this->timezone = $user_timezone;
                    if (!empty($user_output_format_ident))
                        $this->output_format_ident = $user_output_format_ident;
                    if ($user_font_size != 0)
                        $this->font_size = $user_font_size;

                    if ($autocall)
                    {
                        if (!empty($variables))
                        {
                            $variables = unserialize($variables);
                            if ($variables instanceof ub_variable_handler)
                                $this->var = $variables;
                        }

						$this->store->save_environment($environment);

                        $sql_string  = 'UPDATE
                                          sys_users
                                        SET
                                          variables = NULL,
                                          environment_administration = NULL,
                                          environment_dialog = NULL,
                                          environment_form = NULL,
                                          environment_pagebrowser = NULL,
                                          environment_preselect = NULL,
                                          environment_replace = NULL,
                                          environment_stack = NULL,
                                          user_failed_logins = 0
                                        WHERE
                                          user_id = \''.$user_id.'\'';
                        $this->unibox->db->query($sql_string, 'failed to reset user\'s stored variables and environment');
                    }
                    elseif ($user_failed_logins > 0)
                    {
                        $sql_string  = 'UPDATE
                                          sys_users
                                        SET
                                          user_failed_logins = 0
                                        WHERE
                                          user_id = \''.$user_id.'\'';
                        $this->unibox->db->query($sql_string, 'failed to reset login error count');
                    }
                    return LOGIN_SUCCESSFUL;
                }
                // update failed logins
                else
                {
                    if ($user_failed_logins == $this->unibox->config->system->max_failed_logins - 1)
                    {
                        // generate activation key
                        $activation_key = ub_functions::get_random_string(32);
                
                        $email = new ub_email();
                        $email->set_rcpt($user_email, $user_name);
                        $email->load_template('login_disabled', $user_lang_ident);
                        $email->set_replacement('user_name', $user_name);
                        $email->set_replacement('user_email', $user_email);
                        $email->set_replacement('page_url', $this->unibox->config->system->page_url);
                        $email->set_replacement('max_failed_logins', $this->unibox->config->system->max_failed_logins);
                        $email->set_replacement('activation_key', $activation_key);
                        $email->set_replacement('activation_link', $this->unibox->create_link('user_activate', true, array('email' => $user_email, 'key' => $activation_key)));
                        
                        // send email
                        if (!$email->send())
                            return LOGIN_FAILED;

                        $sql_string  = 'UPDATE
                                          sys_users
                                        SET
                                          user_failed_logins = 0,
                                          user_active = 0,
                                          user_key = \''.$activation_key.'\'
                                        WHERE
                                          user_email = \''.$this->unibox->db->cleanup($user_email).'\'';
                        $this->unibox->db->query($sql_string, 'failed to deactivate user');
                        return LOGIN_JUST_DISABLED;
                    }
                    else
                    {
                        $sql_string  = 'UPDATE
                                          sys_users
                                        SET
                                          user_failed_logins = user_failed_logins + 1
                                        WHERE
                                          user_email = \''.$this->unibox->db->cleanup($user_email).'\'';
                        $this->unibox->db->query($sql_string, 'failed to update count of failed logins');
                    }
                }
            }
    
            // delete erroneous datacookie
            if ($autocall)
            {
                $cookiedata = $_COOKIE[SESSION_NAME.'_data'];
                $this->set_cookie(SESSION_NAME.'_data', $cookiedata, time() - 3600, $this->unibox->config->system->path, '.'.$this->unibox->config->system->host);
            }
        }
        elseif (!$this->uses_cookies)
            return LOGIN_FAILED_NO_COOKIES;
        return LOGIN_FAILED;
    }

    /**
    * sets a output format specific cookie
    * 
    * @param        $name                   cookie name (string)
    * @param        $value                  cookie value (string)
    * @param        $maxage                 maximum cookie lifetime (bool)
    * @param        $path                   path within the given domain (bool)
    * @param        $domain                 domain to set cookie for (string)
    * @param        $secure                 set secure cookie? (string)
    */
    protected function set_cookie($name, $value, $maxage, $path = '', $domain = '', $secure = '')
    {
        setcookie($name, $value, $maxage, $path, $domain, $secure);
    }

    /**
    * session destructor - saves session data and unlocks session
    * 
    */
	public function __destruct()
    {
        // delete temp vars
        if (isset($this->var->delete))
            foreach ($this->var->delete AS $key)
                $this->var->unregister($key);

        $this->var->delete = array();

        $getvars = (isset($this->getvars) && count($this->getvars) > 0) ? $this->unibox->db->cleanup(serialize($this->getvars)) : '';

        /* DEBUG ENVIRONMENT IN FILE
		$file = fopen('d:\debug.html', 'a+');
		ob_start();
		echo '<strong>'.$_SERVER['REQUEST_URI'].'</strong><br/>';
		list($usec, $sec) = explode(" ", microtime());
		echo '<strong>'.date('d.m.Y - H:i:s', $sec).' - '.$usec.'</strong><br/>';
		var_dump($this->env);
		echo '<br/><hr /><br/>';
		$debug = ob_get_contents();
		ob_end_clean();
		fwrite($file, $debug);
		fclose($file);
        */

		// extract all relevant objects from environment
		$environment = $system = array();
		$special_objects = $this->env->get_special_objects();
		$system_objects = $this->env->get_system_objects();

		// dissect environment
		foreach ($this->env as $key => $value)
		{
			if (in_array($key, $special_objects))
				$environment[$key] = serialize($value);
			elseif (in_array($key, $system_objects))
				$system[$key] = $value;
		}

		// save environment
		$this->store->save_environment($environment, true);

		// save session
		$this->store->save_session($getvars, ($this->lang_ident_change_temporary ? $this->lang_ident_last : $this->lang_ident), ($this->output_format_change_temporary ? $this->output_format_ident_last : $this->output_format_ident), $system);
    }
}

class ub_variable_handler
{
    /**
    * variables that shouldn't be taken to the next pagecall
    * 
    */
    public $delete;

    /**
    * stores a value into the session variable handler
    * 
    * @param        $key                    variable name (string)
    * @param        $value                  variable value (mixed)
    * @param        $delete_on_destruct     keep variable only for current page-call? (bool)
    */
    public function register($key, $value, $delete_on_destruct = false)
    {
        $this->$key = $value;
        if ($delete_on_destruct)
            $this->delete[] = $key;
    }

    /**
    * deletes a value from the session variable handler
    * 
    * @param        $key                    variable name (string)
    */
    public function unregister($key)
    {
        unset($this->$key);
    }
}

class ub_environment
{
	protected $special_objects = array('administration', 'dialog', 'form', 'pagebrowser', 'preselect', 'replace', 'stack');
	protected $system_objects = array('alias', 'error', 'input', 'system', 'themes');

	protected $manipulated_special_objects = array();

    /**
    * create new environment with empty values
    * 
    */
	public function __construct()
	{
		$this->unibox = ub_unibox::get_instance();
		$this->reset();
	}

    /**
    * getter method for protected special_objects property
    * 
    */
	public function get_special_objects()
	{
		return $this->special_objects;
	}

    /**
    * getter method for protected system_objects property
    * 
    */
	public function get_system_objects()
	{
		return $this->system_objects;
	}

    /**
    * overload get for by-request loading of environment parts
    * 
    */
	public function __get($name)
	{
		if (in_array($name, $this->special_objects) && !$this->reload($name))
            throw new ub_exception_runtime('invalid environment access: '.$name);
		return $this->$name;
	}

    /**
    * overload isset for by-request loading of environment parts
    * 
    */
	public function __isset($name)
	{
		if (in_array($name, $this->special_objects))
			$this->reload($name);
		return isset($this->$name);
	}

    /**
    * by-request loader for environment parts
    * 
    */
	protected function reload($name)
	{
		// check for valid object
		if (in_array($name, $this->special_objects))
		{
			// link preselect to administration
			$sql_string  = 'SELECT
							  environment_'.$name.'
							FROM
							  sys_sessions
							WHERE
							  session_id = \''.$this->unibox->session->session_id.'\'';
	  		$result = $this->unibox->db->query($sql_string, 'failed to reload part of environment: '.$name);
	  		if ($result->num_rows() == 1)
	  		{
	  			list($object) = $result->fetch_row();
	  			$result->free();
	  			if (empty($object))
	  				$this->$name = new StdClass();
	  			elseif ($unserialized_object = @unserialize($object))
	  				$this->$name = $unserialized_object;
	  			else
	  			{
	  				$this->unibox->log(LOG_ERR_GENERAL, array('invalid data in user session', $name));
	  				foreach ($this->special_objects as $object_name)
	  					$this->$object_name = new stdClass();
	  				$this->unibox->error('TRL_ERR_SESSION_INVALID_DATA');
	  				$this->unibox->display_error();
	  			}
  				return true;
	  		}
		}
		return false;
	}

    /**
    * resets the environment
    * 
    */
    public function reset()
    {
    	$this->system = new stdClass();
        $this->system->state = '';
        $this->system->module_ident = '';
        $this->system->action_ident = '';
        $this->system->action_save = 0;
        $this->system->default_alias = $this->unibox->config->system->default_alias;

        $this->input = new stdClass();
        $this->error = array();
        $this->themes = new stdClass();
        $this->themes->current = new stdClass();
        $this->themes->fallback = new stdClass();
        $this->themes->use_fallback = false;
		$this->alias = new stdClass();
    }
}

class ub_session_store
{
	/**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * unibox framework object
    * 
    */
    protected $unibox;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_session_store::version;
    } // end get_version()

    /**
    * returns class instance
    * 
    * @return       object of current class
    */
    public function get_instance()
    {
    	$unibox = ub_unibox::get_instance();

		$class_name = 'ub_session_store_'.$unibox->config->system->session_store_engine;
    	return new $class_name;
    } // end get_instance()

    /**
    * class constructor
    *
    */
    protected function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
    } // end __construct()
}

class ub_session_store_database extends ub_session_store
{
    /**
    * class constructor
    *
    */
    public function __construct()
    {
        parent::__construct();
    } // end __construct()

    public function get_session($session_id, $time)
    {
		// check if the session-id belongs to a stored session and selects the sessions values
        $sql_string  = 'SELECT
						  a.session_id,
                          a.session_user_id,
                          b.user_name,
                          a.session_group_ids,
                          a.session_lang_ident,
                          a.session_locale,
                          a.session_output_format_ident,
                          a.session_font_size,
                          a.session_timezone,
                          a.session_getvars,
                          a.session_access,
                          a.rights,
                          a.rights_categories,
                          a.rights_for_category,
                          a.variables,
                          a.environment_system,
						  c.locale_windows,
						  c.date_format,
						  c.time_format,
						  c.datetime_format
                        FROM
                          sys_sessions AS a
                            INNER JOIN sys_users AS b
                              ON b.user_id = a.session_user_id
							INNER JOIN sys_locales AS c
							  ON c.locale = a.session_locale
                        WHERE
                          a.session_id = \''.$session_id.'\'
						  AND
						  a.session_time > \''.$time.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to get session for session '.$session_id);
        if ($result->num_rows() == 1)
        	$return = $result->fetch_row();
        else
        	$return = false;

    	$result->free();
    	return $return;
    }

	public function logout($session_time, $user_id)
	{
	    $sql_string  = 'UPDATE
	                      sys_sessions
	                    SET
	                      session_user_id = 1,
                          rights = NULL,
                          rights_categories = NULL,
                          rights_for_category = NULL
	                    WHERE
	                      session_id = \''.$this->unibox->session->session_id.'\'';
	    $this->unibox->db->query($sql_string, 'failed to update session on auto-logout for session '.$this->unibox->session->session_id);

        $sql_string  = 'UPDATE
                          sys_users
                        SET
                          user_last_access = \''.$session_time.'\'
                        WHERE
                          user_id = \''.$user_id.'\'';
        $this->unibox->db->query($sql_string, 'failed to update last visit on autologout for user '.$user_id);

		$sql_string =  'SELECT
						  user_name
						FROM
						  sys_users
						WHERE
						  user_id = 1';
		$result = $this->unibox->db->query($sql_string, 'failed to get anonymous user name');
		if ($result->num_rows() == 1)
			list($return) = $result->fetch_row();
		else
			$return = false;

		$result->free();
		return $return;
	}

    /**
    * automatically logs out the user but stores the sessionvariables in the usertable
    *
    */
    function destroy_expired_sessions($time)
    {
        $sql_string  = 'SELECT
                          session_id,
                          session_user_id,
                          session_time,
                          variables,
                          environment_administration,
                          environment_dialog,
                          environment_form,
                          environment_pagebrowser,
                          environment_preselect,
                          environment_replace,
                          environment_stack
                        FROM
                          sys_sessions
                        WHERE
                          session_time < \''.$time.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to get outdated sessions');
        if ($result->num_rows() > 0)
        {
            while (list($session_id, $session_user_id, $session_time, $variables, $environment_administration, $environment_dialog, $environment_form, $environment_pagebrowser, $environment_preselect, $environment_replace, $environment_stack) = $result->fetch_row())
            {
                $sql_string  = 'UPDATE
                                  sys_users
                                SET
                                  variables = \''.$this->unibox->db->cleanup($variables).'\',
                                  environment_administration = \''.$this->unibox->db->cleanup($environment_administration).'\',
                                  environment_dialog = \''.$this->unibox->db->cleanup($environment_dialog).'\',
                                  environment_form = \''.$this->unibox->db->cleanup($environment_form).'\',
                                  environment_pagebrowser = \''.$this->unibox->db->cleanup($environment_pagebrowser).'\',
                                  environment_preselect = \''.$this->unibox->db->cleanup($environment_preselect).'\',
                                  environment_replace = \''.$this->unibox->db->cleanup($environment_replace).'\',
                                  environment_stack = \''.$this->unibox->db->cleanup($environment_stack).'\',
                                  user_last_access = \''.$session_time.'\'
                                WHERE
                                  user_id = \''.$session_user_id.'\'';
                $this->unibox->db->query($sql_string, 'failed to save userdata of outdated session for user '.$session_user_id);

                $sql_string  = 'DELETE FROM
                                  sys_sessions
                                WHERE
                                  session_id = \''.$session_id.'\'';
                $this->unibox->db->query($sql_string, 'failed to delete outdated sessions');
            }
        }
        $result->free();
    }

	public function add_right($action_ident)
	{
		$sql_string  = 'UPDATE
                          sys_sessions
                        SET
                          rights = \''.serialize($this->unibox->session->rights).'\'
                        WHERE
                          session_id = \''.$this->unibox->session->session_id.'\'';
        $this->unibox->db->query($sql_string, 'failed to add right to session '.$this->unibox->session->session_id);
        return (bool)$this->unibox->db->affected_rows();
	}

	public function set_rights($session_id, $rights, $rights_for_category, $rights_categories, $group_ids)
	{
        $sql_string  = 'UPDATE
                          sys_sessions
                        SET
                          rights = \''.serialize($rights).'\',
                          rights_for_category = \''.serialize($rights_for_category).'\',
                          rights_categories = \''.serialize($rights_categories).'\',
                          session_group_ids = \''.serialize($group_ids).'\'
                        WHERE
                          session_id = \''.$session_id.'\'';
        $this->unibox->db->query($sql_string, 'failed to save rights for session '.$session_id);
        return (bool)$this->unibox->db->affected_rows();
	}

	public function get_session_id()
	{
		$session_id = md5(uniqid(mt_rand()));

        // check session-id
        while (true)
        {
            $sql_string  = 'SELECT
                              session_id
                            FROM
                              sys_sessions
                            WHERE
                              session_id = \''.$session_id.'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to generate new session id');
            if ($result->num_rows() > 0)
            {
            	$result->free();
                $session_id = md5(uniqid(mt_rand()));
                continue;
            }
            break;
        }

        return $session_id;
	}

	public function begin_session()
	{
        $sql_string  = 'INSERT INTO
                          sys_sessions
                        SET
                          session_id = \''.$this->unibox->session->session_id.'\',
                          session_ip = \''.ub_functions::encode_ip($_SERVER['REMOTE_ADDR']).'\',
                          session_time = \''.$this->unibox->session->session_time.'\',
                          session_locale = \''.$this->unibox->db->cleanup(serialize($this->unibox->session->locale)).'\',
                          session_lang_ident = \''.$this->unibox->session->lang_ident.'\',
                          session_output_format_ident = \''.$this->unibox->session->output_format_ident.'\',
                          session_font_size = \''.$this->unibox->session->font_size.'\',
                          session_timezone = \''.$this->unibox->session->timezone.'\'';
        $this->unibox->db->query($sql_string, 'failed to insert new session '.$this->unibox->session->session_id);
        return (bool)$this->unibox->db->affected_rows();
	}

	public function save_environment($environment, $quiet = false)
	{
		$fields = array();
		if (is_array($environment))
			foreach ($environment as $ident => $value)
				if (!empty($value))
					$fields[] = 'environment_'.$ident.' = \''.$this->unibox->db->cleanup($value).'\'';

		if (!empty($fields))
		{
	        $sql_string  = 'UPDATE
	                          sys_sessions
	                        SET
							  '.implode(', ', $fields).'
	                        WHERE
	                          session_id = \''.$this->unibox->session->session_id.'\'';
	        $this->unibox->db->query($sql_string, 'failed to restore saved environment for session '.$this->unibox->session->session_id, null, $quiet);
	        return (bool)$this->unibox->db->affected_rows();
		}
		return true;
	}

	public function save_session($getvars, $lang_ident, $output_format_ident, $system)
	{
        $sql_string  = 'UPDATE
						  sys_sessions
						SET
						  session_time = \''.$this->unibox->db->cleanup($this->unibox->session->session_time).'\',
						  session_hit_count = session_hit_count + 1,
						  session_user_id = '.$this->unibox->db->cleanup($this->unibox->session->user_id).',
						  session_getvars = \''.$getvars.'\',
						  variables = \''.$this->unibox->db->cleanup(serialize($this->unibox->session->var)).'\',
						  session_access = \''.$this->unibox->db->cleanup(serialize($this->unibox->session->access)).'\',
						  session_locale = \''.$this->unibox->db->cleanup($this->unibox->session->locale->ident).'\',
						  session_lang_ident = \''.$this->unibox->db->cleanup($lang_ident).'\',
						  session_output_format_ident = \''.$this->unibox->db->cleanup($output_format_ident).'\',
						  session_font_size = \''.$this->unibox->db->cleanup($this->unibox->session->font_size).'\',
						  session_timezone = \''.$this->unibox->db->cleanup($this->unibox->session->timezone).'\',
						  environment_system = \''.$this->unibox->db->cleanup(serialize($system)).'\'
						WHERE
						  session_id = \''.$this->unibox->session->session_id.'\'';
    	$this->unibox->db->query($sql_string, 'updating the session data failed', false, true);
	}
}

?>