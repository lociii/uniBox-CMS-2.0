<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_config
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
	protected static $instance = NULL;

	/**
	* list of already loaded configs
	* 
	*/
	protected $loaded_configs = array();

	/**
	* list of already loaded category configs
	* 
	*/
	protected $loaded_categories = array();

	/**
	* list of already loaded user configs
	* 
	*/
	protected $loaded_users = array();

	/**
	* category's details
	* 
	*/
	public $category;

	/**
	* returns class version
	* 
	* @return       version-number (float)
	*/
	public static function get_version()
	{
		return ub_config::version;
	} // end get_version()

	/**
	* class constructor - loads framework configuration
	*
	*/
	private function __construct()
	{
		$this->unibox = ub_unibox::get_instance();
		$this->load_config();
	} // end __construct()

	/**
	* returns class instance
	* 
	* @return       ub_config (object)
	*/
	public static function get_instance()
	{
		if (self::$instance === null)
			self::$instance = new ub_config;
		return self::$instance;
	} // end get_instance()

	/**
	* loads the configuration of the specified module
	* 
	* @param        $module_ident           what module's config should be loaded (string)
	*/
	public function load_config($module_ident = 'unibox')
	{
		if (!in_array($module_ident, $this->loaded_configs))
		{
			// load config
			$sql_string  = 'SELECT
							  a.config_ident,
							  a.config_value,
							  a.config_type
							FROM sys_config AS a
							  INNER JOIN sys_config_groups AS b
								ON b.config_group_ident = a.config_group_ident
							WHERE
							  b.module_ident = \''.$module_ident.'\'';
			$result = $this->unibox->db->query($sql_string, 'couldn\'t get configuration for module: '.$module_ident);
			if ($result->num_rows() > 0)
				while (list($ident, $value, $type) = $result->fetch_row())
					if ($type == 'meta')
						$this->unibox->meta[$ident] = $value;
					else
						$this->system->$ident = $value;
			$result->free();

			// set base dir
			if ($module_ident == 'unibox' && isset($this->system->path))
				define('DIR_BASE', $_SERVER['DOCUMENT_ROOT'].$this->system->path);

			$this->loaded_configs[] = $module_ident;
		}
	} // end load_config()

	/**
	* loads the configuration of the specified category
	* 
	* @param        $category_id		what category's config should be loaded (string)
	*/
	public function load_category($category_id)
	{
		if (!in_array($category_id, $this->loaded_categories))
		{
			$data = array();

			$sql_string  = 'SELECT
							  detail_ident,
							  detail_value
							FROM
							  sys_category_details
							WHERE
							  category_id = \''.$this->unibox->db->cleanup($category_id).'\'';
			$result = $this->unibox->db->query($sql_string, 'couldn\'t get configuration for category: '.$category_id);
			if ($result->num_rows() > 0)
				while (list($ident, $value) = $result->fetch_row())
					$data[$ident] = $value;
			$result->free();

			foreach ($data as $ident => $value)
				$this->category->$category_id->$ident = $value;
			$this->loaded_categories[] = $category_id;
		}
	}

	/**
	* loads the configuration of the specified user and module
	* 
	* @param        $id                     what category's config should be loaded (string)
	*/
	public function load_user($module_ident, $user_id = null)
	{
		// default to current user
		if ($user_id === null)
    		if ($this->unibox->session->user_id == 1)
    		{
    			if (isset($this->loaded_users[$module_ident]) && in_array(1, $this->loaded_users[$module_ident]))
    				return;

    			$sql_string  = 'SELECT
								  default_value
								FROM
								  sys_user_config_spec
								WHERE
								  module_ident = \''.$this->unibox->db->cleanup($module_ident).'\'';
				$result = $this->unibox->db->query($sql_string, 'couldn\'t get default user config for module: '.$module_ident);
	            if ($result->num_rows() > 0)
	            {
	                while (list($ident, $value) = $result->fetch_row())
	                    $this->user->{'1'}->$ident = $value;
	               	$result->free();
	            }
	            
	            // set user as loaded
	            $this->loaded_users[$module_ident][] = 1;
    			return;
    		}
    		else
				$user_id = $this->unibox->session->user_id;

        if (!isset($this->loaded_user[$module_ident]) || !in_array($user_id, $this->loaded_users[$module_ident]))
        {
			$sql_string  = 'SELECT
							  a.user_config_ident,
							  a.user_config_value
							FROM
							  sys_user_config AS a
								INNER JOIN sys_user_config_spec AS b
								  ON b.user_config_ident = a.user_config_ident
							WHERE
							  b.module_ident = \''.$this->unibox->db->cleanup($module_ident).'\'
							  AND
							  a.user_id = '.$user_id;
            $result = $this->unibox->db->query($sql_string, 'couldn\'t get default config for module: '.$module_ident);
            if ($result->num_rows() > 0)
            {
                while (list($ident, $value) = $result->fetch_row())
                    $this->user->$user_id->$ident = $value;
               	$result->free();
            }
            // set user as loaded
            $this->loaded_users[$module_ident][] = $user_id;
        }
    }

} // end class ub_config
        
?>