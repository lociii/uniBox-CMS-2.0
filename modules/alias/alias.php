<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_alias
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
        return ub_alias::version;
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
            self::$instance = new ub_alias;
        return self::$instance;
    } // end get_instance()

    /**
    * prints welcome message
    */
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_ALIAS_WELCOME_TEXT');
        $msg->display();
        return 0;
    } // end welcome()

	public function alias_edit()
	{
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          alias
	                        FROM
	                          sys_alias';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('alias'));
                if (!$validator->validate('STACK', 'alias', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return 0;
        else
            $stack->switch_to_administration();
	}

	public function alias_edit_prepare_dialog()
	{
		// get stack
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();

		// get dialog
		$dialog = ub_dialog::get_instance();

		// get data
		$sql_string  = 'SELECT
						  c.module_ident,
						  b.action_ident,
						  a.alias_group_ident
						FROM
						  sys_alias AS a
							INNER JOIN sys_actions AS b
							  ON b.action_ident = a.action_ident
							INNER JOIN sys_modules AS c
							  ON c.module_ident = b.module_ident
						WHERE
						  alias = \''.$dataset->alias.'\'';

		$result = $this->unibox->db->query($sql_string, 'failed to get alias data');
		$values = $result->fetch_row(MYSQL_ASSOC);

		// check if first form has already been displayed
		if (!isset($this->unibox->session->env->form->module_selector->spec))
		{
			// refill all forms and mark steps as finished
			$form = ub_form_creator::get_instance();

			// refill module selector
			$form->set_current_form('module_selector');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('module_ident'))));
			$dialog->finish_step(1, 'module_selector');

			// refill action selector
			$form->set_current_form('action_selector');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('action_ident'))));
			$dialog->finish_step(2, 'action_selector');

			// refill details
			$form->set_current_form('details_selector');
			$details = array();
	        $sql_string =  'SELECT
							  name,
							  value
							FROM
							  sys_alias_get
							WHERE
							  alias = \''.$dataset->alias.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to fill getvars');
			while (list($name, $value) = $result->fetch_row())
				$details[$name] = $value;
			$form->set_values_array($details);
			$dialog->finish_step(3, 'details_selector');

			// refill description
			$form->set_current_form('alias_name');
			$values['alias_name'] = $dataset->alias;
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('alias_name', 'alias_group_ident'))));
			$dialog->finish_step(4, 'alias_name');
		}

    	// disable step 3 if no details exist
		$sql_string  = 'SELECT DISTINCT
						  entity_class
						FROM
						  sys_sex
						WHERE
						  module_ident_to = \'alias\'
						  AND
						  module_ident_from = \''.$this->unibox->session->env->form->module_selector->data->module_ident.'\'
						  AND
						  entity_class = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
						ORDER BY
						  entity_detail_int ASC';
    	$dialog->set_condition(2, 'action_ident', CHECK_NOTINSET_SQL, $sql_string, DIALOG_STEPS_DISABLE, array(3));
    	$dialog->set_condition(2, 'action_ident', CHECK_INSET_SQL, $sql_string, DIALOG_STEPS_ENABLE, array(3));
    	
    	// set content title
    	$this->unibox->set_content_title('TRL_ALIAS_EDIT', array($dataset->alias));
	}

	public function alias_module_selector()
	{
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_SELECT_MODULE', true);
        $this->unibox->xml->add_value('form_name', 'module_selector');
        $form = ub_form_creator::get_instance();
    	$form->begin_form('module_selector', $this->unibox->session->env->system->action_ident);
    	$form->set_destructor('ub_stack', 'discard_top');
	}

	public function form_alias_module_selector()
	{
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();

		$form->begin_fieldset('TRL_SELECT_MODULE');
		$sql_string  = 'SELECT
						  a.module_ident,
						  b.string_value
						FROM
						  sys_modules AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_module_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						ORDER BY
						  b.string_value ASC';
		$form->begin_select('module_ident', 'TRL_MODULE', true);
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'alias_edit' || $this->unibox->session->env->system->action_ident == 'alias_extension_edit')
        	$form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->action_ident);
        else
        	$form->cancel('TRL_CANCEL_UCASE', 'alias_administrate');
        $form->end_buttonset();
		$form->end_form();
        return 0;
	}

	public function alias_action_selector()
	{
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_SELECT_ACTION', true);
        $this->unibox->xml->add_value('form_name', 'action_selector');
        $form = ub_form_creator::get_instance();
    	$form->begin_form('action_selector', $this->unibox->session->env->system->action_ident);
	}

	public function form_alias_action_selector()
	{
		$dialog = ub_dialog::get_instance();
		$dialog->enable_step(3);
		$dialog->redraw();
		
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();

		$form->begin_fieldset('TRL_SELECT_ACTION');
		$sql_string  = 'SELECT
						  a.action_ident,
						  b.string_value
						FROM
						  sys_actions AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_action_descr
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						WHERE
						  a.module_ident = \''.$this->unibox->session->env->form->module_selector->data->module_ident.'\'
						ORDER BY
						  b.string_value ASC';
		$form->begin_select('action_ident', 'TRL_ACTION_IDENTIFIER', true);
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'alias_edit' || $this->unibox->session->env->system->action_ident == 'alias_extension_edit')
        	$form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->action_ident);
        else
        	$form->cancel('TRL_CANCEL_UCASE', 'alias_administrate');
        $form->end_buttonset();
		$form->end_form();
        return 0;
	}

	public function alias_details_selector()
	{
		// load config for selected module
		$this->unibox->config->load_config($this->unibox->session->env->form->module_selector->data->module_ident);

		$sql_string  = 'SELECT 
						  COUNT(*) AS count
						FROM
						  sys_sex
						WHERE
						  module_ident_to = \'alias\'
						  AND
						  module_ident_from = \''.$this->unibox->session->env->form->module_selector->data->module_ident.'\'
						  AND
						  entity_class = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
						ORDER BY
						  entity_detail_int ASC';
		$result = $this->unibox->db->query($sql_string);
		list($count) = $result->fetch_row();
		if ($count == 0)
		{
			$dialog = ub_dialog::get_instance();
			$dialog->disable_step(3);
			$dialog->redraw();
			return 1;
		}

		// reset form elements if it exists
		if (isset($this->unibox->session->env->form->details_selector->spec))
			$this->unibox->session->env->form->details_selector->spec->elements = new stdClass;

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_SELECT_DETAILS', true);
        $this->unibox->xml->add_value('form_name', 'details_selector');
        $form = ub_form_creator::get_instance();
    	$form->begin_form('details_selector', $this->unibox->session->env->system->action_ident);
	}

	public function form_alias_details_selector()
	{
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();

		$form->begin_fieldset('TRL_DETAILS');
		$sql_string  = 'SELECT DISTINCT
						  entity_ident,
						  entity_type_definition
						FROM
						  sys_sex
						WHERE
						  module_ident_to = \'alias\'
						  AND
						  module_ident_from = \''.$this->unibox->session->env->form->module_selector->data->module_ident.'\'
						  AND
						  entity_class = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
						ORDER BY
						  entity_detail_int ASC';
		$result = $this->unibox->db->query($sql_string);
		if ($result->num_rows() > 0)
			while (list($entity_ident, $entity_type_definition) = $result->fetch_row())
				$form->import_form_spec($entity_type_definition);

		$form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'alias_edit' || $this->unibox->session->env->system->action_ident == 'alias_extension_edit')
        	$form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->action_ident);
        else
        	$form->cancel('TRL_CANCEL_UCASE', 'alias_administrate');
        $form->end_buttonset();
		$form->end_form();
        return 0;
	}
	
	public function alias_name()
	{
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_ALIAS_SELECT_NAME_AND_GROUP', true);
        $this->unibox->xml->add_value('form_name', 'alias_name');
        $form = ub_form_creator::get_instance();
        $form->begin_form('alias_name', $this->unibox->session->env->system->action_ident);
	}

	public function form_alias_name()
	{
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();

		$form->begin_fieldset('TRL_ALIAS_NAME_AND_GROUP');
		$form->text('alias_name', 'TRL_NAME', '', 30);
		$form->set_condition(CHECK_NOTEMPTY);
		$form->set_condition(CHECK_PREG, '/^[a-z0-9_-]+$/i', 'TRL_ALIAS_INVALID_ALIAS_NAME');
		$sql_string  = 'SELECT
						  alias
						FROM
						  sys_alias';
		if ($this->unibox->session->env->system->action_ident == 'alias_edit')
			$sql_string .= ' WHERE alias != \''.$dataset->alias.'\'';
		$form->set_condition(CHECK_NOTINSET_SQL, $sql_string, 'TRL_ALIAS_ALREADY_IN_USE'); 
		$form->begin_select('alias_group_ident', 'TRL_ALIAS_GROUP');
		$sql_string  = 'SELECT
						  a.alias_group_ident,
						  b.string_value
						FROM sys_alias_groups AS a
						  LEFT JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_alias_group_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						ORDER BY
						  b.string_value ASC';
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'alias_edit')
        	$form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->action_ident);
        else
        	$form->cancel('TRL_CANCEL_UCASE', 'alias_administrate');
        $form->end_buttonset();
		$form->end_form();
        return 0;
	}

	public function extension_data()
	{
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_EXTENSION_SET_NAME', true);
        $this->unibox->xml->add_value('form_name', 'extension_data');
        $form = ub_form_creator::get_instance();
        $form->begin_form('extension_data', $this->unibox->session->env->system->action_ident);
        $form->register_with_dialog();
	}

	public function form_extension_data()
	{
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();
		$form = ub_form_creator::get_instance();
        $form->register_with_dialog();

		$form->begin_fieldset('TRL_GENERAL');
		$form->text('extension_ident', 'TRL_EXTENSION_IDENT', '', 30);
		$form->set_condition(CHECK_NOTEMPTY);
		$form->set_condition(CHECK_PREG, '/^[a-z0-9_-]+$/i', 'TRL_EXTENSION_INVALID_IDENT');
		$sql_string  = 'SELECT
						  extension_ident
						FROM
						  sys_extensions';
		if ($this->unibox->session->env->system->action_ident == 'alias_extension_edit')
			$sql_string .= ' WHERE extension_ident != \''.$dataset->extension_ident.'\'';
		$form->set_condition(CHECK_NOTINSET_SQL, $sql_string, 'TRL_ALIAS_ALREADY_IN_USE'); 
		$form->end_fieldset();

		$form->text_multilanguage('extension_descr', 'TRL_EXTENSION_DESCR', 30);
		$form->set_condition_multilanguage(CHECK_NOTEMPTY);
		$form->set_condition_multilanguage(CHECK_MULTILANG);

        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'alias_extension_edit')
        	$form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->action_ident);
        else
        	$form->cancel('TRL_CANCEL_UCASE', 'alias_extension_administrate');
        $form->end_buttonset();
		$form->end_form();
        return 0;
	}

	public function alias_add_process()
	{
		$this->unibox->db->begin_transaction();

		try
		{
			$sql_string  = 'INSERT INTO
							  sys_alias
							SET
							  alias = \''.$this->unibox->session->env->form->alias_name->data->alias_name.'\',
							  alias_group_ident = \''.$this->unibox->session->env->form->alias_name->data->alias_group_ident.'\',
							  action_ident = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'';
			$this->unibox->db->query($sql_string, 'failed to insert alias');
			if ($this->unibox->db->affected_rows() != 1)
				throw new ub_exception_transaction;

			$sql_string  = 'SELECT DISTINCT
							  entity_ident
							FROM
							  sys_sex
							WHERE
							  module_ident_to = \'alias\'
							  AND
							  entity_class = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
							ORDER BY
							  entity_detail_int ASC';
			$result = $this->unibox->db->query($sql_string);
			while (list($entity_ident) = $result->fetch_row())
			{
				if (isset($this->unibox->session->env->form->details_selector->data->$entity_ident))
				{
					$sql_string  = 'INSERT INTO
									  sys_alias_get
									SET
									  alias = \''.$this->unibox->session->env->form->alias_name->data->alias_name.'\',
									  name = \''.$entity_ident.'\',
									  value = \''.$this->unibox->session->env->form->details_selector->data->$entity_ident.'\'';
					$this->unibox->db->query($sql_string, 'failed to insert alias get var');
					if ($this->unibox->db->affected_rows() != 1)
						throw new ub_exception_transaction;
				}
			}

			$this->unibox->db->commit();
	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_ADD_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ADD_FAILED');
		}

		ub_form_creator::reset('alias_name');
		$this->unibox->switch_alias('alias_administrate', true);
	}

	public function extension_add_process()
	{
		$this->unibox->db->begin_transaction();

		try
		{
			// insert translation
			$si_extension_descr = 'TRL_EXTENSION_'.strtoupper($this->unibox->session->env->form->extension_data->data->extension_ident);
	        if (!$this->unibox->insert_translation($si_extension_descr, $this->unibox->session->env->form->extension_data->data->extension_descr, 'unibox'))
		        throw new ub_exception_transaction('failed to insert translation');

			$sql_string  = 'INSERT INTO
							  sys_extensions
							SET
							  extension_ident = \''.$this->unibox->session->env->form->extension_data->data->extension_ident.'\',
							  si_extension_descr = \''.$this->unibox->db->cleanup($si_extension_descr).'\',
							  action_ident = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'';
			$this->unibox->db->query($sql_string, 'failed to insert extension');
			if ($this->unibox->db->affected_rows() != 1)
				throw new ub_exception_transaction('failed to insert extension');

			$sql_string  = 'SELECT DISTINCT
							  entity_ident
							FROM
							  sys_sex
							WHERE
							  module_ident_to = \'alias\'
							  AND
							  entity_class = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
							ORDER BY
							  entity_detail_int ASC';
			$result = $this->unibox->db->query($sql_string, 'failed to get alias detail definition');
			while (list($entity_ident) = $result->fetch_row())
				if (isset($this->unibox->session->env->form->details_selector->data->$entity_ident))
				{
					$sql_string  = 'INSERT INTO
									  sys_extensions_get
									SET
									  extension_ident = \''.$this->unibox->session->env->form->extension_data->data->extension_ident.'\',
									  name = \''.$entity_ident.'\',
									  value = \''.$this->unibox->session->env->form->details_selector->data->$entity_ident.'\'';
					$this->unibox->db->query($sql_string, 'failed to insert alias get var');
					if ($this->unibox->db->affected_rows() != 1)
						throw new ub_exception_transaction('failed to insert alias get var');
				}

			$this->unibox->db->commit();
	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_ADD_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ADD_FAILED');
		}

		ub_form_creator::reset('extension_data');
		$this->unibox->switch_alias('alias_extension_administrate', true);
	}

	public function alias_edit_process()
	{
		try
		{
			$this->unibox->db->begin_transaction();
			
			// get stack
			$stack = ub_stack::get_instance();
			$dataset = $stack->top();

			// drop all alias get values
			$sql_string =  'DELETE FROM
							  sys_alias_get
							WHERE
							  alias = \''.$dataset->alias.'\'';
			$this->unibox->db->query($sql_string, 'failed to delete old getvars');

			// insert new get values
			$sql_string  = 'SELECT DISTINCT
							  entity_ident
							FROM
							  sys_sex
							WHERE
							  module_ident_to = \'alias\'
							  AND
							  entity_class = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'';
			$result = $this->unibox->db->query($sql_string);
			while (list($entity_ident) = $result->fetch_row())
				if (isset($this->unibox->session->env->form->details_selector->data->$entity_ident))
				{
					$sql_string  = 'INSERT INTO
									  sys_alias_get
									SET
									  alias = \''.$dataset->alias.'\',
									  name = \''.$entity_ident.'\',
									  value = \''.$this->unibox->session->env->form->details_selector->data->$entity_ident.'\'';
					$this->unibox->db->query($sql_string, 'failed to insert alias get var');
				}

			// update alias data
			$sql_string  = 'UPDATE
							  sys_alias
							SET
							  alias = \''.$this->unibox->session->env->form->alias_name->data->alias_name.'\',
							  alias_group_ident = \''.$this->unibox->session->env->form->alias_name->data->alias_group_ident.'\',
							  action_ident = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
							WHERE
							  alias = \''.$dataset->alias.'\'';
			$this->unibox->db->query($sql_string, 'failed to update alias');

			$this->unibox->db->commit();
	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_EDIT_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_EDIT_FAILED');
            $stack->clear();
		}
		ub_form_creator::reset('module_selector');
		$this->unibox->switch_alias('alias_administrate', true);
	}
	
    /**
    * delete()
    *
    * delete dataset
    */
    public function alias_delete()
    {
        // try if the user is allowed to delete aliases
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          a.alias,
							  c.string_value
	                        FROM
	                          sys_alias AS a
								INNER JOIN sys_actions AS b
								  ON b.action_ident = a.action_ident
								INNER JOIN sys_translations AS c
								  ON
								  (
								  c.string_ident = b.si_action_descr
								  AND
								  c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								  )';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('alias'));
                if (!$validator->validate('STACK', 'alias', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('alias_delete');
        else
            $stack->switch_to_administration();
    }

    /**
    * delete_confirm()
    *
    * delete dataset - show confirmation
    */
    public function alias_delete_confirm()
    {
        $stack = ub_stack::get_instance();
        
        // get alias data
        $sql_string  = 'SELECT
                          a.alias,
						  c.string_value
                        FROM
                          sys_alias AS a
							INNER JOIN sys_actions AS b
							  ON b.action_ident = a.action_ident
							INNER JOIN sys_translations AS c
							  ON
							  (
							  c.string_ident = b.si_action_descr
							  AND
							  c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        WHERE
                          a.alias IN (\''.implode('\', \'', $stack->get_stack('alias')).'\')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_ALIAS_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($alias, $descr) = $result->fetch_row())
                $msg->add_listentry($alias.' ('.$descr.')', array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('alias_delete', 'alias_delete');
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'alias_delete');
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'discard_all');
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        $msg->display();
    }

    /**
    * delete_process()
    *
    * delete dataset - execute
    */
    public function alias_delete_process()
    {
        $stack = ub_stack::get_instance();

        // delete all aliases
        $sql_string  = 'DELETE FROM
                          sys_alias
                        WHERE
                          alias IN (\''.implode('\', \'', $stack->get_stack('alias')).'\')';
        $this->unibox->db->query($sql_string, 'failed to delete aliases');
        if ($this->unibox->db->affected_rows() > 0)
        {
            foreach ($stack->get_stack('alias') as $dataset)
                $this->unibox->log(LOG_ALTER, 'alias delete', $dataset);
            $msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_DELETE_SUCCESSFUL');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR, false);
            $msg->add_text('TRL_DELETE_FAILED');
        }
        $msg->display();
        ub_form_creator::reset('alias_delete');
    }
	
	public function alias_administrate()
	{
        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'alias_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('alias_administrate', 'alias_administrate');

        $preselect = ub_preselect::get_instance('alias_administrate');
        $preselect->add_field('alias_name', 'a.alias');
        $preselect->add_field('module_ident', 'b.module_ident', true);
        $preselect->add_field('alias_group_ident', 'a.alias_group_ident', true);
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
   		$sql_string  = 'SELECT DISTINCT
						  a.module_ident,
						  b.string_value
						FROM sys_modules AS a
						  INNER JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_module_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  INNER JOIN sys_actions AS c
							ON c.module_ident = a.module_ident
						  INNER JOIN sys_alias AS d
							ON d.action_ident = c.action_ident
						ORDER BY
						  b.string_value ASC';
		$form->begin_select('module_ident', 'TRL_MODULE', true);
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);

		$form->begin_select('alias_group_ident', 'TRL_ALIAS_GROUP');
		$sql_string  = 'SELECT
						  a.alias_group_ident,
						  b.string_value
						FROM sys_alias_groups AS a
						  LEFT JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_alias_group_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						ORDER BY
						  b.string_value ASC';
		$form->add_option_sql($sql_string);
		$form->end_select();
		
		$form->text('alias_name', 'TRL_NAME', '', 30);
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

        return $preselect->process();
    }

    public function alias_administrate_show()
    {
        // get preselect
        $preselect = ub_preselect::get_instance('alias_administrate');
        $admin = ub_administration_ng::get_instance('alias_administrate');

        $sql_string  = 'SELECT
                          a.alias,
						  c.string_value,
						  e.string_value
                        FROM sys_alias AS a
						  INNER JOIN sys_actions AS b
							ON b.action_ident = a.action_ident
						  INNER JOIN sys_translations AS c
							ON
							(
							c.string_ident = b.si_action_descr
							AND
							c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  INNER JOIN sys_alias_groups AS d
							ON d.alias_group_ident = a.alias_group_ident
						  INNER JOIN sys_translations AS e
							ON
							(
							e.string_ident = d.si_alias_group_name
							AND
							e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  LEFT JOIN sys_sex AS f
							ON
							(
							f.entity_class = a.action_ident
							AND
							f.module_ident_to = \'alias\'
							)
						  WHERE
                          '.$preselect->get_string();

        $admin->add_field('TRL_NAME', 'a.alias');
        $admin->add_field('TRL_ACTION', 'c.string_value');
        $admin->add_field('TRL_ALIAS_GROUP', 'e.string_value');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
			$rights_edit = $this->unibox->session->has_right('alias_edit');
			$rights_delete = $this->unibox->session->has_right('alias_delete');
            while (list($alias, $action_descr, $alias_group) = $result->fetch_row())
            {
                $admin->begin_dataset();
                $admin->add_dataset_ident('alias', $alias);
                $admin->set_dataset_descr($alias);

                $admin->add_data($alias);
                $admin->add_data($action_descr);
                $admin->add_data($alias_group);

                if ($rights_edit)
                    $admin->add_option('content_edit_true.gif', 'TRL_ALT_EDIT', 'alias_edit');
                else
                    $admin->add_option('content_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($rights_delete)
                    $admin->add_option('content_delete_true.gif', 'TRL_ALT_DELETE', 'alias_delete');
                else
                    $admin->add_option('content_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');
                $admin->end_dataset();
            }
            $admin->set_multi_descr('TRL_ALIASES');
            $admin->add_multi_option('content_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'alias_edit');
            $admin->add_multi_option('content_delete_true.gif', 'TRL_ALT_MULTI_DELETE', 'alias_delete');
        }
        $admin->show();
    }

	public function alias_group_administrate()
	{
        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'alias_group_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('alias_group_administrate', 'alias_group_administrate');

        $preselect = ub_preselect::get_instance('alias_group_administrate');
        $preselect->add_field('alias_group_name', 'b.string_value');
        $preselect->add_field('theme_ident', 'a.theme_ident', true);
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
        $form->text('alias_group_name', 'TRL_NAME', '', 30);

   		$sql_string  = 'SELECT DISTINCT
						  a.theme_ident,
						  b.string_value
						FROM sys_themes AS a
						  INNER JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_theme_descr
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  INNER JOIN sys_alias_groups AS c
							ON c.theme_ident = a.theme_ident
						ORDER BY
						  b.string_value ASC';
		$form->begin_select('theme_ident', 'TRL_THEME', true);
		$form->add_option_sql($sql_string);
		$form->end_select();
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

        return $preselect->process();
	}
	
	public function alias_group_administrate_show()
	{
        // get preselect
        $preselect = ub_preselect::get_instance('alias_group_administrate');
        $admin = ub_administration_ng::get_instance('alias_group_administrate');

        $sql_string  = 'SELECT
                          a.alias_group_ident,
						  b.string_value AS alias_group_name,
						  e.string_value AS theme_name,
						  f.string_value AS subtheme_name
                        FROM sys_alias_groups AS a
						  INNER JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_alias_group_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  INNER JOIN sys_themes AS c
							ON c.theme_ident = a.theme_ident
						  LEFT JOIN sys_subthemes AS d
							ON
							(
							d.subtheme_ident = a.subtheme_ident
							AND
							d.theme_ident = a.theme_ident
							)
						  INNER JOIN sys_translations AS e
							ON
							(
							e.string_ident = c.si_theme_descr
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  LEFT JOIN sys_translations AS f
							ON
							(
							f.string_ident = d.si_subtheme_descr
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  WHERE
                          '.$preselect->get_string();

        $admin->add_field('TRL_NAME', 'a.alias_group_ident');
        $admin->add_field('TRL_THEME', 'e.string_value');
        $admin->add_field('TRL_SUBTHEME', 'f.string_value');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
        	$right_extensions = $this->unibox->session->has_right('alias_group_extensions_administrate');
			$right_edit = $this->unibox->session->has_right('alias_group_edit');
			$right_delete = $this->unibox->session->has_right('alias_group_delete');
            while (list($alias_group_ident, $alias_group_name, $theme_name, $subtheme_name) = $result->fetch_row())
            {
                $admin->begin_dataset();
                $admin->add_dataset_ident('alias_group_ident', $alias_group_ident);
                $admin->set_dataset_descr($alias_group_name);

                $admin->add_data($alias_group_name);
                $admin->add_data($theme_name);
        		$admin->add_data($subtheme_name);

                if ($right_extensions)
                    $admin->add_option('assign_true.gif', 'TRL_ALT_ADMINISTRATE_ASSIGNED_EXTENSIONS', 'alias_group_extensions_administrate');
                else
                    $admin->add_option('assign_false.gif', 'TRL_ALT_ADMINISTRATE_ASSIGNED_EXTENSIONS_FORBIDDEN');

                if ($right_edit)
                    $admin->add_option('content_edit_true.gif', 'TRL_ALT_EDIT', 'alias_group_edit');
                else
                    $admin->add_option('content_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($right_delete)
                    $admin->add_option('content_delete_true.gif', 'TRL_ALT_DELETE', 'alias_group_delete');
                else
                    $admin->add_option('content_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');
                $admin->end_dataset();
            }
            $admin->set_multi_descr('TRL_ALIAS_GROUPS');
            $admin->add_multi_option('content_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'alias_group_edit');
            $admin->add_multi_option('content_delete_true.gif', 'TRL_ALT_MULTI_DELETE', 'alias_group_delete');
        }
        $admin->show();
	}

    public function alias_group_add()
    {
        $validator = ub_validator::get_instance();
        return $validator->form_validate('alias_group_add');
    }

    public function alias_group_add_form()
    {
        // load template and add form information
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'alias_group_add');
        $form = ub_form_creator::get_instance();
        $form->begin_form('alias_group_add', 'alias_group_add');
    }

    public function alias_group_add_process()
    {
    	if (stristr($this->unibox->session->env->form->alias_group_add->data->theme_ident, '|'))
    	{
    		list($theme_ident, $subtheme_ident) = explode('|', $this->unibox->session->env->form->alias_group_add->data->theme_ident);
    		$subtheme_ident = '\''.$subtheme_ident.'\'';
    	}
    	else
    	{
    		$theme_ident = $this->unibox->session->env->form->alias_group_add->data->theme_ident;
    		$subtheme_ident = 'NULL';
    	}

		$this->unibox->db->begin_transaction();
		try
		{
	        $si_alias_group_name = 'TRL_ALIAS_GROUP_'.strtoupper($this->unibox->session->env->form->alias_group_add->data->alias_group_ident);
	        if (!$this->unibox->insert_translation($si_alias_group_name, $this->unibox->session->env->form->alias_group_add->data->si_alias_group_name, 'usermanager'))
	        	throw new ub_exception_transaction;
	
	        // insert container
	        $sql_string  = 'INSERT INTO
	                          sys_alias_groups
	                        SET
							  alias_group_ident = \''.$this->unibox->session->env->form->alias_group_add->data->alias_group_ident.'\',
	                          si_alias_group_name = \''.$si_alias_group_name.'\',
	                          theme_ident = \''.$theme_ident.'\',
	                          subtheme_ident = '.$subtheme_ident.',
	                          location_show_backend = '.$this->unibox->session->env->form->alias_group_add->data->location_show_backend.',
	                          location_show_module = '.$this->unibox->session->env->form->alias_group_add->data->location_show_module.',
	                          location_show_action = '.$this->unibox->session->env->form->alias_group_add->data->location_show_action;
	        $this->unibox->db->query($sql_string, 'failed to insert alias group');
	        if ($this->unibox->db->affected_rows() != 1)
	        	throw new ub_exception_transaction;

			$this->unibox->db->commit();

            $this->unibox->log(LOG_ALTER, 'alias group add', $this->unibox->db->last_insert_id());
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_ADD_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ADD_FAILED');
		}
		ub_form_creator::reset('alias_group_add');
        $this->unibox->switch_alias('alias_group_administrate', true);
    }

    /**
    * form_articles()
    *
    * form to process dataset containers
    */
    public function form_group()
    {
        $form = ub_form_creator::get_instance();

		$form->text_multilanguage('si_alias_group_name', 'TRL_LANGUAGE_INDEPENDANT_DESCR', 40);
        $form->set_condition_multilanguage(CHECK_MULTILANG);
        $form->set_condition_multilanguage(CHECK_NOTEMPTY);
        $form->set_condition_multilanguage(CHECK_INRANGE, array(0, 250));

        $form->begin_fieldset('TRL_GENERAL');
        $form->text('alias_group_ident', 'TRL_IDENT', '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_PREG, '/^[a-z0-9_-]+$/i', 'TRL_ALIAS_INVALID_ALIAS_GROUP_IDENT');
        $form->begin_select('theme_ident', 'TRL_THEME', true);
        $sql_string  = 'SELECT
                          a.theme_ident,
						  a.subtheme_ident,
                          c.string_value AS theme_name,
						  d.string_value AS subtheme_name
                        FROM
                          sys_subthemes AS a
							INNER JOIN sys_themes AS b
							  ON b.theme_ident = a.theme_ident
                            INNER JOIN sys_translations AS c
                              ON
							  (
							  c.string_ident = b.si_theme_descr
							  AND
							  c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                            INNER JOIN sys_translations AS d
                              ON
							  (
							  d.string_ident = a.si_subtheme_descr
							  AND
							  d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        ORDER BY
                          c.string_value ASC,
						  d.string_value ASC';
		$result = $this->unibox->db->query($sql_string, 'failed to select themes');
		$current_theme_ident = null;
		$form->set_translation(false);
		while (list($theme_ident, $subtheme_ident, $theme_name, $subtheme_name) = $result->fetch_row())
		{
			if ($theme_ident != $current_theme_ident)
			{
				$form->add_option($theme_ident, $theme_name);
				$current_theme_ident = $theme_ident;
			}
			$form->add_option($theme_ident.'|'.$subtheme_ident, $theme_name.' / '.$subtheme_name);
		}
        $form->set_translation(true);
        $form->end_select();
        $form->set_condition(CHECK_NOTEMPTY);
        $form->checkbox('location_show_backend', 'TRL_ALIASGROUP_LOCATION_SHOW_BACKEND');
        $form->checkbox('location_show_module', 'TRL_ALIASGROUP_LOCATION_SHOW_MODULE');
        $form->checkbox('location_show_action', 'TRL_ALIASGROUP_LOCATION_SHOW_ACTION');
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'alias_group_edit')
            $form->cancel('TRL_CANCEL_UCASE', 'alias_group_edit');
        else
            $form->cancel('TRL_CANCEL_UCASE', 'alias_group_administrate');
        $form->end_buttonset();
        $form->end_form();
    }

	public function alias_group_edit()
	{
		// try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              alias_group_ident
                            FROM
                              sys_alias_groups';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('alias_group_ident'));
                if (!$validator->validate('STACK', 'alias_group_ident', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
	        return $validator->form_validate('alias_group_edit');
        else
            $stack->switch_to_administration();
	}

	public function alias_group_edit_refill()
	{
		$stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        // refill the form
        $sql_string  = 'SELECT
                          a.si_alias_group_name,
                          a.theme_ident,
                          a.subtheme_ident,
						  a.location_show_backend,
						  a.location_show_module,
						  a.location_show_action,
                          b.string_value
                        FROM
                          sys_alias_groups AS a
							INNER JOIN sys_translations AS b
							  ON b.string_ident = a.si_alias_group_name
                        WHERE
                          a.alias_group_ident = \''.$dataset->alias_group_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select data');
        if ($result->num_rows() == 1)
        {
            $data = $result->fetch_row(FETCHMODE_ASSOC);
            if ($data['subtheme_ident'] !== null)
            	$data['theme_ident'] = $data['theme_ident'].'|'.$data['subtheme_ident'];
        	unset($data['subtheme_ident']);
        }
        $data['alias_group_ident'] = $dataset->alias_group_ident;
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'alias_group_edit');
        $form->begin_form('alias_group_edit', 'alias_group_edit');

        $form->set_values_array($data, array('si_alias_group_name'), true);
        $form->set_destructor('ub_stack', 'discard_top');

        // set content title
        $this->unibox->set_content_title('TRL_ALIAS_GROUP_EDIT', array($data['string_value']));
	}
	
	public function alias_group_edit_process()
	{
		try
		{
			$this->unibox->db->begin_transaction();
			
			// get stack
			$stack = ub_stack::get_instance();
			$dataset = $stack->top();

			if (stristr($this->unibox->session->env->form->alias_group_edit->data->theme_ident, '|'))
	    	{
	    		list($theme_ident, $subtheme_ident) = explode('|', $this->unibox->session->env->form->alias_group_edit->data->theme_ident);
	    		$subtheme_ident = '\''.$subtheme_ident.'\'';
	    	}
	    	else
	    	{
	    		$theme_ident = $this->unibox->session->env->form->alias_group_edit->data->theme_ident;
	    		$subtheme_ident = 'NULL';
	    	}

	        $si_alias_group_name = 'TRL_ALIAS_GROUP_'.strtoupper($this->unibox->session->env->form->alias_group_edit->data->alias_group_ident);
	        if (!$this->unibox->insert_translation($si_alias_group_name, $this->unibox->session->env->form->alias_group_edit->data->si_alias_group_name, 'unibox'))
	        	throw new ub_exception_transaction;
	
	        // insert container
	        $sql_string  = 'UPDATE
	                          sys_alias_groups
	                        SET
							  alias_group_ident = \''.$this->unibox->session->env->form->alias_group_edit->data->alias_group_ident.'\',
	                          si_alias_group_name = \''.$si_alias_group_name.'\',
	                          theme_ident = \''.$theme_ident.'\',
	                          subtheme_ident = '.$subtheme_ident.',
	                          location_show_backend = '.$this->unibox->session->env->form->alias_group_edit->data->location_show_backend.',
	                          location_show_module = '.$this->unibox->session->env->form->alias_group_edit->data->location_show_module.',
	                          location_show_action = '.$this->unibox->session->env->form->alias_group_edit->data->location_show_action.'
							WHERE
							  alias_group_ident = \''.$dataset->alias_group_ident.'\'';
	        $this->unibox->db->query($sql_string, 'failed to insert alias group');

			$this->unibox->db->commit();

            $this->unibox->log(LOG_ALTER, 'alias group edit', $dataset->alias_group_ident);
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_EDIT_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_EDIT_FAILED');
		}
		ub_form_creator::reset('alias_group_edit');
    	$this->unibox->switch_alias('alias_group_administrate', true);
	}

    /**
    * delete()
    *
    * delete dataset
    */
    public function alias_group_delete()
    {
        // try if the user is allowed to delete aliases
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          a.alias_group_ident,
							  b.string_value
	                        FROM
	                          sys_alias_groups AS a
								INNER JOIN sys_translations AS b
								  ON
								  (
								  b.string_ident = a.si_alias_group_name
								  AND
								  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								  )';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('alias_group_ident'));
                if (!$validator->validate('STACK', 'alias_group_ident', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('alias_group_delete');
        else
            $stack->switch_to_administration();
    }

    /**
    * delete_confirm()
    *
    * delete dataset - show confirmation
    */
    public function alias_group_delete_confirm()
    {
        $stack = ub_stack::get_instance();
        
        // get alias data
        $sql_string  = 'SELECT
						  b.string_value
                        FROM
                          sys_alias_groups AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_alias_group_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        WHERE
                          a.alias_group_ident IN (\''.implode('\', \'', $stack->get_stack('alias_group_ident')).'\')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_ALIAS_GROUP_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($descr) = $result->fetch_row())
                $msg->add_listentry($descr, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('alias_group_delete', 'alias_group_delete');
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'alias_group_delete');
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'discard_all');
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        $msg->display();
    }

    /**
    * delete_process()
    *
    * delete dataset - execute
    */
    public function alias_group_delete_process()
    {
        $stack = ub_stack::get_instance();

        // delete all aliases
        $sql_string  = 'DELETE FROM
                          sys_alias_groups
                        WHERE
                          alias_group_ident IN (\''.implode('\', \'', $stack->get_stack('alias_group_ident')).'\')';
        $this->unibox->db->query($sql_string, 'failed to delete alias groups');
        if ($this->unibox->db->affected_rows() > 0)
        {
            foreach ($stack->get_stack('alias_group_ident') as $dataset)
                $this->unibox->log(LOG_ALTER, 'alias group delete', $dataset);
            $msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_DELETE_SUCCESSFUL');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR, false);
            $msg->add_text('TRL_DELETE_FAILED');
        }
        $msg->display();
        ub_form_creator::reset('alias_group_delete');
    }

	public function alias_group_extensions_administrate()
	{
		$validator = ub_validator::get_instance();
		$sql_string =  'SELECT
						  alias_group_ident
						FROM
						  sys_alias_groups';

		if ($validator->validate('GET', 'alias_group_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
		{
			$this->unibox->session->var->register('alias_group_extensions_ident', $this->unibox->session->env->input->alias_group_ident);
			return 1;
		}
		elseif (isset($this->unibox->session->var->alias_group_extensions_ident))
			return 1;
		else
			$this->unibox->display_error();
	}

	public function alias_group_extensions_administrate_show()
	{
		$this->unibox->load_template('shared_administration');

		$right_assign = $this->unibox->session->has_right('alias_group_extensions_assign');
		$right_sort = $this->unibox->session->has_right('alias_group_extensions_sort');
		$right_cleanup = $this->unibox->session->has_right('alias_group_extensions_cleanup');

        $admin = ub_administration::get_instance('alias_group_extensions_administrate');
        $admin->add_link($this->unibox->identify_alias('alias_group_administrate'), 'TRL_BACK_TO_ALIAS_GROUP_ADMINISTRATION');
        
        $admin->add_field('TRL_NAME');
        $admin->add_field('TRL_SORT');

		$sql_string  = 'SELECT
						  a.theme_ident,
						  a.subtheme_ident,
						  b.default_subtheme_ident,
						  c.string_value
						FROM
						  sys_alias_groups AS a
							INNER JOIN sys_themes AS b
							  ON b.theme_ident = a.theme_ident
							INNER JOIN sys_translations AS c
							  ON
							  (
							  c.string_ident = a.si_alias_group_name
							  AND
							  c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						WHERE
						  a.alias_group_ident = \''.$this->unibox->session->var->alias_group_extensions_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to get alias group details');
        if ($result->num_rows() != 1)
        	throw new ub_exception_general('failed to get alias group details');
    	list($theme_ident, $subtheme_ident, $default_subtheme_ident, $alias_group_name) = $result->fetch_row();
    	if ($subtheme_ident === null)
    		$subtheme_ident = $default_subtheme_ident;

		// set content title
		$this->unibox->set_content_title('TRL_ALIAS_GROUP_EXTENSIONS_ADMINISTRATE', array($alias_group_name));

		$sql_string  = 'SELECT
						  a.output_format_ident,
						  a.filename_extension,
						  b.string_value AS output_format_descr,
						  c.extension_ident,
						  c.extension_group_ident,
						  c.sort,
						  c.pre_content,
						  e.string_value AS extension_name
						FROM
						  sys_output_formats AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_output_format_descr
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						  	LEFT JOIN sys_alias_group_extensions AS c
							  ON
							  (
							  c.output_format_ident = a.output_format_ident
							  AND
							  c.alias_group_ident = \''.$this->unibox->session->var->alias_group_extensions_ident.'\'
							  )
							LEFT JOIN sys_extensions AS d
							  ON d.extension_ident = c.extension_ident
							LEFT JOIN sys_translations AS e
							  ON
							  (
							  e.string_ident = d.si_extension_descr
							  AND
							  e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						ORDER BY
						  b.string_value ASC,
						  c.sort ASC';
        $result = $this->unibox->db->query($sql_string, 'failed to select extensions');

		// get all extension data for current alias group
        if ($result->num_rows() > 0)
        {
        	$data = array();
        	while (list($output_format_ident, $filename_extension, $output_format_descr, $extension_ident, $extension_group_ident, $sort, $pre_content, $extension_name) = $result->fetch_row())
        	{
        		$data[$output_format_ident]['descr'] = $output_format_descr;
        		$data[$output_format_ident]['file_extension'] = $filename_extension;
        		if ($extension_ident !== null)
        		{
        			$data[$output_format_ident]['groups'][$extension_group_ident][$extension_ident]['sort'] = $sort;
        			$data[$output_format_ident]['groups'][$extension_group_ident][$extension_ident]['pre_content'] = $pre_content;
        			$data[$output_format_ident]['groups'][$extension_group_ident][$extension_ident]['name'] = $extension_name;
        		}
        	}
        }

		// loop through output formats
        foreach ($data as $output_format_ident => $output_format_data)
        {
			// check if output format if file based - skip if not
			if ($output_format_data['file_extension'] === null || !($file = file_get_contents(DIR_THEMES.$theme_ident.'/'.$subtheme_ident.'/'.$output_format_ident.'/templates/unibox/main.'.$output_format_data['file_extension'])))
				continue;

			// read current extension groups from template
			$matches = array();
			preg_match_all('/<xsl:apply-templates select="\/root\/extensions\/group\[@ident=\'(.*)\'\]">/i', $file, $matches);

			// add output format
        	$admin->begin_dataset(false);
			$admin->set_dataset_descr($output_format_data['descr']);
			$admin->add_data($output_format_data['descr']);

			// add each group
			foreach ($matches[1] as $extension_group_ident)
			{
				$sql_string  = 'SELECT
								  MIN(sort) AS minimum,
								  MAX(sort) AS maximum
								FROM
								  sys_alias_group_extensions
								WHERE
								  alias_group_ident = \''.$this->unibox->session->var->alias_group_extensions_ident.'\'
								  AND
								  extension_group_ident = \''.$extension_group_ident.'\'';
				$result = $this->unibox->db->query($sql_string, 'failed to get max/min sort values');
				if ($result->num_rows() > 0)
					list($minimum, $maximum) = $result->fetch_row();

				$admin->begin_dataset(false);
				$admin->add_dataset_ident('alias_group_ident', $this->unibox->session->var->alias_group_extensions_ident);
				$admin->add_dataset_ident('output_format_ident', $output_format_ident);
				$admin->add_dataset_ident('extension_group_ident', $extension_group_ident);
				$admin->set_dataset_descr($extension_group_ident);
				$admin->add_data($extension_group_ident);
				if ($right_assign)
					$admin->add_option('assign_true.gif', 'TRL_ALT_ASSIGN_EXTENSIONS', 'alias_group_extensions_assign');
				else
					$admin->add_option('assign_false.gif', 'TRL_ALT_ASSIGN_EXTENSIONS_FORBIDDEN');

				// check if there are any extensions associated with the current group
				if (isset($output_format_data['groups'][$extension_group_ident]))
				{
					// add them
		        	foreach ($output_format_data['groups'][$extension_group_ident] as $extension_ident => $extension_data)
		        	{
		        		$admin->begin_dataset(false);
		        		$admin->set_dataset_descr($extension_data['name']);

						$admin->add_dataset_ident('alias_group_ident', $this->unibox->session->var->alias_group_extensions_ident);
						$admin->add_dataset_ident('output_format_ident', $output_format_ident);
		        		$admin->add_dataset_ident('extension_group_ident', $extension_group_ident);
		        		$admin->add_dataset_ident('extension_ident', $extension_ident);

		        		$admin->add_data($extension_data['name']);
		        		$admin->add_data($extension_data['sort']);

						if ($right_sort)
						{
			        		if ($extension_data['sort'] == $minimum && $extension_data['sort'] < $maximum)
			        		{
		        				$admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
		        				$admin->add_option('sort_down_true.gif', 'TRL_ALT_SORT_DOWN', 'alias_group_extensions_sort');

			        		}
		        			elseif ($extension_data['sort'] == $maximum && $extension_data['sort'] > $minimum)
		        			{
			        			$admin->add_option('sort_up_true.gif', 'TRL_ALT_SORT_UP', 'alias_group_extensions_sort');
			        			$admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
		        			}
		        			elseif ($extension_data['sort'] < $maximum && $extension_data['sort'] > $minimum)
		        			{
		        				$admin->add_option('sort_up_true.gif', 'TRL_ALT_SORT_UP', 'alias_group_extensions_sort');
		        				$admin->add_option('sort_down_true.gif', 'TRL_ALT_SORT_DOWN', 'alias_group_extensions_sort');
		        			}
		        			else
		        			{
		        				$admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
		        				$admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
		        			}
						}
	        			else
	        			{
	        				$admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
	        				$admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
	        			}
		        		
		        		$admin->end_dataset();
		        	}
		        	// unset extension
		        	unset($output_format_data['groups'][$extension_group_ident]);
				}

				// close group
				$admin->end_dataset();
			}

			// check if there are any groups that don't exist anymore
			if (isset($output_format_data['groups']) && count($output_format_data['groups']) > 0)
			{
				foreach ($output_format_data['groups'] as $extension_group_ident => $extension_data)
				{
	        		$admin->begin_dataset(false);
	        		$admin->add_icon('outdated.gif', 'TRL_OUTDATED_EXTENSION_GROUP_ASSIGNED');
	        		$admin->set_dataset_descr($extension_group_ident);

					$admin->add_dataset_ident('alias_group_ident', $this->unibox->session->var->alias_group_extensions_ident);
					$admin->add_dataset_ident('output_format_ident', $output_format_ident);
	        		$admin->add_dataset_ident('extension_group_ident', $extension_group_ident);
	        		
	        		$admin->add_data($extension_group_ident);
	        		if ($right_cleanup)
	        			$admin->add_option('container_delete_true.gif', 'TRL_ALT_CLEANUP_EXTENSION_GROUP', 'alias_group_extensions_cleanup');
        			else
        				$admin->add_option('container_delete_false.gif', 'TRL_ALT_CLEANUP_EXTENSION_GROUP_FORBIDDEN');

					// add all extensions from the missing group
					foreach ($extension_data as $extension_ident => $extension_data)
		        	{
		        		$admin->begin_dataset(false);
		        		$admin->set_dataset_descr($extension_data['name']);
		        		$admin->add_data($extension_data['name']);
	
		        		$admin->end_dataset();
		        	}

					// close missing group
	        		$admin->end_dataset();
				}
			}

			// close output format
        	$admin->end_dataset();
        }
        $admin->show();
	}

    public function alias_group_extensions_assign()
    {
        $validator = ub_validator::get_instance();

		$sql_string =  'SELECT
						  alias_group_ident
						FROM
						  sys_alias_groups';
		if (!$validator->validate('GET', 'alias_group_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
			$this->unibox->display_error();

		$sql_string =  'SELECT
						  output_format_ident
						FROM
						  sys_output_formats';
		if (!$validator->validate('GET', 'output_format_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
			$this->unibox->display_error();

		$sql_string  = 'SELECT
						  filename_extension
						FROM
						  sys_output_formats
						WHERE
						  output_format_ident = \''.$this->unibox->session->env->input->output_format_ident.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to select extension data');
		list($filename_extension) = $result->fetch_row();
		if ($filename_extension === null)
			throw new ub_exception_general('failed to get output format file extension', 'TRL_ERR_EXTENSIONS_ASSIGN_OUTPUT_FORMAT_NOT_FILEBASED');

		// read current extension groups from template
		$matches = array();
		preg_match_all('/<xsl:apply-templates select="\/root\/extensions\/group\[@ident=\'(.*)\'\]">/i', $file, $matches);

		return 1;
    }

    public function alias_group_extensions_assign_show()
    {
    	$stack = ub_stack::get_instance();
    	$dataset = $stack->top();

		$sql_string  = 'SELECT
						  a.theme_ident,
						  a.subtheme_ident,
						  b.default_subtheme
						FROM
						  sys_alias_groups AS a
							INNER JOIN sys_themes AS b
							  ON b.theme_ident = a.theme_ident
						WHERE
						  a.alias_group_ident = \''.$dataset->alias_group_ident.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to select extension data');
		if ($result->num_rows() != 1)
			throw new ub_exception_general('failed to load alias group details');
		list($theme_ident, $subtheme_ident, $default_subtheme) = $result->fetch_row();

		// load main template
		$path = DIR_THEMES.$theme_ident.'/'.($subtheme_ident === null ? $default_subtheme : $subtheme_ident);

		// select extensions to assign
		$sql_string  = 'SELECT
						  a.extension_ident,
						  b.string_value,
						  c.pre_content
						FROM
						  sys_extensions AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_extension_descr
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
							LEFT JOIN sys_alias_group_extensions AS c
							  ON
							  (
							  c.extension_ident = a.extension_ident
							  AND
							  c.alias_group_ident = \''.$dataset->alias_group_ident.'\'
							  )
						ORDER BY
						  b.string_value';
		$result = $this->unibox->db->query($sql_string, 'failed to select extension data');
		if ($result->num_rows() > 0)
		{
	        // load template and add form information
	        $this->unibox->load_template('shared_form_display');
	        $this->unibox->xml->add_value('form_name', 'alias_group_extensions');
	        $form = ub_form_creator::get_instance();
	        $form->begin_form('alias_group_extensions', 'alias_group_extensions');
	        $form->set_destructor('ub_stack', 'discard_top');

			$form->begin_fieldset('TRL_EXTENSIONS');
			while (list($extension_ident, $extension_descr, $pre_content) = $result->fetch_row())
			{
				$form->begin_select('extension_'.$extension_ident, $extension_descr, false);
				$form->add_option('-1', 'TRL_DONT_ASSIGN', $pre_content === null ? true : false);
				$form->add_option('1', 'TRL_PRE_CONTENT', ($pre_content !== null && $pre_content == 1) ? true : false);
				$form->add_option('0', 'TRL_PAST_CONTENT', ($pre_content !== null && $pre_content == 0) ? true : false);
				$form->end_select();
			}
			$form->end_fieldset();

	        $form->begin_buttonset();
	        $form->submit('TRL_SAVE_UCASE');
	        $form->cancel('TRL_CANCEL_UCASE', 'alias_group_administrate');
	        $form->end_buttonset();
	        $form->end_form();
		}
		else
		{
			$msg = new ub_message(MSG_INFO);
			$msg->add_text('TRL_NO_EXTENSIONS');
			$msg->display();
		}
    }

	public function alias_group_extensions_assign_process()
	{
    	$stack = ub_stack::get_instance();
    	$dataset = $stack->top();
		$this->unibox->db->begin_transaction();

		try
		{
			$sql_string  = 'DELETE FROM
							  sys_alias_group_extensions
							WHERE
							  alias_group_ident = \''.$dataset->alias_group_ident.'\'';
			$this->unibox->db->query($sql_string, 'failed to select extension data');

			$sql_string  = 'SELECT
							  extension_ident
							FROM
							  sys_extensions';
			$result = $this->unibox->db->query($sql_string, 'failed to select extension data');
			while (list($extension_ident) = $result->fetch_row())
			{
				if ($this->unibox->session->env->form->alias_group_extensions->data->{'extension_'.$extension_ident} != -1)
				{
					$sql_string  = 'INSERT INTO
									  sys_alias_group_extensions
									SET
									  alias_group_ident = \''.$dataset->alias_group_ident.'\',
									  extension_ident = \''.$extension_ident.'\',
									  pre_content = '.$this->unibox->session->env->form->alias_group_extensions->data->{'extension_'.$extension_ident};
					$this->unibox->db->query($sql_string, 'failed to assign extension');
				}
			}
			$this->unibox->db->commit();

			$msg = new ub_message(MSG_SUCCESS);
			$msg->add_text('TRL_ASSIGN_SUCCESS');
			$msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ASSIGN_FAILED');
		}
		ub_form_creator::reset('alias_group_extensions');
		$this->unibox->switch_alias('alias_group_administrate', true);
	}

	public function extension_administrate()
	{
        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'alias_extension_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('alias_extension_administrate', 'alias_extension_administrate');

        $preselect = ub_preselect::get_instance('alias_extension_administrate');
        $preselect->add_field('extension_descr', 'b.string_value');
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
		$form->text('extension_descr', 'TRL_NAME', '', 30);
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

        return $preselect->process();
	}

	public function extension_administrate_show()
	{
        // get preselect
        $preselect = ub_preselect::get_instance('alias_extension_administrate');
        $admin = ub_administration_ng::get_instance('alias_extension_administrate');

        $sql_string  = 'SELECT
						  a.extension_ident,
						  b.string_value AS extension_descr,
						  d.string_value AS action_name
                        FROM
						  sys_extensions AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_extension_descr
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
							INNER JOIN sys_actions AS c
							  ON c.action_ident = a.action_ident
							INNER JOIN sys_translations AS d
							  ON
							  (
							  d.string_ident = c.si_action_descr
							  AND
							  d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						  WHERE
                          '.$preselect->get_string();

		$admin->add_field('TRL_NAME', 'b.string_value');
        $admin->add_field('TRL_IDENT', 'a.extension_ident');
        $admin->add_field('TRL_ACTION', 'd.string_value');

		$admin->sort_by('b.string_value');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
			$rights_edit = $this->unibox->session->has_right('alias_extension_edit');
			$rights_delete = $this->unibox->session->has_right('alias_extension_delete');
            while (list($extension_ident, $extension_descr, $action_name) = $result->fetch_row())
            {
                $admin->begin_dataset();
                $admin->add_dataset_ident('extension_ident', $extension_ident);
                $admin->set_dataset_descr($extension_descr);

				$admin->add_data($extension_descr);
				$admin->add_data($extension_ident);
                $admin->add_data($action_name);

                if ($rights_edit)
                    $admin->add_option('content_edit_true.gif', 'TRL_ALT_EDIT', 'alias_extension_edit');
                else
                    $admin->add_option('content_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($rights_delete)
                    $admin->add_option('content_delete_true.gif', 'TRL_ALT_DELETE', 'alias_extension_delete');
                else
                    $admin->add_option('content_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');
                $admin->end_dataset();
            }
            $admin->set_multi_descr('TRL_ALIASES');
            $admin->add_multi_option('content_delete_true.gif', 'TRL_ALT_MULTI_DELETE', 'alias_extension_delete');
        }
        $admin->show();
	}

    /**
    * delete()
    *
    * delete dataset
    */
    public function extension_edit()
    {
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          a.extension_ident,
							  b.string_value
	                        FROM
	                          sys_extensions AS a
								INNER JOIN sys_translations AS b
								  ON
								  (
								  b.string_ident = a.si_extension_descr
								  AND
								  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								  )';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('extension_ident'));
                if (!$validator->validate('STACK', 'extension_ident', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return 0;
        else
            $stack->switch_to_administration();
    }

    public function extension_edit_prepare_dialog()
    {
		// get stack
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();

		// get dialog
		$dialog = ub_dialog::get_instance();

		// get data
		$sql_string  = 'SELECT
						  c.module_ident,
						  b.action_ident,
						  a.si_extension_descr AS extension_descr,
						  d.string_value
						FROM
						  sys_extensions AS a
							INNER JOIN sys_actions AS b
							  ON b.action_ident = a.action_ident
							INNER JOIN sys_modules AS c
							  ON c.module_ident = b.module_ident
							INNER JOIN sys_translations AS d
							  ON
							  (
							  d.string_ident = a.si_extension_descr
							  AND
							  d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						WHERE
						  a.extension_ident = \''.$dataset->extension_ident.'\'';

		$result = $this->unibox->db->query($sql_string, 'failed to get extension data');
		$values = $result->fetch_row(MYSQL_ASSOC);

		// check if first form has already been displayed
		if (!isset($this->unibox->session->env->form->module_selector->spec))
		{
			// refill all forms and mark steps as finished
			$form = ub_form_creator::get_instance();

			// refill module selector
			$form->set_current_form('module_selector');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('module_ident'))));
			$dialog->finish_step(1, 'module_selector');

			// refill action selector
			$form->set_current_form('action_selector');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('action_ident'))));
			$dialog->finish_step(2, 'action_selector');

			// refill details
			$form->set_current_form('details_selector');
			$details = array();
	        $sql_string =  'SELECT
							  name,
							  value
							FROM
							  sys_extensions_get
							WHERE
							  extension_ident = \''.$dataset->extension_ident.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to fill getvars');
			while (list($name, $value) = $result->fetch_row())
				$details[$name] = $value;
			$form->set_values_array($details);
			$dialog->finish_step(3, 'details_selector');

			// refill description
			$form->set_current_form('extension_data');
			$values['extension_ident'] = $dataset->extension_ident;
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('extension_descr', 'extension_ident'))), array('extension_descr'));
			$dialog->finish_step(4, 'extension_data');
		}

    	// disable step 3 if no details exist
		$sql_string  = 'SELECT DISTINCT
						  entity_class
						FROM
						  sys_sex
						WHERE
						  module_ident_to = \'alias\'
						  AND
						  module_ident_from = \''.$this->unibox->session->env->form->module_selector->data->module_ident.'\'
						  AND
						  entity_class = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
						ORDER BY
						  entity_detail_int ASC';
    	$dialog->set_condition(2, 'action_ident', CHECK_NOTINSET_SQL, $sql_string, DIALOG_STEPS_DISABLE, array(3));
    	$dialog->set_condition(2, 'action_ident', CHECK_INSET_SQL, $sql_string, DIALOG_STEPS_ENABLE, array(3));

    	// set content title
    	$this->unibox->set_content_title('TRL_ALIAS_EXTENSION_EDIT', array($values['string_value']));
    }

	public function extension_edit_process()
	{
		try
		{
			$this->unibox->db->begin_transaction();
			
			// get stack
			$stack = ub_stack::get_instance();
			$dataset = $stack->top();

			// insert translation
			$si_extension_descr = 'TRL_EXTENSION_'.strtoupper($this->unibox->session->env->form->extension_data->data->extension_ident);
			$this->unibox->delete_translation('TRL_EXTENSION_'.strtoupper($dataset->extension_ident), 'unibox');
	        if (!$this->unibox->insert_translation($si_extension_descr, $this->unibox->session->env->form->extension_data->data->extension_descr, 'unibox'))
		        throw new ub_exception_transaction('failed to insert translation');

			// drop all alias get values
			$sql_string =  'DELETE FROM
							  sys_extensions_get
							WHERE
							  extension_ident = \''.$dataset->extension_ident.'\'';
			$this->unibox->db->query($sql_string, 'failed to delete old getvars');

			$sql_string  = 'SELECT DISTINCT
							  entity_ident
							FROM
							  sys_sex
							WHERE
							  module_ident_to = \'alias\'
							  AND
							  entity_class = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
							ORDER BY
							  entity_detail_int ASC';
			$result = $this->unibox->db->query($sql_string, 'failed to get alias detail definition');
			while (list($entity_ident) = $result->fetch_row())
				if (isset($this->unibox->session->env->form->details_selector->data->$entity_ident))
				{
					$sql_string  = 'INSERT INTO
									  sys_extensions_get
									SET
									  extension_ident = \''.$dataset->extension_ident.'\',
									  name = \''.$entity_ident.'\',
									  value = \''.$this->unibox->session->env->form->details_selector->data->$entity_ident.'\'';
					$this->unibox->db->query($sql_string, 'failed to insert extension get var');
					if ($this->unibox->db->affected_rows() != 1)
						throw new ub_exception_transaction('failed to insert extension get var');
				}

			$sql_string  = 'UPDATE
							  sys_extensions
							SET
							  extension_ident = \''.$this->unibox->session->env->form->extension_data->data->extension_ident.'\',
							  si_extension_descr = \''.$this->unibox->db->cleanup($si_extension_descr).'\',
							  action_ident = \''.$this->unibox->session->env->form->action_selector->data->action_ident.'\'
							WHERE
							  extension_ident = \''.$dataset->extension_ident.'\'';
			$this->unibox->db->query($sql_string, 'failed to insert extension');

			$this->unibox->db->commit();

	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_EDIT_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_EDIT_FAILED');
            $stack->clear();
            
		}
		ub_form_creator::reset('module_selector');
		$this->unibox->switch_alias('alias_extension_administrate', true);
	}

    /**
    * delete()
    *
    * delete dataset
    */
    public function extension_delete()
    {
        // try if the user is allowed to delete aliases
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          a.extension_ident,
							  b.string_value
	                        FROM
	                          sys_extensions AS a
								INNER JOIN sys_translations AS b
								  ON
								  (
								  b.string_ident = a.si_extension_descr
								  AND
								  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								  )';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('extension_ident'));
                if (!$validator->validate('STACK', 'extension_ident', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('alias_extension_delete');
        else
            $stack->switch_to_administration();
    }

    /**
    * delete_confirm()
    *
    * delete dataset - show confirmation
    */
    public function extension_delete_confirm()
    {
        $stack = ub_stack::get_instance();
        
        // get alias data
        $sql_string  = 'SELECT
						  b.string_value
                        FROM
                          sys_extensions AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_extension_descr
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        WHERE
                          a.extension_ident IN (\''.implode('\', \'', $stack->get_stack('extension_ident')).'\')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_EXTENSION_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($descr) = $result->fetch_row())
                $msg->add_listentry($descr, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('alias_extension_delete', 'alias_extension_delete');
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'alias_extension_delete');
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'discard_all');
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        $msg->display();
    }

    /**
    * delete_process()
    *
    * delete dataset - execute
    */
    public function extension_delete_process()
    {
        $stack = ub_stack::get_instance();

        // delete all aliases
        $sql_string  = 'DELETE FROM
                          sys_extensions
                        WHERE
                          extension_ident IN (\''.implode('\', \'', $stack->get_stack('extension_ident')).'\')';
        $this->unibox->db->query($sql_string, 'failed to delete extension');
        if ($this->unibox->db->affected_rows() > 0)
        {
            foreach ($stack->get_stack('extension_ident') as $dataset)
                $this->unibox->log(LOG_ALTER, 'extension delete', $dataset);
            $msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_DELETE_SUCCESSFUL');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR, false);
            $msg->add_text('TRL_DELETE_FAILED');
        }
        $msg->display();
        ub_form_creator::reset('alias_extension_delete');
    }

}

?>