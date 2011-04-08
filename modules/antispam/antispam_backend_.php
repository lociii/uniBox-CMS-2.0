<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_antispam_backend
{
    /**
    * $version
    *
    * variable that contains the class version
    * 
    * @access   protected
    */
    const version = '0.1.0';

    /**
    * $instance
    *
    * instance of own class
    * 
    * @access   protected
    */
    private static $instance = NULL;

    /**
    * $unibox
    *
    * complete unibox framework
    * 
    * @access   protected
    */
    protected $unibox = NULL;

    /**
    * get_version()
    *
    * returns class version
    * 
    * @access   public
    * @return   float       version-number
    */
    public static function get_version()
    {
        return ub_antispam_backend::version;
    }

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      object of current class
    */
    public function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_antispam_backend;
        return self::$instance;
    }

    /**
    * __construct()
    *
    * session constructor - gets called everytime the objects get instantiated
    * 
    * @access   public
    */
    protected function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
    }

    /**
    * welcome()
    *
    * shows the welcome message
    */
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_ANTISPAM_WELCOME_TEXT');
        $msg->display();
        return 0;
    }

	public function antispam_group_add()
	{
		$validator = ub_validator::get_instance();
		return $validator->form_validate('antispam_group_add');
	}

	public function antispam_group_add_form()
	{
        // load template and add form information
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'antispam_group_add');
        $form = ub_form_creator::get_instance();
        $form->begin_form('antispam_group_add', $this->unibox->identify_alias('antispam_group_add'));
	}
	
	public function antispam_group_add_process()
	{
		try
		{
			$this->unibox->db->begin_transaction();
			
			$si_group_name = 'TRL_ANTISPAM_GROUP_NAME_'.strtoupper($this->unibox->session->env->form->antispam_group_add->data->antispam_group_ident);
			if (!$this->unibox->insert_translation($si_group_name, $this->unibox->session->env->form->antispam_group_add->data->si_group_name, 'unibox'))
	        	throw new ub_exception_transaction('failed to insert translations');

	        $sql_string  = 'INSERT INTO
	                          sys_antispam_groups
	                        SET
	                          antispam_group_ident = \''.$this->unibox->session->env->form->antispam_group_add->data->antispam_group_ident.'\',
	                          si_group_name = \''.$this->unibox->db->cleanup($si_group_name).'\'';
			$this->unibox->db->query($sql_string, 'failed to insert antispam group');
			if ($this->unibox->db->affected_rows() != 1)
				throw new ub_exception_transaction('failed to insert antispam group');

			$this->unibox->log(LOG_ALTER, 'antispam group add', $this->unibox->session->env->form->antispam_group_add->data->antispam_group_ident);

			$this->unibox->db->commit();
			$msg = new ub_message(MSG_SUCCESS);
        	$msg->add_text('TRL_ADD_SUCCESSFUL');
        	$msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ADD_FAILED');
		}
		ub_form_creator::reset('antispam_group_add');
		$this->unibox->switch_alias($this->unibox->identify_alias('antispam_group_administrate'), true);
	}

	public function antispam_group_edit()
	{
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          antispam_group_ident
	                        FROM
							  sys_antispam_groups';							

            $stack->reset();
            do
            {
                $stack->keep_keys(array('antispam_group_ident'));
                if (!$validator->validate('STACK', 'antispam_group_ident', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);
            $stack->validate();
        }

        if ($stack->is_valid())
		    return $validator->form_validate('antispam_group_edit');
        else
            $stack->switch_to_administration();
	}

	public function antispam_group_edit_refill()
	{
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        // refill the form
        $sql_string  = 'SELECT
                          si_group_name
                        FROM
                          sys_antispam_groups
                        WHERE
                          antispam_group_ident = \''.$dataset->antispam_group_ident.'\'';

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'antispam_group_edit');
        $form->begin_form('antispam_group_edit', $this->unibox->identify_alias('antispam_group_edit'));

        $form->set_values($sql_string, array('si_group_name'), true);
        $form->set_destructor('ub_stack', 'discard_top');

        // set content title
        $this->unibox->set_content_title('TRL_ANTISPAM_GROUP_EDIT', array($dataset->antispam_group_ident));
	}
	
	public function antispam_group_edit_process()
	{
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

		try
		{
			$this->unibox->db->begin_transaction();

			$si_group_name = 'TRL_ANTISPAM_GROUP_NAME_'.strtoupper($dataset->antispam_group_ident);
			if (!$this->unibox->insert_translation($si_group_name, $this->unibox->session->env->form->antispam_group_edit->data->si_group_name, 'unibox'))
	        	throw new ub_exception_transaction('failed to update translations');

			$this->unibox->log(LOG_ALTER, 'antispam group edit', $dataset->antispam_group_ident);

			$this->unibox->db->commit();
			$msg = new ub_message(MSG_SUCCESS);
        	$msg->add_text('TRL_EDIT_SUCCESSFUL');
        	$msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_EDIT_FAILED');
		}
		ub_form_creator::reset('antispam_group_edit');
	}

	public function antispam_group_delete()
	{
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          antispam_group_ident
	                        FROM
							  sys_antispam_groups';							

            $stack->reset();
            do
            {
                $stack->keep_keys(array('antispam_group_ident'));
                if (!$validator->validate('STACK', 'antispam_group_ident', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);
            $stack->validate();
        }

        if ($stack->is_valid())
		    return $validator->form_validate('antispam_group_delete');
        else
            $stack->switch_to_administration();
	}

	public function antispam_group_delete_confirm()
    {
        $stack = ub_stack::get_instance();
        
        $sql_string  = 'SELECT
                          b.string_value
                        FROM
                          sys_antispam_groups AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_group_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        WHERE
                          antispam_group_ident IN (\''.implode('\', \'', $stack->get_stack('antispam_group_ident')).'\')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_ANTISPAM_GROUP_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($name) = $result->fetch_row())
                $msg->add_listentry($name, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('antispam_group_delete', $this->unibox->identify_alias('antispam_group_delete'));
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', $this->unibox->identify_alias('antispam_group_delete'));
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

	public function antispam_group_delete_process()
	{
		try
		{
			$stack = ub_stack::get_instance();
			$this->unibox->db->begin_transaction();

	        // delete
	        $sql_string  = 'DELETE FROM
	                          sys_antispam_groups
	                        WHERE
	                          antispam_group_ident IN (\''.implode('\', \'', $stack->get_stack('antispam_group_ident')).'\')';
	        $this->unibox->db->query($sql_string, 'failed to delete datasets');
	        if ($this->unibox->db->affected_rows() > 0)
	            foreach ($stack->get_stack('antispam_group_ident') as $ident)
	                $this->unibox->log(LOG_ALTER, 'antispam group delete', $ident);

			// delete translations
			foreach ($stack->get_stack('antispam_group_ident') as $ident)
				if (!$this->unibox->delete_translation('TRL_ANTISPAM_GROUP_NAME_'.strtoupper($ident), 'unibox'))
					throw new ub_exception_transaction('failed to delete translation for antispam group '.$ident);

			$this->unibox->db->commit();
			$msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_DELETE_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_DELETE_FAILED');
		}
        ub_form_creator::reset('antispam_group_delete');
	}

	public function antispam_group_administrate()
	{
		$this->unibox->load_template('shared_administration');
		$admin = ub_administration_ng::get_instance('antispam_group_administrate');

        $sql_string  = 'SELECT
                          a.antispam_group_ident,
                          b.string_value
                        FROM
                          sys_antispam_groups AS a
                            INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_group_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )';

        // add header fields
        $admin->add_field('TRL_NAME', 'b.string_value');
        $admin->add_field('TRL_IDENT', 'a.antispam_group_ident');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
            $rights_edit = $this->unibox->session->has_right('antispam_group_edit');
            $rights_delete = $this->unibox->session->has_right('antispam_group_delete');
            while (list($ident, $name) = $result->fetch_row())
            {
                $admin->begin_dataset();

                $admin->add_dataset_ident('antispam_group_ident', $ident);
                $admin->set_dataset_descr($name);

                $admin->add_data($name);
				$admin->add_data($ident);
                
                if ($rights_edit)
                    $admin->add_option('container_edit_true.gif', 'TRL_ALT_EDIT', $this->unibox->identify_alias('antispam_group_edit'));
                else
                    $admin->add_option('container_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($rights_delete)
                    $admin->add_option('container_delete_true.gif', 'TRL_ALT_DELETE', $this->unibox->identify_alias('antispam_group_delete'));
                else
                    $admin->add_option('container_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');

                $admin->end_dataset();
            }
            $admin->add_multi_option('container_edit_true.gif', 'TRL_ALT_MULTI_EDIT', $this->unibox->identify_alias('antispam_group_edit'));
            $admin->add_multi_option('container_delete_true.gif', 'TRL_ALT_MULTI_DELETE', $this->unibox->identify_alias('antispam_group_delete'));
            $admin->set_multi_descr('TRL_ARTICLES');
        }
        $admin->show($this->unibox->identify_alias('antispam_group_administrate'));
	}

	public function antispam_group_form()
	{
		$form = ub_form_creator::get_instance();

		if ($this->unibox->session->env->system->action_ident == 'antispam_group_add')
		{
			$form->begin_fieldset('TRL_GENERAL');
	        $form->text('antispam_group_ident', 'TRL_IDENT', '', 40);
	        $form->set_condition(CHECK_NOTEMPTY);
	        $form->set_condition(CHECK_INRANGE, array(0, 50));
	        $form->set_condition(CHECK_PREG, '/^[a-z0-9_-]+$/i', 'TRL_ANTISPAM_INVALID_GROUP_IDENT');
	        $form->end_fieldset();
		}

        $form->text_multilanguage('si_group_name', 'TRL_NAME', '', 40);
        $form->set_condition_multilanguage(CHECK_NOTEMPTY);

        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'antispam_group_edit')
            $form->cancel('TRL_CANCEL_UCASE', $this->unibox->identify_alias('antispam_group_edit'));
        else
            $form->cancel('TRL_CANCEL_UCASE', $this->unibox->identify_alias('antispam_group_administrate'));
        $form->end_buttonset();
        $form->end_form();
	}

	public function antispam_add()
	{
		$validator = ub_validator::get_instance();
		return $validator->form_validate('antispam_add');
	}

	public function antispam_add_form()
	{
        // load template and add form information
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'antispam_add');
        $form = ub_form_creator::get_instance();
        $form->begin_form('antispam_add', $this->unibox->identify_alias('antispam_add'));
	}
	
	public function antispam_add_process()
	{
		try
		{
			$this->unibox->db->begin_transaction();
			
			$si_group_name = 'TRL_ANTISPAM_NAME_'.strtoupper($this->unibox->session->env->form->antispam_add->data->antispam_ident);
			if (!$this->unibox->insert_translation($si_group_name, $this->unibox->session->env->form->antispam_add->data->si_name, 'unibox'))
	        	throw new ub_exception_transaction('failed to insert translations');
		
	        $sql_string  = 'INSERT INTO
	                          sys_antispam
	                        SET
							  antispam_ident = \''.$this->unibox->session->env->form->antispam_add->data->antispam_ident.'\',
	                          antispam_group_ident = \''.$this->unibox->session->env->form->antispam_add->data->antispam_group_ident.'\',
	                          si_name = \''.$this->unibox->db->cleanup($si_group_name).'\'';
			$this->unibox->db->query($sql_string, 'failed to insert antispam entry');
			if ($this->unibox->db->affected_rows() != 1)
				throw new ub_exception_transaction('failed to insert antispam entry');

			$this->unibox->log(LOG_ALTER, 'antispam entry add', $this->unibox->session->env->form->antispam_add->data->antispam_group_ident);

			$this->unibox->db->commit();
			$msg = new ub_message(MSG_SUCCESS);
        	$msg->add_text('TRL_ADD_SUCCESSFUL');
        	$msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ADD_FAILED');
		}
		ub_form_creator::reset('antispam_add');
		$this->unibox->switch_alias($this->unibox->identify_alias('antispam_administrate'), true);
	}

	public function antispam_edit()
	{
		$validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          antispam_ident
	                        FROM
							  sys_antispam';							

            $stack->reset();
            do
            {
                $stack->keep_keys(array('antispam_ident'));
                if (!$validator->validate('STACK', 'antispam_ident', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);
            $stack->validate();
        }

        if ($stack->is_valid())
		    return $validator->form_validate('antispam_edit');
        else
            $stack->switch_to_administration();
	}

	public function antispam_edit_refill()
	{
		$stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        // refill the form
        $sql_string  = 'SELECT
                          si_name,
						  antispam_group_ident
                        FROM
                          sys_antispam
                        WHERE
                          antispam_ident = \''.$dataset->antispam_ident.'\'';

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'antispam_edit');
        $form->begin_form('antispam_edit', $this->unibox->identify_alias('antispam_edit'));
        
        $form->set_values($sql_string, array('si_name'), true);
        $form->set_destructor('ub_stack', 'discard_top');

        // set content title
        $this->unibox->set_content_title('TRL_ANTISPAM_EDIT', array($dataset->antispam_ident));
	}
	
	public function antispam_edit_process()
	{
		$stack = ub_stack::get_instance();
        $dataset = $stack->top();

		try
		{
			$this->unibox->db->begin_transaction();
			
			$si_group_name = 'TRL_ANTISPAM_NAME_'.strtoupper($dataset->antispam_ident);
			if (!$this->unibox->insert_translation($si_group_name, $this->unibox->session->env->form->antispam_edit->data->si_name, 'unibox'))
	        	throw new ub_exception_transaction('failed to update translations');

			$sql_string  = 'UPDATE
							  sys_antispam
							SET
							  antispam_group_ident = \''.$this->unibox->session->env->form->antispam_edit->data->antispam_group_ident.'\'
							WHERE
							  antispam_ident = \''.$dataset->antispam_ident.'\'';
			$this->unibox->db->query($sql_string, 'failed to update antispam entry');

			$this->unibox->log(LOG_ALTER, 'antispam entity edit', $dataset->antispam_ident);

			$this->unibox->db->commit();
			$msg = new ub_message(MSG_SUCCESS);
        	$msg->add_text('TRL_EDIT_SUCCESSFUL');
        	$msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_EDIT_FAILED');
		}
		ub_form_creator::reset('antispam_edit');
	}

	public function antispam_delete()
	{
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          antispam_ident
	                        FROM
							  sys_antispam';							

            $stack->reset();
            do
            {
                $stack->keep_keys(array('antispam_ident'));
                if (!$validator->validate('STACK', 'antispam_ident', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);
            $stack->validate();
        }

        if ($stack->is_valid())
		    return $validator->form_validate('antispam_delete');
        else
            $stack->switch_to_administration();
	}

	public function antispam_delete_confirm()
	{
		$stack = ub_stack::get_instance();
        
        $sql_string  = 'SELECT
                          b.string_value
                        FROM
                          sys_antispam AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        WHERE
                          antispam_ident IN (\''.implode('\', \'', $stack->get_stack('antispam_ident')).'\')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_ANTISPAM_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($name) = $result->fetch_row())
                $msg->add_listentry($name, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('antispam_delete', $this->unibox->identify_alias('antispam_delete'));
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', $this->unibox->identify_alias('antispam_delete'));
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
	
	public function antispam_delete_process()
	{
		try
		{
			$stack = ub_stack::get_instance();
			$this->unibox->db->begin_transaction();

	        // delete
	        $sql_string  = 'DELETE FROM
	                          sys_antispam
	                        WHERE
	                          antispam_ident IN (\''.implode('\', \'', $stack->get_stack('antispam_ident')).'\')';
	        $this->unibox->db->query($sql_string, 'failed to delete datasets');
	        if ($this->unibox->db->affected_rows() > 0)
	            foreach ($stack->get_stack('antispam_ident') as $ident)
	                $this->unibox->log(LOG_ALTER, 'antispam entry delete', $ident);

			// delete translations
			foreach ($stack->get_stack('antispam_ident') as $ident)
				if (!$this->unibox->delete_translation('TRL_ANTISPAM_NAME_'.strtoupper($ident), 'unibox'))
					throw new ub_exception_transaction('failed to delete translation for antispam entry '.$ident);

			$this->unibox->db->commit();
			$msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_DELETE_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_DELETE_FAILED');
		}
        ub_form_creator::reset('antispam_delete');
	}

	public function antispam_administrate()
	{
        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'antispam_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('antispam_administrate', $this->unibox->identify_alias('antispam_administrate'));

        $preselect = ub_preselect::get_instance('antispam_administrate');
        $preselect->add_field('antispam_group_ident', 'a.antispam_group_ident', true);
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
        $form->begin_select('antispam_group_ident', 'TRL_ANTISPAM_GROUP');
        $sql_string  = 'SELECT
                          a.antispam_group_ident,
                          b.string_value
                        FROM
                          sys_antispam_groups AS a
                            INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_group_name
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
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

        return $preselect->process();
	}
	
	public function antispam_administrate_show()
	{
		// get preselect
        $preselect = ub_preselect::get_instance('antispam_administrate');
        $admin = ub_administration_ng::get_instance('antispam_administrate');

        $sql_string  = 'SELECT
                          a.antispam_ident,
                          b.string_value
                        FROM
                          sys_antispam AS a
                            INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        WHERE
                          '.$preselect->get_string();

        // add header fields
        $admin->add_field('TRL_NAME', 'b.string_value');
        $admin->add_field('TRL_IDENT', 'a.antispam_ident');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
            $rights_edit = $this->unibox->session->has_right('antispam_edit');
            $rights_delete = $this->unibox->session->has_right('antispam_delete');
            while (list($ident, $name) = $result->fetch_row())
            {
                $admin->begin_dataset();

                $admin->add_dataset_ident('antispam_ident', $ident);
                $admin->set_dataset_descr($name);

				$admin->add_data($name);
				$admin->add_data($ident);

                if ($rights_edit)
                    $admin->add_option('container_edit_true.gif', 'TRL_ALT_EDIT', $this->unibox->identify_alias('antispam_edit'));
                else
                    $admin->add_option('container_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($rights_delete)
                    $admin->add_option('container_delete_true.gif', 'TRL_ALT_DELETE', $this->unibox->identify_alias('antispam_delete'));
                else
                    $admin->add_option('container_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');

                $admin->end_dataset();
            }
            $admin->add_multi_option('container_edit_true.gif', 'TRL_ALT_MULTI_EDIT', $this->unibox->identify_alias('antispam_edit'));
            $admin->add_multi_option('container_delete_true.gif', 'TRL_ALT_MULTI_DELETE', $this->unibox->identify_alias('antispam_delete'));
            $admin->set_multi_descr('TRL_ARTICLES');
        }
        $admin->show($this->unibox->identify_alias('antispam_administrate'));
	}

	public function antispam_form()
	{
		$form = ub_form_creator::get_instance();

		$form->begin_fieldset('TRL_GENERAL');
		if ($this->unibox->session->env->system->action_ident == 'antispam_add')
		{
	        $form->text('antispam_ident', 'TRL_IDENT', '', 40);
	        $form->set_condition(CHECK_NOTEMPTY);
	        $form->set_condition(CHECK_INRANGE, array(0, 50));
	        $form->set_condition(CHECK_PREG, '/^[a-z0-9_-]+$/i', 'TRL_ANTISPAM_INVALID_IDENT');
		}
		$form->begin_select('antispam_group_ident', 'TRL_ANTISPAM_GROUP');
        $sql_string  = 'SELECT
						  a.antispam_group_ident,
                          b.string_value
                        FROM
                          sys_antispam_groups AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_group_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						ORDER BY
						  b.string_value ASC';
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->end_fieldset();

        $form->text_multilanguage('si_name', 'TRL_NAME', '', 40);
        $form->set_condition_multilanguage(CHECK_NOTEMPTY);

        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'antispam_group_edit')
            $form->cancel('TRL_CANCEL_UCASE', $this->unibox->identify_alias('antispam_group_edit'));
        else
            $form->cancel('TRL_CANCEL_UCASE', $this->unibox->identify_alias('antispam_group_administrate'));
        $form->end_buttonset();
        $form->end_form();
	}

}

?>