<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_languagemanager
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
        return ub_languagemanager::version;
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
            self::$instance = new ub_languagemanager;
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
        $this->unibox->config->load_config('languagemanager');
    }

    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_LANGUAGE_WELCOME_TEXT');
        $msg->display();
        return 0;
    }

    public function administrate()
    {
        $this->unibox->load_template('shared_administration');
        $sql_string  = 'SELECT
                          a.module_ident,
                          a.lang_ident,
                          a.version,
                          b.module_min_lang_pack_version,
                          c.string_value,
                          e.string_value
                        FROM
                          sys_language_packs AS a
                            INNER JOIN sys_modules AS b
                              ON b.module_ident = a.module_ident
                            INNER JOIN sys_translations AS c
                              ON c.string_ident = b.si_module_name
                            INNER JOIN sys_languages AS d
                              ON d.lang_ident = a.lang_ident
                            INNER JOIN sys_translations AS e
                              ON e.string_ident = d.si_lang_descr
                        WHERE
                          c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                          AND
                          e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                        ORDER BY
                          c.string_value ASC,
                          e.string_value ASC';
        $result = $this->unibox->db->query($sql_string, 'failed to select language packs');

        $pagebrowser = ub_pagebrowser::get_instance('languagemanager_administrate');
        $pagebrowser->process($sql_string, 25);

        $admin = ub_administration::get_instance('languagemanager_administrate');

        // add header fields
        $admin->add_field('TRL_MODULE');
        $admin->add_field('TRL_LANGUAGE');
        $admin->add_field('TRL_VERSION');

        if ($result->num_rows() > 0)
        {
            $rights_uninstall = $this->unibox->session->has_right('languagemanager_uninstall');
            $rights_info = $this->unibox->session->has_right('languagemanager_info');
            while (list($module_ident, $lang_ident, $version, $min_version, $module_name, $lang_name) = $result->fetch_row())
            {
                $admin->begin_dataset(false);

                $admin->add_dataset_ident('module_ident', $module_ident);
                $admin->add_dataset_ident('lang_ident', $lang_ident);
                $admin->set_dataset_descr($module_name);

                if ($version < $min_version)
                    $admin->add_icon('outdated.gif', 'TRL_OUTDATED');

                $admin->add_data($module_name);
                $admin->add_data($lang_name);

                if (floor($version) == $version)
                    $version = sprintf('%0.1f', $version);
                $admin->add_data($version);

                if ($rights_info)
                    $admin->add_option('info_true.gif', 'TRL_ALT_INFO', 'languagemanager_info');
                else
                    $admin->add_option('info_false.gif', 'TRL_ALT_INFO_FORBIDDEN');
                if ($rights_uninstall)
                    $admin->add_option('content_delete_true.gif', 'TRL_ALT_UNINSTALL', 'languagemanager_uninstall');
                else
                    $admin->add_option('content_delete_false.gif', 'TRL_ALT_UNINSTALL_FORBIDDEN');

                $admin->end_dataset();
            }
        }
        $admin->show();
    }

    public function info()
    {
        $validator = ub_validator::get_instance();
        $validator->reset();
        $validator->validate('GET', 'module_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_NO_MODULE_PASSED', 'SELECT module_ident FROM sys_modules');
        $validator->validate('GET', 'lang_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_NO_LANGUAGE_PASSED', 'SELECT lang_ident FROM sys_languages');
        if ($validator->get_result())
        {
            $sql_string  = 'SELECT
                              a.version,
                              a.install_date,
                              b.user_name,
                              e.string_value,
                              g.string_value,
                              c.string_ident
                            FROM
                              sys_language_packs AS a
                                INNER JOIN sys_users AS b
                                  ON b.user_id = a.install_user_id
                                INNER JOIN sys_translation_dependencies AS c
                                  ON c.module_ident = a.module_ident
                                INNER JOIN sys_modules AS d
                                  ON d.module_ident = a.module_ident
                                INNER JOIN sys_translations AS e
                                  ON e.string_ident = d.si_module_name
                                INNER JOIN sys_languages AS f
                                  ON f.lang_ident = a.lang_ident
                                INNER JOIN sys_translations AS g
                                  ON g.string_ident = f.si_lang_descr
                            WHERE
                              a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                              AND
                              a.lang_ident = \''.$this->unibox->session->env->input->lang_ident.'\'
                              AND
                              e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              AND
                              g.lang_ident = \''.$this->unibox->session->lang_ident.'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select language pack information');
            if ($result->num_rows() > 0)
            {
                $msg = new ub_message(MSG_INFO);
                $msg->begin_summary();
                list($version, $date, $user_name, $module_name, $lang_name, $value) = $result->fetch_row();
                $msg->add_summary_content('TRL_MODULE', $module_name);
                $msg->add_summary_content('TRL_LANGUAGE', $lang_name);
                $msg->add_summary_content('TRL_VERSION', $version);
                $msg->add_newline();

                $time = new ub_time();
                $msg->add_summary_content('TRL_INSTALLED_DATETIME', $time->format_datetime($date));
                $msg->add_summary_content('TRL_INSTALLED_BY', $user_name);
                $result->goto();
                $translations = array();
                $translations[] = $value;
                while (list($foo, $foo, $foo, $foo, $foo, $value) = $result->fetch_row())
                    $translations[] = $value;
                $translations = implode('[br /]', $translations);
                $msg->add_newline();

                $msg->add_summary_content('TRL_TRANSLATIONS_COUNT', $result->num_rows());
                $msg->add_summary_content('TRL_TRANSLATIONS', $translations);
                $msg->end_summary();
                $msg->display();
                return;
            }
            else
            {
                $msg = new ub_message(MSG_ERROR);
                $msg->add_text('TRL_ERR_NO_LANGUAGE_PACK_FOUND');
                $msg->display();
            }
        }
        else
            $this->unibox->display_error();
    }

    public function install()
    {
        $validator = ub_validator::get_instance();
        // already processing a language pack
        if (isset($this->unibox->session->var->language_install))
            return 2;
        // no previous installtion left to finish
        else
            return $validator->form_validate('languagemanager_install');
    }

    public function install_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'languagemanager_install');
        $form = ub_form_creator::get_instance();
        $form->begin_form('languagemanager_install', 'languagemanager_install');
        $form->begin_fieldset('TRL_GENERAL');
        $form->file('language_pack', 'TRL_LANGUAGE_PACK', DIR_TEMP);
        $form->set_condition(CHECK_NOTEMPTY);
        $file_types = array('application/x-tar');
        if (extension_loaded('bz2'))
            $file_types[] = 'application/x-bzip2';
        if (extension_loaded('zlib'))
            $file_types[] = 'application/x-gzip';
        $form->set_condition(CHECK_FILE_EXTENSION, $file_types);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_INSTALL_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'languagemanager_welcome');
        $form->end_buttonset();
        $form->end_form();
    }

    public function install_process()
    {
        // check for zip extension
        if (!extension_loaded('zip'))
        {
            ub_form_creator::reset('languagemanager_install');
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_ZIP_EXTENSION_MISSING');
            $msg->display();
            return 0;
        }

        if (!($tar = $this->language_check_tar()))
        {
            ub_form_creator::reset('languagemanager_install');
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_LANGUAGE_PACK');
            $msg->display();
            return;
        }

        $this->unibox->session->var->register('language_install', $tar->{'translations.xml'});

        // process language pack if no conflicts
        if ($this->install_process_xml())
            return 1;
        else
            return 2;
    }

    public function language_check_tar($form = 'languagemanager_install')
    {
        // check for valid tar file, open it and get content
        if (
            !($tar_content = ub_functions::tar_get_content(DIR_BASE.$this->unibox->session->env->form->$form->data->language_pack['tmp_path'].$this->unibox->session->env->form->$form->data->language_pack['tmp_name'], $this->unibox->session->env->form->$form->data->language_pack['type']))
            ||
            !($xml = @DOMDocument::loadXML($tar_content->{'translations.xml'}))
            ||
            !file_exists(DIR_BASE.DIR_FRAMEWORK_SCHEMATA.'language.xsd')
            ||
            !$xml->schemaValidate(DIR_BASE.DIR_FRAMEWORK_SCHEMATA.'language.xsd')
           )
            return false;
        return $tar_content;
    }

    public function install_process_xml()
    {
        // convert to simplexml
        $xml = simplexml_load_string($this->unibox->session->var->language_install);
        
        // initialize
        $language_install = new StdClass();
        $language_install->count = 0;
        $language_install->data = new StdClass();
        $language_install->data->translations = array();
        $language_install->data->translations_conflicted = array();
        $language_install->data->email_containers = array();
        $language_install->data->emails = array();
        $language_install->data->emails_conflicted = array();
        $language_install->module_ident = (string)$xml->module_ident;
        $language_install->lang_ident = (string)$xml->lang_ident;
        $language_install->lang_version = (string)$xml->lang_version;

        // read data
        $language_pack = array();
        if (isset($xml->translations->translation))
            foreach ($xml->translations->translation AS $translation)
            {
                $ident = (string)$translation->ident;
                $value = (string)$translation->value;
                $language_install->data->translations[$ident] = $value;
            }

        if (isset($xml->email_containers->email_container))
            foreach ($xml->email_containers->email_container AS $translation)
            {
                $ident = (string)$translation->ident;
                $si_descr = (string)$translation->si_descr;
                $language_install->data->email_containers[$ident] = $si_descr;
            }

        if (isset($xml->emails->email))
            foreach ($xml->emails->email AS $translation)
            {
                $ident = (string)$translation->ident;
                $subject = (string)$translation->subject;
                $body = (string)$translation->body;
                $language_install->data->emails[$ident] = array('subject' => $subject, 'body' => $body);
            }

        // get conflicted translations
        $sql_string  = 'SELECT
                          a.string_ident,
                          a.string_value,
                          c.module_ident,
                          d.string_value
                        FROM
                          sys_translations AS a
                            LEFT JOIN sys_translation_dependencies AS b
                              ON b.string_ident = a.string_ident
                            INNER JOIN sys_modules AS c
                              ON c.module_ident = b.module_ident
                            INNER JOIN sys_translations AS d
                              ON
                              (
                              d.string_ident = c.si_module_name
                              AND
                              d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              ) 
                        WHERE
                          a.string_ident IN (\''.implode('\', \'', array_keys($language_install->data->translations)).'\')
                        ORDER BY
                          a.string_ident,
                          d.string_value';
        $result = $this->unibox->db->query($sql_string, 'failed to select already used translations');
        while (list($ident, $value, $module_ident, $module_name) = $result->fetch_row())
        {
            // string exists with different value
            if (isset($language_install->data->translations[$ident]) && $language_install->data->translations[$ident] != $value)
            {
                if (!isset($language_install->data->translations_conflicted[$ident]))
                {
                    $language_install->data->translations_conflicted[$ident] = new StdClass();
                    $language_install->data->translations_conflicted[$ident]->old = $value;
                    $language_install->data->translations_conflicted[$ident]->new = $language_install->data->translations[$ident];
                    $language_install->data->translations_conflicted[$ident]->modules = array();
                    unset($language_install->data->translations[$ident]);
                }
            }
            if (isset($language_install->data->translations_conflicted[$ident]) && !isset($language_install->data->translations_conflicted[$ident]->modules[$module_ident]) && $module_ident != null)
                $language_install->data->translations_conflicted[$ident]->modules[$module_ident] = $module_name;
        }

        // get conflicted email templates
        $sql_string  = 'SELECT
                          template_container_ident,
                          template_subject,
                          template_body
                        FROM
                          sys_email_templates
                        WHERE
                          template_container_ident IN (\''.implode('\', \'', array_keys($language_install->data->emails)).'\')
                        ORDER BY
                          template_subject';
        $result = $this->unibox->db->query($sql_string, 'failed to select already used emails');
        while (list($ident, $subject, $body) = $result->fetch_row())
        {
            // string exists with different value
            if ($language_install->data->emails[$ident]['subject'] != $subject || $language_install->data->emails[$ident]['body'] != $body)
            {
                if (!isset($language_install->data->emails_conflicted[$ident]))
                {
                    $language_install->data->emails_conflicted[$ident] = new StdClass();
                    $language_install->data->emails_conflicted[$ident]->old = array('subject' => $subject, 'body' => $body);
                    $language_install->data->emails_conflicted[$ident]->new = $language_install->data->emails[$ident];
                    $language_install->data->emails_conflicted[$ident]->modules = array();
                    unset($language_install->data->emails[$ident]);
                }
            }
        }

        $this->unibox->session->var->language_install = $language_install;
        
        return (bool)(count($language_install->data->translations_conflicted) + count($language_install->data->emails_conflicted));
    }

    public function install_process_conflict()
    {
        $validator = ub_validator::get_instance();
        // process last translation decision
        if ($validator->form_validate('languagemanager_install_translation_conflict') && $validator->validate('GET', 'ident', TYPE_STRING, CHECK_INSET, null, array_keys($this->unibox->session->var->language_install->data->translations_conflicted)))
        {
            $this->unibox->session->var->language_install->data->translations[$this->unibox->session->env->input->ident] = ($this->unibox->session->env->form->languagemanager_install_translation_conflict->data->submit_id == 1) ? $this->unibox->session->var->language_install->data->translations_conflicted[$this->unibox->session->env->input->ident]->new : $this->unibox->session->var->language_install->data->translations_conflicted[$this->unibox->session->env->input->ident]->old;
            unset($this->unibox->session->var->language_install->data->translations_conflicted[$this->unibox->session->env->input->ident]);
            ub_form_creator::reset('languagemanager_install_translation_conflict');
        }
        // process last email decision
        elseif ($validator->form_validate('languagemanager_install_email_conflict') && $validator->validate('GET', 'ident', TYPE_STRING, CHECK_INSET, null, array_keys($this->unibox->session->var->language_install->data->emails_conflicted)))
        {
            $this->unibox->session->var->language_install->data->emails[$this->unibox->session->env->input->ident] = ($this->unibox->session->env->form->languagemanager_install_email_conflict->data->submit_id == 1) ? $this->unibox->session->var->language_install->data->emails_conflicted[$this->unibox->session->env->input->ident]->new : $this->unibox->session->var->language_install->data->emails_conflicted[$this->unibox->session->env->input->ident]->old;
            unset($this->unibox->session->var->language_install->data->emails_conflicted[$this->unibox->session->env->input->ident]);
            ub_form_creator::reset('languagemanager_install_email_conflict');
        }

        // add new decision if any 
        if (count($this->unibox->session->var->language_install->data->translations_conflicted) > 0)
        {
            $value = reset($this->unibox->session->var->language_install->data->translations_conflicted);
            $key = key($this->unibox->session->var->language_install->data->translations_conflicted);
            $msg = new ub_message(MSG_QUESTION, true);
            $msg->add_text('TRL_LANGUAGE_IDENT_IS_CONFLICTED', array($key));
            $msg->add_newline(2);
            $msg->begin_summary();
            $msg->add_summary_content('TRL_LANGUAGE_OLD_TRANSLATION', $value->old, null);
            $msg->add_summary_content('TRL_LANGUAGE_NEW_TRANSLATION', $value->new, null);
            $msg->add_summary_content('TRL_LANGUAGE_USED_IN_MODULES', implode(', ', $value->modules), null);
            $msg->add_newline();
            $msg->begin_form('languagemanager_install_translation_conflict', $this->unibox->session->env->system->action_ident.'/ident/'.$key);
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_OVERWRITE_UCASE', 1);
            $msg->form->submit('TRL_KEEP_UCASE', 2);
            $msg->form->end_buttonset();
            $msg->end_form();
            $msg->display();
            return 0;
        }
        elseif (count($this->unibox->session->var->language_install->data->emails_conflicted) > 0)
        {
            $value = reset($this->unibox->session->var->language_install->data->emails_conflicted);
            $key = key($this->unibox->session->var->language_install->data->emails_conflicted);
            $msg = new ub_message(MSG_QUESTION, true);
            $msg->add_text('TRL_LANGUAGE_EMAIL_IS_CONFLICTED', array($key));
            $msg->add_newline(2);
            
            $msg->add_summary('TRL_LANGUAGE_OLD_EMAIL_SUBJECT', $value->old['subject'], null);
            $msg->add_summary('TRL_LANGUAGE_OLD_EMAIL_BODY', $value->old['body'], null);
            $msg->end_summary();
            $msg->add_newline(2);
            $msg->begin_summary();
            $msg->add_summary('TRL_LANGUAGE_NEW_EMAIL_SUBJECT', $value->new['subject'], null);
            $msg->add_summary('TRL_LANGUAGE_NEW_EMAIL_BODY', $value->new['body'], null);
            
            $msg->add_newline(2);
            $msg->begin_form('languagemanager_install_email_conflict', $this->unibox->session->env->action_ident.'/ident/'.$key);
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_OVERWRITE_UCASE', 1);
            $msg->form->submit('TRL_KEEP_UCASE', 2);
            $msg->form->end_buttonset();
            $msg->end_form();
            $msg->display();
            return 0;
        }
        return 1;
    }

    public function install_insert($foreign_call = false)
    {
        // begin transaction
        if (!$foreign_call)
            $this->unibox->db->begin_transaction();
        $translations_inserted = $email_containers_inserted = $emails_inserted = 0;

		try
		{
	        // check if language pack is already installed - uninstall first
	        $sql_string  = 'SELECT
	                          COUNT(*)
	                        FROM
	                          sys_language_packs
	                        WHERE
	                          module_ident = \''.$this->unibox->session->var->language_install->module_ident.'\'
	                          AND
	                          lang_ident = \''.$this->unibox->session->var->language_install->lang_ident.'\''; 
	        $result = $this->unibox->db->query($sql_string, 'failed to determine if language pack is already installed');
	        list($count) = $result->fetch_row();
	        if ($count > 0)
	        {
	            $this->unibox->session->env->input->module_ident = $this->unibox->session->var->language_install->module_ident;
	            $this->unibox->session->env->input->lang_ident = $this->unibox->session->var->language_install->lang_ident;
	            $this->uninstall_process(true);
	        }
	
	        if (count($this->unibox->session->var->language_install->data->translations) > 0)
	            foreach ($this->unibox->session->var->language_install->data->translations AS $key => $value)
	            {
	                if (!$this->unibox->insert_translation($key, array($this->unibox->session->var->language_install->lang_ident => $value), $this->unibox->session->var->language_install->module_ident))
	                	throw new ub_exception_transaction;
	                $translations_inserted++;
	            }
	
	        if (count($this->unibox->session->var->language_install->data->email_containers) > 0)
	            foreach ($this->unibox->session->var->language_install->data->email_containers AS $key => $si_descr)
	            {
	                $sql_string  = 'REPLACE INTO
	                                  sys_email_templates_container
	                                SET
	                                  template_container_ident = \''.$this->unibox->db->cleanup($key).'\',
	                                  module_ident = \''.$this->unibox->session->var->language_install->module_ident.'\',
	                                  si_template_container_descr = \''.$this->unibox->db->cleanup($si_descr).'\'';
	                $this->unibox->db->query($sql_string, 'failed to insert email container');
	                if ($this->unibox->db->affected_rows() >= 1)
	                	$email_containers_inserted++;
            		else
            			throw new ub_exception_transaction;
	            }
	
	        if (count($this->unibox->session->var->language_install->data->emails) > 0)
	            foreach ($this->unibox->session->var->language_install->data->emails AS $key => $array)
	            {
	                $sql_string  = 'REPLACE INTO
	                                  sys_email_templates
	                                SET
	                                  template_container_ident = \''.$this->unibox->db->cleanup($key).'\',
	                                  lang_ident = \''.$this->unibox->session->var->language_install->lang_ident.'\',
	                                  template_subject = \''.$this->unibox->db->cleanup($array['subject']).'\',
	                                  template_body = \''.$this->unibox->db->cleanup($array['body']).'\'';
	                $this->unibox->db->query($sql_string, 'failed to insert email');
	                if ($this->unibox->db->affected_rows() >= 1)
	                	$emails_inserted++;
            		else
            			throw new ub_exception_transaction;
	            }
	
	        if ($translations_inserted == 0 && $email_containers_inserted == 0 && $emails_inserted == 0)
	        	throw new ub_exception_transaction('TRL_ERR_NO_VALID_TRANSLATIONS');
	
	        $sql_string  = 'REPLACE INTO
	                          sys_language_packs
	                        SET
	                          module_ident = \''.$this->unibox->session->var->language_install->module_ident.'\',
	                          lang_ident = \''.$this->unibox->session->var->language_install->lang_ident.'\',
	                          version = '.$this->unibox->session->var->language_install->lang_version.',
	                          install_date = NOW(),
	                          install_user_id = '.$this->unibox->session->user_id;
	        $this->unibox->db->query($sql_string, 'failed to insert language pack');

            if ($this->unibox->db->affected_rows() < 1)
    			throw new ub_exception_transaction;
	
	        // unset language array
	        $this->unibox->session->var->unregister('language_install');
	
	        if ($foreign_call)
	            return 1;
	        
	        $this->unibox->db->commit();
	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_INSTALLATION_SUCCESS');
	        $msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_INSTALLATION_FAILED');
		}

        ub_form_creator::reset('languagemanager_install');
        $this->unibox->switch_alias('languagemanager_administrate', true);
    }

    public function uninstall()
    {
        $validator = ub_validator::get_instance();
        $validator->reset();
        $validator->validate('GET', 'module_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_NO_MODULE_PASSED', 'SELECT module_ident FROM sys_modules');
        $validator->validate('GET', 'lang_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_NO_LANGUAGE_PASSED', 'SELECT lang_ident FROM sys_languages');
        if (!$validator->get_result())
        {
            $this->unibox->display_error();
            return 2;
        }
        else
        {
            $validator = ub_validator::get_instance();
            return $validator->form_validate('languagemanager_delete_confirm');
        }
    }
    
    public function uninstall_decision()
    {
        $sql_string  = 'SELECT
                          a.version,
                          c.string_value,
                          e.string_value
                        FROM
                          sys_language_packs AS a
                            INNER JOIN sys_modules AS b
                              ON b.module_ident = a.module_ident
                            INNER JOIN sys_translations AS c
                              ON c.string_ident = b.si_module_name
                            INNER JOIN sys_languages AS d
                              ON d.lang_ident = a.lang_ident
                            INNER JOIN sys_translations AS e
                              ON e.string_ident = d.si_lang_descr
                        WHERE
                          a.module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                          AND
                          a.lang_ident = \''.$this->unibox->session->env->input->lang_ident.'\'
                          AND
                          c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                          AND
                          e.lang_ident = \''.$this->unibox->session->lang_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select language pack data');
        if ($result->num_rows() > 0)
        {
            $array = $result->fetch_row();
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_QUESTION_UNINSTALL_LANGUAGE_PACK', $array);
            $msg->add_newline(2);
            $msg->add_decision('languagemanager_uninstall/module_ident/'.$this->unibox->session->env->input->module_ident.'/lang_ident/'.$this->unibox->session->env->input->lang_ident, 'TRL_UNINSTALL_UCASE', 'languagemanager_administrate', 'TRL_CANCEL_UCASE', 'languagemanager_delete_confirm');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
        
        $msg->display();
    }

    public function uninstall_process($return = false)
    {
        $this->unibox->db->begin_transaction();
        
        $sql_string  = 'DELETE FROM
                          sys_translation_dependencies
                        WHERE
                          module_ident = \''.$this->unibox->session->env->input->module_ident.'\'';
        if (!$this->unibox->db->query($sql_string, 'failed to delete requirements'))
        {
            $this->unibox->db->rollback('TRL_UNINSTALL_FAILED');
            return 0;
        }

        // cleanup translations
        $this->translations_cleanup_process(true);

        $sql_string  = 'DELETE FROM
                          sys_language_packs
                        WHERE
                          module_ident = \''.$this->unibox->session->env->input->module_ident.'\'
                          AND
                          lang_ident = \''.$this->unibox->session->env->input->lang_ident.'\'';
        if (!$this->unibox->db->query($sql_string, 'failed to delete language pack') || $this->unibox->db->affected_rows() == 0)
        {
            $this->unibox->db->rollback('TRL_UNINSTALL_FAILED');
            return 0;
        }

        if ($return)
            return;

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS);
        $msg->add_text('TRL_UNINSTALL_SUCCESS');
        $msg->display();
    }

    /**
    * assign()
    *
    * re-assign translations
    * 
    * @access   public
    */
    public function translations_assign()
    {
        $validator = ub_validator::get_instance();
        return $validator->form_validate('languagemanager_translations_assign_decision');
    }

    /**
    * assign_form()
    *
    * re-assign translations - decision message
    * 
    * @access   public
    */
    public function translations_assign_form()
    {
        $msg = new ub_message(MSG_QUESTION);
        $msg->add_text('TRL_QUESTION_ASSIGN_TRANSLATIONS');
        $msg->add_newline(2);
        $msg->begin_form('languagemanager_translations_assign_decision', 'languagemanager_translations_assign');
        $msg->form->begin_buttonset(false);
        $msg->form->submit('TRL_ASSIGN_UCASE');
        $msg->form->cancel('TRL_CANCEL_UCASE', 'languagemanager_welcome');
        $msg->form->end_buttonset();
        $msg->end_form();
        $msg->display();
    }

    /**
    * assign_process()
    *
    * re-assign translations - process
    * 
    * @access   public
    */
    public function translations_assign_process()
    {
    	set_time_limit(0);
    	
        // begin transaction
        $this->unibox->db->begin_transaction();

        try
        {
        	$result = $this->translations_assign_execute();
        	$this->unibox->db->commit();

	        $msg = new ub_message(MSG_INFO);
	        $msg->begin_summary();
	        $msg->add_summary_content('TRL_TEMPLATE_TRANSLATIONS', $result['templates']);
	        $msg->add_summary_content('TRL_SYSTABLE_TRANSLATIONS', $result['tables']);
	        $msg->add_summary_content('TRL_CODE_TRANSLATIONS', $result['code']);
	        $msg->end_summary();
	        $msg->display();
        }
        catch (ub_exception_transaction $exception)
        {
        	$exception->process('TRL_ERR_FAILED_TO_ASSIGN_TRANSLATIONS');
        }

        // show result
        ub_form_creator::reset('languagemanager_translations_assign_decision');
        return 0;
    }

	public function translations_assign_execute()
	{
        // initialize
        $result = array('templates' => 0,
                        'tables' => 0,
                        'code'      => 0);

        // delete all translation dependencies
        $sql_string  = 'DELETE FROM
                          sys_translation_dependencies';
        $this->unibox->db->query($sql_string, 'failed to delete old translation dependencies');

        // delete all templates translations
        $sql_string  = 'DELETE FROM
                          sys_template_translations';
        $this->unibox->db->query($sql_string, 'failed to delete old template translations');

        // get all output-formats and themes
        $sql_string  = 'SELECT DISTINCT
                          a.output_format_ident,
                          b.theme_ident,
                          b.subtheme_ident,
                          a.filename_extension
                        FROM
                          sys_output_formats AS a
                            INNER JOIN sys_theme_output_formats AS b
                              ON b.output_format_ident = a.output_format_ident
                        WHERE
                          filename_extension IS NOT NULL';
        $themes = array();
        $result_output_formats = $this->unibox->db->query($sql_string, 'failed to select ALL output formats and their correspondenting themes');
        if ($result_output_formats->num_rows() > 0)
            while ($row = $result_output_formats->fetch_row())
                $themes[] = $row;

        // get modules
        $sql_string  = 'SELECT
                          module_ident,
                          si_module_name
                        FROM
                          sys_modules
                        ORDER BY
                          module_ident ASC';
        $result_modules = $this->unibox->db->query($sql_string, 'failed to select modules');

        // loop through modules
        if ($result_modules->num_rows() > 0)
        {
            while (list($module_ident, $module_name) = $result_modules->fetch_row())
            {
                $translations = array();
                // process assigned templates
                $sql_string  = 'SELECT DISTINCT
                                  template_ident,
                                  template_filename,
                                  module_ident
                                FROM
                                  sys_templates
                                WHERE
                                  module_ident = \''.$module_ident.'\'';
                $result_templates = $this->unibox->db->query($sql_string, 'failed to select the modules templates');

                // loop through templates
                if ($result_templates->num_rows() > 0)
                {
                    while (list($template_ident, $template_filename, $template_module_ident) = $result_templates->fetch_row())
                    {
                        // loop through themes
                        foreach ($themes AS $theme)
                        {
                            list($output_format_ident, $theme_ident, $subtheme_ident, $filename_extension) = $theme;
                            $content_to_search = '';
                            $matches = $translations = array();
                            // get template content
                            $file = DIR_THEMES.$theme_ident.'/'.$subtheme_ident.'/'.$output_format_ident.'/templates/'.$template_module_ident.'/'.$template_filename.'.'.$filename_extension;
                            if (file_exists($file))
                                $content_to_search = file_get_contents($file);
        
                            // process matches for current template
                            preg_match_all('/TRL(?>(_([A-Z0-9])+)+)(?!_)/', $content_to_search, $matches);
                            $matches = array_unique($matches[0]);

                            // insert new assignments
                            foreach ($matches as $string_ident)
                            {
                                if (!empty($string_ident) && !in_array($string_ident, $translations))
                                {
                                	// insert string ident
                                    $this->unibox->insert_string_ident($string_ident);

									if (!$this->unibox->insert_translation_dependency($this->unibox->db->cleanup($string_ident), $module_ident))
										throw new ub_exception_transaction;
                                    $sql_string  = 'REPLACE INTO
                                                      sys_template_translations
                                                    SET
                                                      string_ident = \''.$this->unibox->db->cleanup($string_ident).'\',
                                                      theme_ident = \''.$theme_ident.'\',
                                                      subtheme_ident = \''.$subtheme_ident.'\',
                                                      template_ident = \''.$template_ident.'\'';
                                    $this->unibox->db->query($sql_string, 'failed to insert template-translation-assignment: '.$string_ident);
                                    if ($this->unibox->db->affected_rows() != 1)
                                    	throw new ub_exception_transaction;
                                    $result['templates']++;
                                    $translations[] = $string_ident;
                                }
                            }
                        }
                    }
                }

                // re-initialize
                $content_to_search = '';
                $matches = array();
    
                // loop through class files
                $sql_string  = 'SELECT
                                  class_filename
                                FROM
                                  sys_class_files
                                WHERE
                                  module_ident = \''.$module_ident.'\'';
                $result_class_files = $this->unibox->db->query($sql_string, 'failed to select classfiles');
                if ($result_class_files->num_rows() > 0)
                    while (list($class_filename) = $result_class_files->fetch_row())
                        if (file_exists(DIR_MODULES.$module_ident.'/'.$class_filename.'.php'))
                            $content_to_search .= file_get_contents(DIR_MODULES.$module_ident.'/'.$class_filename.'.php');

                if ($module_ident == 'unibox')
                {
                    foreach (get_framework_classes() as $files)
                        foreach ($files as $file)
                            $content_to_search .= file_get_contents($file);
                    $content_to_search .= file_get_contents(file_exists('index.php5') ? 'index.php5' : 'index.php');
                }

                // match translations if class files were found
                if (trim($content_to_search) != '')
                {
                    preg_match_all('/TRL(?>(_([A-Z0-9])+)+)(?!_)/', $content_to_search, $matches);
                    $matches = array_unique($matches[0]);
                    if (isset($matches) && count($matches) > 0)
                        foreach ($matches AS $value)
                            // require translation
                            if (!$this->unibox->insert_translation_dependency($this->unibox->db->cleanup($value), $module_ident))
                            	throw new ub_exception_transaction;
                            else
                                $result['code']++;
                }
            }
        }

        $translations = $this->get_translation_usage();

        // insert system table translations
        if (count($translations) > 0)
            foreach ($translations as $module => $module_translations)
                if (count($module_translations) > 0)
                    foreach ($module_translations as $translation)
                    {
                        // require translation
                        if (!$this->unibox->insert_translation_dependency($this->unibox->db->cleanup($translation), $module))
                        	throw new ub_exception_transaction;
                        else
                            $result['tables']++;
                    }
		return $result;
	}

    /**
     * 
     */
    public function translations_administrate()
    {
        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'languagemanager_translations_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('languagemanager_translations_administrate', 'languagemanager_translations_administrate');

        $preselect = ub_preselect::get_instance('languagemanager_translations_administrate');
        $preselect->add_field('lang_ident', null, true);
        $preselect->add_field('string_ident', 'a.string_ident');
        $preselect->add_field('string_value', 'b.string_value');
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
        $form->begin_select('lang_ident', 'TRL_LANGUAGE');
        $sql_string  = 'SELECT
                          a.lang_ident,
                          b.string_value
                        FROM
                          sys_languages AS a
                            INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_lang_descr
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        ORDER BY
                          b.string_value ASC';
        $form->add_option_sql($sql_string);
        $form->end_select();
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('string_ident', 'TRL_IDENT', '', 60);
        $form->text('string_value', 'TRL_VALUE', '', 60);
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

        return $preselect->process();
    }

    public function translations_administrate_show_content()
    {
        // get preselect
        $preselect = ub_preselect::get_instance('languagemanager_translations_administrate');
        $admin = ub_administration_ng::get_instance('languagemanager_translations_administrate');

        $sql_string  = 'SELECT DISTINCT
                          a.string_ident,
                          b.string_value
                        FROM sys_string_identifiers AS a
                          LEFT JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.string_ident
                            AND
                            b.lang_ident = \''.$preselect->get_value('lang_ident').'\'
                            )
                        WHERE
                          '.$preselect->get_string();

        // add header fields
        $admin->add_field('TRL_IDENT', 'a.string_ident');
        $admin->add_field('TRL_VALUE', 'b.string_value');

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
            $rights_edit = $this->unibox->session->has_right('languagemanager_translations_edit');
            while (list($string_ident, $string_value) = $result->fetch_row())
            {
                $admin->begin_dataset();

                $admin->add_dataset_ident('string_ident', $string_ident);
                $admin->add_dataset_ident('lang_ident', $this->unibox->session->env->form->languagemanager_translations_administrate->data->lang_ident);
                $admin->set_dataset_descr($string_ident);

                $admin->add_data($string_ident);
                $admin->add_data($string_value);

                if ($rights_edit)
                    $admin->add_option('content_edit_true.gif', 'TRL_ALT_EDIT', 'languagemanager_translations_edit');
                else
                    $admin->add_option('content_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                $admin->end_dataset();
            }
            $admin->add_multi_option('content_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'languagemanager_translations_edit');
            $admin->set_multi_descr('TRL_TRANSLATIONS');
        }
        $admin->show('languagemanager_translations_administrate');
    }

    public function translations_edit()
    {
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        $string_idents = array();
        $sql_string  = 'SELECT string_ident FROM sys_string_identifiers';
        $result = $this->unibox->db->query($sql_string, 'failed to select valid string identifiers');
        while (list($string_ident) = $result->fetch_row())
            $string_idents[] = $string_ident;

        $lang_idents = array();
        $sql_string  = 'SELECT lang_ident FROM sys_languages';
        $result = $this->unibox->db->query($sql_string, 'failed to select valid language identifiers');
        while (list($lang_ident) = $result->fetch_row())
            $lang_idents[] = $lang_ident;

        if (!$stack->is_valid())
        {
            $stack->reset();
            do
            {
                $stack->keep_keys(array('string_ident', 'lang_ident'));
                if (!$validator->validate('STACK', 'string_ident', TYPE_STRING, CHECK_INSET, 'TRL_ERR_INVALID_DATA_PASSED', $string_idents) || !$validator->validate('STACK', 'lang_ident', TYPE_STRING, CHECK_INSET, 'TRL_ERR_INVALID_DATA_PASSED', $lang_idents))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('languagemanager_translations_edit');
        else
            $stack->switch_to_administration();
    }
    
    public function form_translations()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'languagemanager_translations_edit');
        $form->begin_form('languagemanager_translations_edit', 'languagemanager_translations_edit');

        $sql_string =  'SELECT DISTINCT
                          c.string_value
                        FROM
                          sys_translation_dependencies AS a
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
                          a.string_ident = \''.$dataset->string_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select translation usage');
        if ($result->num_rows() > 0)
        {
            $form->begin_fieldset('TRL_TRANSLATIONS_USED_BY_FOLLOWING_MODULES');
            while (list($module_name) = $result->fetch_row())
                $form->plaintext($module_name.'[br /]', false);
            $form->end_fieldset();
        }

        $sql_string =  'SELECT
                          a.string_value,
                          c.string_value
                        FROM
                          sys_translations AS a
                            INNER JOIN sys_languages AS b
                              ON b.lang_ident = a.lang_ident
                            INNER JOIN sys_translations AS c
                              ON
                              (
                              c.string_ident = b.si_lang_descr
                              AND
                              c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              )
                        WHERE
                          a.lang_ident != \''.$dataset->lang_ident.'\'
                          AND
                          a.string_ident = \''.$dataset->string_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to select other translations');
        if ($result->num_rows() > 0)
        {
            $form->begin_fieldset('TRL_TRANSLATION_IN_OTHER_LANGUAGES');
            while (list($translation, $language) = $result->fetch_row())
                $form->plaintext('[strong]'.$language.'[/strong]: '.$translation.'[br /]');
            $form->end_fieldset();
        }

        $sql_string =  'SELECT
                          string_value
                        FROM
                          sys_translations
                        WHERE
                          lang_ident = \''.$dataset->lang_ident.'\'
                          AND
                          string_ident = \''.$dataset->string_ident.'\'';
        $values = $form->set_values($sql_string, array(), true);

        $form->begin_fieldset('TRL_GENERAL');
        $form->textarea('string_value', 'TRL_TRANSLATION', '', 80, 15);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'languagemanager_translations_edit');
        $form->end_buttonset();
        $form->end_form();
        $form->set_destructor('ub_stack', 'discard_top');

        // set content title
        $this->unibox->set_content_title('TRL_TRANSLATION_EDIT', array($dataset->string_ident));
    }

    public function translations_edit_process()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string =  'REPLACE INTO
                          sys_translations
                        SET
                          string_value = \''.$this->unibox->session->env->form->languagemanager_translations_edit->data->string_value.'\',
                          lang_ident = \''.$dataset->lang_ident.'\',
                          string_ident = \''.$dataset->string_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to insert/update translation');
        if ($this->unibox->db->affected_rows() >= 1)
        {
            $msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_EDIT_SUCCESS');
            $msg->display();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR, false);
            $msg->add_text('TRL_EDIT_FAILED');
            $msg->display();
        }
        ub_form_creator::reset('languagemanager_translations_edit');
    }

    /**
    * translations_cleanup()
    *
    * delete unused translations
    * 
    * @access   public
    */
    public function translations_cleanup()
    {
        $validator = ub_validator::get_instance();
        return $validator->form_validate('languagemanager_translations_cleanup_decision');
    }

    /**
    * assign_form()
    *
    * delete unused translations - decision message
    * 
    * @access   public
    */
    public function translations_cleanup_decision()
    {
        $msg = new ub_message(MSG_QUESTION);
        $msg->add_text('TRL_QUESTION_CLEANUP_TRANSLATIONS');
        $msg->add_newline(2);
        $msg->add_decision('languagemanager_translations_cleanup', 'TRL_CLEANUP_UCASE', 'languagemanager_welcome', 'TRL_CANCEL_UCASE', 'languagemanager_translations_cleanup_decision');
        $msg->display();
    }

    public function translations_cleanup_process($return = false)
    {
    	$restricted_idents = array();
        $deleted = $restricted = 0;

        $sql_string  = 'SELECT
                          a.string_ident
                        FROM
                          sys_string_identifiers AS a
                            LEFT JOIN sys_translation_dependencies AS b
                              ON b.string_ident = a.string_ident
                        WHERE
                          b.string_ident IS NULL';
        $result = $this->unibox->db->query($sql_string, 'failed to get translations to cleanup');
        if ($result->num_rows() > 0)
        {
            while (list($ident) = $result->fetch_row())
            {
                $sql_string  = 'DELETE FROM
                                  sys_string_identifiers
                                WHERE
                                  string_ident = \''.$this->unibox->db->cleanup($ident).'\'';
                if ($this->unibox->db->query($sql_string, 'failed to delete translation: '.$ident, false, true))
                    $deleted++;
                else
                {
                    $restricted++;
                    $restricted_idents[] = $ident;
                }
            }
        }

        $sql_string  = 'SELECT
                          a.string_ident
                        FROM
                           sys_translations AS a
                            LEFT JOIN sys_string_identifiers AS b
                              ON b.string_ident = a.string_ident
                        WHERE
                          b.string_ident IS NULL';
        $result = $this->unibox->db->query($sql_string, 'failed to get translations to cleanup');
        if ($result->num_rows() > 0)
        {
            while (list($ident) = $result->fetch_row())
            {
                $sql_string  = 'DELETE FROM
                                  sys_translations
                                WHERE
                                  string_ident = \''.$this->unibox->db->cleanup($ident).'\'';
                if ($this->unibox->db->query($sql_string, 'failed to delete translation: '.$ident, false, true))
                    $deleted++;
                else
                {
                    $restricted++;
                    $restricted_idents[] = $ident;
                }
            }
        }

        if ($return)
            return;

        $msg = new ub_message(MSG_SUCCESS, false);
        if ($restricted > 0)
            $msg->add_text('TRL_TRANSLATION_CLEANUP_SUCCESS_SHOW', array($deleted, $restricted, implode(', ', $restricted_idents)));
        else
            $msg->add_text('TRL_TRANSLATION_CLEANUP_SUCCESS', array($deleted, $restricted));
        $msg->display();
        $this->unibox->switch_alias('languagemanager_translations_administrate', true);
    }
    
    protected function get_translation_usage($list_per_module = true)
    {
        // reset translations array
        $translations = array();

        // module depending tables
        $tables = array(    'sys_actions',
                            'sys_categories',
                            'sys_config_groups',
                            'sys_email_templates_container',
                            'sys_modules',
                            array('sys_dialogs', 'sys_actions'),
                            array('sys_config', 'sys_config_groups'),
                            array('sys_extensions', 'sys_actions'),
                            'sys_sex',
                            'sys_styles',
                            'sys_templates',
                            'sys_ucm');

        // loop through module depending tables
        foreach ($tables as $table)
        {
            $def = array();
            if (!is_array($table))
            {
                $table_definition = $this->unibox->db->tools->show_create_table($table);

                // get all string idents
                $matches = array();
                preg_match_all('/FOREIGN KEY \(`(.+)`\) REFERENCES `(.+)` \(`(.+)`\)/i', $table_definition, $matches, PREG_SET_ORDER);
                foreach ($matches as $match)
                {
                    if ($match[2] == 'sys_string_identifiers' && $match[3] == 'string_ident')
                        $def['string_idents'][] = $match[1];
                    elseif ($match[2] == 'sys_modules' && $match[3] == 'module_ident')
                        $def['module_ident'] = $match[1];
                }
                if ($table == 'sys_modules')
                    $def['module_ident'] = 'module_ident';

                if (isset($def['string_idents']) && isset($def['module_ident']))
                {
                    $sql_string  = 'SELECT
                                      '.$def['module_ident'].',
                                      '.implode(', ', $def['string_idents']).'
                                    FROM
                                      '.$table;
                    $result_translations = $this->unibox->db->query($sql_string, 'failed to get string idents from table '.$table);
                    if ($result_translations->num_rows() > 0)
                    {
                        while ($row = $result_translations->fetch_row())
                        {
                            $module_ident = $row[0];
                            unset($row[0]);
                            foreach ($row as $string_value)
                                if ($string_value !== null)
                                {
                                    if ($list_per_module)
                                        $translations[$module_ident][] = $string_value;
                                    else
                                        $translations[] = $string_value;
                                }
                        }
                    }
                }
            }
            else
            {
                $child_table = $table[0];
                $child_table_definition = $this->unibox->db->tools->show_create_table($child_table);
                $child_table_string_idents = array();

                $parent_table = $table[1];
                $parent_table_definition = $this->unibox->db->tools->show_create_table($parent_table);

                // get primary key from parent table
                $matches = array();
                preg_match('/PRIMARY KEY.*\(`(.+)`\)/i', $parent_table_definition, $matches);
                $parent_table_primary_key = $matches[1];

                // get module column from parent table
                preg_match_all('/FOREIGN KEY \(`(.+)`\) REFERENCES `(.+)` \(`(.+)`\)/i', $parent_table_definition, $matches, PREG_SET_ORDER);
                foreach ($matches as $match)
                    if ($match[2] == 'sys_modules' && $match[3] == 'module_ident')
                        $parent_table_module_ident = $match[1];
                
                // get foreign keys of child table
                preg_match_all('/FOREIGN KEY \(`(.+)`\) REFERENCES `(.+)` \(`(.+)`\)/i', $child_table_definition, $matches, PREG_SET_ORDER);
                foreach ($matches as $match)
                    if ($match[2] == $parent_table && $match[3] == $parent_table_primary_key)
                        $child_table_parent_key = $match[1];
                    elseif ($match[2] == 'sys_string_identifiers' && $match[3] == 'string_ident')
                        $child_table_string_idents[] = $match[1];
                
                $sql_string  = 'SELECT
                                  a.'.$parent_table_module_ident.',
                                  b.'.implode(', b.', $child_table_string_idents).'
                                FROM
                                  '.$parent_table.' AS a
                                    INNER JOIN '.$child_table.' AS b
                                      ON b.'.$child_table_parent_key.' = a.'.$parent_table_primary_key;
                $result_translations = $this->unibox->db->query($sql_string, 'failed to get string idents from table '.$parent_table.'/'.$child_table);

                if ($result_translations->num_rows() > 0)
                    while ($row = $result_translations->fetch_row())
                    {
                        $module_ident = $row[0];
                        unset($row[0]);
                        foreach ($row as $string_ident)
                            if ($string_ident !== null)
                                if ($list_per_module)
                                    $translations[$module_ident][] = $string_ident;
                                else
                                    $translations[] = $string_ident;
                    }
            }
        }

		// get actionbar menus
		$sql_string  = 'SELECT
						  module_actionbar_menu_id,
						  module_ident
						FROM
						  sys_modules
						WHERE
						  module_actionbar_menu_id IS NOT NULL';
		$result = $this->unibox->db->query($sql_string, 'actionbar menus');
        if ($result->num_rows() > 0)
            while (list($menu_id, $module_ident) = $result->fetch_row())
            	$actionbar[$menu_id] = $module_ident;

		// module assigned tables
		$sql_string  = 'SELECT
						  table_name,
						  module_ident
						FROM
						  sys_module_tables';
		$result = $this->unibox->db->query($sql_string, 'failed to get module tables');
		while (list($table, $module_ident) = $result->fetch_row())
		{
            if ($list_per_module && !isset($translations[$module_ident]))
                $translations[$module_ident] = array();

            $table_definition = $this->unibox->db->tools->show_create_table($table);
            $matches = $string_idents = array();
            preg_match_all('/FOREIGN KEY \(`(.+)`\) REFERENCES `(.+)` \(`(.+)`\)/i', $table_definition, $matches, PREG_SET_ORDER);
            foreach ($matches as $match)
                if ($match[2] == 'sys_string_identifiers' && $match[3] == 'string_ident')
                    $string_idents[] = $match[1];
            if (count($string_idents) > 0)
            {
            	if ($module_ident == 'menu')
        			$string_idents[] = 'menu_id';
                $sql_string  = 'SELECT
                                  '.implode(', ', $string_idents).'
                                FROM
                                  '.$table;
                $result_translations = $this->unibox->db->query($sql_string, 'failed to get string idents from table '.$table);
                if ($result_translations->num_rows() > 0)
                    while ($row = $result_translations->fetch_row(FETCHMODE_ASSOC))
                    {
                    	if ($module_ident == 'menu')
                    	{
                    		$menu_id = $row['menu_id'];
                    		unset($row['menu_id']);
                    	}
                        foreach ($row as $string_ident)
                            if ($string_ident !== null)
                                if ($list_per_module)
                                {
                                	if ($module_ident == 'menu' && in_array($menu_id, array_keys($actionbar)))
                                		$translations[$actionbar[$menu_id]][] = $string_ident;
                            		else
                                    	$translations[$module_ident][] = $string_ident;
                                }
                                else
                                    $translations[] = $string_ident;
                    }
            }
        }

        // load sex table definitions
        $sql_string  = 'SELECT
                          module_ident_from,
                          entity_type_definition,
                          entity_value,
                          entity_detail_text
                        FROM
                          sys_sex';
        $result = $this->unibox->db->query($sql_string, 'failed to get sex definitions');
        if ($result->num_rows() > 0)
            while (list($module_ident, $entity_type_definition, $entity_value, $entity_detail_text) = $result->fetch_row())
            {
                $sex_translations = $matches = array();
                preg_match_all('/TRL(?>(_([A-Z0-9])+)+)(?!_)/', $entity_type_definition, $matches);
                $sex_translations = array_unique(array_merge($sex_translations, $matches[0]));
                
                $matches = array();
                preg_match_all('/TRL(?>(_([A-Z0-9])+)+)(?!_)/', $entity_value, $matches);
                $sex_translations = array_unique(array_merge($sex_translations, $matches[0]));
                
                $matches = array();
                preg_match_all('/TRL(?>(_([A-Z0-9])+)+)(?!_)/', $entity_detail_text, $matches);
                $sex_translations = array_unique(array_merge($sex_translations, $matches[0]));

                if ($list_per_module)
                    $translations[$module_ident] = array_unique(array_merge($translations[$module_ident], $sex_translations));
                else
                    $translations = array_unique(array_merge($translations, $sex_translations));
                
            }

        // load ucm table definitions
        $sql_string  = 'SELECT
                          module_ident,
                          field_map
                        FROM
                          sys_ucm';
        $result = $this->unibox->db->query($sql_string, 'failed to get sex definitions');
        if ($result->num_rows() > 0)
            while (list($module_ident, $field_map) = $result->fetch_row())
            {
                $matches = array();
                preg_match_all('/TRL(?>(_([A-Z0-9])+)+)(?!_)/', $field_map, $matches);
                if ($list_per_module)
                    $translations[$module_ident] = array_unique(array_merge($translations[$module_ident], $matches[0]));
                else
                    $translations = array_unique(array_merge($translations, $matches[0]));
            }

        // load config translations
        $sql_string  = 'SELECT
                          b.module_ident,
                          a.config_field_spec
                        FROM
                          sys_config AS a
                            INNER JOIN sys_config_groups AS b
                              ON b.config_group_ident = a.config_group_ident';
        $result = $this->unibox->db->query($sql_string, 'failed to get sex definitions');
        if ($result->num_rows() > 0)
            while (list($module_ident, $config_field_spec) = $result->fetch_row())
            {
                $matches = array();
                preg_match_all('/TRL(?>(_([A-Z0-9])+)+)(?!_)/', $config_field_spec, $matches);
                if ($list_per_module)
                    $translations[$module_ident] = array_unique(array_merge($translations[$module_ident], $matches[0]));
                else
                    $translations = array_unique(array_merge($translations, $matches[0]));
            }

        return $translations;
    }
}

?>