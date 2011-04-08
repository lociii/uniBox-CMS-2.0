<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_user_config
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
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_user_config::version;
    } // end get_version()

    /**
    * class constructor
    *
    */
    private function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
    } // end __construct()

    /**
    * returns class instance
    * 
    * @return       ub_alias (object)
    */
    public static function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_user_config;
        return self::$instance;
    } // end get_instance()

    /**
    * prints welcome message
    */
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_USER_CONFIG_WELCOME_TEXT');
        $msg->display();
        return 0;
    } // end welcome()

	public function configure()
	{
		$validator = ub_validator::get_instance();
		return $validator->form_validate('user_config_configure');
	}

	public function configure_foreign()
	{
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              user_id
                            FROM
                              sys_users
							WHERE
							  user_id > 0';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('user_id'));
                if (!$validator->validate('STACK', 'user_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('user_config_configure');
        else
            $stack->switch_to_administration();
	}
	
	public function configure_form()
	{
		$stack = ub_stack::get_instance();
		
        // get user id
        if (!($user_id = $stack->top()))
        	$user_id = $this->unibox->session->user_id;
        else
        	$user_id = $user_id->user_id;
        
        // load template
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'user_config_configure');

        // reset form elements if form exists
        if (isset($this->unibox->session->env->form->user_config_configure->spec))
            $this->unibox->session->env->form->user_config_configure->spec->elements = new stdClass();
		
		$form = ub_form_creator::get_instance();
		$form->begin_form('user_config_configure', $this->unibox->session->env->alias->name);

        // initialize variables and load values
        $cur_module_ident = null;
        $config = array();
        
        // load all values the user has filled in
		$sql_string =  'SELECT
						  user_config_ident,
						  user_config_value
						FROM
						  sys_user_config
						WHERE
						  user_id = '.$user_id;
		$result = $this->unibox->db->query($sql_string, 'failed to set values for user configuration');
		while (list($config_ident, $config_value) = $result->fetch_row())
			$config[$config_ident] = $config_value;
		
		// load default values, if the user did not set all of them
		$sql_string =  'SELECT
						  user_config_ident,
						  default_value
						FROM
						  sys_user_config_spec';
		if (!empty($config))
			$sql_string .= ' WHERE user_config_ident NOT IN (\''.implode('\', \'', array_keys($config)).'\')';
		$result = $this->unibox->db->query($sql_string, 'failed to set default values for user configuration');
		while (list($config_ident, $config_value) = $result->fetch_row())
			$config[$config_ident] = $config_value;
		
		// set form values
		$form->set_values_array($config);
		$form->set_restore(true);
		
		// build form
		$sql_string =  'SELECT
						  a.si_user_config_descr,
						  a.user_config_field_spec,
						  a.module_ident,
						  c.string_value
						FROM sys_user_config_spec AS a
						  INNER JOIN sys_modules AS b
							ON b.module_ident = a.module_ident
						  INNER JOIN sys_translations AS c
							ON
							(
							  c.string_ident = b.si_module_name
							  AND
							  c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						ORDER BY
						  c.string_value ASC,
						  a.user_config_sort ASC';
		$result = $this->unibox->db->query($sql_string, 'failed to get user configuration');
		if ($result->num_rows() < 1)
		{
			$form->plaintext('TRL_USER_CONFIG_NO_OPTIONS');
			$form->end_form();
			return;
		}

		while (list($si_user_config_descr, $user_config_field_spec, $module_ident, $module_name) = $result->fetch_row())
		{
			if ($module_ident !== $cur_module_ident)
			{
				if ($cur_module_ident !== null)
					$form->end_fieldset();
					
				$form->begin_fieldset($module_name);
			}
			$form->import_form_spec($user_config_field_spec);
			if ($si_user_config_descr !== null)
				$form->set_help($si_user_config_descr);

			$cur_module_ident = $module_ident;
		}
		
        // close last group's fieldset
        $form->end_fieldset();

		$form->begin_buttonset();
		$form->submit('TRL_SAVE_UCASE');
		if ($this->unibox->session->env->system->action_ident == 'user_config_configure_foreign')
		{
        	$form->set_destructor('ub_stack', 'discard_top');
			$form->cancel('TRL_CANCEL_UCASE', 'user_config_configure_foreign');
		}
		else
			$form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
		$form->end_buttonset();
		$form->end_form();
	}
	
	public function configure_process()
	{
		$stack = ub_stack::get_instance();
		
        // get user id
        if (!($user_id = $stack->top()))
        	$user_id = $this->unibox->session->user_id;
        else
        	$user_id = $user_id->user_id;

		$sql_string =  'SELECT
						  user_config_ident
						FROM sys_user_config_spec';
		$result = $this->unibox->db->query($sql_string, 'failed to get config idents');
		$sql_string = 'REPLACE INTO sys_user_config VALUES ';
		while (list($config_ident) = $result->fetch_row())
			if (isset($this->unibox->session->env->form->user_config_configure->data->$config_ident))
				$sql_string .= '(\''.$this->unibox->db->cleanup($config_ident).'\', '.$user_id.', \''.$this->unibox->db->cleanup($this->unibox->session->env->form->user_config_configure->data->$config_ident).'\'), ';
				
		$sql_string = substr($sql_string, 0, -2);
		$result = $this->unibox->db->query($sql_string);
		
		// reset form
		ub_form_creator::reset('user_config_configure');
		
		if ($this->unibox->db->affected_rows() > 0)
		{
			$msg = new ub_message(MSG_SUCCESS, false);
			$msg->add_text('TRL_USER_CONFIGURATION_SUCCESSFULLY_CHANGED');
			$msg->display();
		}
		else
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_USER_CONFIGURATION_CHANGE_FAILED_OR_NO_CHANGE');
			$msg->display();
		}
	}
}
?>