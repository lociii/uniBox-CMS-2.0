<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_configuration
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
        return ub_configuration::version;
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
            self::$instance = new ub_configuration;
        return self::$instance;
    } // end get_instance()

    /**
    * prints welcome message
    */
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_CONFIGURATION_WELCOME_TEXT');
        $msg->display();
        return 0;
    } // end welcome()

	public function configure()
	{
		$validator = ub_validator::get_instance();
		return $validator->form_validate('configuration_configure');
	}
	
	public function configure_preselect()
	{
        $form = ub_form_creator::get_instance();

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'preselect_configuration_configure');

        $preselect = ub_preselect::get_instance('preselect_configuration_configure');
        $preselect->add_field('module_ident', 'b.module_ident', true);
        $preselect->check();

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('preselect_configuration_configure', 'configuration_configure');

        $form->begin_fieldset('TRL_PRESELECT');
        $form->begin_select('module_ident', 'TRL_MODULE');
		$sql_string =  'SELECT DISTINCT
						  a.module_ident,
						  c.string_value
						FROM sys_config_groups AS a
						  INNER JOIN sys_modules AS b
							ON b.module_ident = a.module_ident
						  INNER JOIN sys_translations AS c
							ON
							(
							c.string_ident = b.si_module_name
							AND
							c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						WHERE
						  EXISTS (
							SELECT
							  config_ident
							FROM sys_config AS d
							WHERE
							  d.config_group_ident = a.config_group_ident
							  AND
							  d.config_type = \'value\'
							  AND
							  d.config_field_spec IS NOT NULL
							)
						  AND
						  b.module_active = 1
                        ORDER BY
                          c.string_value';
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

		return $preselect->process();
	}
	
	public function configure_form()
	{
        $this->unibox->xml->add_value('form_name', 'configuration_configure');
        $preselect = ub_preselect::get_instance('preselect_configuration_configure');

		$sql_string =  'SELECT
						  a.si_config_descr,
						  a.config_field_spec,
						  a.config_group_ident,
						  c.string_value
						FROM sys_config AS a
						  INNER JOIN sys_config_groups AS b
							ON b.config_group_ident = a.config_group_ident
						  INNER JOIN sys_translations AS c
							ON c.string_ident = b.si_config_group_descr
						WHERE
						  '.$preselect->get_string().'
						  AND
						  a.config_type = \'value\'
						  AND
						  a.config_field_spec IS NOT NULL
						  AND
						  c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						ORDER BY
						  b.config_group_sort ASC,
						  a.config_sort ASC';
		$result = $this->unibox->db->query($sql_string, 'failed to get configuration');
		if ($result->num_rows() > 0)
		{
	        // reset form elements if form exists
	        if (isset($this->unibox->session->env->form->configuration_configure->spec))
	            $this->unibox->session->env->form->configuration_configure->spec->elements = new stdClass;
			
			$form = ub_form_creator::get_instance();
			$form->begin_form('configuration_configure', 'configuration_configure');
	
	        // initialize variables and load values
	        $cur_config_group_ident = null;
	        $config = array();
	        
			$sql_string =  'SELECT
							  a.config_ident,
							  a.config_value
							FROM sys_config AS a
							  INNER JOIN sys_config_groups AS b
								ON b.config_group_ident = a.config_group_ident
							WHERE
							  '.$preselect->get_string().'
							  AND
							  a.config_type = \'value\'
							  AND
							  a.config_field_spec IS NOT NULL';
			$result_data = $this->unibox->db->query($sql_string, 'failed to set values for configuration');
			while (list($config_ident, $config_value) = $result_data->fetch_row())
				$config[$config_ident] = $config_value;

			// free result
			$result_data->free();

			// set values
			$form->set_values_array($config);
			$form->set_restore(true);

			while (list($si_config_descr, $config_field_spec, $config_group_ident, $config_group_descr) = $result->fetch_row())
			{
				if ($config_group_ident !== $cur_config_group_ident)
				{
					if ($cur_config_group_ident !== null)
						$form->end_fieldset();

					$translate = $form->set_translation(false);
					$form->begin_fieldset($config_group_descr);
					$translate = $form->set_translation(true);
				}
				$form->import_form_spec($config_field_spec);
				if ($si_config_descr !== null)
					$form->set_help($si_config_descr);
	
				$cur_config_group_ident = $config_group_ident;
			}

			// free result
			$result->free();

	        // close last group's fieldset
	        $form->end_fieldset();

			$form->begin_buttonset();
			$form->submit('TRL_SAVE_UCASE');
			$form->cancel('TRL_CANCEL_UCASE', 'configuration_configure');
			$form->end_buttonset();
			$form->end_form();
		}
	}
	
	public function configure_process()
	{
		$preselect = ub_preselect::get_instance('preselect_configuration_configure');
		
		$sql_string =  'SELECT
						  a.config_ident,
						  a.si_config_descr,
						  a.config_type,
						  a.config_group_ident,
						  a.config_field_spec,
						  a.config_sort
						FROM sys_config AS a
						  INNER JOIN sys_config_groups AS b
							ON b.config_group_ident = a.config_group_ident
						WHERE
						  '.$preselect->get_string().'
						  AND
						  a.config_type = \'value\'
						  AND
						  a.config_field_spec IS NOT NULL';
		$result = $this->unibox->db->query($sql_string, 'failed to get config idents');
		$sql_string = 'REPLACE INTO sys_config VALUES ';
		while (list($config_ident, $si_config_descr, $config_type, $config_group_ident, $config_field_spec, $config_sort) = $result->fetch_row())
			if (isset($this->unibox->session->env->form->configuration_configure->data->$config_ident))
				$sql_string .= '(\''.$this->unibox->db->cleanup($config_ident).'\', \''.$this->unibox->db->cleanup($this->unibox->session->env->form->configuration_configure->data->$config_ident).'\', '.(($si_config_descr === null) ? 'NULL' : '\''.$this->unibox->db->cleanup($si_config_descr).'\'').', \''.$config_type.'\', \''.$config_group_ident.'\', \''.$this->unibox->db->cleanup($config_field_spec).'\', \''.$config_sort.'\'), ';
				
		$sql_string = substr($sql_string, 0, -2);
		$result = $this->unibox->db->query($sql_string);
		
		if ($this->unibox->db->affected_rows() > 0)
		{
			$msg = new ub_message(MSG_SUCCESS, false);
			$msg->add_text('TRL_CONFIGURATION_SUCCESSFULLY_CHANGED');
			$msg->display();
		}
		else
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_CONFIGURATION_CHANGE_FAILED_OR_NO_CHANGE');
			$msg->display();
		}
	}
}
?>