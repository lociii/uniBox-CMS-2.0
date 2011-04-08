<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_modulemanager
{
    /**
    * version
    *
    * contains the class version
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
    private static $instance = null;

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
        return ub_modulemanager::version;
    }

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      ub_language object
    */
    public static function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_modulemanager;
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
        $this->unibox->config->load_config('modulemanager');
    }
    
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_MODULEMANAGER_WELCOME_TEXT');
        $msg->display();
        return 0;
    }

    /**
    * administrate()
    *
    * container administration - show/process preselect
    */
    public function administrate()
    {
    	$admin = ub_administration_ng::get_instance('modulemanager_administrate');
    	
        $this->unibox->load_template('shared_administration');
        $sql_string  = 'SELECT
                          a.module_ident,
                          c.string_value AS module_name,
                          a.module_version,
                          a.module_active,
                          a.module_builtin,
                          a.module_install_date,
                          b.user_name
                        FROM
                          sys_modules AS a
                            INNER JOIN sys_users AS b
                              ON b.user_id = a.module_install_user_id
                            INNER JOIN sys_translations AS c
                              ON c.string_ident = a.si_module_name';

        // add header fields
        $admin->add_field('TRL_NAME', 'c.string_value');
        $admin->add_field('TRL_VERSION', 'a.module_version');
        $admin->add_field('TRL_INSTALL_DATE', 'a.module_install_date');
        $admin->add_field('TRL_INSTALL_USER', 'b.user_name');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
            $right_info = ($this->unibox->session->has_right('modulemanager_info'));
            $right_activate = ($this->unibox->session->has_right('modulemanager_activate'));
            $right_uninstall = ($this->unibox->session->has_right('modulemanager_uninstall'));
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            {
                $admin->begin_dataset(false);

                $admin->add_dataset_ident('module_ident', $row['module_ident']);
                $admin->set_dataset_descr($row['module_name']);

                $admin->add_data($row['module_name']);
                $admin->add_data($row['module_version']);
                $time = new ub_time(TIME_TYPE_DB, TIME_TYPE_USER);
                $time->parse_datetime($row['module_install_date']);
                $admin->add_data($time->get_datetime(), false, $row['module_install_date']);
                $admin->add_data($row['user_name']);

                if ($right_info)
                    $admin->add_option('info_true.gif', 'TRL_ALT_INFO', 'modulemanager_info');
                else
                    $admin->add_option('info_false.gif', 'TRL_ALT_INFO_FORBIDDEN');

                if ($right_activate)
                {
                    if ($row['module_active'])
                        $admin->add_option('activate_deactivate_true.gif', 'TRL_ALT_DEACTIVATE', 'modulemanager_deactivate');
                    else
                        $admin->add_option('activate_true.gif', 'TRL_ALT_ACTIVATE', 'modulemanager_activate');
                }
                else
                    $admin->add_option('activate_false.gif', 'TRL_ALT_ACTIVATE_FORBIDDEN');

                if ($row['module_builtin'] == 0)
                {
                    if ($right_uninstall)
                        $admin->add_option('content_delete_true.gif', 'TRL_ALT_UNINSTALL', 'modulemanager_uninstall');
                    else
                        $admin->add_option('content_delete_false.gif', 'TRL_ALT_UNINSTALL_FORBIDDEN');
                }
                else
                    $admin->add_option('content_delete_false.gif', 'TRL_ALT_UNINSTALL_SYSTEM_FORBIDDEN');

                $admin->end_dataset();
            }
        }
        $admin->show('modulemanager_administrate');
    }

    public function install()
    {
        // process language pack if not conflicts
        if (isset($this->unibox->session->var->language_install))
            return 2;
        else
        {
            $validator = ub_validator::get_instance();
            return $validator->form_validate('modulemanager_install');
        }
    }

    public function install_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'modulemanager_install');
        $form = ub_form_creator::get_instance();
        $form->begin_form('modulemanager_install', 'modulemanager_install');
        $form->begin_fieldset('TRL_GENERAL');
        $form->file('module_pack', 'TRL_MODULE_PACK', DIR_TEMP);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->file('language_pack', 'TRL_LANGUAGE_PACK', DIR_TEMP);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_INSTALL_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'modulemanager_welcome');
        $form->end_buttonset();
        $form->end_form();
    }

    public function install_check()
    {
        try
        {
            if (!$this->unibox->module_available('languagemanager'))
                throw new ub_exception_general('TRL_ERR_LANGUAGEMANAGER_NOT_AVAILABLE');
    
                // open, check and process module pack
            if (!($module_tar = $this->module_check_tar()))
                throw new ub_exception_general('TRL_ERR_INVALID_MODULE_PACK');
    
            // convert install file to simplexml
            $xml_module = simplexml_load_string($module_tar->{'install.xml'});
    
            // check if module is already installed
            $sql_string  = 'SELECT
                              module_ident
                            FROM
                              sys_modules
                            WHERE
                              module_ident = \''.$this->unibox->db->cleanup((string)$xml_module->module_ident).'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select modules');
            if ($result->num_rows() > 0)
                throw new ub_exception_general('TRL_ERR_MODULE_ALREADY_INSTALLED_PLEASE_UPDATE');

            // get languagemanager instance
            $ub_languagemanager = ub_languagemanager::get_instance();
    
            if (!($language_tar = $ub_languagemanager->language_check_tar('modulemanager_install')))
                throw new ub_exception_general('TRL_ERR_INVALID_LANGUAGE_PACK');
    
            // convert language pack to simplexml
            $xml_language = simplexml_load_string($language_tar->{'translations.xml'});
    
            // check if language pack matches module
            if ((float)$xml_module->module_ident != (float)$xml_language->module_ident)
                throw new ub_exception_general('TRL_ERR_PROVIDED_LANG_PACK_WRONG_MODULE');
    
            // check if language pack version is sufficient for the given module
            if ((float)$xml_module->lang_pack_min_version > (float)$xml_language->lang_version)
                throw new ub_exception_general('TRL_ERR_PROVIDED_LANG_PACK_OUTDATED');

            $module_xml = $module_tar->{'install.xml'};
            unset($module_tar->{'install.xml'});
            if (!ub_functions::tar_check_extract_content($module_tar, DIR_BASE, '/'))
                throw new ub_exception_general('TRL_ERR_MODULE_CANNOT_BE_EXTRACTED');

            // save variables
            $this->unibox->session->var->register('module_install', $module_xml);
            $this->unibox->session->var->register('module_install_archive', $module_tar);
            $this->unibox->session->var->register('language_install', $language_tar->{'translations.xml'});
        }
        catch (ub_exception_general $exception)
        {
            $exception->process('TRL_INSTALLATION_FAILED');
            ub_form_creator::reset('modulemanager_install');
            return;
        }

        // parse language pack for conflicts
        if ($ub_languagemanager->install_process_xml())
            return 1;
        else
            return 2;
    }

    protected function module_check_tar($form = 'modulemanager_install')
    {
        // check for valid tar file, open it and get content
        if (
            !($tar_content = ub_functions::tar_get_content(DIR_BASE.$this->unibox->session->env->form->$form->data->module_pack['tmp_path'].$this->unibox->session->env->form->$form->data->module_pack['tmp_name'], $this->unibox->session->env->form->$form->data->module_pack['type']))
            ||
            !($xml = @DOMDocument::loadXML($tar_content->{'install.xml'}))
            ||
            !file_exists(DIR_BASE.DIR_FRAMEWORK_SCHEMATA.'module.xsd')
            ||
            !$xml->schemaValidate(DIR_BASE.DIR_FRAMEWORK_SCHEMATA.'module.xsd')
           )
            return false;
        return $tar_content;
    }

    public function install_process()
    {
        try
        {
        	$created_tables = array();
        	
            // open xml
            $xml = simplexml_load_string($this->unibox->session->var->module_install);
            $module_ident = '\''.$this->unibox->db->cleanup($xml->module_ident).'\'';
/*
			if (isset($xml->tables->table))
			{
				// create data tables
				foreach ($xml->tables->table as $table)
				{
					$this->unibox->db->tools->create_table_from_structure($table);
					$created_tables[] = $table;
				}

				// create constraints
				foreach ($xml->tables->table as $table)
					$this->unibox->db->tools->create_constraint_from_structure($table);
			}
*/
			// begin transaction
		    $this->unibox->db->begin_transaction(true);

            // get languagemanager instance
            $ub_languagemanager = ub_languagemanager::get_instance();
            // insert language pack
            if (!$ub_languagemanager->install_insert(true))
                throw new ub_exception_transaction('failed to install language pack');

			// process xml file
			$this->process_xml($xml);

			// set installing user
			$time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
			$time->now();
			$sql_string  = 'UPDATE
							  sys_modules
							SET
							  module_install_user_id = '.$this->unibox->session->user_id.',
							  module_install_date = \''.$time->get_datetime().'\'
							WHERE
							  module_ident = '.$module_ident;
			$this->unibox->db->query($sql_string, 'failed to update module info');

			// check database integrity
            if (($tables = $this->unibox->db->tools->check_integrity()) !== true)
                throw new ub_exception_transaction('database inconsistent after module install');

			// extract files
            if (!ub_functions::tar_extract_content($this->unibox->session->var->module_install_archive, DIR_BASE))
            	throw new ub_exception_transaction('failed to extract module');

			// commit changes
	        $this->unibox->db->commit();

	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_INSTALLATION_SUCCESS');
	        $msg->add_newline(2);
	        $msg->add_text('TRL_PLEASE_REASSIGN_TRANSLATIONS');
	        $msg->display();
        }
        catch (ub_exception_transaction $exception)
        {
		    // drop all created tables
			foreach ($created_tables as $table)
				$this->unibox->db->tools->drop_table($table);
			$exception->process('TRL_INSTALLATION_FAILED');
        }
        catch (ub_exception_database $exception)
        {
		    // drop all created tables
			foreach ($created_tables as $table)
				$this->unibox->db->tools->drop_table($table);
			$exception->process('TRL_INSTALLATION_FAILED');
        }

        ub_form_creator::reset('modulemanager_install');
        $this->unibox->session->var->unregister('module_install');
        $this->unibox->session->var->unregister('module_install_archive');
    }

    protected function install_cleanup_xml($node)
    {
        foreach ($node as $name => $value)
            if ((string)$value != '')
                $node->$name = '\''.$this->unibox->db->cleanup($value).'\'';
            else
                $node->$name = 'NULL';
        return $node;
    }

    public function update()
    {
        $validator = ub_validator::get_instance();
        return $validator->form_validate('modulemanager_update');
    }

    public function update_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'modulemanager_update');
        $form = ub_form_creator::get_instance();
        $form->begin_form('modulemanager_update', 'modulemanager_update');
        $form->begin_fieldset('TRL_GENERAL');
        $form->file('module_pack', 'TRL_MODULE_PACK', DIR_TEMP);
        $form->set_condition(CHECK_NOTEMPTY);
        $file_types = array('application/x-tar');
        if (extension_loaded('bz2'))
            $file_types[] = 'application/x-bzip2';
        if (extension_loaded('zlib'))
            $file_types[] = 'application/x-gzip';
        $form->set_condition(CHECK_FILE_EXTENSION, $file_types);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_UPDATE_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'modulemanager_welcome');
        $form->end_buttonset();
        $form->end_form();
    }

    public function update_process()
    {
		try
        {
            // open, check and process module pack
            if (!($tar = $this->module_check_tar('modulemanager_update')))
                throw new ub_exception_general('TRL_ERR_INVALID_MODULE_PACK');

            // convert install file to simplexml
            $xml = simplexml_load_string($tar->{'install.xml'});
            unset($tar->{'install.xml'});

            // check if module is already installed
            $sql_string  = 'SELECT
                              module_version
                            FROM
                              sys_modules
                            WHERE
                              module_ident = \''.$this->unibox->db->cleanup((string)$xml->module_ident).'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select modules');
	        if ($result->num_rows() > 0)
	        {
	            list($version) = $result->fetch_row();
	            if (ub_functions::compare_versions($version, (string)$xml->data->sys_modules->node->module_version) != -1)
	            	throw new ub_exception_general('TRL_ERR_MODULE_PACK_OUTDATED');
	        }
	        else
				throw new ub_exception_general('TRL_ERR_MODULE_NOT_INSTALLED');

			// check if everything is writable
            if (!ub_functions::tar_check_extract_content($tar, DIR_BASE, '/'))
                throw new ub_exception_general('TRL_ERR_MODULE_CANNOT_BE_EXTRACTED');

			// TODO: modify tables

			// extract updatefile
			if (
				isset($tar->modules->{(string)$xml->module_ident}->{'update.php'})
				&&
					(
						(
							!file_exists(DIR_BASE.DIR_MODULES.(string)$xml->module_ident.'/update.php')
							&&
							!is_writable(DIR_BASE.DIR_MODULES.(string)$xml->module_ident)
						)
						||
						(
							file_exists(DIR_BASE.DIR_MODULES.(string)$xml->module_ident.'/update.php')
							&&
							!is_writable(DIR_BASE.DIR_MODULES.(string)$xml->module_ident.'/update.php')
						)
						||
						!file_put_contents(DIR_BASE.DIR_MODULES.(string)$xml->module_ident.'/update.php', $tar->modules->{(string)$xml->module_ident}->{'update.php'})
					)
				)
				throw new ub_exception_general('TRL_ERR_UPDATEFILE_CANNOT_BE_EXTRACTED');
			unset($tar->modules->{(string)$xml->module_ident}->{'update.php'});

			// call updatescript
	        if (file_exists(DIR_BASE.DIR_MODULES.(string)$xml->module_ident.'/update.php'))
	        {
	            $update = new ub_update((string)$xml->module_ident);
	            if (!$update->update())
	            	throw new ub_exception_general('TRL_ERR_UPDATESCRIPT_FAILED');
	        }

			// begin update
			$this->unibox->db->begin_transaction(true);

			// process module xml
			$this->process_xml($xml);

			// assign translations
    		$languagemanager = ub_languagemanager::get_instance();
    		$languagemanager->translations_assign_execute();
    		$languagemanager->translations_cleanup_process(true);

			// check database integrity
            if (($tables = $this->unibox->db->tools->check_integrity()) !== true)
                throw new ub_exception_transaction('database inconsistent after module install');

			// update module statistics
			$time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
			$time->now();
			$sql_string  = 'UPDATE
							  sys_modules
							SET
							  module_update_user_id = '.$this->unibox->session->user_id.',
							  module_update_date = \''.$time->get_datetime().'\'
							WHERE
							  module_ident = \''.$this->unibox->db->cleanup((string)$xml->module_ident).'\'';
			$this->unibox->db->query($sql_string, 'failed to update module info');

	        if (!ub_functions::tar_extract_content($tar, DIR_BASE))
	        	throw new ub_exception_transaction('failed to update module table');

	        $this->unibox->db->commit();
	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_UPDATE_SUCCESS');
	        $msg->display();
        }
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_UPDATE_FAILED');
		}
        catch (ub_exception_general $exception)
        {
            $exception->process('TRL_UPDATE_FAILED');
        }
        
        ub_form_creator::reset('modulemanager_update');
        $this->unibox->switch_alias('modulemanager_administrate', true);
    }

    public function info()
    {
        $sql_string  = 'SELECT
                          module_ident
                        FROM
                          sys_modules';
        
        $validator = ub_validator::get_instance();
        if (!$validator->validate('GET', 'module_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
        {
            $this->unibox->display_error();
            return;
        }
        
        // collect module data
        $sql_string  = 'SELECT
                          c.string_value AS module_name,
                          a.module_version,
                          a.module_active,
                          a.module_builtin,
                          a.module_install_date,
                          b.user_name
                        FROM
                          sys_modules AS a
                            INNER JOIN sys_users AS b
                              ON b.user_id = a.module_install_user_id
                            INNER JOIN sys_translations AS c
                              ON c.string_ident = a.si_module_name
                        WHERE
                          a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select modules');
        if ($result->num_rows() > 0)
        {
            $time = new ub_time(TIME_TYPE_DB, TIME_TYPE_USER);
            $row = $result->fetch_row(FETCHMODE_ASSOC);

            $sql_string  = 'SELECT
                              COUNT(*) AS action_count
                            FROM
                              sys_actions
                            WHERE
                              module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select modules');
            list($action_count) = $result->fetch_row();

            $sql_string  = 'SELECT
                              COUNT(*) AS path_count
                            FROM
                              sys_paths AS a
                                INNER JOIN sys_actions AS b
                                  ON b.action_ident = a.action_ident
                            WHERE
                              b.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';;
            $result = $this->unibox->db->query($sql_string, 'failed to select modules');
            list($path_count) = $result->fetch_row();

            $msg = new ub_message(MSG_INFO);
            $msg->begin_summary();
            $msg->add_summary_content('TRL_NAME', $row['module_name']);
            $msg->add_summary_content('TRL_VERSION', $row['module_version']);
            $time->parse_datetime($row['module_install_date']);
            $msg->add_summary_content('TRL_INSTALL_DATE', $time->get_datetime());
            $msg->add_summary_content('TRL_INSTALL_USER', $row['user_name']);
            $msg->add_newline();
            
            $msg->add_summary_content('TRL_MODULE_INFO_BUILTIN', ($row['module_builtin'] ? 'TRL_YES' : 'TRL_NO'), true, array(), array(), $row['module_builtin']);
            $msg->add_summary_content('TRL_MODULE_INFO_ACTIVE', ($row['module_active'] ? 'TRL_YES' : 'TRL_NO'), true, array(), array(), $row['module_active']);
            $msg->add_newline();
            
            $msg->add_summary_content('TRL_MODULE_INFO_ACTION_COUNT', $action_count);
            $msg->add_summary_content('TRL_MODULE_INFO_PATHS_COUNT', $path_count);
            $msg->end_summary();
            $msg->add_newline();

            if (file_exists(DIR_BASE.DIR_MODULES.$this->unibox->session->env->input->module_ident.'/changelog.xml') && is_readable(DIR_BASE.DIR_MODULES.$this->unibox->session->env->input->module_ident.'/changelog.xml') && $content = simplexml_load_file(DIR_BASE.DIR_MODULES.$this->unibox->session->env->input->module_ident.'/changelog.xml'))
            {
            	$msg->begin_list();
	            foreach ($content->changelog as $changelog)
	            {
	            	$attributes = $changelog->attributes();
					$msg->add_listentry('TRL_CHANGELOG_FOR_VERSION', true, array($attributes['version']));
	            	
	            	$msg->begin_list();
	            	foreach ($changelog->entry as $entry)
	            		$msg->add_listentry((string)$entry, false);
	            	$msg->end_list();
	            }
	            $msg->end_list();
            }
            
            $msg->add_newline();
            $msg->add_link('modulemanager_administrate', 'TRL_BACK_TO_OVERVIEW');
            $msg->display();
        }
    }

    public function activate()
    {
        $sql_string  = 'SELECT
                          module_ident
                        FROM
                          sys_modules
                        WHERE
                          module_active = 0';
        
        $validator = ub_validator::get_instance();
        if (!$validator->validate('GET', 'module_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
        {
            $this->unibox->display_error();
            return;
        }
        return (int)$validator->form_validate('modulemanager_activate') + 1;
    }
    
    public function activate_confirm()
    {
        // check if module is still required
        $sql_string  = 'SELECT
                          c.string_value
                        FROM sys_module_dependencies AS a
                          INNER JOIN sys_modules AS b
                            ON b.module_ident = a.depends_on_module_ident
                          INNER JOIN sys_translations AS c
                            ON c.string_ident = b.si_module_name
                        WHERE
                          a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                          AND
                          b.module_active = 0';
        $result = $this->unibox->db->query($sql_string, 'failed to select depending modules');
        if ($result->num_rows() > 0)
        {
            $modules = array();
            while (list($module_name) = $result->fetch_row())
                $modules[] = $module_name;
            $msg = new ub_message(MSG_INFO);
            $msg->add_text('TRL_MODULE_STILL_REQUIRED_DEACTIVATE_FIRST', array(implode(', ', $modules)));
            $msg->display();
            $this->unibox->switch_alias('modulemanager_administrate', true);
            return;
        }

        // get module name
        $sql_string  = 'SELECT
                          b.string_value
                        FROM
                          sys_modules AS a
                            INNER JOIN sys_translations AS b
                              ON b.string_ident = a.si_module_name
                        WHERE
                          a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm activation');
        if ($result->num_rows() > 0)
        {
            list($name) = $result->fetch_row();
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_MODULE_ACTIVATE_CONFIRM', array($name));
            $msg->add_newline(2);
            $msg->begin_form('modulemanager_activate', 'modulemanager_activate/module_ident/'.$this->unibox->session->env->input->module_ident);
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'modulemanager_administrate');
            $msg->form->end_buttonset();
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        $msg->display();
    }
    
    public function activate_process()
    {
        $this->unibox->db->begin_transaction();

        try
        {
            // check if module extends another one and deactivate the original's actionbar
            $sql_string  = 'SELECT
                              b.module_actionbar_menu_id
                            FROM sys_modules AS a
                              INNER JOIN sys_modules AS b
                                ON b.module_ident = a.extends_module_ident
                              INNER JOIN sys_menu AS c
                                ON c.menu_id = b.module_actionbar_menu_id
                            WHERE
                              a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                              AND
                              b.module_actionbar_menu_id IS NOT NULL
                              AND
                              c.menu_active = 1';
            $result = $this->unibox->db->query($sql_string, 'failed to select extended module');
            if ($result->num_rows() > 0)
            {
                while (list($actionbar_menu_id) = $result->fetch_row())
                {
                    $sql_string  = 'UPDATE
                                      sys_menu
                                    SET
                                      menu_active = 0
                                    WHERE
                                      menu_id = '.$actionbar_menu_id;
                    $this->unibox->db->query($sql_string, 'failed to switch menu active state for menu '.$actionbar_menu_id);
                    if ($this->unibox->db->affected_rows() != 1)
                        throw new ub_exception_transaction('failed to switch extended modules\'s menu state');
                }
            }

            $sql_string  = 'UPDATE
                              sys_modules
                            SET
                              module_active = 1
                            WHERE
                              module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
            $this->unibox->db->query($sql_string, 'failed to switch active state');
            if ($this->unibox->db->affected_rows() != 1)
                throw new ub_exception_transaction('failed to switch module state');

            $sql_string  = 'UPDATE
                              sys_sessions
                            SET
                              rights = \'\'';
            $this->unibox->db->query($sql_string, 'failed to force rights update in session');
            if ($this->unibox->db->affected_rows() == 0)
                throw new ub_exception_transaction('failed force session set rights');

            // set rights for current session
            $this->unibox->session->set_rights();
        }
        catch (ub_exception_transaction $exception)
        {
            $exception->process('TRL_ACTIVATION_FAILED');
            $this->unibox->switch_alias('modulemanager_administrate', true);
            return;
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS);
        $msg->add_text('TRL_ACTIVATION_SUCCESS');
        $msg->display();
        $this->unibox->switch_alias('modulemanager_administrate', true);
    }

    public function deactivate()
    {
        $sql_string  = 'SELECT
                          module_ident
                        FROM
                          sys_modules
                        WHERE
                          module_active = 1';
        
        $validator = ub_validator::get_instance();
        if (!$validator->validate('GET', 'module_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
        {
            $this->unibox->display_error();
            return;
        }

        // check if module is still required
        $sql_string  = 'SELECT
                          c.string_value
                        FROM sys_module_dependencies AS a
                          INNER JOIN sys_modules AS b
                            ON b.module_ident = a.module_ident
                          INNER JOIN sys_translations AS c
                            ON c.string_ident = b.si_module_name
                        WHERE
                          a.depends_on_module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                          AND
                          b.module_active = 1';
        $result = $this->unibox->db->query($sql_string, 'failed to select depending modules');
        if ($result->num_rows() > 0)
        {
            $modules = array();
            while (list($module_name) = $result->fetch_row())
                $modules[] = $module_name;
            $msg = new ub_message(MSG_INFO);
            $msg->add_text('TRL_MODULE_STILL_REQUIRED_DEACTIVATE_FIRST', array(implode(', ', $modules)));
            $msg->display();
            $this->unibox->switch_alias('modulemanager_administrate', true);
            return;
        }

        return (int)$validator->form_validate('modulemanager_deactivate') + 1;
    }

    public function deactivate_confirm()
    {
        // get module name
        $sql_string  = 'SELECT
                          b.string_value
                        FROM
                          sys_modules AS a
                            INNER JOIN sys_translations AS b
                              ON b.string_ident = a.si_module_name
                        WHERE
                          a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm deactivation');
        if ($result->num_rows() > 0)
        {
            list($name) = $result->fetch_row();
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_MODULE_DEACTIVATE_CONFIRM', array($name));
            $msg->add_newline(2);
            $msg->begin_form('modulemanager_deactivate', 'modulemanager_deactivate/module_ident/'.$this->unibox->session->env->input->module_ident);
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'modulemanager_administrate');
            $msg->form->end_buttonset();
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        $msg->display();
    }

    public function deactivate_process()
    {
        $this->unibox->db->begin_transaction();

        try
        {
            // check if module extends another one and deactivate the original's actionbar
            $sql_string  = 'SELECT
                              b.module_actionbar_menu_id
                            FROM sys_modules AS a
                              INNER JOIN sys_modules AS b
                                ON b.module_ident = a.extends_module_ident
                              INNER JOIN sys_menu AS c
                                ON c.menu_id = b.module_actionbar_menu_id
                            WHERE
                              a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                              AND
                              b.module_actionbar_menu_id IS NOT NULL
                              AND
                              c.menu_active = 0';
            $result = $this->unibox->db->query($sql_string, 'failed to select extended module');
            if ($result->num_rows() > 0)
            {
                while (list($actionbar_menu_id) = $result->fetch_row())
                {
                    $sql_string  = 'UPDATE
                                      sys_menu
                                    SET
                                      menu_active = 1
                                    WHERE
                                      menu_id = '.$actionbar_menu_id;
                    $this->unibox->db->query($sql_string, 'failed to switch menu active state for menu '.$actionbar_menu_id);
                    if ($this->unibox->db->affected_rows() != 1)
                        throw new ub_exception_transaction('failed to switch extended modules\'s menu state');
                }
            }

            $sql_string  = 'UPDATE
                              sys_modules
                            SET
                              module_active = 0
                            WHERE
                              module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
            $this->unibox->db->query($sql_string, 'failed to switch active state');
            if ($this->unibox->db->affected_rows() != 1)
                throw new ub_exception_transaction('failed to switch module state');

            $sql_string  = 'UPDATE
                              sys_sessions
                            SET
                              rights = \'\'';
            $this->unibox->db->query($sql_string, 'failed to force rights update in session');
            if ($this->unibox->db->affected_rows() == 0)
                throw new ub_exception_transaction('failed force session set rights');

            // set rights for current session
            $this->unibox->session->set_rights();
        }
        catch (ub_exception_transaction $exception)
        {
            $exception->process('TRL_DEACTIVATION_FAILED');
            $this->unibox->switch_alias('modulemanager_administrate', true);
            return;
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS);
        $msg->add_text('TRL_DEACTIVATION_SUCCESS');
        $msg->display();
        $this->unibox->switch_alias('modulemanager_administrate', true);
    }

    public function uninstall()
    {
        $sql_string  = 'SELECT
                          module_ident
                        FROM
                          sys_modules
                        WHERE
                          module_builtin = 0';
        
        $validator = ub_validator::get_instance();
        if (!$validator->validate('GET', 'module_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
        {
            $this->unibox->display_error();
            return;
        }
        return (int)$validator->form_validate('modulemanager_uninstall') + 1;
    }

    public function uninstall_confirm()
    {
        // get module name
        $sql_string  = 'SELECT
                          b.string_value
                        FROM
                          sys_modules AS a
                            INNER JOIN sys_translations AS b
                              ON b.string_ident = a.si_module_name
                        WHERE
                          a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm uninstall');
        if ($result->num_rows() > 0)
        {
            list($name) = $result->fetch_row();
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_MODULE_UNINSTALL_CONFIRM', array($name));
            $msg->add_newline(2);

            $sql_string  = 'SELECT
                              COUNT(*) AS count
                            FROM
                              sys_templates AS a
                                INNER JOIN sys_translations AS b
                                  ON b.string_ident = a.si_template_descr
                            WHERE
                              b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              AND
                              a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                            ORDER BY
                              b.string_value';
            $result = $this->unibox->db->query($sql_string, 'failed to select template count');
            list($count_templates) = $result->fetch_row();

            $sql_string  = 'SELECT
                              COUNT(*) AS count
                            FROM
                              sys_styles AS a
                                INNER JOIN sys_translations AS b
                                  ON b.string_ident = a.si_style_descr
                            WHERE
                              b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              AND
                              a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                            ORDER BY
                              b.string_value';
            $result = $this->unibox->db->query($sql_string, 'failed to select style count');
            list($count_styles) = $result->fetch_row();

            if ($count_templates > 0 || $count_styles > 0)
                $msg->add_text('TRL_SELECT_TEMPLATES_AND_STYLES_TO_DELETE');

            $msg->begin_form('modulemanager_uninstall', 'modulemanager_uninstall/module_ident/'.$this->unibox->session->env->input->module_ident);
            if ($count_templates > 0)
            {
                $msg->form->begin_checkbox('template_ident', 'TRL_TEMPLATES');
                $sql_string  = 'SELECT
                                  a.template_ident,
                                  b.string_value
                                FROM
                                  sys_templates AS a
                                    INNER JOIN sys_translations AS b
									  ON
									  (
									  b.string_ident = a.si_template_descr
									  AND
									  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
									  )
                                WHERE
                                  a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                                ORDER BY
                                  b.string_value';
                $msg->form->add_option_sql($sql_string);
                $msg->form->end_checkbox();
            }
    
            if ($count_styles > 0)
            {
                $msg->form->begin_checkbox('style_ident', 'TRL_STYLES');
                $sql_string  = 'SELECT
                                  a.style_ident,
                                  b.string_value
                                FROM
                                  sys_styles AS a
                                    INNER JOIN sys_translations AS b
									  ON
									  (
									  b.string_ident = a.si_style_descr
									  AND
									  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
									  )
                                WHERE
                                  a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                                ORDER BY
                                  b.string_value';
                $msg->form->add_option_sql($sql_string);
                $msg->form->end_checkbox();
            }

            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_UNINSTALL_UCASE');
            $msg->form->cancel('TRL_CANCEL_UCASE', 'modulemanager_administrate');
            $msg->form->end_buttonset();
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        $msg->display();
    }

    public function uninstall_process()
    {
        $files = $tables = $info = array();
        $this->unibox->db->begin_transaction();

        try
        {
            // select data tables
            $sql_string  = 'SELECT
                              a.config_value
                            FROM sys_config AS a
                              INNER JOIN sys_config_groups AS b
                                ON b.config_group_ident = a.config_group_ident
                            WHERE
                              b.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                              AND
                              config_type = \'table\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_class_files');
            while (list($table) = $result->fetch_row())
                $tables[] = $table;

            // select class files to delete
            $sql_string  = 'SELECT
                              class_filename
                            FROM
                              sys_class_files
                            WHERE
                              module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_class_files');
            while (list($filename) = $result->fetch_row())
                if (file_exists(DIR_MODULES.$this->unibox->session->env->input->module_ident.'/'.$filename.'.php'))
                    if (is_writable(DIR_MODULES.$this->unibox->session->env->input->module_ident.'/'.$filename.'.php'))
                        $files[] = DIR_MODULES.$this->unibox->session->env->input->module_ident.'/'.$filename.'.php';
                    else
                        throw new ub_exception_transaction('class file not writable: '.DIR_MODULES.$this->unibox->session->env->input->module_ident.'/'.$filename.'.php');

            // select style files to delete
            if (isset($this->unibox->session->env->form->modulemanager_uninstall))
            {
                if (isset($this->unibox->session->env->form->modulemanager_uninstall->data->style_ident) && count($this->unibox->session->env->form->modulemanager_uninstall->data->style_ident) > 0)
                {
                    $sql_string  = 'SELECT
                                      *
                                    FROM
                                      sys_styles
                                    WHERE
                                      module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                                      AND
                                      style_ident IN (\''.implode('\', \'', $this->unibox->session->env->form->modulemanager_uninstall->data->style_ident).'\')';
                    $result = $this->unibox->db->query($sql_string, 'failed to select from sys_styles');
                    while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                        $info['sys_styles'][] = $row;
                }
        
                // templates, template translations, template styles
                if (isset($this->unibox->session->env->form->modulemanager_uninstall->data->template_ident) && count($this->unibox->session->env->form->modulemanager_uninstall->data->template_ident) > 0)
                {
                    $sql_string  = 'SELECT
                                      *
                                    FROM
                                      sys_templates
                                    WHERE
                                      module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                                      AND
                                      template_ident IN (\''.implode('\', \'', $this->unibox->session->env->form->modulemanager_uninstall->data->template_ident).'\')';
                    $result = $this->unibox->db->query($sql_string, 'failed to select from sys_templates');
                    while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                        $info['sys_templates'][] = $row;
                }
            }
    
            // select template files to delete
            $sql_string  = 'SELECT
                              a.theme_ident,
                              a.subtheme_ident,
                              a.output_format_ident,
                              b.filename_extension
                            FROM
                              sys_theme_output_formats AS a
                                INNER JOIN sys_output_formats AS b
                                  ON b.output_format_ident = a.output_format_ident
                            WHERE
                              b.filename_extension IS NOT NULL';
            $result = $this->unibox->db->query($sql_string, 'failed to select themes');
            while (list($theme, $subtheme, $output_format, $file_extension) = $result->fetch_row())
            {
                if (isset($info['sys_templates']) && count($info['sys_templates']) > 0)
                    foreach ($info['sys_templates'] AS $value)
                        if (file_exists(DIR_THEMES.$theme.'/'.$subtheme.'/'.$output_format.'/templates/'.$this->unibox->session->env->input->module_ident.'/'.$value['template_filename'].'.'.$file_extension))
                            if (is_writable((DIR_THEMES.$theme.'/'.$subtheme.'/'.$output_format.'/templates/'.$this->unibox->session->env->input->module_ident.'/'.$value['template_filename'].'.'.$file_extension)))
                                $files[] = DIR_THEMES.$theme.'/'.$subtheme.'/'.$output_format.'/templates/'.$this->unibox->session->env->input->module_ident.'/'.$value['template_filename'].'.'.$file_extension;
                            else
                                throw new ub_exception_transaction('template file not writable: '.DIR_THEMES.$theme.'/'.$subtheme.'/'.$output_format.'/templates/'.$this->unibox->session->env->input->module_ident.'/'.$value['template_filename'].'.'.$file_extension);
    
                if (isset($info['sys_styles']) && count($info['sys_styles']) > 0)
                    foreach ($info['sys_styles'] AS $value)
                        if (file_exists(DIR_THEMES.$theme.'/'.$subtheme.'/'.$output_format.'/styles/'.$this->unibox->session->env->input->module_ident.'/'.$value['style_filename'].'.css'))
                            if (is_writable(DIR_THEMES.$theme.'/'.$subtheme.'/'.$output_format.'/styles/'.$this->unibox->session->env->input->module_ident.'/'.$value['style_filename'].'.css'))
                                $files[] = DIR_THEMES.$theme.'/'.$subtheme.'/'.$output_format.'/styles/'.$this->unibox->session->env->input->module_ident.'/'.$value['style_filename'].'.css';
                            else
                                throw new ub_exception_transaction('style file not writable: '.DIR_THEMES.$theme.'/'.$subtheme.'/'.$output_format.'/styles/'.$this->unibox->session->env->input->module_ident.'/'.$value['style_filename'].'.css');
            }

            // select actionbar menu
            $sql_string  = 'SELECT
                              module_actionbar_menu_id
                            FROM
                              sys_modules
                            WHERE
                              module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select actionbar menu');
            if ($result->num_rows() == 1)
                list($menu_id) = $result->fetch_row();
    
            // delete actionbar menu - if one
            if (isset($menu_id) && $menu_id !== null)
            {
                $sql_string  = 'DELETE FROM
                                  sys_menu
                                WHERE
                                  menu_id = '.$menu_id;
                $this->unibox->db->query($sql_string, 'failed to delete actionbar menu');
            }
    
            // delete module
            $sql_string  = 'DELETE FROM
                              sys_modules
                            WHERE
                              module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
            $this->unibox->db->query($sql_string, 'failed to uninstall module');
            if ($this->unibox->db->affected_rows() != 1)
                throw new ub_exception_transaction('failed to delete module entry');

            // commit database manipulation
            $this->unibox->db->commit();

            // delete files
            foreach ($files as $file)
            {
                @unlink($file);
                @rmdir(dirname($file));
            }
            
            // delete content tables
            foreach ($tables as $table)
                $this->unibox->db->tools->drop_table($table);

	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_UNINSTALL_SUCCESS');
	        $msg->display();
        }
        catch (ub_exception_transaction $exception)
        {
            $exception->process('TRL_UNINSTALL_FAILED');
        }

        ub_form_creator::reset('modulemanager_uninstall');
        $this->unibox->switch_alias('modulemanager_administrate', true);
    }
    
    protected function process_xml($xml)
    {
    	// get sql builder
    	$sql = new ub_sql_builder(SQL_QUERY_UPDATE);

    	// collect module data
    	$module_data = $this->collect_module_data($xml->module_ident);

		// check if a actionbar menu exists and delete it
		if (isset($module_data['sys_modules'][0]['module_actionbar_menu_id']))
		{
			// get menu items
			$menu_items = array();
			$sql_string  = 'SELECT
							  menu_item_id
							FROM
							  sys_menu_items
							WHERE
							  menu_id = \''.$this->unibox->db->cleanup($module_data['sys_modules'][0]['module_actionbar_menu_id']).'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to select menu items');
			while (list($menu_item) = $result->fetch_row())
				$menu_items[] = $menu_item;

			if (count($menu_items) > 0)
			{
				// delete menu item get
				$sql_string  = 'DELETE FROM
								  sys_menu_items_get
								WHERE
								  menu_item_id IN ('.implode(', ', $menu_items).')';
				$this->unibox->db->query($sql_string, 'failed to delete menu items get');
	
				// delete menu items
				$sql_string  = 'DELETE FROM
								  sys_menu_items
								WHERE
								  menu_item_id IN ('.implode(', ', $menu_items).')';
				$this->unibox->db->query($sql_string, 'failed to delete menu items');
			}

			// delete menu
			$sql_string =  'DELETE FROM
							  sys_menu
							WHERE
							  menu_id = \''.$this->unibox->db->cleanup($module_data['sys_modules'][0]['module_actionbar_menu_id']).'\'';
			$this->unibox->db->query($sql_string, 'failed to delete actionbar menu');
			
			// update module
			$sql_string  = 'UPDATE
							  sys_modules
							SET
							  module_actionbar_menu_id = NULL,
							  module_actionbar_group_ident = NULL
							WHERE
							  module_ident = \''.$xml->module_ident.'\'';
			$this->unibox->db->query($sql_string, 'failed to update module');
			unset($module_data['sys_menu'], $module_data['sys_menu_items']);
		}

		// load xsd file
        $xsd = simplexml_load_file(DIR_BASE.DIR_FRAMEWORK_SCHEMATA.'module.xsd');
		$root = $xsd->children('http://www.w3.org/2001/XMLSchema');

		// get keyrefs from xsd file
		$keyrefs = $matches = array();
		foreach ($root->element->keyref as $node)
		{
			$attributes = $node->attributes();
			$references = (string)$attributes['refer'];
			
			// get table name
			$attributes = $node->selector->attributes();
			preg_match('#data/([^/]+)/node#', (string)$attributes['xpath'], $matches);
			
			// get field name
			$attributes = $node->field->attributes();
			
			$keyrefs[$matches[1]][(string)$attributes['xpath']] = $references;
		}

		// get autoincrements from xsd file
		$autoincrements = array();
		foreach ($root as $node => $child)
		{
			// table fields
			if (isset($child->sequence->element))
			{
				$attributes = $child->attributes();
				$table = (string)$attributes['name'];

				foreach ($child->sequence->element as $element)
				{
					$attributes = $element->attributes();
					if ((string)$attributes['type'] == 'autoincrement')
						$autoincrements[$table][(string)$attributes['name']] = true;
				}
			}
		}

		// loop through all tables
		foreach ($xml->data[0] as $table_name => $table)
		{
			// get primary key
			$primary_key = $this->unibox->db->tools->get_primary_key($table_name);
			
			// initialize table data & keys array
			$keys = $data = array();
			$autoincrement = false;

			// loop through all table rows
			$index = 0;
			foreach ($table as $row)
			{
				// loop through primary key
				foreach ($primary_key as $column_name)
					if (isset($row->$column_name))
						$keys[$index][$column_name] = $row->$column_name;
				
				// save row data
				if (isset($keys[$index]))
					$data[$index] = $row;
					
				$index++;
			}

			// if data is present for current table - update or delete it depending on status in module pack
			if (isset($module_data[$table_name]))
				foreach ($module_data[$table_name] as $row)
				{
					// extract primary key
					$key = array();
					foreach ($primary_key as $column_name)
						$key[$column_name] = $row[$column_name];
						
					// check if dataset exists in xml
					// update if existing in module pack
					if (($row_index = array_search($key, $keys)) !== false)
					{
						$process = false;

						// reset sql builder
						$sql->reset(SQL_QUERY_UPDATE);
						$sql->add_table($table_name);

						foreach ($data[$row_index] as $column_name => $value)
						{
							// get node's attributes
							$attributes = $value->attributes();

							// check if column references an autoincrement key
							if (isset($keyrefs[$table_name][$column_name]) && (list($ref_table, $ref_column) = explode('.', $keyrefs[$table_name][$column_name])) && isset($autoincrements[$ref_table][$ref_column]))
							{
								if (isset($autoincrement_values[$ref_table][(string)$value]))
									$value = $autoincrement_values[$ref_table][(string)$value];
								else
									throw new ub_exception_transaction('invalid autoincrement order');
							}

							if (isset($key[$column_name]))
								$sql->add_condition($column_name, $this->unibox->db->cleanup($value));
							else
							{
								if ((string)$value != '')
			                		$sql->add_field($column_name, null, null, $this->unibox->db->cleanup($value));
			            		else
			                		$sql->add_field($column_name, null, null, null, null, true);
								$process = true;
							}
						}

						if ($process)
							$this->unibox->db->query($sql->get_string(), 'failed to update dataset');
						unset($keys[$row_index], $data[$row_index]);
					}
					// delete if not existing in module pack
					else
					{
						// reset sql builder
						$sql->reset(SQL_QUERY_DELETE);
						$sql->add_table($table_name);
						
						foreach ($key as $column_name => $value)
							$sql->add_condition($column_name, $this->unibox->db->cleanup($value));

						// delete dataset
						$this->unibox->db->query($sql->get_string(), 'failed to delete dataset');
						if ($this->unibox->db->affected_rows() != 1)
							throw new ub_exception_transaction('failed to delete dataset');
					}
				}

			// insert remaining datasets
			foreach ($data as $row_index => $row)
			{
				// reset sql builder
				$sql->reset(SQL_QUERY_INSERT);
				$sql->add_table($table_name);

				// build sql string
				foreach ($data[$row_index] as $column_name => $value)
				{
					// get node's attributes
					$attributes = $value->attributes();

					// check if column references an autoincrement key
					if (isset($keyrefs[$table_name][$column_name]) && (list($ref_table, $ref_column) = explode('.', $keyrefs[$table_name][$column_name])) && isset($autoincrements[$ref_table][$ref_column]))
					{
						if (isset($autoincrement_values[$ref_table][(string)$value]))
							$value = $autoincrement_values[$ref_table][(string)$value];
						else
							throw new ub_exception_transaction('invalid autoincrement order');
					}

					// add field to sql string
					if (!(in_array($column_name, $primary_key) && count($primary_key) == 1 && isset($autoincrements[$table_name][$column_name])))
						if ((string)$value != '')
	                		$sql->add_field($column_name, null, null, $this->unibox->db->cleanup($value));
	            		else
	                		$sql->add_field($column_name, null, null, null, null, true);
				}

				// insert dataset
				$this->unibox->db->query($sql->get_string(), 'failed to insert dataset');
				if ($this->unibox->db->affected_rows() != 1)
					throw new ub_exception_transaction('failed to insert dataset');

				// if key is autoincrement, save new value
				if (isset($autoincrements[$table_name]))
					$autoincrement_values[$table_name][(string)$data[$row_index]->{$primary_key[0]}] = $this->unibox->db->last_insert_id();
			}
		}
    }
    
    public function collect_module_data($module_ident, $templates = null, $styles = null, $aliases = null)
    {
    	// module
        $sql_string =  'SELECT
                          *
                        FROM
                          sys_modules
                        WHERE
                          module_ident = \''.$module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select from sys_modules');
        while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            $info['sys_modules'][] = $row;

        // styles
        if ($styles === null || (is_array($styles) && !empty($styles)))
        {
            $temp_styles = array();
            $sql_string  = 'SELECT
                              *
                            FROM
                              sys_styles
                            WHERE
                              module_ident = \''.$module_ident.'\'';
			if ($styles !== null)
				$sql_string .= ' AND style_ident IN (\''.implode('\', \'', $styles).'\')';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_styles');
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            {
                $info['sys_styles'][] = $row;
            	$temp_styles[] = $row['style_ident'];
            }
        }
    
        // templates, template translations, template styles
        if ($templates === null || (is_array($templates) && !empty($templates)))
        {
        	$temp_templates = array();
            $sql_string  = 'SELECT
                              *
                            FROM
                              sys_templates
                            WHERE
                              module_ident = \''.$module_ident.'\'';
			if ($templates !== null)
				$sql_string .= ' AND template_ident IN (\''.implode('\', \'', $templates).'\')';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_templates');
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            {
                $info['sys_templates'][] = $row;
            	$temp_templates[] = $row['template_ident'];
            }

            // template translations
			if (!empty($temp_templates))
			{
	            $sql_string  = 'SELECT
	                              *
	                            FROM
	                              sys_template_translations
	                            WHERE
	                              template_ident IN (\''.implode('\', \'', $temp_templates).'\')';
	            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_template_translations');
	            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
	                $info['sys_template_translations'][] = $row;
			}

            // template styles
            if (!empty($temp_styles) && !empty($temp_templates))
            {
                $sql_string  = 'SELECT
                                  *
                                FROM
                                  sys_template_styles
                                WHERE
                                  template_ident IN (\''.implode('\', \'', $temp_templates).'\')
                                  AND
                                  style_ident IN (\''.implode('\', \'', $temp_styles).'\')';
                $result = $this->unibox->db->query($sql_string, 'failed to select from sys_template_styles');
                while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                    $info['sys_template_styles'][] = $row;
            }
        }

        // class files
        $sql_string  = 'SELECT
                          *
                        FROM
                          sys_class_files
                        WHERE
                          module_ident = \''.$module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select from sys_class_files');
        if ($result->num_rows() > 0)
        {
        	$temp = array();
	        while ($row = $result->fetch_row(FETCHMODE_ASSOC))
	        {
	            $info['sys_class_files'][] = $row;
	            $temp[] = $row['class_name'];
	        }

            // state functions
            $sql_string  = 'SELECT
                              *
                            FROM
                              sys_state_functions
                            WHERE
                              class_name IN (\''.implode('\', \'', $temp).'\')';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_state_functions');
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                $info['sys_state_functions'][] = $row;
        }

        // menu, menu items, menu items get
        if (isset($info['sys_modules']))
        {
	        $module_actionbar_menu_id = $info['sys_modules'][0]['module_actionbar_menu_id'];
	        if ($module_actionbar_menu_id !== null)
	        {
	            $sql_string  = 'SELECT
	                              *
	                            FROM
	                              sys_menu
	                            WHERE
	                              menu_id = \''.$module_actionbar_menu_id.'\'';
	            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_menu');
	            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
	                $info['sys_menu'][] = $row;
	
	            // menu items
	            $sql_string  = 'SELECT
	                              *
	                            FROM
	                              sys_menu_items
	                            WHERE
	                              menu_id = \''.$module_actionbar_menu_id.'\'
	                            ORDER BY
	                              menu_item_parent_id ASC';
	            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_menu_items');
	            if ($result->num_rows() > 0)
	            {
	                $temp = $menu_items = array();
	                $count = 1;
	                while ($row = $result->fetch_row(FETCHMODE_ASSOC))
	                {
	                    $temp[] = $row['menu_item_id'];
	                    $menu_items[$row['menu_item_id']] = $count;
	                    $row['menu_item_id'] = $count;
	                    if ($row['menu_item_parent_id'] !== null)
	                        $row['menu_item_parent_id'] = $menu_items[$row['menu_item_parent_id']];
	                    
	                    $info['sys_menu_items'][] = $row;
	                    $count++;
	                }
	
	                $sql_string  = 'SELECT
	                                  *
	                                FROM
	                                  sys_menu_items_get
	                                WHERE
	                                  menu_item_id IN ('.implode(', ', $temp).')';
	                $result = $this->unibox->db->query($sql_string, 'failed to select from sys_class_files');
	                while ($row = $result->fetch_row(FETCHMODE_ASSOC))
	                    $info['sys_menu_items_get'][] = $row;
	            }
	        }
        }

        // actions, action inheritance, dialogs, paths
        $sql_string  = 'SELECT
                          *
                        FROM
                          sys_actions
                        WHERE
                          module_ident = \''.$module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select from sys_actions');
        if ($result->num_rows() > 0)
        {
            $temp = array();
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            {
                $info['sys_actions'][] = $row;
                $temp[] = $row['action_ident'];
            }

            // action inheritance
            $sql_string  = 'SELECT
                              *
                            FROM
                              sys_action_inheritance
                            WHERE
                              inherits_from_action_ident IN (\''.implode('\', \'', $temp).'\')';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_action_inheritance');
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                $info['sys_action_inheritance'][] = $row;

            // dialogs
            $sql_string  = 'SELECT
                              *
                            FROM
                              sys_dialogs
                            WHERE
                              action_ident IN (\''.implode('\', \'', $temp).'\')
                            ORDER BY
                              action_ident ASC,
                              step ASC';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_dialogs');
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                $info['sys_dialogs'][] = $row;

            // paths
            $sql_string  = 'SELECT
                              *
                            FROM
                              sys_paths
                            WHERE
                              action_ident IN (\''.implode('\', \'', $temp).'\')';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_paths');
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                $info['sys_paths'][] = $row;
			
            // alias, alias get
            if ($aliases === null || (is_array($aliases) && !empty($aliases)))
            {
                $sql_string  = 'SELECT
                                  *
                                FROM
                                  sys_alias
                                WHERE
                                  action_ident IN (\''.implode('\', \'', $temp).'\')';
				if ($aliases !== null)
					$sql_string .= ' AND alias IN (\''.implode('\', \'', $aliases).'\')';
                $result = $this->unibox->db->query($sql_string, 'failed to select from sys_alias');
                if ($result->num_rows() > 0)
                {
                    $temp = array();
                    while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                    {
                        $info['sys_alias'][] = $row;
                        $temp[] = $row['alias'];
                    }
        
                    // alias get
                    $sql_string  = 'SELECT
                                      *
                                    FROM
                                      sys_alias_get
                                    WHERE
                                      alias IN (\''.implode('\', \'', $temp).'\')';
                    $result = $this->unibox->db->query($sql_string, 'failed to select from sys_alias_get');
                    while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                        $info['sys_alias_get'][] = $row;
                }
            }
        }

        // config groups, config
        $sql_string  = 'SELECT
                          *
                        FROM
                          sys_config_groups
                        WHERE
                          module_ident = \''.$module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select from sys_config_groups');
        if ($result->num_rows() > 0)
        {
            $temp = array();
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            {
                $info['sys_config_groups'][] = $row;
                $temp[] = $row['config_group_ident'];
            }

            // config
            $sql_string  = 'SELECT
                              *
                            FROM
                              sys_config
                            WHERE
                              config_group_ident IN (\''.implode('\', \'', $temp).'\')';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_config');
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                $info['sys_config'][] = $row;
        }

        // email containers, emails
        $sql_string  = 'SELECT
                          *
                        FROM
                          sys_email_templates_container
                        WHERE
                          module_ident = \''.$module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select from sys_email_templates_container');
        if ($result->num_rows() > 0)
        {
            $temp = array();
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            {
                $info['sys_email_templates_container'][] = $row;
                $temp[] = $row['template_container_ident'];
            }

            // emails
            $sql_string  = 'SELECT
                              *
                            FROM
                              sys_email_templates
                            WHERE
                              template_container_ident IN (\''.implode('\', \'', $temp).'\')';
            $result = $this->unibox->db->query($sql_string, 'failed to select from sys_email_templates');
            while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                $info['sys_email_templates'][] = $row;
        }

        // sex entries
        $sql_string  = 'SELECT
                          *
                        FROM
                          sys_sex
                        WHERE
                          module_ident_from = \''.$module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select from sys_sex');
        while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            $info['sys_sex'][] = $row;

        // ucm entries
        $sql_string  = 'SELECT
                          *
                        FROM
                          sys_ucm
                        WHERE
                          module_ident = \''.$module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select from sys_ucm');
        while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            $info['sys_ucm'][] = $row;

        // translation dependencies
        $sql_string  = 'SELECT
                          *
                        FROM
                          sys_translation_dependencies
                        WHERE
                          module_ident = \''.$module_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select from sys_translation_dependencies');
        while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            $info['sys_translation_dependencies'][] = $row;

		// tables
		$sql_string =  'SELECT
						  *
						FROM
						  sys_module_tables
						WHERE
						  module_ident = \''.$module_ident.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to select from sys_module_tables');
        while ($row = $result->fetch_row(FETCHMODE_ASSOC))
            $info['sys_module_tables'][] = $row;

		return $info;
    }
}

?>