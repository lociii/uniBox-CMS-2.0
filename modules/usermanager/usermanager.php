<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_usermanager
{ 
    /**
    * $version
    *
    * variable that contains the class version
    * 
    * @access   protected
    */
    const version = '0.1.1';

    /**
    * $instance
    *
    * instance of own class
    * 
    * @access   protected
    */
    private static $instance = null;

    /**
    * $unibox
    *
    * complete unibox framework
    * 
    * @access   protected
    */
    protected $unibox;

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
        return ub_usermanager::version;
    }

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      ub_news_frontend object
    */
    public static function get_instance()
    {
        if (self::$instance == null)
            self::$instance = new ub_usermanager;
        return self::$instance;
    }

    /**
    * __construct()
    *
    * session constructor - gets called everytime the objects get instantiated
    * 
    * @access   public
    */
    public function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->config->load_config('usermanager');
    }

    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_USERMANAGER_WELCOME_TEXT');
        $msg->display();
        return 0;
    }

	public function users_edit()
    {
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              user_id
                            FROM
                              sys_users';
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
            return (int)$validator->form_validate('usermanager_users_edit');
        else
            $stack->switch_to_administration();
    }
	
	public function users_edit_refill()
	{
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        // refill the form
		$sql_string =  'SELECT
						  user_name,
						  user_email,
						  user_active,
						  user_locale,
						  user_lang_ident,
                          user_notes,
                          user_timezone
						FROM
                          sys_users
						WHERE
						  user_id = \''.$dataset->user_id.'\'';
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'usermanager_users_edit');
        $form->begin_form('usermanager_users_edit', 'usermanager_users_edit');
        $values = $form->set_values($sql_string, array(), true);
        $form->set_destructor('ub_stack', 'discard_top');
        
        // set content title
        $this->unibox->set_content_title('TRL_USERS_EDIT', array($values['user_name']));
	}
	
	public function users_edit_process()
	{
        $this->unibox->db->begin_transaction();

		try
		{
	        $stack = ub_stack::get_instance();
	        $dataset = $stack->top();
	        
			$sql_string =  'UPDATE
	                          sys_users
	                        SET
							  user_active = \''.$this->unibox->session->env->form->usermanager_users_edit->data->user_active.'\',
							  user_name = \''.$this->unibox->session->env->form->usermanager_users_edit->data->user_name.'\',';
	
			if (isset($this->unibox->session->env->form->usermanager_users_edit->data->user_password) && $this->unibox->session->env->form->usermanager_users_edit->data->user_password != '')
				$sql_string .= 'user_password = \''.sha1($this->unibox->session->env->form->usermanager_users_edit->data->user_password).'\', ';
	
			$sql_string .= '  user_email = \''.$this->unibox->session->env->form->usermanager_users_edit->data->user_email.'\',
							  user_lang_ident = \''.$this->unibox->session->env->form->usermanager_users_edit->data->user_lang_ident.'\',
							  user_locale = \''.$this->unibox->session->env->form->usermanager_users_edit->data->user_locale.'\',
	                          user_notes = \''.$this->unibox->session->env->form->usermanager_users_edit->data->user_notes.'\',
	                          user_timezone = \''.$this->unibox->session->env->form->usermanager_users_edit->data->user_timezone.'\'
							WHERE
							  user_id = \''.$dataset->user_id.'\'';
			$this->unibox->db->query($sql_string, 'failed to update user');
	        if ($this->unibox->db->affected_rows() == 0)
	        	throw new ub_exception_transaction();

			// change local data in all sessions
			$this->unibox->session->set_locale($this->unibox->session->env->form->usermanager_users_edit->data->user_locale);

	        $this->unibox->db->commit();
	        $msg = new ub_message(MSG_SUCCESS, false);
	        $msg->add_text('TRL_EDIT_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
	            $this->unibox->db->rollback('TRL_EDIT_FAILED');
	            $stack->clear();
		}

        ub_form_creator::reset('usermanager_users_edit');
        $this->unibox->switch_alias('usermanager_users_administrate', true);
	}

	public function users_form()
	{
        if ($this->unibox->session->env->system->action_ident == 'usermanager_users_edit')
        {
            $stack = ub_stack::get_instance();
            $dataset = $stack->top();
        }

		$form = ub_form_creator::get_instance();
		$form->begin_fieldset('TRL_GENERAL');

		$form->text('user_name', 'TRL_USER_NAME', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_INRANGE, array(3, 200));
        $form->set_condition(CHECK_PREG, '/^[A-Za-z0-9äöüß\.\-_ ]+$/', 'TRL_USER_NAME_CONTAINS_INVALID_CHARS');
        if ($this->unibox->config->system->user_names_unique)
    		if ($this->unibox->session->env->system->action_ident == 'usermanager_users_edit')
                $form->set_condition(CHECK_NOTINSET_SQL, 'SELECT user_name FROM sys_users WHERE user_id != \''.$dataset->user_id.'\'', 'TRL_ERR_USER_NAME_ALREADY_IN_USE');
            else
                $form->set_condition(CHECK_NOTINSET_SQL, 'SELECT user_name FROM sys_users', 'TRL_ERR_USER_NAME_ALREADY_IN_USE');

		$form->text('user_email', 'TRL_EMAIL', '', 40);
        if ($this->unibox->session->env->system->action_ident == 'usermanager_users_edit' && $dataset->user_id == 1)
            $form->set_disabled();
        else
        {
            $form->set_condition(CHECK_NOTEMPTY);
            $form->set_condition(CHECK_INRANGE, array(0, 255));
            $form->set_condition(CHECK_EMAIL);
            
    		if ($this->unibox->session->env->system->action_ident == 'usermanager_users_edit')
                $form->set_condition(CHECK_NOTINSET_SQL, 'SELECT user_email FROM sys_users WHERE user_id != \''.$dataset->user_id.'\'', 'TRL_ERR_EMAIL_ADDRESS_ALREADY_IN_USE');
            else
                $form->set_condition(CHECK_NOTINSET_SQL, 'SELECT user_email FROM sys_users', 'TRL_ERR_EMAIL_ADDRESS_ALREADY_IN_USE');
        }

        if ($this->unibox->session->env->system->action_ident == 'usermanager_users_add')
		  $form->checkbox('user_active', 'TRL_USER_ACTIVE', true);
        $form->textarea('user_notes', 'TRL_USER_NOTES', '', 55, 15);
        $form->set_condition(CHECK_INRANGE, array(0, 65535));
		$form->end_fieldset();
		$form->begin_fieldset('TRL_PASSWORD');
		$form->password('user_password', 'TRL_PASSWORD');
		if ($this->unibox->session->env->system->action_ident == 'usermanager_users_add')
			$form->set_condition(CHECK_NOTEMPTY);
        elseif ($this->unibox->session->env->system->action_ident == 'usermanager_users_edit' && $dataset->user_id == 1)
            $form->set_disabled();
		if (!isset($dataset) || $dataset->user_id != 1)
	        $form->set_condition(CHECK_INRANGE, array($this->unibox->config->system->password_min_length, $this->unibox->config->system->password_max_length));
		$form->password('user_password_rpt', 'TRL_PASSWORD_REPETITION');
		if ($this->unibox->session->env->system->action_ident == 'usermanager_users_add')
			$form->set_condition(CHECK_NOTEMPTY);
        elseif ($this->unibox->session->env->system->action_ident == 'usermanager_users_edit' && $dataset->user_id == 1)
            $form->set_disabled();
		if (!isset($dataset) || $dataset->user_id != 1)
		{
	        $form->set_condition(CHECK_INRANGE, array($this->unibox->config->system->password_min_length, $this->unibox->config->system->password_max_length));
			$form->set_condition(CHECK_EQUAL, 'user_password');
		}
		$form->end_fieldset();
		$form->begin_fieldset('TRL_REGION_AND_LANGUAGE_SETTINGS');
		$sql_string =  'SELECT
						  a.locale,
						  b.string_value
						FROM sys_locales AS a
						  INNER JOIN sys_translations AS b
							ON
                            (
                            b.string_ident = a.si_locale_descr
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
						ORDER BY
						  b.string_value';
		$form->begin_select('user_locale', 'TRL_LOCALE');
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);

		$sql_string =  'SELECT
						  a.lang_ident,
						  b.string_value
						FROM sys_languages AS a
						  INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_lang_descr
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
						ORDER BY
						  b.string_value';
		$form->begin_select('user_lang_ident', 'TRL_LANGUAGE');
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);

        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_USER);
        $form->begin_select('user_timezone', 'TRL_TIMEZONE');
        $time->fill_timezone_form($form, $this->unibox->session->timezone);
        $form->end_select();
        $form->set_condition(CHECK_NOTEMPTY);

		$form->end_fieldset();
		$form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
		if ($this->unibox->session->env->system->action_ident == 'usermanager_users_edit')
			$form->cancel('TRL_CANCEL_UCASE', 'usermanager_users_edit');
        else
			$form->cancel('TRL_CANCEL_UCASE', 'usermanager_users_administrate');
		$form->end_buttonset();
		$form->end_form();
	}

	public function users_administrate()
	{
        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'usermanager_users_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('usermanager_users_administrate', 'usermanager_users_administrate');

        $preselect = ub_preselect::get_instance('usermanager_users_administrate');
        $preselect->add_field('user_name', 'user_name');
        $preselect->add_field('user_email', 'user_email');
        $preselect->add_field('user_show_deactivated', null, true);
        $preselect->add_field('user_show_locked', null, true);
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
        $form->text('user_name', 'TRL_NAME', '', 30);
        $form->text('user_email', 'TRL_EMAIL', '', 30);
        $form->checkbox('user_show_deactivated', 'TRL_SHOW_DEACTIVATED_USERS', '', 30);
        $form->checkbox('user_show_locked', 'TRL_SHOW_LOCKED_USERS', '', 30);
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

        return $preselect->process();
    }

    public function users_administrate_show_content()
    {
        // get preselect
        $preselect = ub_preselect::get_instance('usermanager_users_administrate');
        $admin = ub_administration_ng::get_instance('usermanager_users_administrate');

        $sql_string  = 'SELECT
                          user_id,
                          user_locked,
						  user_active,
						  user_name,
						  user_email
                        FROM
                          sys_users
                        WHERE
						  user_id > 0
						  AND
                          '.$preselect->get_string();

        if ($preselect->get_value('user_show_deactivated') != '1')
            $sql_string .= ' AND user_active = 1';

        if ($preselect->get_value('user_show_locked') != '1')
            $sql_string .= ' AND user_locked = 0';

        $admin->add_field('TRL_NAME', 'user_name');
        $admin->add_field('TRL_EMAIL', 'user_email');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
			$rights_edit = $this->unibox->session->has_right('usermanager_users_edit');
			$rights_activate = $this->unibox->session->has_right('usermanager_users_activate');
			$rights_deactivate = $this->unibox->session->has_right('usermanager_users_deactivate');
            $rights_lock = $this->unibox->session->has_right('usermanager_users_lock');
            $rights_unlock = $this->unibox->session->has_right('usermanager_users_unlock');
			$rights_assign_groups = $this->unibox->session->has_right('usermanager_users_assign_groups');
            $rights_edit_rights = $this->unibox->session->has_right('usermanager_users_rights');
            $rights_edit_category_rights = $this->unibox->session->has_right('usermanager_users_category_rights');
            $rights_info = $this->unibox->session->has_right('usermanager_users_info');
            $rights_user_config = $this->unibox->session->has_right('user_config_configure_foreign');
            while (list($user_id, $user_locked, $user_active, $user_name, $user_email) = $result->fetch_row())
            {
            	$admin->begin_dataset();
            	
                $admin->add_data($user_name);
                $admin->add_data($user_email);

                $admin->add_dataset_ident('user_id', $user_id);
                $admin->set_dataset_descr($user_name);

				if ($rights_info)
                    $admin->add_option('info_true.gif', 'TRL_ALT_INFO', 'usermanager_users_info');
                else
                    $admin->add_option('info_false.gif', 'TRL_ALT_INFO_FORBIDDEN');

				if ($rights_assign_groups)
					$admin->add_option('user_assign_groups_true.gif', 'TRL_ALT_GROUPS_ASSIGN', 'usermanager_users_assign_groups');
				else
					$admin->add_option('user_assign_groups_false.gif', 'TRL_ALT_GROUPS_ASSIGN_FORBIDDEN');

                if ($rights_edit)
                    $admin->add_option('user_edit_true.gif', 'TRL_ALT_EDIT', 'usermanager_users_edit');
                else
                    $admin->add_option('user_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($rights_user_config && $user_id > 1)
                    $admin->add_option('user_config_true.gif', 'TRL_ALT_EDIT_USER_CONFIG', 'user_config_configure_foreign');
                else
                    $admin->add_option('user_config_false.gif', 'TRL_ALT_EDIT_USER_CONFIG_FORBIDDEN');

                if ($user_id > 1)
                {
                    if ($user_active)
                    {
                        if ($rights_deactivate)
                            $admin->add_option('activate_deactivate_true.gif', 'TRL_ALT_DEACTIVATE', 'usermanager_users_deactivate');
                        else
                            $admin->add_option('activate_false.gif', 'TRL_ALT_DEACTIVATE_FORBIDDEN');
                    }
                    else
                    {
                        if ($rights_activate)
                            $admin->add_option('activate_true.gif', 'TRL_ALT_ACTIVATE', 'usermanager_users_activate');
                        else
                            $admin->add_option('activate_false.gif', 'TRL_ALT_ACTIVATE_FORBIDDEN');
                    }
                }
                else
                    $admin->add_option('activate_false.gif', 'TRL_ALT_DEACTIVATE_FORBIDDEN_DEFAULT_USER');

                if ($user_id > 1)
                {
                    if ($user_locked)
                    {
                        if ($rights_unlock)
                            $admin->add_option('lock_unlock_true.gif', 'TRL_ALT_UNLOCK', 'usermanager_users_unlock');
                        else
                            $admin->add_option('lock_false.gif', 'TRL_ALT_UNLOCK_FORBIDDEN');
                    }
                    else
                    {
                        if ($rights_lock)
                            $admin->add_option('lock_true.gif', 'TRL_ALT_LOCK', 'usermanager_users_lock');
                        else
                            $admin->add_option('lock_false.gif', 'TRL_ALT_LOCK_FORBIDDEN');
                    }
                }
                else
                    $admin->add_option('lock_false.gif', 'TRL_ALT_LOCK_FORBIDDEN_DEFAULT_USER');

                $admin->end_dataset();
            }
            $admin->add_multi_option('lock_unlock_true.gif', 'TRL_ALT_MULTI_UNLOCK', 'usermanager_users_unlock');
            $admin->add_multi_option('user_assign_groups_true.gif', 'TRL_ALT_MULTI_GROUPS_ASSIGN', 'usermanager_users_assign_groups');
            $admin->add_multi_option('user_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'usermanager_users_edit');
            $admin->add_multi_option('activate_true.gif', 'TRL_ALT_MULTI_ACTIVATE', 'usermanager_users_activate');
            $admin->add_multi_option('activate_deactivate_true.gif', 'TRL_ALT_MULTI_DEACTIVATE', 'usermanager_users_deactivate');
            $admin->add_multi_option('lock_true.gif', 'TRL_ALT_MULTI_LOCK', 'usermanager_users_lock');
            $admin->set_multi_descr('TRL_USERS');
        }
        $admin->show('usermanager_users_administrate');
    }

	public function users_info()
	{
		$validator = ub_validator::get_instance();
        $sql_string  = 'SELECT
                          user_id
                        FROM
                          sys_users';
        if ($validator->validate('GET', 'user_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
        	return 1;
       	else
       		$this->unibox->display_error();
	}

	public function users_info_show()
	{
		$sql_string  = 'SELECT
						  user_name,
						  user_regdate,
						  user_notes,
						  user_last_access
						FROM
						  sys_users
						WHERE
						  user_id = '.$this->unibox->session->env->input->user_id;
		$result = $this->unibox->db->query($sql_string, 'failed to select user data');
		if ($result->num_rows() != 1)
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
			$msg->display();
			return;
		}
		list($user_name, $user_regdate, $user_notes, $user_last_access) = $result->fetch_row();
		$time = new ub_time(TIME_TYPE_DB, TIME_TYPE_USER);
		$msg = new ub_message(MSG_INFO);
		$msg->begin_summary();
		$msg->add_summary_content('TRL_USER_NAME', $user_name);
		if ($user_regdate == '0000-00-00 00:00:00')
			$msg->add_summary_content('TRL_USER_REGDATE', 'TRL_UNKNOWN', true);
		else
		{
			$time->reset();
			$time->parse_datetime($user_regdate);
			$msg->add_summary_content('TRL_USER_REGDATE', $time->get_datetime());
		}
		$time->reset();
		$time->parse_datetime($user_last_access);
		$msg->add_summary_content('TRL_USER_LAST_ACCESS', $time->get_datetime());
		$msg->add_newline();
		if (empty($user_notes))
			$msg->add_summary_content('TRL_USER_NOTES', 'TRL_NO_USER_NOTES', true);
		else
			$msg->add_summary_content('TRL_USER_NOTES', $user_notes);
		$msg->end_summary();

		$msg->add_newline(2);
		$msg->add_link('usermanager_users_administrate', 'TRL_BACK_TO_OVERVIEW');
		$msg->display();
	}

	public function users_add()
	{
		$validator = ub_validator::get_instance();
	    return $validator->form_validate('usermanager_users_add');
	}
	
	public function users_add_form()
	{
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_ADD_USER', true);
        $this->unibox->xml->add_value('form_name', 'usermanager_users_add');
		$form = ub_form_creator::get_instance();
		$form->begin_form('usermanager_users_add', 'usermanager_users_add');
	}

	public function users_add_process()
	{
        $this->unibox->db->begin_transaction();

		try
		{
			$sql_string  = 'SELECT
							  user_output_format_ident
							FROM
							  sys_users
							WHERE
							  user_id = 1';
			$result = $this->unibox->db->query($sql_string, 'failed to select default output format');
			if ($result->num_rows() != 1)
				throw new ub_exception_transaction();
			list($output_format_ident) = $result->fetch_row();
	
	        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
	        $time->now();
			$sql_string =  'INSERT INTO
	                          sys_users
	                        SET
							  user_active = '.$this->unibox->session->env->form->usermanager_users_add->data->user_active.',
							  user_name = \''.$this->unibox->session->env->form->usermanager_users_add->data->user_name.'\',
							  user_password = \''.sha1($this->unibox->session->env->form->usermanager_users_add->data->user_password).'\',
							  user_email = \''.$this->unibox->session->env->form->usermanager_users_add->data->user_email.'\',
							  user_regdate = \''.$time->get_datetime().'\',
							  user_lang_ident = \''.$this->unibox->session->env->form->usermanager_users_add->data->user_lang_ident.'\',
	                          user_notes = \''.$this->unibox->session->env->form->usermanager_users_add->data->user_notes.'\',
	                          user_timezone = \''.$this->unibox->session->env->form->usermanager_users_add->data->user_timezone.'\',
							  user_locale = \''.$this->unibox->session->env->form->usermanager_users_add->data->user_locale.'\',
							  user_output_format_ident = \''.$output_format_ident.'\'';
			if (!$this->unibox->db->query($sql_string, 'failed to insert user') || $this->unibox->db->affected_rows() != 1)
		        throw new ub_exception_transaction();

	        $this->unibox->db->commit();
			$msg = new ub_message(MSG_SUCCESS);
			$msg->add_text('TRL_ADD_SUCCESS');
			$msg->add_newline(2);
	        $msg->add_link('usermanager_users_assign_groups/user_id/'.$this->unibox->db->last_insert_id(), 'TRL_ASSIGN_GROUPS');
	        $msg->add_newline(2);
			$msg->add_link('usermanager_users_add', 'TRL_INSERT_ANOTHER_DATASET');
			$msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ADD_FAILED');
		}

        ub_form_creator::reset('usermanager_users_add');
        $this->unibox->switch_alias('usermanager_users_administrate', true);
	}

    public function users_activate()
    {
        // try if the given user exists
        $validator = ub_validator::get_instance();
        $sql_string  = 'SELECT
                          user_id
                        FROM
                          sys_users
                        WHERE
                          user_active = 0';

        $stack = ub_stack::get_instance();
        $validator->reset();
        if (!$stack->is_valid())
        {
            $stack->reset();
            do
            {
                $stack->keep_keys(array('user_id'));
                if (!$validator->validate('STACK', 'user_id', TYPE_INTEGER, CHECK_INSET_SQL, null, $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('usermanager_users_activate');
        else
        {
            $this->unibox->error('TRL_ERR_HIGHLIGHTED_USERS_ALREADY_ACTIVE');
            $stack->switch_to_administration();
        }
    }

    public function users_activate_confirm()
    {
        $stack = ub_stack::get_instance();
        
        // get usernames to ask for confirmation
        $sql_string  = 'SELECT
                          user_name
                        FROM
                          sys_users
                        WHERE
                          user_id IN ('.implode(', ', $stack->get_stack('user_id')).')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm activation');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_USERS_ACTIVATE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($username) = $result->fetch_row())
                $msg->add_listentry($username, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('usermanager_users_activate', 'usermanager_users_activate');
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'usermanager_users_administrate');
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'kill_instance');
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        $msg->display();
    }
    
    public function users_activate_process()
    {
        $this->unibox->db->begin_transaction();
        $stack = ub_stack::get_instance();

        $sql_string =  'UPDATE
                          sys_users
                        SET
                          user_active = 1
                        WHERE
                          user_id IN ('.implode(', ', $stack->get_stack('user_id')).')';
        if (!($result = $this->unibox->db->query($sql_string, 'failed to activate users')) || $this->unibox->db->affected_rows() == 0)
        {
            $this->unibox->db->rollback('TRL_USERS_ACTIVATE_FAILED');
            $stack->clear();
            return;
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_USERS_ACTIVATE_SUCCESS');
        $msg->display();
        $stack->clear();
    }
    
    public function users_deactivate()
    {
        // try if the given user exists
        $validator = ub_validator::get_instance();
        $sql_string  = 'SELECT
                          user_id
                        FROM
                          sys_users
                        WHERE
                          user_active = 1
                          AND
                          user_id != 1';

        $stack = ub_stack::get_instance();
        $validator->reset();
        if (!$stack->is_valid())
        {
            $stack->reset();
            do
            {
                $stack->keep_keys(array('user_id'));
                if (!$validator->validate('STACK', 'user_id', TYPE_INTEGER, CHECK_INSET_SQL, null, $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);
            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('usermanager_users_deactivate');
        else
        {
            $this->unibox->error('TRL_ERR_HIGHLIGHTED_USERS_ALREADY_INACTIVE');
            $stack->switch_to_administration();
        }
    }

    public function users_deactivate_confirm()
    {
        $stack = ub_stack::get_instance();
        
        // get usernames to ask for confirmation
        $sql_string  = 'SELECT
                          user_name
                        FROM
                          sys_users
                        WHERE
                          user_id IN ('.implode(', ', $stack->get_stack('user_id')).')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm activation');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_USERS_DEACTIVATE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($username) = $result->fetch_row())
                $msg->add_listentry($username, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('usermanager_users_deactivate', 'usermanager_users_deactivate');
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'usermanager_users_administrate');
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'kill_instance');
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        $msg->display();
    }
    
    public function users_deactivate_process()
    {
        $this->unibox->db->begin_transaction();
        $stack = ub_stack::get_instance();

        $sql_string =  'UPDATE
                          sys_users
                        SET
                          user_active = 0
                        WHERE
                          user_id IN ('.implode(', ', $stack->get_stack('user_id')).')';
        if (!($result = $this->unibox->db->query($sql_string, 'failed to deactivate users')) || $this->unibox->db->affected_rows() == 0)
        {
            $this->unibox->db->rollback('TRL_USERS_DEACTIVATE_FAILED');
            $stack->clear();
            return;
        }

        $sql_string =  'DELETE FROM
                          sys_sessions
                        WHERE
                          session_user_id IN ('.implode(', ', $stack->get_stack('user_id')).')';
        if (!$this->unibox->db->query($sql_string, 'failed to delete sessions of deactivated users'))
        {
            $this->unibox->db->rollback('TRL_USERS_DEACTIVATE_FAILED');
            $stack->clear();
            return;
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_USERS_DEACTIVATE_SUCCESS');
        $msg->display();
        $stack->clear();
    }

    public function users_lock()
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
                              user_locked = 0
                              AND
                              user_id != 1';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('user_id'));
                if (!$validator->validate('STACK', 'user_id', TYPE_INTEGER, CHECK_INSET_SQL, null, $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('usermanager_users_lock');
        else
        {
            $this->unibox->error('TRL_ERR_HIGHLIGHTED_USERS_ALREADY_LOCKED');
            $stack->switch_to_administration();
        }
    }

    public function users_lock_refill()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        // refill the form
        $sql_string =  'SELECT
                          user_notes,
                          user_name
                        FROM
                          sys_users
                        WHERE
                          user_id = \''.$dataset->user_id.'\'';
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'usermanager_users_lock');
        $form->begin_form('usermanager_users_lock', 'usermanager_users_lock');
        $values = $form->set_values($sql_string, array(), true);
        $form->set_destructor('ub_stack', 'discard_top');

        // set content title
        $this->unibox->set_content_title('TRL_USERS_LOCK', array($values['user_name']));
    }

    public function users_lock_process()
    {
        $this->unibox->db->begin_transaction();
        
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string =  'UPDATE
                          sys_users
                        SET
                          user_locked = 1,
                          user_notes = \''.$this->unibox->session->env->form->usermanager_users_lock->data->user_notes.'\'
                        WHERE
                          user_id = '.$dataset->user_id;
        if (!$this->unibox->db->query($sql_string, 'failed to lock user') || $this->unibox->db->affected_rows() != 1)
        {
            $this->unibox->db->rollback('TRL_LOCK_FAILED');
            ub_form_creator::reset('usermanager_users_lock');
            return;
        }

        $sql_string =  'DELETE FROM
                          sys_sessions
                        WHERE
                          session_user_id = '.$dataset->user_id;
        if (!$this->unibox->db->query($sql_string, 'failed to delete session of locked user'))
        {
            $this->unibox->db->rollback('TRL_LOCK_FAILED');
            ub_form_creator::reset('usermanager_users_lock');
            return;
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_LOCK_SUCCESSFUL');
        $msg->display();

        ub_form_creator::reset('usermanager_users_lock');
    }

    public function users_unlock()
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
                              user_locked = 1';
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
            return (int)$validator->form_validate('usermanager_users_unlock');
        else
        {
            $this->unibox->error('TRL_ERR_HIGHLIGHTED_USERS_ALREADY_UNLOCKED');
            $stack->switch_to_administration();
        }
    }

    public function users_unlock_refill()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        // refill the form
        $sql_string =  'SELECT
                          user_notes,
                          user_name
                        FROM
                          sys_users
                        WHERE
                          user_id = \''.$dataset->user_id.'\'';
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'usermanager_users_unlock');
        $form->begin_form('usermanager_users_unlock', 'usermanager_users_unlock');
        $values = $form->set_values($sql_string, array(), true);
        $form->set_destructor('ub_stack', 'discard_top');

        // set content title
        $this->unibox->set_content_title('TRL_USERS_UNLOCK', array($values['user_name']));
    }

    public function users_unlock_process()
    {
        $this->unibox->db->begin_transaction();
        
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string =  'UPDATE
                          sys_users
                        SET
                          user_locked = 0,
                          user_notes = \''.$this->unibox->session->env->form->usermanager_users_unlock->data->user_notes.'\'
                        WHERE
                          user_id = '.$dataset->user_id;
        if (!$this->unibox->db->query($sql_string, 'failed to unlock user') || $this->unibox->db->affected_rows() != 1)
        {
            $this->unibox->db->rollback('TRL_UNLOCK_FAILED');
            ub_form_creator::reset('usermanager_users_unlock');
            return;
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_UNLOCK_SUCCESSFUL');
        $msg->display();

        ub_form_creator::reset('usermanager_users_unlock');
    }

    public function users_lock_form()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $form = ub_form_creator::get_instance();
        $form->begin_fieldset('TRL_GENERAL');
        $form->textarea('user_notes', 'TRL_USER_NOTES', '', 55, 15);
        $form->end_fieldset();
        $form->begin_buttonset();
        if ($this->unibox->session->env->system->action_ident == 'usermanager_users_lock')
        {
            $form->submit('TRL_LOCK_UCASE');
            $form->cancel('TRL_CANCEL_UCASE', 'usermanager_users_lock');
        }
        else
        {
            $form->submit('TRL_UNLOCK_UCASE');
            $form->cancel('TRL_CANCEL_UCASE', 'usermanager_users_unlock');
        }
        $form->end_buttonset();
        $form->end_form();
    }

    public function users_assign_groups()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              user_id
                            FROM
                              sys_users';
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
            return (int)$validator->form_validate('usermanager_users_assign_groups');
        else
            $stack->switch_to_administration();
    }
    
    public function users_assign_groups_form()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'usermanager_users_assign_groups');
        $form = ub_form_creator::get_instance();
        $form->begin_form('usermanager_users_assign_groups', 'usermanager_users_assign_groups');

        $sql_string =  'SELECT
                          a.group_id,
                          b.string_value,
                          c.user_id,
                          c.begin,
                          c.end
                        FROM sys_groups AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_group_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          LEFT JOIN sys_user_groups AS c
                            ON
                              c.group_id = a.group_id
                              AND
                              (
                              c.user_id = \''.$dataset->user_id.'\'
                              OR
                              c.user_id IS NULL
                              )
                        WHERE
                          a.group_id > 1
                          AND
                          a.group_id IN (\''.implode('\', \'', $this->unibox->session->group_ids).'\')
                        ORDER BY
                          b.string_value';
        $result = $this->unibox->db->query($sql_string, 'failed to select groups');
        $time = new ub_time(TIME_TYPE_DB, TIME_TYPE_USER);
        while (list($group_id, $group_name, $is_member, $begin, $end) = $result->fetch_row())
        {
            $form->set_translation(false);
            $form->begin_fieldset($group_name);
            $form->set_translation(true);
            $is_member = !($is_member == NULL);
            $form->checkbox('group_ids_'.$group_id, 'TRL_IS_MEMBER', $is_member);

            if ($begin !== null)
            {
                $time->parse_datetime($begin);
                $begin = $time->get_date();
            }
            $form->text('group_ids_begin_'.$group_id, 'TRL_MEMBERSHIP_BEGIN', $begin);
            $form->set_type(TYPE_DATE);

            if ($end !== null)
            {
                $time->parse_datetime($end);
                $end = $time->get_date();
            }
            $form->text('group_ids_end_'.$group_id, 'TRL_MEMBERSHIP_END', $end);
            $form->set_type(TYPE_DATE);
            
            $form->end_fieldset();
        }
        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'usermanager_users_assign_groups');
        $form->end_buttonset();
        $form->end_form();
        $form->set_destructor('ub_stack', 'discard_top');
        
        // set content title
        $sql_string =  'SELECT
                          user_name
                        FROM
                          sys_users
                        WHERE
                          user_id = \''.$dataset->user_id.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select username');
        if ($result->num_rows() == 1)
        {
            list($user_name) = $result->fetch_row();
            $this->unibox->set_content_title('TRL_USERS_ASSIGN_GROUPS', array($user_name));
        }
    }
    
    public function users_assign_groups_process()
    {
        $this->unibox->db->begin_transaction();
        
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string =  'DELETE FROM
                          sys_user_groups
                        WHERE
                          user_id = '.$dataset->user_id;
        if (!($result = $this->unibox->db->query($sql_string, 'failed to drop all existant group assignments')))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED');
            ub_form_creator::reset('usermanager_users_assign_groups');
            return;
        }

        $sql_string =  'SELECT
                          group_id
                        FROM sys_groups AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_group_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                        WHERE
                          a.group_id > 1
                          AND
                          a.group_id IN (\''.implode('\', \'', $this->unibox->session->group_ids).'\')';
        if (!($result = $this->unibox->db->query($sql_string, 'failed to select possible groups')))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED');
            ub_form_creator::reset('usermanager_users_assign_groups');
            return;
        }

        $time = new ub_time(TIME_TYPE_USER, TIME_TYPE_DB);
        $datasets = array();

        while (list($group_id) = $result->fetch_row())
        {
            $string = 'group_ids_'.$group_id;
            if ($this->unibox->session->env->form->usermanager_users_assign_groups->data->$string)
            {
                // check for time limitation
                $time->reset();
                $string = 'group_ids_begin_'.$group_id;
                if (isset($this->unibox->session->env->form->usermanager_users_assign_groups->data->$string) && ($time->parse_date($this->unibox->session->env->form->usermanager_users_assign_groups->data->$string)))
                    $begin = '\''.$time->get_datetime().'\'';
                else
                    $begin = 'NULL';

                $time->reset();
                $string = 'group_ids_end_'.$group_id;
                if (isset($this->unibox->session->env->form->usermanager_users_assign_groups->data->$string) && ($time->parse_date($this->unibox->session->env->form->usermanager_users_assign_groups->data->$string)))
                    $end = '\''.$time->get_datetime().'\'';
                else
                    $end = 'NULL';

                $datasets[] = '('.$dataset->user_id.', '.$group_id.', '.$begin.', '.$end.')';
            }
        }

        if (count($datasets) > 0)
        {
            // build sql string to insert with a single query
            $sql_string =  'INSERT INTO
                              sys_user_groups
                            (user_id, group_id, begin, end)
                            VALUES 
                              '.implode(', ', $datasets);
            if (!$this->unibox->db->query($sql_string, 'failed to insert group assignments') || $this->unibox->db->affected_rows() == 0)
            {
                $this->unibox->db->rollback('TRL_EDIT_FAILED');
                ub_form_creator::reset('usermanager_users_assign_groups');
                return;
            }
        }

        // update sessions
        $sql_string =  'UPDATE
                          sys_sessions
                        SET
                          rights = \'\'
                        WHERE
                          session_user_id = '.$dataset->user_id;
        if (!$this->unibox->db->query($sql_string, 'failed to update sessions'))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED');
            ub_form_creator::reset('usermanager_users_assign_groups');
            return;
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_EDIT_SUCCESSFUL');
        $msg->display();
        ub_form_creator::reset('usermanager_users_assign_groups');
    }

    //
    // GROUP-FUNCTIONS
    //

    public function groups_administrate()
    {
        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'usermanager_groups_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('usermanager_groups_administrate', 'usermanager_groups_administrate');

        $preselect = ub_preselect::get_instance('usermanager_groups_administrate');
        $preselect->add_field('group_name', 'b.string_value');
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
        $form->text('group_name', 'TRL_NAME', '', 30);
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

        return $preselect->process();
    }

    public function groups_administrate_show_content()
    {
        // get preselect
        $preselect = ub_preselect::get_instance('usermanager_groups_administrate');
        $admin = ub_administration_ng::get_instance('usermanager_groups_administrate');

        $sql_string  = 'SELECT
                          a.group_id,
                          b.string_value,
                          c.string_value,
                          a.group_show
                        FROM
                          sys_groups AS a
                            INNER JOIN sys_translations AS b
                              ON
                              (
                              b.string_ident = a.si_group_name
                              AND
                              b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              )
                            LEFT JOIN sys_translations AS c
                              ON
                               (
                               c.string_ident = a.si_group_descr
                               AND
                               c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                               )
                        WHERE
                          '.$preselect->get_string();

        // add header fields
        $admin->add_field('TRL_NAME', 'b.string_value');
        $admin->add_field('TRL_DESCR', 'c.string_value');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
            $rights_edit = $this->unibox->session->has_right('usermanager_groups_edit');
            $rights_delete = $this->unibox->session->has_right('usermanager_groups_delete');
            $rights_edit_inheritance = $this->unibox->session->has_right('usermanager_groups_inheritance');
            $rights_edit_rights = $this->unibox->session->has_right('usermanager_groups_rights');
            $rights_edit_category_rights = $this->unibox->session->has_right('usermanager_groups_category_rights');
            while (list($group_id, $group_name, $group_descr, $group_show) = $result->fetch_row())
            {
                $admin->begin_dataset();

                $admin->add_dataset_ident('group_id', $group_id);
                $admin->set_dataset_descr($group_name);

                $admin->add_data($group_name);
                $admin->add_data($group_descr);

                if ($rights_edit_rights)
                    $admin->add_option('rights_true.gif', 'TRL_ALT_EDIT_RIGHTS', 'usermanager_groups_rights');
                else
                    $admin->add_option('rights_false.gif', 'TRL_ALT_EDIT_RIGHTS_FORBIDDEN');
                
                if ($rights_edit_category_rights)
                    $admin->add_option('rights_content_true.gif', 'TRL_ALT_EDIT_CATEGORY_RIGHTS', 'usermanager_groups_category_rights');
                else
                    $admin->add_option('rights_content_false.gif', 'TRL_ALT_EDIT_CATEGORY_RIGHTS_FORBIDDEN');

                if ($rights_edit_inheritance)
                    $admin->add_option('inheritance_true.gif', 'TRL_ALT_EDIT_INHERITANCE', 'usermanager_groups_inheritance');
                else
                    $admin->add_option('inheritance_false.gif', 'TRL_ALT_EDIT_INHERITANCE_FORBIDDEN');

                if ($rights_edit)
                    $admin->add_option('usergroups_edit_true.gif', 'TRL_ALT_EDIT', 'usermanager_groups_edit');
                else
                    $admin->add_option('usergroups_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($rights_delete)
                    $admin->add_option('usergroups_delete_true.gif', 'TRL_ALT_DELETE', 'usermanager_groups_delete');
                else
                    $admin->add_option('usergroups_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');

                $admin->end_dataset();
            }
            $admin->add_multi_option('rights_true.gif', 'TRL_ALT_MULTI_EDIT_RIGHTS', 'usermanager_groups_rights');
            $admin->add_multi_option('rights_content_true.gif', 'TRL_ALT_MULTI_EDIT_CATEGORY_RIGHTS', 'usermanager_groups_category_rights');
            $admin->add_multi_option('inheritance_true.gif', 'TRL_ALT_MULTI_EDIT_INHERITANCE', 'usermanager_groups_inheritance');
            $admin->add_multi_option('usergroups_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'usermanager_groups_edit');
            $admin->add_multi_option('usergroups_delete_true.gif', 'TRL_ALT_MULTI_DELETE', 'usermanager_groups_delete');
            $admin->set_multi_descr('TRL_GROUPS');
        }
        $admin->show('usermanager_groups_administrate');
    }

    public function groups_add()
    {
        $validator = ub_validator::get_instance();
        return $validator->form_validate('usermanager_groups_add');
    }
    
    public function groups_add_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_ADD_GROUP', true);
        $this->unibox->xml->add_value('form_name', 'usermanager_groups_add');
        $form = ub_form_creator::get_instance();
        $form->begin_form('usermanager_groups_add', 'usermanager_groups_add');
    }
        
    public function groups_add_process()
    {
        $this->unibox->db->begin_transaction();

		try
		{
	        $sql_string =  'INSERT INTO
	                          sys_groups
	                        SET
	                          group_show = \''.$this->unibox->session->env->form->usermanager_groups_add->data->group_show.'\'';
	        if (!($result = $this->unibox->db->query($sql_string, 'failed to insert group')) || $this->unibox->db->affected_rows() != 1 || !($group_id = $this->unibox->db->last_insert_id()))
	        	throw new ub_exception_transaction;
	
	        $si_group_name = 'TRL_GROUP_NAME_'.$group_id;
	        if (!$this->unibox->insert_translation($si_group_name, $this->unibox->session->env->form->usermanager_groups_add->data->group_name, 'usermanager'))
		        throw new ub_exception_transaction;
	
	        if (!ub_functions::array_empty($this->unibox->session->env->form->usermanager_groups_add->data->group_descr))
	        {
	            $si_group_descr = 'TRL_GROUP_DESCR_'.$group_id;
	            if (!$this->unibox->insert_translation($si_group_descr, $this->unibox->session->env->form->usermanager_groups_add->data->group_descr, 'usermanager'))
	            	throw new ub_exception_transaction;
	        }
	        else
	        {
	            $si_group_descr = null;
	            if (!$this->unibox->delete_translation('TRL_GROUP_DESCR_'.$group_id, 'usermanager'))
		            throw new ub_exception_transaction;
	        }
	
	        $sql_string =  'UPDATE
	                          sys_groups
	                        SET
	                          si_group_name = \''.$si_group_name.'\',
	                          si_group_descr = '.(($si_group_descr === null) ? 'NULL' : '\''.$si_group_descr.'\'').'
	                        WHERE
	                          group_id = '.$group_id;
	        if (!($result = $this->unibox->db->query($sql_string, 'failed to update group string identifiers')))
		        throw new ub_exception_transaction;
	
			// insert current user
			$sql_string  = 'INSERT INTO
							  sys_user_groups
							SET
							  user_id = '.$this->unibox->session->user_id.',
							  group_id = '.$group_id;
	        if (!($result = $this->unibox->db->query($sql_string, 'failed to insert current user')))
		        throw new ub_exception_transaction;

			$this->unibox->db->commit();
	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_ADD_SUCCESS');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ADD_FAILED');
		}

		ub_form_creator::reset('usermanager_groups_add');
        $this->unibox->switch_alias('usermanager_groups_administrate', true);
    }

    public function groups_edit()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              group_id
                            FROM
                              sys_groups';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('group_id'));
                if (!$validator->validate('STACK', 'group_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('usermanager_groups_edit');
        else
            $stack->switch_to_administration();
    }
    
    public function groups_edit_refill()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        $sql_string =  'SELECT
                          a.si_group_name AS group_name,
                          a.si_group_descr AS group_descr,
                          a.group_show,
                          b.string_value AS translated_group_name
                        FROM sys_groups AS a
                          LEFT JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_group_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                        WHERE
                          a.group_id = \''.$dataset->group_id.'\'';
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'usermanager_groups_edit');
        $form->begin_form('usermanager_groups_edit', 'usermanager_groups_edit');
        $values = $form->set_values($sql_string, array('group_name', 'group_descr'), true);
        $form->set_destructor('ub_stack', 'discard_top');

        // set content title
        if (trim($values['translated_group_name']) != '')
            $this->unibox->set_content_title('TRL_GROUPS_EDIT', array($values['translated_group_name']));
    }
    
    public function groups_edit_process()
    {
        $this->unibox->db->begin_transaction();
        
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        if (!$this->unibox->insert_translation('TRL_GROUP_NAME_'.$dataset->group_id, $this->unibox->session->env->form->usermanager_groups_edit->data->group_name, 'usermanager'))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_edit');
            return;
        }

        if (!ub_functions::array_empty($this->unibox->session->env->form->usermanager_groups_edit->data->group_descr))
        {
            $si_group_descr = 'TRL_GROUP_DESCR_'.$dataset->group_id;
            if (!$this->unibox->insert_translation($si_group_descr, $this->unibox->session->env->form->usermanager_groups_edit->data->group_descr, 'usermanager'))
            {
                $this->unibox->db->rollback('TRL_EDIT_FAILED_OR_NO_CHANGE');
                ub_form_creator::reset('usermanager_groups_edit');
                return;
            }
        }
        else
            $si_group_descr = null;

        $sql_string =  'UPDATE
                          sys_groups
                        SET
                          si_group_descr = '.(($si_group_descr === null) ? 'NULL' : '\''.$si_group_descr.'\'').',
                          group_show = \''.$this->unibox->session->env->form->usermanager_groups_edit->data->group_show.'\'
                        WHERE
                          group_id = \''.$dataset->group_id.'\'';
        if (!$this->unibox->db->query($sql_string, 'failed to update group'))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_edit');
            return;
        }

        if ($si_group_descr === null && !$this->unibox->delete_translation('TRL_GROUP_DESCR_'.$dataset->group_id, 'usermanager'))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_edit');
            return;
        }

        $this->unibox->db->commit();        
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_EDIT_SUCCESS');
        $msg->display();
        ub_form_creator::reset('usermanager_groups_edit');
    }
    
    public function groups_form()
    {
        $form = ub_form_creator::get_instance();
        $form->text_multilanguage('group_name', 'TRL_GROUP_NAME', 30);
        $form->set_condition_multilanguage(CHECK_MULTILANG);
        $form->set_condition_multilanguage(CHECK_NOTEMPTY);
        $form->textarea_multilanguage('group_descr', 'TRL_GROUP_DESCR', 30, 5);
        $form->begin_fieldset('TRL_GROUP_SHOW');
        $form->checkbox('group_show', 'TRL_GROUP_SHOW', true);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'usermanager_groups_edit')
            $form->cancel('TRL_CANCEL_UCASE', 'usermanager_groups_edit');
        else
            $form->cancel('TRL_CANCEL_UCASE', 'usermanager_groups_administrate');
        $form->end_buttonset();
        $form->end_form();
    }

    public function groups_delete()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              group_id
                            FROM
                              sys_groups';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('group_id'));
                if (!$validator->validate('STACK', 'group_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('usermanager_groups_delete');
        else
            $stack->switch_to_administration();
    }

    public function groups_delete_confirm()
    {
        $stack = ub_stack::get_instance();

        $sql_string =  'SELECT
                          b.string_value
                        FROM sys_groups AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_group_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                        WHERE
                          a.group_id IN ('.implode(', ', $stack->get_stack('group_id')).')
                        ORDER BY
                          b.string_value';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_GROUP_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($group_name) = $result->fetch_row())
                $msg->add_listentry($group_name, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('usermanager_groups_delete', 'usermanager_groups_delete');
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'usermanager_groups_delete');
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
    
    public function groups_delete_process()
    {
        $this->unibox->db->begin_transaction();
        
        $stack = ub_stack::get_instance();

        $sql_string =  'DELETE FROM
                          sys_groups
                        WHERE
                          group_id IN ('.implode(', ', $stack->get_stack('group_id')).')';
        if (!($result = $this->unibox->db->query($sql_string, 'failed to delete groups')) || $this->unibox->db->affected_rows() == 0)
        {
            $this->unibox->db->rollback('TRL_DELETE_FAILED');
            ub_form_creator::reset('usermanager_groups_delete');
            return;
        }

        $languagemanager = ub_languagemanager::get_instance();
        $languagemanager->translations_cleanup_process(true);

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_DELETE_SUCCESSFUL');
        $msg->display();
        ub_form_creator::reset('usermanager_groups_delete');
    }
    
    public function groups_inheritance()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              group_id
                            FROM
                              sys_groups';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('group_id'));
                if (!$validator->validate('STACK', 'group_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('usermanager_groups_inheritance');
        else
            $stack->switch_to_administration();
    }
    
    public function groups_inheritance_form()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string =  'SELECT
                          b.string_value
                        FROM sys_groups AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_group_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                        WHERE
                          a.group_id = \''.$dataset->group_id.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to retrieve group name');
        list ($group_name) = $result->fetch_row();
        
        $groups[] = $dataset->group_id;
        $this->unibox->session->get_inheriting_groups($dataset->group_id, $groups);
        $sql_string =  'SELECT
                          a.group_id,
                          b.string_value,
                          d.group_id
                        FROM sys_groups AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_group_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          LEFT JOIN sys_group_inheritance AS d
                            ON
                              (d.inherits_from_group_id = a.group_id 
                              AND
                              d.group_id = \''.$dataset->group_id.'\')
                        WHERE
                          a.group_id NOT IN (\''.implode('\', \'', $groups).'\')
                        ORDER BY
                          b.string_value ASC';
        $result = $this->unibox->db->query($sql_string, 'failed to select groups');
        if ($result->num_rows() > 0)
        {
            // set content title
            $this->unibox->set_content_title('TRL_GROUPS_INHERITANCE', array($group_name));

            $form = ub_form_creator::get_instance();
            $this->unibox->load_template('shared_form_display');
            $this->unibox->xml->add_value('form_name', 'usermanager_groups_inheritance');
            $form->begin_form('usermanager_groups_inheritance', 'usermanager_groups_inheritance');

            $form->begin_checkbox('group_ids', 'TRL_SELECT_INHERITING_GROUPS');
            $form->set_translation(false);
            while (list($group_id, $group_name, $inherits) = $result->fetch_row())
            {
                $inherits = !($inherits == NULL);
                $form->add_option($group_id, $group_name, $inherits);
            }
            $form->set_translation(true);
            $form->end_checkbox();
            $form->begin_buttonset();
            $form->submit('TRL_SAVE_UCASE');
            $form->cancel('TRL_CANCEL_UCASE', 'usermanager_groups_inheritance');
            $form->end_buttonset();
            $form->end_form();
            $form->set_destructor('ub_stack', 'discard_top');
        }
        else
        {
            $msg = new ub_message(MSG_WARNING, false);
            if (isset($group_name) && trim($group_name) != '')
            $msg->add_text('TRL_GROUP_IS_INHERITED_BY_ALL_OTHER_GROUPS', array($group_name));
            $stack->pop();
            if (!$stack->is_empty())
            {
                $msg->add_newline(2);
                $msg->add_link('usermanager_groups_inheritance', 'TRL_NEXT_DATASET');
            }
            else
            {
                $msg->add_newline(2);
                $msg->add_link('usermanager_groups_administrate', 'TRL_BACK_TO_ADMINISTRATION');
            }
            $msg->display();
        }
    }
    
    public function groups_inheritance_process()
    {
        $this->unibox->db->begin_transaction();
        
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string =  'DELETE FROM
                          sys_group_inheritance
                        WHERE
                          group_id = \''.$dataset->group_id.'\'';
        if (!$this->unibox->db->query($sql_string, 'failed to drop all existant group inheritances'))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED');
            ub_form_creator::reset('usermanager_groups_inheritance');
            return;
        }
        
        if (isset($this->unibox->session->env->form->usermanager_groups_inheritance->data->group_ids) && count($this->unibox->session->env->form->usermanager_groups_inheritance->data->group_ids) > 0)
        {
            // build sql string to insert with a single query
            $sql_string =  'INSERT INTO
                              sys_group_inheritance
                            VALUES 
                              (\''.$dataset->group_id.'\', \'';
            $sql_string .= implode('\'), (\''.$dataset->group_id.'\', \'', $this->unibox->session->env->form->usermanager_groups_inheritance->data->group_ids);
            $sql_string .= '\')';
            if (!$this->unibox->db->query($sql_string, 'failed to insert group inheritances') || $this->unibox->db->affected_rows() == 0)
            {
                $this->unibox->db->rollback('TRL_EDIT_FAILED');
                ub_form_creator::reset('usermanager_groups_inheritance');
                return;
            }
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_EDIT_SUCCESSFUL');
        $msg->display();
        ub_form_creator::reset('usermanager_groups_inheritance');
    }
    
    public function groups_rights()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              group_id
                            FROM
                              sys_groups';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('group_id'));
                if (!$validator->validate('STACK', 'group_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('usermanager_groups_rights');
        else
            $stack->switch_to_administration();
    }
    
    public function groups_rights_form()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string =  'SELECT
                          b.string_value
                        FROM sys_groups AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_group_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                        WHERE
                          a.group_id = \''.$dataset->group_id.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to retrieve group name');
        list ($group_name) = $result->fetch_row();

        // set content title
        $this->unibox->set_content_title('TRL_EDIT_GROUP_RIGHTS', array($group_name));

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'usermanager_groups_rights');
        $form = ub_form_creator::get_instance();
        $form->begin_form('usermanager_groups_rights', 'usermanager_groups_rights');
        
        $groups = array($dataset->group_id);
        $this->unibox->session->get_inherited_groups($dataset->group_id, $groups);

        // initialize variables
        $cur_module_ident = $cur_action_ident = '';
        $allowing_groups = $denying_groups = array();
        $group_grant = null;

        $sql_string =  'SELECT
                          a.module_ident,
                          b.string_value AS module_name,
                          c.action_ident,
                          d.string_value AS action_descr,
                          e.group_id,
                          g.string_value AS group_name,
                          e.flag AS `grant`
                        FROM sys_actions AS c
                          INNER JOIN sys_translations AS d
                            ON
                            (
                            d.string_ident = c.si_action_descr
                            AND
                            d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          INNER JOIN sys_modules AS a
                            ON a.module_ident = c.module_ident
                          LEFT JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_module_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          LEFT JOIN sys_group_rights AS e
                            ON
                              (
                              e.action_ident = c.action_ident
                              AND
                              e.group_id IN ('.implode(', ', $groups).')
                              )
                          LEFT JOIN sys_groups AS f
                            ON f.group_id = e.group_id
                          LEFT JOIN sys_translations AS g
                            ON
                              (
                              g.string_ident = f.si_group_name
                              AND
                              g.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              )
                        WHERE
                          a.module_active = 1
                          AND
                          c.bitmask = 0
                          AND
                          c.action_sort > 0
                        ORDER BY
                          b.string_value ASC,
                          c.action_sort ASC,
                          d.string_value ASC';
        $result = $this->unibox->db->query($sql_string, 'failed to get actions');
        $cur_action_ident = $action_ident = $action_descr = '';
        while ($row = $result->fetch_row(FETCHMODE_ASSOC))
        {
            if ($row['action_ident'] != $cur_action_ident && $cur_action_ident != '')
            {
            	$form->set_translation(false);
                $form->begin_select('right_'.$action_ident, $action_descr, false);
                $form->set_translation(true);
                $form->add_option('-1', 'TRL_INHERIT_RIGHT', ($group_grant === null));
                $form->add_option('1', 'TRL_GRANT', ($group_grant === true));
                $form->add_option('0', 'TRL_FORBID', ($group_grant === false));
                $form->end_select();

                if (!empty($denying_groups))
                {
                    $form->comment('TRL_DENIED_BY', implode(', ', $denying_groups));
                    $form->set_disabled(-1);
                }
                elseif (!empty($allowing_groups))
                    $form->comment('TRL_ALLOWED_BY', implode(', ', $allowing_groups));

                $allowing_groups = $denying_groups = array();
                $cur_action_ident = $row['action_ident'];
                $group_grant = null;
            }
            elseif ($cur_action_ident == '')
                $cur_action_ident = $row['action_ident'];

            $module_ident = $row['module_ident'];
            $module_name = $row['module_name'];
            $action_ident = $row['action_ident'];
            $action_descr = $row['action_descr'];
            $group_id = $row['group_id'];
            $group_name = $row['group_name'];
            $grant = $row['grant'];
            
            // close old and open new module's fieldset
            if ($module_ident != $cur_module_ident)
            {
                if ($cur_module_ident != '')
                    $form->end_fieldset();
                $form->begin_fieldset($this->unibox->translate('TRL_MODULE_GROUP_RIGHTS', array($module_name)));
                $cur_module_ident = $module_ident;
            }

            // check for inherited or direct rights
            if ($group_id != $dataset->group_id && $group_id != null)
            {
                if ($grant)
                    $allowing_groups[$group_id] = $group_name;
                else
                    $denying_groups[$group_id] = $group_name;
            }
            elseif ($group_id == $dataset->group_id)
                $group_grant = ($grant === null) ? $grant : (bool)$grant;
        }

		$form->set_translation(false);
        $form->begin_select('right_'.$action_ident, $action_descr, false);
        $form->set_translation(true);
        $form->add_option('-1', 'TRL_INHERIT_RIGHT', $group_grant === null);
        $form->add_option('1', 'TRL_GRANT', $group_grant === true);
        $form->add_option('0', 'TRL_FORBID', $group_grant === false);
        $form->end_select();

        if (!empty($denying_groups))
        {
            $form->comment('TRL_DENIED_BY', implode(', ', $denying_groups));
            $form->set_disabled(-1);
        }
        elseif (!empty($allowing_groups))
            $form->comment('TRL_ALLOWED_BY', implode(', ', $allowing_groups));

        // close last module's fieldset
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'usermanager_groups_rights');
        $form->end_buttonset();
        $form->end_form();
        $form->set_destructor('ub_stack', 'discard_top');
    }
    
    public function groups_rights_process()
    {
        $this->unibox->db->begin_transaction();

        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string =  'DELETE FROM
                          sys_group_rights
                        WHERE
                          group_id = \''.$dataset->group_id.'\'';
        if (!($result = $this->unibox->db->query($sql_string, 'failed to delete existant group rights')))
        {
            $this->unibox->db->rollback('TRL_GROUP_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_rights');
            return;
        }
        $right_dropped = (bool)$this->unibox->db->affected_rows();

        $sql_string =  'SELECT
                          c.action_ident
                        FROM sys_actions AS c
                          INNER JOIN sys_translations AS d
                            ON
                            (
                            d.string_ident = c.si_action_descr
                            AND
                            d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          INNER JOIN sys_modules AS a
                            ON a.module_ident = c.module_ident
                        WHERE
                          a.module_active = 1
                          AND
                          c.bitmask = 0
                          AND
                          c.action_sort > 0';
        if (!($result = $this->unibox->db->query($sql_string, 'failed to get actions')) || $result->num_rows() == 0)
        {
            $this->unibox->db->rollback('TRL_GROUP_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_rights');
            return;
        }

        $rights_to_set = false;
        $sql_string =  'INSERT INTO
                          sys_group_rights
                        VALUES ';
        while (list($action_ident) = $result->fetch_row())
        {
            if (isset($this->unibox->session->env->form->usermanager_groups_rights->data->{'right_'.$action_ident}) && $this->unibox->session->env->form->usermanager_groups_rights->data->{'right_'.$action_ident} != '-1')
            {
                $sql_string .= '(\''.$dataset->group_id.'\', \''.$action_ident.'\', \''.$this->unibox->session->env->form->usermanager_groups_rights->data->{'right_'.$action_ident}.'\'), ';
                $rights_to_set = true;
            }
        }

        if ($rights_to_set)
        {
            $sql_string = substr($sql_string, 0, -2);
            if (!($result = $this->unibox->db->query($sql_string, 'failed to insert group rights')) || (!$this->unibox->db->affected_rows() > 0 && !$right_dropped))
            {
                $this->unibox->db->rollback('TRL_GROUP_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
                ub_form_creator::reset('usermanager_groups_rights');
                return;
            }
        }

        // update sessions
        $sql_string =  'UPDATE
                          sys_sessions
                        SET
                          rights = \'\'';
        if (!$this->unibox->db->query($sql_string, 'failed to delete rights from sessions'))
        {
            $this->unibox->db->rollback('TRL_GROUP_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_rights');
            return;
        }

        if (in_array($dataset->group_id, $this->unibox->session->group_ids))
            $this->unibox->session->set_rights();

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_GROUP_RIGHTS_EDIT_SUCCESS');
        $msg->display();
        ub_form_creator::reset('usermanager_groups_rights');
    }

    public function groups_category_rights()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              group_id
                            FROM
                              sys_groups';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('group_id'));
                if (!$validator->validate('STACK', 'group_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('usermanager_groups_category_rights');
        else
            $stack->switch_to_administration();
    }

    public function groups_category_rights_preselect()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        $sql_string =  'SELECT
                          b.string_value
                        FROM sys_groups AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_group_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                        WHERE
                          a.group_id = \''.$dataset->group_id.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to retrieve group name');
        list ($group_name) = $result->fetch_row();

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'preselect_groups_category_rights');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('preselect_groups_category_rights', 'usermanager_groups_category_rights');

        $preselect = ub_preselect::get_instance('preselect_groups_category_rights');
        $preselect->add_field('module_ident', 'a.module_ident', true);
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
        $form->begin_select('module_ident', 'TRL_MODULE');

        // set content title
        $this->unibox->set_content_title('TRL_EDIT_GROUP_CATEGORY_RIGHTS', array($group_name));

        $sql_string =  'SELECT DISTINCT
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
                          INNER JOIN sys_translations AS d
                            ON
                            (
                            d.string_ident = c.si_action_descr
                            AND
                            d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          INNER JOIN sys_categories AS e
                            ON e.module_ident = a.module_ident 
                        WHERE
                          a.module_active = 1
                          AND
                          c.bitmask > 0
                        ORDER BY
                          b.string_value';
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
    
    public function groups_category_rights_form()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $this->unibox->xml->add_value('form_name', 'usermanager_groups_category_rights');
        $preselect = ub_preselect::get_instance('preselect_groups_category_rights');
        
        $form = ub_form_creator::get_instance();
        $form->begin_form('usermanager_groups_category_rights', 'usermanager_groups_category_rights');

        $groups = array($dataset->group_id);
        $this->unibox->session->get_inherited_groups($dataset->group_id, $groups);

        // initialize variables
        $cur_module_ident = $cur_action_ident = '';
        $cur_category_id = 0;
        $allowing_groups = $denying_groups = array();
        $group_grant = null;

        $sql_string =  'SELECT
                          a.category_id,
                          d.string_value AS category_name,
                          c.action_ident,
                          a.module_ident,
                          e.string_value AS module_name,
                          g.string_value AS action_descr,
                          h.group_id,
                          i.string_value AS group_name,
                          (c.bitmask & f.bit_allow) AS allow,
                          (c.bitmask & f.bit_deny) AS deny
                        FROM sys_categories AS a
                          LEFT JOIN sys_modules AS b
                            ON b.module_ident = a.module_ident
                          LEFT JOIN sys_actions AS c
                            ON c.module_ident = a.module_ident
                          LEFT JOIN sys_translations AS d
                            ON
                            (
                            d.string_ident = a.si_category_name
                            AND
                            d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          LEFT JOIN sys_translations AS e
                            ON
                            (
                            e.string_ident = b.si_module_name
                            AND
                            e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          LEFT JOIN sys_group_rights_content AS f
                            ON
                              (
                              f.category_id = a.category_id
                              AND
                              f.group_id IN ('.implode(', ', $groups).')
                              )
                          LEFT JOIN sys_translations AS g
                            ON
                            (
                            g.string_ident = c.si_action_descr
                            AND
                            g.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                          LEFT JOIN sys_groups AS h
                            ON h.group_id = f.group_id
                          LEFT JOIN sys_translations AS i
                            ON
                              (
                              i.string_ident = h.si_group_name
                              AND
                              i.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              )
                        WHERE
                          '.$preselect->get_string().'
                          AND
                          b.module_active = 1
                          AND
                          c.bitmask > 0
                          AND
                          c.action_sort > 0
                        ORDER BY
                          e.string_value ASC,
                          d.string_value ASC,
                          c.action_sort ASC,
                          i.string_value ASC';
        $result = $this->unibox->db->query($sql_string, 'failed to get actions');
        $category_id = $action_ident = $action_descr = '';
        if ($result->num_rows() == 0)
        {
        	$preselect->init('preselect_groups_category_rights', true);
        	throw new ub_exception_general('no category rights found for category '.$preselect->get_value('module_ident'), 'TRL_NO_CATEGORY_RIGHTS_FOR_MODULE', array($preselect->get_value('module_ident')));
        }

        while ($row = $result->fetch_row(FETCHMODE_ASSOC))
        {
            if ($row['action_ident'] != $cur_action_ident && $cur_action_ident != '')
            {
            	$form->set_translation(false);
                $form->begin_select('category_'.$category_id.'_right_'.$action_ident, $action_descr, false);
                $form->set_translation(true);
                $form->add_option('-1', 'TRL_INHERIT_RIGHT', ($group_grant === null));
                $form->add_option('1', 'TRL_GRANT', ($group_grant === true));
                $form->add_option('0', 'TRL_FORBID', ($group_grant === false));
                $form->end_select();

                if (!empty($denying_groups))
                {
                    $form->comment('TRL_DENIED_BY', implode(', ', $denying_groups));
                    $form->set_disabled(-1);
                }
                elseif (!empty($allowing_groups))
                    $form->comment('TRL_ALLOWED_BY', implode(', ', $allowing_groups));

                $allowing_groups = $denying_groups = array();
                $cur_action_ident = $row['action_ident'];
                $group_grant = null;
            }
            elseif ($cur_action_ident == '')
                $cur_action_ident = $row['action_ident'];
            
            $category_id = $row['category_id'];
            $category_name = $row['category_name'];
            $action_ident = $row['action_ident'];
            $module_ident = $row['module_ident'];
            $module_name = $row['module_name'];
            $action_descr = $row['action_descr'];
            $group_id = $row['group_id'];
            $group_name = $row['group_name'];
            $allow = $row['allow'];
            $deny = $row['deny'];

            // close old category's fieldset if category changed
            if ($category_id != $cur_category_id)
                if ($cur_category_id != 0)
                    $form->end_fieldset();

            // open new category's fieldset if category changed
            if ($category_id != $cur_category_id)
            {
                $form->begin_fieldset($category_name);
                $cur_category_id = $category_id;
            }

            // check for inherited or direct rights
            if ($group_id != $dataset->group_id && $group_id != null)
            {
                if ($allow > 0)
                    $allowing_groups[$group_id] = $group_name;
                elseif ($deny > 0)
                    $denying_groups[$group_id] = $group_name;
            }
            elseif ($group_id == $dataset->group_id)
            {
                if ($allow > 0)
                    $group_grant = true;
                elseif ($deny > 0)
                    $group_grant = false;
            }
        }

		$form->set_translation(false);
        $form->begin_select('category_'.$category_id.'_right_'.$action_ident, $action_descr, false);
        $form->set_translation(true);
        $form->add_option('-1', 'TRL_INHERIT_RIGHT', ($group_grant === null));
        $form->add_option('1', 'TRL_GRANT', ($group_grant === true));
        $form->add_option('0', 'TRL_FORBID', ($group_grant === false));
        $form->end_select();

        if (!empty($denying_groups))
        {
            $form->comment('TRL_DENIED_BY', implode(', ', $denying_groups));
            $form->set_disabled(-1);
        }
        elseif (!empty($allowing_groups))
            $form->comment('TRL_ALLOWED_BY', implode(', ', $allowing_groups));

        // close last module's and category's fieldset
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'usermanager_groups_category_rights');
        $form->end_buttonset();
        $form->set_destructor('ub_stack', 'discard_top');
        $form->end_form();
    }
    
    public function groups_category_rights_process()
    {
        $this->unibox->db->begin_transaction();
        
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $preselect = ub_preselect::get_instance('preselect_groups_category_rights');
        $categories = $actions = array();

        $sql_string =  'SELECT
                          a.category_id
                        FROM sys_categories AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_category_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                        WHERE
                          '.$preselect->get_string();
        if (!($result = $this->unibox->db->query($sql_string, 'failed to get categories')) || $result->num_rows() == 0)
        {
            $this->unibox->db->rollback('TRL_GROUP_CATEGORY_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_category_rights');
            return;
        }

        while (list($category_id) = $result->fetch_row())
            $categories[] = $category_id;       

        $sql_string =  'DELETE FROM
                          sys_group_rights_content
                        WHERE
                          group_id = '.$dataset->group_id.'
                          AND
                          category_id IN ('.implode(', ', $categories).')';
        if (!($result = $this->unibox->db->query($sql_string, 'failed to delete category rights')))
        {
            $this->unibox->db->rollback('TRL_GROUP_CATEGORY_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_category_rights');
            return;
        }
        $rights_dropped = (bool)$this->unibox->db->affected_rows();

        $sql_string =  'SELECT
                          a.action_ident,
                          a.bitmask
                        FROM sys_actions AS a
                          INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_action_descr
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
                        WHERE
                          a.bitmask > 0
                          AND
                          '.$preselect->get_string().'
                        ORDER BY
                          a.action_sort ASC';
        if (!($result = $this->unibox->db->query($sql_string, 'failed to get actions')) || $result->num_rows() == 0)
        {
            $this->unibox->db->rollback('TRL_GROUP_CATEGORY_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
            ub_form_creator::reset('usermanager_groups_category_rights');
            return;
        }

        while (list($action_ident, $bitmask) = $result->fetch_row())
            $actions[$action_ident] = $bitmask;

        $rights_to_set = false;
        $sql_string =  'INSERT INTO
                          sys_group_rights_content
                        VALUES ';
        foreach ($categories as $category_id)
        {
            $bit_allow = 0;
            $bit_deny = 0;
            foreach ($actions as $action_ident => $bitmask)
            {
                if (isset($this->unibox->session->env->form->usermanager_groups_category_rights->data->{'category_'.$category_id.'_right_'.$action_ident}))
                {
                    if ($this->unibox->session->env->form->usermanager_groups_category_rights->data->{'category_'.$category_id.'_right_'.$action_ident} == '1')
                        $bit_allow += $bitmask;
                    elseif ($this->unibox->session->env->form->usermanager_groups_category_rights->data->{'category_'.$category_id.'_right_'.$action_ident} == '0')
                        $bit_deny += $bitmask;
                }
            }
            if ($bit_allow > 0 || $bit_deny > 0)
            {
                $sql_string .= '('.$dataset->group_id.', '.$category_id.', '.$bit_allow.', '.$bit_deny.'), ';
                $rights_to_set = true;
            }
        }

        if ($rights_to_set)
        {
            $sql_string = substr($sql_string, 0, -2);
            if (!($result = $this->unibox->db->query($sql_string, 'failed to insert category rights')))
            {
                $this->unibox->db->rollback('TRL_GROUP_CATEGORY_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
                ub_form_creator::reset('usermanager_groups_category_rights');
                return;
            }
        }

        if ($this->unibox->db->affected_rows() > 0 || $rights_dropped)
        {
            // update sessions
            $sql_string =  'UPDATE
                              sys_sessions
                            SET
                              rights = \'\'';
            if (!($result = $this->unibox->db->query($sql_string, 'failed to update sessions')))
            {
                $this->unibox->db->rollback('TRL_GROUP_CATEGORY_RIGHTS_EDIT_FAILED_OR_NO_CHANGE');
                ub_form_creator::reset('usermanager_groups_category_rights');
                return;
            }
        }

        if (in_array($dataset->group_id, $this->unibox->session->group_ids))
            $this->unibox->session->set_rights();

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_GROUP_CATEGORY_RIGHTS_EDIT_SUCCESS');
        $msg->display();
        ub_form_creator::reset('usermanager_groups_category_rights');
    }
}

?>