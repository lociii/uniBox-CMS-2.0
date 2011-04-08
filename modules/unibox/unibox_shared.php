<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_unibox_shared
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
        return ub_unibox_shared::version;
    }

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      ub_unibox_shared object
    */
    public function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_unibox_shared;
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
    * change_font_size()
    *
    * changes the font-size depending on the given action
    * 
    * @access   public
    */
    public function change_font_size()
    {
        $validator = ub_validator::get_instance();
        if ($validator->validate('GET', 'resize', TYPE_STRING, CHECK_INSET, null, array('smaller', 'standard', 'bigger')))
        {
            switch ($this->unibox->session->env->input->resize)
            {
                case 'smaller':
                    $this->unibox->session->font_size -= $this->unibox->config->system->font_size_change_value;
                    break;
                case 'standard':
                    $this->unibox->session->font_size = 0;
                    break;
                case 'bigger':
                    $this->unibox->session->font_size += $this->unibox->config->system->font_size_change_value;
                    break;
            }
            $sql_string  = 'UPDATE
                              sys_sessions
                            SET
                              session_font_size = \''.$this->unibox->session->font_size.'\'
                            WHERE
                              session_id = \''.$this->unibox->session->session_id.'\'';
            $this->unibox->db->query($sql_string, 'failed to update font-size');
        }
        $this->unibox->redirect();
    }

    /**
    * change_subtheme()
    *
    * changes the current page subtheme
    * 
    * @access   public
    */
    public function change_subtheme()
    {
        $validator = ub_validator::get_instance();
        $sql_string  = 'SELECT
                          a.subtheme_ident
                        FROM
                          sys_subthemes AS a
                            INNER JOIN sys_theme_output_formats AS b
                              ON
                              (
                              b.theme_ident = a.theme_ident
                              AND
                              b.subtheme_ident = a.subtheme_ident
                              )
                        WHERE
                          a.theme_ident = \''.$this->unibox->session->env->themes->current->theme_ident.'\'';
        if ($validator->validate('GET', 'subtheme_ident', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
            $this->unibox->session->env->themes->current->subtheme_ident = $this->unibox->session->env->input->subtheme_ident;
        $this->unibox->redirect();
    }

    public function change_language()
    {
        // check for language switch
        $languages = array();
        $sql_string  = 'SELECT
                          lang_ident
                        FROM
                          sys_languages';
        $result = $this->unibox->db->query($sql_string, 'failed to select valid languages');
        while (list($lang_ident) = $result->fetch_row())
            $languages[] = $lang_ident;
        if (isset($this->unibox->session->env->alias->get['lang_ident']) && in_array($this->unibox->session->env->alias->get['lang_ident'], $languages))
        {
            $this->lang_ident = $this->unibox->session->env->input->language;
            $sql_string  = 'UPDATE
                              sys_sessions
                            SET
                              session_lang_ident = \''.$this->unibox->session->env->alias->get['lang_ident'].'\'
                            WHERE
                              session_id = \''.$this->unibox->session->session_id.'\'';
            $this->unibox->db->query($sql_string, 'failed to change language');
        }
        $this->unibox->redirect();
    }

	public function unibox_welcome()
	{
		if ($this->unibox->session->user_id == 1)
			$this->unibox->switch_action('login');
		else
		{
			$msg = new ub_message(MSG_INFO);
			$msg->add_text('TRL_FRAMEWORK_UNIBOX_WELCOME_TEXT');
			$msg->display();
		}
	}

    public function login()
    {
        $this->unibox->session->var->unregister('unibox_module_ident');
        if ($this->unibox->session->user_id == 1)
        {
            $validator = ub_validator::get_instance();
            if ($validator->form_validate('login'))
            {
				// allow login only if using cookies
		        if (!$this->unibox->session->uses_cookies)
		        {
		            $msg = new ub_message(MSG_INFO, false);
		            $msg->add_text('TRL_LOGIN_ONLY_ALLOWED_IF_COOKIES_ENABLED');
		            $msg->display();
		            return 1;
		        }

                try
                {
                    switch ($return_state = $this->unibox->session->auth($this->unibox->session->env->form->login->data->email, $this->unibox->session->env->form->login->data->password, $this->unibox->session->env->form->login->data->autologin))
                    {
                        case LOGIN_SUCCESSFUL:
                            $this->unibox->session->set_rights();
                            ub_form_creator::reset('login');
                            if (isset($this->unibox->session->env->themes->current->default_alias))
                                $this->unibox->redirect($this->unibox->session->env->themes->current->default_alias);
                            else
                                $this->unibox->redirect($this->unibox->session->env->system->default_alias);
                            return 0;
                        case LOGIN_DISABLED:
                            throw new ub_exception_security('deactivated user tried to login with email \''.$this->unibox->session->env->form->login->data->email.'\'', 'TRL_ERR_LOGIN_DISABLED');
                        case LOGIN_JUST_DISABLED:
                            throw new ub_exception_security('too many failed login attempts for email \''.$this->unibox->session->env->form->login->data->email.'\'', 'TRL_ERR_LOGIN_JUST_DISABLED', array($this->unibox->config->system->max_failed_logins));
                        case LOGIN_LOCKED:
                            throw new ub_exception_security('locked user tried to login with email \''.$this->unibox->session->env->form->login->data->email.'\'', 'TRL_ERR_LOGIN_LOCKED');
                        default:
                            $validator->set_restore(true);
                            throw new ub_exception_security('login failed for email \''.$this->unibox->session->env->form->login->data->email.'\'', 'TRL_ERR_LOGIN_FAILED');
                    }
                }
                catch (ub_exception_security $exception)
                {
                    $exception->process();
                }
            }
            return 1;
        }

        // switch to default action if user is logged in
        if (isset($this->unibox->session->env->themes->current->default_alias))
            $this->unibox->redirect($this->unibox->session->env->themes->current->default_alias);
        else
            $this->unibox->redirect($this->unibox->session->env->system->default_alias);
    }

    public function login_show()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_USER_FORM_LOGIN', TRUE);
        $this->unibox->xml->add_value('form_name', 'login');

        // create form
        $form = ub_form_creator::get_instance();
        $form->begin_form('login', $this->unibox->session->env->alias->name);
        $form->text('email', 'TRL_EMAIL_ADDRESS', '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_EMAIL);
        $form->password('password', 'TRL_PASSWORD', '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->checkbox('autologin', 'TRL_REMEMBER_LOGIN');
        $form->begin_buttonset();
        $form->submit('TRL_LOGIN', 0);
        if (isset($this->unibox->session->env->themes->current->default_alias))
            $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->themes->current->default_alias);
        else
            $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();
        $form->end_form();

        return 0;
    }

    public function logout()
    {
        $this->unibox->session->logout();
        if (isset($this->unibox->session->env->themes->current->default_alias))
            $this->unibox->redirect($this->unibox->session->env->themes->current->default_alias);
        else
            $this->unibox->redirect($this->unibox->session->env->system->default_alias);
    }

	public function dialog_init()
	{
		$dialog = ub_dialog::get_instance();
		return $dialog->init();
	}
	
	public function framework_editor_link_list()
    {
        // set javascript header
        header('Content-Type: application/x-javascript; charset=iso-8859-1');
        
        $sql_string  = 'SELECT
                          a.module_ident_from,
                          a.entity_type_definition,
                          c.string_value
                        FROM
                          sys_sex AS a
                            INNER JOIN sys_modules AS b
                              ON b.module_ident = a.module_ident_from
                            INNER JOIN sys_translations AS c
                              ON c.string_ident = b.si_module_name
                        WHERE
                          a.entity_type = \'editor_linklist\'
                          AND
                          b.module_active = 1
                          AND
                          c.lang_ident = \''.$this->unibox->session->lang_ident.'\'';
        $result = $this->unibox->db->query($sql_string, null, true, true);
        // begin output
        $module_list = 'var module_list = new Array('."\n";
        while (list($ident, $sql_string, $name) = $result->fetch_row())
        {
            // add current module to modulelist
            if ((@iconv('UTF-8', 'UTF-8', $name) == $name))
                $name = utf8_decode($name);

            $this->unibox->config->load_config($ident);
            $result2 = $this->unibox->db->query($sql_string, null, true, true);
            if ($result2 instanceof ub_db_result && $result2->num_rows() > 0)
            {
                $module_list .= '["'.$ident.'", module_'.$ident.', "'.addslashes($name).'"],'."\n";
                // begin module content
                $content_list = 'var module_'.$ident.' = new Array('."\n";
                while ($row = $result2->fetch_row(FETCHMODE_ASSOC))
                {
                    if ((@iconv('UTF-8', 'UTF-8', $row['name']) == $row['name']))
                        $row['name'] = utf8_decode($row['name']);
                    if (isset($row['category']) && (@iconv('UTF-8', 'UTF-8', $row['category']) == $row['category']))
                        $row['category'] = utf8_decode($row['category']);

					if ($this->unibox->config->system->url_rewrite == 0)
						$row['detail'] = $this->unibox->url_rewrite_output_transform($row['detail'], false);

                    if (isset($row['category']))
                    	$content_list .= '["'.$row['category'].'", "'.addslashes($row['name']).'", "'.$row['detail'].'"],'."\n";
                    else
                    	$content_list .= '["'.addslashes($row['name']).'", "'.$row['detail'].'"],'."\n";
                }
                echo substr($content_list, 0, strlen($content_list)-2)."\n);\n\n";
            }
        }
        // close modulelist
        if ($result2->num_rows() > 0)
        	$module_list = substr($module_list, 0, strlen($module_list)-2);
        die($module_list."\n);");
    }

    public function administration_process()
    {
        $validator = ub_validator::get_instance();
        $sql_string  = 'SELECT
                          a.alias
                        FROM
                          sys_alias AS a
                            INNER JOIN sys_actions AS b
                              ON b.action_ident = a.action_ident
                            INNER JOIN sys_modules AS c
                              ON c.module_ident = b.module_ident
                        WHERE
                          c.module_active = 1
						  AND
                          b.action_ident IN (\''.implode('\', \'', $this->unibox->session->get_rights()).'\')';

        // get current administration multi option
        $matches = array();
        foreach ($_POST as $key => $value)
            if (preg_match('/administration_submit_(.*)_x/', $key, $matches))
                $_POST['administration_submit'] = $matches[1];

        if (!$validator->validate('POST', 'administration_submit', TYPE_STRING, CHECK_INSET_SQL, 'TRL_INVALID_ALIAS_SUBMITTED_FOR_ADMINISTRATION', $sql_string) || !$validator->validate('POST', 'administration_checkbox', TYPE_ARRAY, CHECK_NOTEMPTY, 'TRL_NO_DATASET_SELECTED'))
        {
            $this->unibox->display_error();
            if ($validator->validate('GET', 'administration_fallback', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
            	$this->unibox->switch_alias($this->unibox->session->env->input->administration_fallback, true);
            elseif (isset($this->unibox->session->env->alias->last->name))
                $this->unibox->switch_alias($this->unibox->session->env->alias->last->name, true);
            return;
        }

        $sql_string  = 'SELECT
                          a.alias
                        FROM
                          sys_alias AS a
                            INNER JOIN sys_actions AS b
                              ON b.action_ident = a.action_ident
                            INNER JOIN sys_modules AS c
                              ON c.module_ident = b.module_ident
                        WHERE
                          c.module_active = 1';
        if ($validator->validate('POST', 'administration_fallback', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
            $this->unibox->session->var->register('administration_alias', $this->unibox->session->env->input->administration_fallback, true);
        $this->unibox->switch_alias($this->unibox->session->env->input->administration_submit);
    }

    public function stack_init()
    {
        $stack = ub_stack::get_instance();
        $validator = ub_validator::get_instance();

		// drop top element if requested
		if ($stack->is_valid() && !$stack->is_empty() && $validator->validate('GET', 'stack_pop', TYPE_STRING, CHECK_ISSET))
		{
			$stack->pop();
			$this->stack_check();
			return;
		}

		// clear stack if requested
		if ($stack->is_valid() && !$stack->is_empty() && $validator->validate('GET', 'stack_clear', TYPE_STRING, CHECK_ISSET))
		{
			$stack->clear();
			$this->stack_check();
			return;
		}

        if (!$stack->is_empty() && (isset($this->unibox->session->env->input->administration_checkbox) || !empty($this->unibox->session->env->alias->url_get) || (isset($this->unibox->session->var->administration_url_get) && !empty($this->unibox->session->var->administration_url_get))))
        {
        	// check if only a dialog step was passed
        	if (!empty($this->unibox->session->env->alias->url_get) && count($this->unibox->session->env->alias->url_get) == 1 && isset($this->unibox->session->env->alias->url_get['step']))
        		return;

            if ($validator->form_validate('stack_init'))
            {
                if ($this->unibox->session->env->form->stack_init->data->submit_id == 'discard')
                    $stack->kill();
            }
            else
            {
                if (isset($this->unibox->session->env->input->administration_checkbox))
                {
                    // build temporary stack and compare it to existing stack
                    $temp = ub_stack::get_instance('temp');
                    $data = array_reverse($this->unibox->session->env->input->administration_checkbox);
                    foreach ($data as $value)
                        $temp->push(ub_functions::key_explode('/', $value, true));

                    $temp_stack = $temp->get_stack();
                    $cur_stack = $stack->get_stack();
                    sort($temp_stack);
                    sort($cur_stack);
                    
                    if ($temp_stack == $cur_stack)
                    {
                        $stack->set_stack($temp->get_stack());
                        unset($this->unibox->session->env->input->administration_checkbox);
                        ub_stack::kill_instance('temp');
                        return;
                    }
                    ub_stack::kill_instance('temp');
                }
                elseif (!empty($this->unibox->session->env->alias->url_get))
                {
                    $temp = ub_stack::get_instance('temp');
                    $temp->push($this->unibox->session->env->alias->url_get);

                    if ($temp->get_stack() == $stack->get_stack())
                    {
                        ub_stack::kill_instance('temp');
                        return;
                    }
                    ub_stack::kill_instance('temp');                    

                    $this->unibox->session->var->register('administration_url_get', $this->unibox->session->env->alias->url_get);
                }

                $msg = new ub_message(MSG_WARNING);
                $msg->add_text('TRL_ELEMENTS_LEFT_ON_STACK', array($stack->count()));
                $msg->add_newline();
                $msg->add_text('TRL_DISCARD_STACK_OR_PROCESS_EXISTING_ELEMENTS');
                $msg->begin_form('stack_init', $this->unibox->session->env->alias->name);
                $msg->form->begin_buttonset(false);
                $msg->form->submit('TRL_DISCARD_UCASE', 'discard');
                $msg->form->submit('TRL_PROCESS_EXISTING_ELEMENTS_UCASE', 'keep');
                $msg->form->end_buttonset();
                $msg->form->end_form();
                $msg->display();
                return;
            }
        }

        // check if datasets were submitted by checkbox
        if (isset($this->unibox->session->env->input->administration_checkbox))
        {
            // set administration alias
            if (isset($this->unibox->session->var->administration_alias))
                $stack->set_administration($this->unibox->session->var->administration_alias);
            
            foreach ($this->unibox->session->env->input->administration_checkbox as $value)
                $stack->unshift(ub_functions::key_explode('/', $value, true));

            unset($this->unibox->session->env->input->administration_checkbox);
            return;
        }

        // check if a dataset was submitted via get and stack was empty
        if (!empty($this->unibox->session->env->alias->url_get))
        {
            // set administration alias
            if (isset($this->unibox->session->env->alias->url_get['administration_fallback']))
            {
                $stack->set_administration($this->unibox->session->env->alias->url_get['administration_fallback']);
                unset($this->unibox->session->env->alias->url_get['administration_fallback']);
            }
            elseif (isset($this->unibox->session->env->alias->last) && isset($this->unibox->session->env->alias->last->name))
                $stack->set_administration($this->unibox->session->env->alias->last->name);

            $stack->unshift($this->unibox->session->env->alias->url_get);
            $this->unibox->session->env->alias->url_get = array();
            return;
        }

        // check if a dataset was submitted via get and stack was not empty
        if (isset($this->unibox->session->var->administration_url_get))
        {
            $stack->unshift($this->unibox->session->var->administration_url_get);
            $this->unibox->session->var->unregister('administration_url_get');
            return;
        }

        $this->stack_check();
    }

	protected function stack_check()
	{
		$stack = ub_stack::get_instance();

		if ($stack->is_empty())
        {
            if (!$stack->is_valid())
            {
                $msg = new ub_message(MSG_ERROR);
                $msg->add_text('TRL_NO_DATA_PASSED');
                $msg->display();
                
                if ($stack->get_administration() !== null)
                    $this->unibox->switch_alias($stack->get_administration(), true);
            }
            else
            {
                if (!$stack->has_single_element())
                {
                    $msg = new ub_message(MSG_NOTICE);
                    $msg->add_text('TRL_STACK_PROCESSED');
                    $msg->display();
                }

                $admin = $stack->get_administration();
                ub_stack::kill_instance();
                
                if ($admin !== null)
                    $this->unibox->switch_alias($admin, true);
            }
        }
	}

    public function password_request()
    {
        $validator = ub_validator::get_instance();
        return (int) $validator->form_validate('password_request');
    }

    public function password_request_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_REQUEST_PASSWORD', true);
        $this->unibox->xml->add_value('form_name', 'password_request');
        $form = ub_form_creator::get_instance();
        $form->begin_form('password_request', 'password_request');
        $form->begin_fieldset('TRL_GENERAL');
        $form->text('email', 'TRL_EMAIL_ADDRESS', '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_EMAIL);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_REQUEST_PASSWORD');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();
        $form->end_form();
        return 0;
    }

    public function password_request_process()
    {
        $sql_string =  'SELECT
                          user_id,
                          user_name,
                          user_email,
                          user_lang_ident
                        FROM
                          sys_users
                        WHERE
                          user_email = \''.$this->unibox->session->env->form->password_request->data->email.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to retrieve user id');
        if ($result->num_rows() != 1)
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_UNKNOWN_EMAIL_ADDRESS');
            $msg->display();
            return 0;
        }
        
        list($user_id, $user_name, $user_email, $user_lang_ident) = $result->fetch_row();

        // generate new password
        $password = ub_functions::get_random_string(10, true);

        // generate activation key
        $activation_key = ub_functions::get_random_string(32);

        $email = new ub_email();
        $email->set_rcpt($user_email, $user_name);
        $email->load_template('password_request', $user_lang_ident);
        $email->set_replacement('user_name', $user_name);
        $email->set_replacement('user_email', $user_email);
        $email->set_replacement('page_url', $this->unibox->config->system->page_url);
        $email->set_replacement('new_password', $password);
        $email->set_replacement('activation_key', $activation_key);
        $email->set_replacement('activation_link', $this->unibox->create_link('password_reset', true, array('email' => $user_email, 'key' => $activation_key)));
        
        // send email
        if (!$email->send())
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_PASSWORD_REQUEST_FAILED');
            $msg->display();
            return 0;
        }

        // set temporary password & activation key
        $sql_string =  'UPDATE
                          sys_users
                        SET
                          user_key = \''.$activation_key.'\',
                          user_temp_password = \''.sha1($password).'\'
                        WHERE
                          user_id = '.$user_id;
        $this->unibox->db->query($sql_string, 'failed to update user with activation key and temporary password');
        if ($this->unibox->db->affected_rows() == 1)
        {
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_NEW_PASSWORD_SUCCESSFULLY_REQUESTED');
            $msg->display();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_PASSWORD_REQUEST_FAILED');
            $msg->display();
        }
        return 0;
    }
    
    public function password_reset()
    {
        $validator = ub_validator::get_instance();
        $validator->reset();

        if ($validator->form_sent('password_reset'))
            return $validator->form_validate('password_reset');
        elseif (isset($_GET['email']) || isset($_GET['key']))
        {           
            $validator->validate('GET', 'email', TYPE_STRING, CHECK_NOTEMPTY, 'TRL_ERR_ENTER_EMAIL_ADDRESS');
            $validator->validate('GET', 'email', TYPE_STRING, CHECK_EMAIL, 'TRL_ERR_ENTER_VALID_EMAIL_ADDRESS');
            $validator->validate('GET', 'key', TYPE_STRING, CHECK_NOTEMPTY, 'TRL_ERR_ENTER_KEY');
            $validator->validate('GET', 'key', TYPE_STRING, CHECK_INRANGE, 'TRL_ERR_ENTER_KEY_32_CHARS', array(32, 32));
            $this->unibox->session->env->form->password_reset->data = $this->unibox->session->env->input;
            return $validator->get_result();
        }
        else
            return 0;
    }

    public function password_reset_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_RESET_PASSWORD', true);
        $this->unibox->xml->add_value('form_name', 'password_reset');
        $form = ub_form_creator::get_instance();
        $form->begin_form('password_reset', 'password_reset');
        $form->begin_fieldset('TRL_GENERAL');
        $form->text('email', 'TRL_EMAIL_ADDRESS', isset($this->unibox->session->env->form->password_reset->data->email) ? $this->unibox->session->env->form->password_reset->data->email : '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_EMAIL);
        $form->text('key', 'TRL_KEY', isset($this->unibox->session->env->form->password_reset->data->key) ? $this->unibox->session->env->form->password_reset->data->key : '', 35);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_INRANGE, array(32, 32));
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_RESET_PASSWORD');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();
        $form->end_form();
        return 0;
    }

    public function password_reset_process()
    {
        $sql_string  = 'SELECT
                          user_id,
                          user_key,
                          user_temp_password
                        FROM
                          sys_users
                        WHERE
                          user_id > 1
                          AND
                          user_email = \''.$this->unibox->session->env->form->password_reset->data->email.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to get activation key');
        if ($result->num_rows() != 1)
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_UNKNOWN_EMAIL_ADDRESS');
            $msg->display();
            return 0;
        }

        list($id, $act_key, $temp_password) = $result->fetch_row();
        if ($act_key != $this->unibox->session->env->form->password_reset->data->key || strlen($act_key) != 32 || strlen($temp_password) != 40)
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ACTIVATION_KEY_INVALID');
            $msg->display();
            return 0;
        }

        $sql_string  = 'UPDATE
                          sys_users
                        SET
                          user_password = \''.$temp_password.'\',
                          user_key = NULL,
                          user_temp_password = NULL
                        WHERE
                          user_id = '.$id;
        $result = $this->unibox->db->query($sql_string, 'failed to reset password');
        if ($this->unibox->db->affected_rows() == 1)
        {
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_PASSWORD_SUCCESSFULLY_RESET');
            $msg->display();
            
            // reset form
            ub_form_creator::reset('password_reset');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_PASSWORD_RESET_FAILED');
            $msg->display();
        }
        return 0;
    }

    public function user_activate()
    {
        $validator = ub_validator::get_instance();
        $validator->reset();

        if ($validator->form_sent('user_activate'))
            return $validator->form_validate('user_activate');
        elseif (isset($_GET['email']) || isset($_GET['key']))
        {           
            $validator->validate('GET', 'email', TYPE_STRING, CHECK_NOTEMPTY, 'TRL_ERR_ENTER_EMAIL_ADDRESS');
            $validator->validate('GET', 'email', TYPE_STRING, CHECK_EMAIL, 'TRL_ERR_ENTER_VALID_EMAIL_ADDRESS');
            $validator->validate('GET', 'key', TYPE_STRING, CHECK_NOTEMPTY, 'TRL_ERR_ENTER_KEY');
            $validator->validate('GET', 'key', TYPE_STRING, CHECK_INRANGE, 'TRL_ERR_ENTER_KEY_32_CHARS', array(32, 32));
            $this->unibox->session->env->form->user_activate->data = $this->unibox->session->env->input;
            return $validator->get_result();
        }
        else
            return 0;
    }

    public function user_activate_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_USER_ACTIVATE', true);
        $this->unibox->xml->add_value('form_name', 'user_activate');
        $form = ub_form_creator::get_instance();
        $form->begin_form('user_activate', 'user_activate');
        $form->begin_fieldset('TRL_GENERAL');
        $form->text('email', 'TRL_EMAIL_ADDRESS', isset($this->unibox->session->env->form->user_activate->data->email) ? $this->unibox->session->env->form->user_activate->data->email : '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_EMAIL);
        $form->text('key', 'TRL_KEY', isset($this->unibox->session->env->form->user_activate->data->key) ? $this->unibox->session->env->form->user_activate->data->key : '', 35);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_INRANGE, array(32, 32));
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_ACTIVATE_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();
        $form->end_form();
        return 0;
    }

    public function user_activate_process()
    {
        $sql_string  = 'SELECT
                          user_id,
                          user_active,
                          user_key
                        FROM
                          sys_users
                        WHERE
                          user_id > 1
                          AND
                          user_email = \''.$this->unibox->session->env->form->user_activate->data->email.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to get activation key');
        if ($result->num_rows() != 1)
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_UNKNOWN_EMAIL_ADDRESS');
            $msg->display();
            return 0;
        }

        list($id, $active, $act_key) = $result->fetch_row();

        if ($active)
        {
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_USER_ACTIVATED_OR_ALREADY_ACTIVE');
            $msg->display();
            return 0;
        }

        if ($act_key != $this->unibox->session->env->form->user_activate->data->key)
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ACTIVATION_KEY_INVALID');
            $msg->display();
            return 0;
        }

        $sql_string  = 'UPDATE
                          sys_users
                        SET
                          user_active = 1,
                          user_key = NULL
                        WHERE
                          user_id = '.$id;
        $result = $this->unibox->db->query($sql_string, 'failed to activate user');
        if ($this->unibox->db->affected_rows() == 1)
        {
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_USER_SUCCESSFULLY_ACTIVATED');
            $msg->display();

            // reset form
            ub_form_creator::reset('user_activate');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_USER_ACTIVATION_FAILED');
            $msg->display();
        }
        return 0;
    }
}

?>