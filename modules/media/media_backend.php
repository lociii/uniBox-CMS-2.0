<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_media_backend
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
        return ub_media_backend::version;
    } // end get_version()

    /**
    * class constructor
    *
    */
    private function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->config->load_config('media');
    } // end __construct()

    /**
    * returns class instance
    * 
    * @return       ub_media_base (object)
    */
    public static function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_media_backend;
        return self::$instance;
    } // end get_instance()

    /**
    * form to select how media should be added
    * 
    */
    public function file_select_type()
    {
        if ($this->unibox->session->env->system->action_ident == 'media_edit_file')
        {
            // try if the user is allowed to perform the requested action
            $validator = ub_validator::get_instance();
            $sql_string  = 'SELECT
                              media_id
                            FROM
                              data_media_base
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_edit_file')).')';
            if ($validator->validate('GET', 'media_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                $this->unibox->session->env->dialog->media_edit_file->media_id = $this->unibox->session->env->input->media_id;
            
            if (!isset($this->unibox->session->env->dialog->media_edit_file->media_id))
            {
                $msg = new ub_message(MSG_ERROR);
                $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
                $msg->display();
                return;
            }
        }

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', $this->unibox->session->env->system->action_ident.'_switch_type');
        $form = ub_form_creator::get_instance();
        if ($this->unibox->session->env->system->action_ident == 'media_add')
            $alias = $this->unibox->session->env->alias->name;
        else
            $alias = $this->unibox->session->env->alias->name.'/media_id/'.$this->unibox->session->env->dialog->media_edit_file->media_id;
        $form->begin_form($this->unibox->session->env->system->action_ident.'_switch_type', $alias);
        $form->register_with_dialog();
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
            $form->begin_fieldset('TRL_GENERAL');
            $sql_string  = 'SELECT
                              a.category_id,
                              b.string_value
                            FROM
                              sys_categories AS a
                                INNER JOIN sys_translations AS b
								  ON
								  (
								  b.string_ident = a.si_category_name
								  AND
								  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								  )
                            WHERE
                              a.module_ident = \'media\'
                              AND
                              a.category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_add')).')
                            ORDER BY
                              b.string_value ASC';
            $form->begin_select('category_id', 'TRL_CATEGORY');
	        $form->add_option_sql($sql_string);
            $form->end_select();
            $form->set_condition(CHECK_NOTEMPTY);
            $form->end_fieldset();
        }
        $form->begin_radio('type', 'TRL_MEDIA_ADD_BY');
        $form->add_option('1', 'TRL_MEDIA_ADD_BY_UPLOAD');
        $form->add_option('2', 'TRL_MEDIA_ADD_BY_POOL');
        $form->add_option('3', 'TRL_MEDIA_ADD_BY_LINK');
        $form->end_radio();
        $form->set_condition(CHECK_NOTEMPTY);
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
        $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
        $form->end_buttonset();
        $form->end_form();
    } // end add_select_type()

    /**
    * switch how media should be added
    * 
    */
    public function file_switch_type()
    {
        if ($this->unibox->session->env->system->action_ident == 'media_edit_file' && !isset($this->unibox->session->env->dialog->media_edit_file->media_id))
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
            $msg->display();
            return;
        }
        return (($this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->step - 2) * 3 + $this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_switch_type'}->data->type);
    } // end add_switch_type()

    protected function file_reset($new_form_name)
    {
        // reset old data if add type has changed
        if (isset($this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->forms[2]) && $this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->forms[2] != $new_form_name)
        {
            $this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->history = array();
            $this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->history[1] = true;

            // reset forms
            if (isset($this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->forms[2]))
            {
                ub_form_creator::reset($this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->forms[2], true, false);
                unset($this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->forms[2]);
            }
            if (isset($this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->forms[3]))
            {
                ub_form_creator::reset($this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->forms[3], true, false);
                unset($this->unibox->session->env->dialog->{$this->unibox->session->env->system->action_ident}->forms[3]);
            }
            
            // restore manipulated dialog
            $dialog = ub_dialog::get_instance();
            $dialog->redraw();
        }
    }

    /**
    * add media by upload
    * 
    */
    public function file_type_upload()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', $this->unibox->session->env->system->action_ident.'_type_upload');

        // check for insertion type changed and reset data
        $this->file_reset($this->unibox->session->env->system->action_ident.'_type_upload');

        $form = ub_form_creator::get_instance();
        $form->begin_form($this->unibox->session->env->system->action_ident.'_type_upload', $this->unibox->session->env->alias->name);
        $form->register_with_dialog();
        $form->begin_fieldset('TRL_GENERAL');
        $form->file('media', 'TRL_FILE', DIR_MEDIA_BASE_UPLOAD);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
        $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
        $form->end_buttonset();
        $form->end_form();
        return 0;
    } // end add_type_upload()

    /**
    * add details information to the media
    * 
    */
    public function file_type_upload_details()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', $this->unibox->session->env->system->action_ident.'_type_upload_details');

        $form = ub_form_creator::get_instance();
        $form->begin_form($this->unibox->session->env->system->action_ident.'_type_upload_details', $this->unibox->session->env->alias->name);
        $form->register_with_dialog();

        $form->begin_fieldset('TRL_GENERAL');
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
            $form->text('media_name', 'TRL_LANGUAGE_INDEPENDANT_DESCR', $this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_upload'}->data->media['name'], 40);
            $form->set_condition(CHECK_NOTEMPTY);
        }
        $form->text('media_file_name', 'TRL_FILENAME', ub_functions::get_file_name($this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_upload'}->data->media['name']), 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->comment('', 'TRL_WITHOUT_FILE_EXTENSION');
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
            $form->text('media_hits', 'TRL_SET_HIT_COUNTER_TO', '0', 15);
            $form->set_type(TYPE_INTEGER);
            $form->set_condition(CHECK_NOTEMPTY);
        }
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
        $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
        $form->end_buttonset();
        $form->end_form();
        return 0;
    } // end add_details()

    public function file_type_upload_process()
    {
        $this->unibox->db->begin_transaction();
        $file_extension = ub_functions::get_file_extension($this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_upload'}->data->media['name']);

        try
        {
            // get file extension
            $sql_string  = 'SELECT
                              mime_file_extension
                            FROM
                              sys_mime_types
                            WHERE
                              mime_file_extension = \''.$file_extension.'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select mime type');
            if ($result->num_rows() == 1)
            {
                list($mime_file_extension) = $result->fetch_row();
                $mime_file_extension = '\''.$this->unibox->db->cleanup($mime_file_extension).'\'';
            }
            else
                $mime_file_extension = 'NULL';

            // get size if image
            if ($media_size = @getimagesize(DIR_MEDIA_BASE_UPLOAD.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_upload'}->data->media['tmp_name']))
            {
                $media_width = $media_size[0];
                $media_height = $media_size[1];
            }
            else
                $media_width = $media_height = 'NULL';

            // build sql statement
            if ($this->unibox->session->env->system->action_ident == 'media_add')
            {
                $category_id = $this->unibox->session->env->form->media_add_switch_type->data->category_id;
                $sql_string  = 'INSERT INTO
                                  data_media_base
                                SET
                                  file_name = \''.$this->unibox->session->env->form->media_add_type_upload_details->data->media_file_name.'\',
                                  file_extension = \''.$this->unibox->db->cleanup($file_extension).'\',
                                  mime_file_extension = '.$mime_file_extension.',
                                  media_link = NULL,
                                  category_id = '.$category_id.',
                                  media_name = \''.$this->unibox->session->env->form->media_add_type_upload_details->data->media_name.'\',
                                  media_hits = '.$this->unibox->session->env->form->media_add_type_upload_details->data->media_hits.',
                                  media_size = '.$this->unibox->session->env->form->media_add_type_upload->data->media['size'].',
                                  media_width = '.$media_width.',
                                  media_height = '.$media_height;
            }
            else
            {
                // select category id
                $sql_string  = 'SELECT
                                  category_id,
                                  media_name,
                                  media_hits,
                                  file_extension,
                                  media_link
                                FROM
                                  data_media_base
                                WHERE
                                  media_id = '.$this->unibox->session->env->dialog->media_edit_file->media_id;
                $result = $this->unibox->db->query($sql_string, 'failed to select category');
                if ($result->num_rows() != 1)
                    throw new ub_exception_transaction('failed to select old media data');
                list($category_id, $media_name, $media_hits, $old_file_extension, $old_media_link) = $result->fetch_row();
    
                $sql_string  = 'REPLACE INTO
                                  data_media_base
                                SET
                                  media_id = '.$this->unibox->session->env->dialog->media_edit_file->media_id.',
                                  file_name = \''.$this->unibox->session->env->form->media_edit_file_type_upload_details->data->media_file_name.'\',
                                  file_extension = \''.$this->unibox->db->cleanup($file_extension).'\',
                                  mime_file_extension = '.$mime_file_extension.',
                                  category_id = '.$category_id.',
                                  media_name = \''.$media_name.'\',
                                  media_hits = '.$media_hits.',
                                  media_size = '.$this->unibox->session->env->form->media_edit_file_type_upload->data->media['size'].',
                                  media_width = '.$media_width.',
                                  media_height = '.$media_height;
            }

            // execute query
            $this->unibox->db->query($sql_string, 'failed to insert file by upload');

            // past processing
            if ($this->unibox->session->env->system->action_ident == 'media_add')
                $media_id = $this->unibox->db->last_insert_id();
            else
            {
                $media_id = $this->unibox->session->env->dialog->media_edit_file->media_id;
                // delete old thumbnail if any
                if ($old_media_link !== null)
                    $old_file_extension = ub_functions::get_file_extension($old_media_link);
                if (file_exists(DIR_MEDIA_BASE.$category_id.'/'.$media_id.'.'.$old_file_extension) && !unlink(DIR_MEDIA_BASE.$category_id.'/'.$media_id.'.'.$old_file_extension))
                    throw new ub_exception_transaction('failed to delete old file');
            }
    
            // check if directory exists or attempt to create it
            if (!is_dir(DIR_MEDIA_BASE.$category_id) && !mkdir(DIR_MEDIA_BASE.$category_id))
                throw new ub_exception_transaction('failed to create directory for media category '.$category_id);
    
            // copy file
            if (!rename(DIR_MEDIA_BASE_UPLOAD.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_upload'}->data->media['tmp_name'], DIR_MEDIA_BASE.$category_id.'/'.$media_id.'.'.$file_extension))
                throw new ub_exception_transaction('failed to rename temporary file');
        }
        catch (ub_exception_transaction $exception)
        {
            if ($this->unibox->session->env->system->action_ident == 'media_add')
                $exception->process('TRL_MEDIA_ADD_FAILED');
            else
                $exception->process('TRL_MEDIA_EXCHANGE_FAILED');
            ub_form_creator::reset($this->unibox->session->env->system->action_ident.'_switch_type');
            $this->unibox->switch_alias('media_administrate', true);
            return;
        }

        $this->unibox->db->commit();

        $msg = new ub_message(MSG_SUCCESS);
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
        	$msg->add_text('TRL_MEDIA_ADD_SUCCESSFUL');
            // switch administration category
            $preselect = ub_preselect::get_instance('media_administrate');
            $preselect->set_value('category_id', $category_id);

            $msg->add_newline(2);
            $msg->add_link('media_lang_administrate/media_id/'.$media_id, 'TRL_ALT_ADMINISTRATE_DESCRIPTIONS', array($this->unibox->session->env->form->media_add_type_upload_details->data->media_name));
        }
        else
        {
        	$msg->add_text('TRL_MEDIA_EXCHANGE_SUCCESSFUL');
        	$this->clear_cache($category_id, $media_id);
        }
        $msg->display();
        ub_form_creator::reset($this->unibox->session->env->system->action_ident.'_switch_type');
        $this->unibox->switch_alias('media_administrate', true);
        return;
    }

    /**
    * add media by upload pool
    * 
    */
    public function file_type_pool()
    {
        $dir = dir(DIR_MEDIA_BASE_UPLOAD_POOL);
        $files = array();
        while (false !== ($entry = $dir->read()))
            if ($entry != '.' && $entry != '..' && !is_dir(DIR_MEDIA_BASE_UPLOAD_POOL.$entry))
                $files[] = utf8_encode($entry);
        $dir->close();
        if (count($files) > 0)
        {
            $this->unibox->load_template('shared_form_display');
            $this->unibox->xml->add_value('form_name', $this->unibox->session->env->system->action_ident.'_type_pool');
    
            // check for insertion type changed and reset data
            $this->file_reset($this->unibox->session->env->system->action_ident.'_type_pool');

            $form = ub_form_creator::get_instance();
            $form->begin_form($this->unibox->session->env->system->action_ident.'_type_pool', $this->unibox->session->env->alias->name);
            $this->unibox->xml->add_value('title', 'TRL_MEDIA_ADD_SELECT_FROM_POOL', true);
            $form->register_with_dialog();
            
            // multi-checkbox on file add or single-radio on edit
            if ($this->unibox->session->env->system->action_ident == 'media_add')
                $form->begin_checkbox('media', 'TRL_FILE');
            else
                $form->begin_radio('media', 'TRL_FILE');

			$form->set_translation(false);
            foreach ($files as $file)
                $form->add_option($file, $file);
            $form->set_translation(true);

            // end checkbox/radio
            if ($this->unibox->session->env->system->action_ident == 'media_add')
                $form->end_checkbox();
            else
                $form->end_radio();
            $form->set_condition(CHECK_NOTEMPTY);
            $form->begin_buttonset();
            $form->submit('TRL_NEXT_UCASE', 'next');
            $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
            $form->end_buttonset();
            $form->end_form();
            return 0;
        }
        else
        {
            $msg = new ub_message(MSG_INFO);
            $msg->add_text('TRL_MEDIA_NO_FILES_IN_UPLOAD_POOL');
            $msg->add_newline(2);
            $msg->add_link($this->unibox->session->env->alias->name.'/step/1', 'TRL_BACK_TO_DIALOG_START');
            $msg->display();
        }
    } // end add_type_pool()

    /**
    * add details information to the media
    * 
    */
    public function file_type_pool_details_add()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'media_add_type_pool_details');

        $form = ub_form_creator::get_instance();
        $form->begin_form($this->unibox->session->env->system->action_ident.'_type_pool_details', $this->unibox->session->env->alias->name);
        $form->register_with_dialog();

        foreach ($this->unibox->session->env->form->media_add_type_pool->data->media as $media)
        {
            $unique = md5($media);
            $form->set_translation(false);
            $form->begin_fieldset($media);
            $form->set_translation(true);
            $form->text('media_name_'.$unique, 'TRL_LANGUAGE_INDEPENDANT_DESCR', $media, 40);
            $form->set_condition(CHECK_NOTEMPTY);
            $form->text('media_file_name_'.$unique, 'TRL_FILENAME', ub_functions::get_file_name($media), 40);
            $form->set_condition(CHECK_NOTEMPTY);
            $form->comment('TRL_INFO', 'TRL_WITHOUT_FILE_EXTENSION');
            $form->text('media_hits_'.$unique, 'TRL_SET_HIT_COUNTER_TO', '0', 15);
            $form->set_type(TYPE_INTEGER);
            $form->set_condition(CHECK_NOTEMPTY);
            $form->end_fieldset();
        }
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
        $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
        $form->end_buttonset();
        $form->end_form();
        return 0;
    } // end add_details()

    public function file_type_pool_details_edit()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'media_edit_file_type_pool_details');

        $form = ub_form_creator::get_instance();
        $form->begin_form('media_edit_file_type_pool_details', $this->unibox->session->env->alias->name);
        $form->register_with_dialog();

        $form->begin_fieldset('TRL_GENERAL');
        $form->text('media_file_name', 'TRL_FILENAME', ub_functions::get_file_name($this->unibox->session->env->form->media_edit_file_type_pool->data->media), 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->comment('TRL_INFO', 'TRL_WITHOUT_FILE_EXTENSION');
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
        $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
        $form->end_buttonset();
        $form->end_form();
        return 0;
    } // end add_details()

    public function file_type_pool_process_add()
    {
        $this->unibox->db->begin_transaction();

        try
        {
            // check if directory exists or attempt to create it
            if ((!is_dir(DIR_MEDIA_BASE.$this->unibox->session->env->form->media_add_switch_type->data->category_id) && !mkdir(DIR_MEDIA_BASE.$this->unibox->session->env->form->media_add_switch_type->data->category_id)) || !is_writable(DIR_MEDIA_BASE.$this->unibox->session->env->form->media_add_switch_type->data->category_id))
                throw new ub_exception_transaction('failed to create media category directory');
    
            // process each media
            $media_id = array();
            foreach ($this->unibox->session->env->form->media_add_type_pool->data->media as $media)
            {
                $unique = md5($media);
    
                // check for known file type
                $file_extension = $this->unibox->db->cleanup(ub_functions::get_file_extension($media));
                $sql_string  = 'SELECT
                                  mime_file_extension
                                FROM
                                  sys_mime_types
                                WHERE
                                  mime_file_extension = \''.$file_extension.'\'';
                $result = $this->unibox->db->query($sql_string, 'failed to select mime type');
                if ($result->num_rows() == 1)
                {
                    list($mime_file_extension) = $result->fetch_row();
                    $mime_file_extension = '\''.$this->unibox->db->cleanup($mime_file_extension).'\'';
                }
                else
                    $mime_file_extension = 'NULL';

                // get file size if image
                if ($media_size = @getimagesize(DIR_MEDIA_BASE_UPLOAD_POOL.$media))
                {
                    $media_width = $media_size[0];
                    $media_height = $media_size[1];
                }
                else
                    $media_width = $media_height = 'NULL';
    
                // build sql string
                $sql_string  = 'INSERT INTO
                                  data_media_base
                                SET
                                  file_name = \''.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_pool_details'}->data->{'media_file_name_'.$unique}.'\',
                                  file_extension = \''.$file_extension.'\',
                                  mime_file_extension = '.$mime_file_extension.',
                                  media_link = NULL,
                                  category_id = '.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_switch_type'}->data->category_id.',
                                  media_name = \''.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_pool_details'}->data->{'media_name_'.$unique}.'\',
                                  media_hits = '.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_pool_details'}->data->{'media_hits_'.$unique}.',
                                  media_size = '.filesize(DIR_MEDIA_BASE_UPLOAD_POOL.$media).',
                                  media_width = '.$media_width.',
                                  media_height = '.$media_height;
                $this->unibox->db->query($sql_string, 'failed to insert file by pool');
                if ($this->unibox->db->affected_rows() != 1)
                    throw new ub_exception_transaction('failed to insert dataset');
                $media_id[$media] = $this->unibox->db->last_insert_id();
    
                if (
                    !file_exists(DIR_MEDIA_BASE_UPLOAD_POOL.$media)
                    ||
                    (
                    file_exists(DIR_MEDIA_BASE.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_switch_type'}->data->category_id.'/'.$media_id[$media].'.'.$file_extension)
                    &&
                    !unlink(DIR_MEDIA_BASE.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_switch_type'}->data->category_id.'/'.$media_id[$media].'.'.$file_extension)
                    )
                   )
                    throw new ub_exception_transaction('file removed from pool or old file could not be removed');
            }
    
            foreach ($this->unibox->session->env->form->media_add_type_pool->data->media as $media)
            {
                $unique = md5($media);
                $file_extension = $this->unibox->db->cleanup(ub_functions::get_file_extension($media));
                // move temporary file
                if (!rename(DIR_MEDIA_BASE_UPLOAD_POOL.$media, DIR_MEDIA_BASE.$this->unibox->session->env->form->media_add_switch_type->data->category_id.'/'.$media_id[$media].'.'.$file_extension))
                    throw new ub_exception_transaction('file could not be moved from pool');
            }
        }
        catch (ub_exception_transaction $exception)
        {
            $exception->process('TRL_MEDIA_ADD_FAILED');
            ub_form_creator::reset('media_add_switch_type');
            $this->unibox->switch_alias('media_administrate', true);
            return;
        }

        $this->unibox->db->commit();

        // switch administration category
        $preselect = ub_preselect::get_instance('media_administrate');
        $preselect->set_value('category_id', $this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_switch_type'}->data->category_id);

        $msg = new ub_message(MSG_SUCCESS);
        if (count($this->unibox->session->env->form->media_add_type_pool->data->media) > 1)
            $msg->add_text('TRL_MEDIA_MULTI_ADD_SUCCESSFUL');
        else
            $msg->add_text('TRL_MEDIA_ADD_SUCCESSFUL');
        $msg->display();

        ub_form_creator::reset('media_add_switch_type');
        $this->unibox->switch_alias('media_administrate', true);
        return;
    }

    public function file_type_pool_process_edit()
    {
        $this->unibox->db->begin_transaction();

        try
        {
            // select category id
            $sql_string  = 'SELECT
                              category_id,
                              media_name,
                              media_hits,
                              media_link,
                              file_extension
                            FROM
                              data_media_base
                            WHERE
                              media_id = '.$this->unibox->session->env->dialog->media_edit_file->media_id;
            $result = $this->unibox->db->query($sql_string, 'failed to select category');
            if ($result->num_rows() != 1)
                throw new ub_exception_transaction('failed to select old media data');
            list($category_id, $media_name, $media_hits, $media_link, $old_file_extension) = $result->fetch_row();
    
            // check if directory exists or attempt to create it
            if ((!is_dir(DIR_MEDIA_BASE.$category_id) && !mkdir(DIR_MEDIA_BASE.$category_id)) || !is_writable(DIR_MEDIA_BASE.$category_id))
                throw new ub_exception_transaction('failed to create media category directory');
    
            // check for known file type
            $file_extension = ub_functions::get_file_extension($this->unibox->session->env->form->media_edit_file_type_pool->data->media);
            $sql_string  = 'SELECT
                              mime_file_extension
                            FROM
                              sys_mime_types
                            WHERE
                              mime_file_extension = \''.$this->unibox->db->cleanup($file_extension).'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select mime type');
            if ($result->num_rows() == 1)
            {
                list($mime_file_extension) = $result->fetch_row();
                $mime_file_extension = '\''.$this->unibox->db->cleanup($mime_file_extension).'\'';
            }
            else
                $mime_file_extension = 'NULL';

            // get media size if image
            if ($media_size = @getimagesize(DIR_MEDIA_BASE_UPLOAD_POOL.$this->unibox->session->env->form->media_edit_file_type_pool->data->media))
            {
                $media_width = $media_size[0];
                $media_height = $media_size[1];
            }
            else
                $media_width = $media_height = 'NULL';
    
            $sql_string  = 'REPLACE INTO
                              data_media_base
                            SET
                              media_id = '.$this->unibox->session->env->dialog->media_edit_file->media_id.',
                              file_name = \''.$this->unibox->session->env->form->media_edit_file_type_pool_details->data->media_file_name.'\',
                              file_extension = \''.$this->unibox->db->cleanup($file_extension).'\',
                              mime_file_extension = '.$mime_file_extension.',
                              category_id = '.$category_id.',
                              media_name = \''.$media_name.'\',
                              media_hits = '.$media_hits.',
                              media_size = '.filesize(DIR_MEDIA_BASE_UPLOAD_POOL.$this->unibox->session->env->form->media_edit_file_type_pool->data->media).',
                              media_width = '.$media_width.',
                              media_height = '.$media_height;
            $this->unibox->db->query($sql_string, 'failed to insert file by pool');
            if ($this->unibox->db->affected_rows() < 1)
                throw new ub_exception_transaction('failed to re-insert media');
    
            if ($media_link !== null)
                $old_file_extension = ub_functions::get_file_extension($media_link);
            $file = DIR_MEDIA_BASE.$category_id.'/'.$this->unibox->session->env->dialog->media_edit_file->media_id.'.'.$old_file_extension;
            if (file_exists($file) && !unlink($file))
                throw new ub_exception_transaction('failed to delete old file');

            if (
                !file_exists(DIR_MEDIA_BASE_UPLOAD_POOL.$this->unibox->session->env->form->media_edit_file_type_pool->data->media)
                ||
                !rename(DIR_MEDIA_BASE_UPLOAD_POOL.$this->unibox->session->env->form->media_edit_file_type_pool->data->media, DIR_MEDIA_BASE.$category_id.'/'.$this->unibox->session->env->dialog->media_edit_file->media_id.'.'.$file_extension)
               )
                throw new ub_exception_transaction('failed to move new media');
        }
        catch (ub_exception_transaction $exception)
        {
            $exception->process('TRL_MEDIA_EXCHANGE_FAILED');
            ub_form_creator::reset($this->unibox->session->env->system->action_ident.'_switch_type');
            $this->unibox->switch_alias('media_administrate', true);
            return;
        }

		$this->clear_cache($category_id, $this->unibox->session->env->dialog->media_edit_file->media_id);
        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS);
        $msg->add_text('TRL_MEDIA_EXCHANGE_SUCCESSFUL');
        $msg->display();
        
        ub_form_creator::reset('media_edit_file_switch_type');
        $this->unibox->switch_alias('media_administrate', true);
        return;
    }

    /**
    * add media by external link
    * 
    */
    public function file_type_link()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', $this->unibox->session->env->system->action_ident.'_type_link');

        // check for insertion type changed and reset data
        $this->file_reset($this->unibox->session->env->system->action_ident.'_type_link');

        $form = ub_form_creator::get_instance();
        $form->begin_form($this->unibox->session->env->system->action_ident.'_type_link', $this->unibox->session->env->alias->name);
        $this->unibox->xml->add_value('title', 'TRL_MEDIA_ADD_ENTER_LINK', true);
        $form->register_with_dialog();
        $form->begin_fieldset('TRL_GENERAL');
        $form->text('media', 'TRL_LINK', '', 50);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_URL, array('http', 'https', 'ftp'));

        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
        $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
        $form->end_buttonset();
        $form->end_form();
        return 0;
    } // end add_type_link()

    /**
    * add details information to the media
    * 
    */
    public function file_type_link_details()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', $this->unibox->session->env->system->action_ident.'_type_link_details');

        $form = ub_form_creator::get_instance();
        $form->begin_form($this->unibox->session->env->system->action_ident.'_type_link_details', $this->unibox->session->env->alias->name);
        $form->register_with_dialog();

        $url_array = parse_url($this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link'}->data->media);
        $form->begin_fieldset('TRL_GENERAL');
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
            $form->text('media_name', 'TRL_LANGUAGE_INDEPENDANT_DESCR', (isset($url_array['path'])) ? ub_functions::get_file_name($url_array['path']).'.'.ub_functions::get_file_extension($url_array['path']) : '', 50);
            $form->set_condition(CHECK_NOTEMPTY);
        }
        $form->begin_select('mime_file_extension', 'TRL_MEDIA_TYPE');
        $sql_string  = 'SELECT DISTINCT
                          mime_file_extension,
                          CONCAT(mime_type, \'/\', mime_subtype) AS display
                        FROM
                          sys_mime_types
                        ORDER BY
                          mime_type ASC,
                          mime_subtype ASC';
        $form->add_option_sql($sql_string, (isset($url_array['path']) ? ub_functions::get_file_extension($url_array['path']) : ''));
        $form->end_select();
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
            $form->text('media_hits', 'TRL_SET_HIT_COUNTER_TO', '0', 15);
            $form->set_type(TYPE_INTEGER);
            $form->set_condition(CHECK_NOTEMPTY);
        }
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
        $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
        $form->end_buttonset();
        $form->end_form();
        return 0;
    } // end add_details()

    /**
    * save media to database
    * 
    */
    public function file_type_link_process()
    {
        // process mime file_extension
        $mime_file_extension = (!isset($this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link_details'}->data->mime_file_extension)) ? 'NULL' : '\''.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link_details'}->data->mime_file_extension.'\'';
        if (((int)ini_get('allow_url_fopen') == 1) && $media_size = getimagesize($this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link'}->data->media))
        {
            $media_width = $media_size[0];
            $media_height = $media_size[1];
        }
        else
            $media_width = $media_height = 'NULL';

        // build sql statement
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
            $sql_string  = 'INSERT INTO
                              data_media_base
                            SET
                              file_name = NULL,
                              file_extension = NULL,
                              mime_file_extension = '.$mime_file_extension.',
                              media_link = \''.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link'}->data->media.'\',
                              category_id = '.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_switch_type'}->data->category_id.',
                              media_name = \''.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link_details'}->data->media_name.'\',
                              media_hits = '.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link_details'}->data->media_hits.',
                              media_size = '.$this->get_remote_filesize($this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link'}->data->media).',
                              media_width = '.$media_width.',
                              media_height = '.$media_height;
        }
        else
        {
            // select category id
            $sql_string  = 'SELECT
                              category_id,
                              media_name,
                              media_link,
                              file_extension,
                              media_hits
                            FROM
                              data_media_base
                            WHERE
                              media_id = '.$this->unibox->session->env->dialog->media_edit_file->media_id;
            if (!($result = $this->unibox->db->query($sql_string, 'failed to select category')) || $result->num_rows() != 1)
            {
                $msg = new ub_message(MSG_ERROR);
                $msg->add_text('TRL_MEDIA_ADD_OR_EXCHANGE_FAILED');
                $msg->display();
                ub_form_creator::reset('media_edit_file_switch_type');
                $this->unibox->switch_alias('media_administrate', true);
                return;
            }
            list($category_id, $media_name, $old_media_link, $file_extension, $media_hits) = $result->fetch_row();

            $sql_string  = 'REPLACE INTO
                              data_media_base
                            SET
                              media_id = '.$this->unibox->session->env->dialog->media_edit_file->media_id.',
                              mime_file_extension = '.$mime_file_extension.',
                              media_link = \''.$this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link'}->data->media.'\',
                              category_id = '.$category_id.',
                              media_name = \''.$this->unibox->db->cleanup($media_name).'\',
                              media_hits = '.$media_hits.',
                              media_size = '.$this->get_remote_filesize($this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link'}->data->media).',
                              media_width = '.$media_width.',
                              media_height = '.$media_height;
        }

        // insert / update has failed
        if (!$this->unibox->db->query($sql_string, 'failed to insert/update linked media') || $this->unibox->db->affected_rows() < 1)
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_MEDIA_ADD_OR_EXCHANGE_FAILED');
            $msg->display();
            ub_form_creator::reset($this->unibox->session->env->system->action_ident.'_switch_type');
            $this->unibox->switch_alias('media_administrate', true);
            return;
        }

        // set corresponding media id
        if ($this->unibox->session->env->system->action_ident == 'media_add')
            $media_id = $this->unibox->db->last_insert_id();
        else
        {
            $media_id = $this->unibox->session->env->dialog->media_edit_file->media_id;
            // delete old file
            if ($old_media_link !== null)
                $file_extension = ub_functions::get_file_extension($old_media_link);
            $file = DIR_MEDIA_BASE.$category_id.'/'.$this->unibox->session->env->dialog->media_edit_file->media_id.'.'.$file_extension;
            if (file_exists($file) && !unlink($file))
            {
                $msg = new ub_message(MSG_ERROR);
                $msg->add_text('TRL_MEDIA_ADD_OR_EXCHANGE_FAILED');
                $msg->display();
                ub_form_creator::reset('media_edit_file_switch_type');
                $this->unibox->switch_alias('media_administrate', true);
                return;
            }
        }

        // success
        $msg = new ub_message(MSG_SUCCESS);
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
            // switch administration category
            $preselect = ub_preselect::get_instance('media_administrate');
            $preselect->set_value('category_id', $this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_switch_type'}->data->category_id);

            $msg->add_text('TRL_MEDIA_ADD_SUCCESSFUL');
        }
        else
            $msg->add_text('TRL_MEDIA_EXCHANGE_SUCCESSFUL');
        if ($this->unibox->session->env->system->action_ident == 'media_add')
        {
            $msg->add_newline(2);
            $msg->add_link('media_lang_administrate/media_id/'.$media_id, 'TRL_MEDIA_ADMINISTRATE_DESCRIPTIONS', array($this->unibox->session->env->form->{$this->unibox->session->env->system->action_ident.'_type_link_details'}->data->media_name));
        }
        $msg->display();

        ub_form_creator::reset($this->unibox->session->env->system->action_ident.'_switch_type');
        $this->unibox->switch_alias('media_administrate', true);
    }

	protected function clear_cache($category_id, $media_id)
	{
		// clear cache
		$dir = DIR_MEDIA_CACHE.$category_id.'/'.$media_id;
		
		if (file_exists($dir))
		   if ($handle = opendir($dir))
		   {
		       while (($file = readdir($handle)) !== false)
		           @unlink($dir.'/'.$file);
		       closedir($handle);
		   }
	}

    public function administrate()
    {
		// get preselect object
		$preselect = ub_preselect::get_instance('media_administrate');

    	// check if we got a new category via url
    	$validator = ub_validator::get_instance();
    	if ($validator->validate('GET', 'category_id', TYPE_STRING, CHECK_ISSET))
    	{
    		$sql_string  = 'SELECT
							  category_id
							FROM
							  sys_categories
							WHERE
							  category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_edit', 'media_edit_file', 'media_lang_edit', 'media_delete')).')';
			if ($validator->validate('GET', 'category_id', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
			{
    			$this->unibox->session->env->form->media_administrate->data->category_id = $this->unibox->session->env->input->category_id;
            	$preselect->set_value('category_id', $this->unibox->session->env->form->media_administrate->data->category_id);
			}
    	}

        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'media_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('media_administrate', 'media_administrate');

        $preselect->add_field('category_id', 'a.category_id', true);
        $preselect->add_field('media_name', 'a.media_name');
        $preselect->add_field('file_name', 'a.file_name');
        $preselect->add_field('file_extension', 'a.file_extension', true);
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
        $form->begin_select('category_id', 'TRL_CATEGORY');
        $sql_string  = 'SELECT DISTINCT
                          a.category_id,
                          b.string_value
                        FROM
                          sys_categories AS a
                            INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_category_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        WHERE
                          a.category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_edit', 'media_edit_file', 'media_lang_edit', 'media_delete')).')
                        ORDER BY
                          b.string_value ASC';
        $form->add_option_sql($sql_string);
        $form->end_select();
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('media_name', 'TRL_LANGUAGE_INDEPENDANT_DESCR');
        $form->text('file_name', 'TRL_FILENAME');
        $form->begin_select('file_extension', 'TRL_FILE_EXTENSION');
        $sql_string  = 'SELECT DISTINCT
                          file_extension AS value,
                          file_extension AS display
                        FROM
                          data_media_base
                        ORDER BY
                          file_extension ASC';
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

    public function administrate_show_content()
    {
        // get preselect
        $preselect = ub_preselect::get_instance('media_administrate');
        $admin = ub_administration_ng::get_instance('media_administrate');

        $admin->add_field('TRL_LANGUAGE_INDEPENDANT_DESCR', 'a.media_name');
        $admin->add_field('TRL_FILENAME', 'a.file_name');
        $admin->add_field('TRL_FILE_EXTENSION', 'a.file_extension');
        $admin->add_field('TRL_FILE_SIZE', 'a.media_size');

		// select parent category
		$sql_string  = 'SELECT
						  a.category_parent_id,
						  c.string_value
						FROM
						  sys_categories AS a
							INNER JOIN sys_categories AS b
							  ON b.category_id = a.category_parent_id
							INNER JOIN sys_translations AS c
							  ON
							  (
							  c.string_ident = b.si_category_name
							  AND
							  c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						WHERE
						  a.category_id = '.$preselect->get_value('category_id').'
						  AND
						  a.category_parent_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_edit', 'media_edit_file', 'media_lang_edit', 'media_delete')).')';
		$result_parent = $this->unibox->db->query($sql_string, 'failed to select parent category');
		if ($result_parent->num_rows() > 0)
		{
			list($category_parent_id, $category_parent_name) = $result_parent->fetch_row();

			if ($category_parent_id !== null)
			{
				$admin->begin_dataset(false, true);
				$admin->add_dataset_ident('category_id', $category_parent_id);
				$admin->set_dataset_descr($category_parent_name);
				$admin->add_data($category_parent_name);
				$admin->add_option('container_up_true.gif', 'TRL_GOTO_PARENT_CATEGORY', $this->unibox->identify_alias('media_administrate'));
				$admin->end_dataset();
			}
		}

		// select child categories
		$sql_string  = 'SELECT
						  a.category_id,
						  b.string_value
						FROM
						  sys_categories AS a
							INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_category_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
						WHERE
						  a.category_parent_id = '.$preselect->get_value('category_id').'
						  AND
						  a.category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_edit', 'media_edit_file', 'media_lang_edit', 'media_delete')).')';
		$result_child = $this->unibox->db->query($sql_string, 'failed to select subcategories');
		if ($result_child->num_rows() > 0)
			while (list($category_id, $category_name) = $result_child->fetch_row())
			{
				$admin->begin_dataset(false, true);
				$admin->add_dataset_ident('category_id', $category_id);
				$admin->set_dataset_descr($category_name);
				$admin->add_data($category_name);
				$admin->add_option('container_down_true.gif', 'TRL_GOTO_CHILD_CATEGORY', $this->unibox->identify_alias('media_administrate'));
				$admin->end_dataset();
			}

		// select data to administrate
        $sql_string  = 'SELECT
                          a.media_id,
                          a.media_name,
                          a.file_name,
                          a.media_link,
                          a.file_extension,
                          a.media_size,
                          a.category_id,
                          b.mime_type,
                          a.media_width,
                          a.media_height
                        FROM
                          data_media_base AS a
                            LEFT JOIN sys_mime_types AS b
                              ON b.mime_file_extension = a.mime_file_extension
                        WHERE
                          '.$preselect->get_string();

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
            $rights_lang = ($this->unibox->session->has_right('media_lang_add', $preselect->get_value('category_id')) || $this->unibox->session->has_right('media_lang_edit', $preselect->get_value('category_id')));
            $rights_edit = $this->unibox->session->has_right('media_edit', $preselect->get_value('category_id'));
            $rights_delete = $this->unibox->session->has_right('media_delete', $preselect->get_value('category_id'));
            $rights_edit_file = $this->unibox->session->has_right('media_edit_file', $preselect->get_value('category_id'));
            $rights_preview = $this->unibox->session->has_right('media_show', $preselect->get_value('category_id'));
            while (list($media_id, $media_name, $file_name, $media_link, $file_extension, $media_size, $category_id, $mime_type, $media_width, $media_height) = $result->fetch_row())
            {
                $admin->begin_dataset();
                
                $admin->add_dataset_ident('media_id', $media_id);
                $admin->set_dataset_descr($media_name);
                
                $admin->add_data($media_name);
                if ($media_link !== null)
                {
                    $url_array = parse_url($media_link);
                    $admin->add_data(ub_functions::get_file_name($url_array['path']).' @ '.$url_array['host']);
                }
                else
                    $admin->add_data($file_name);
                $admin->add_data($file_extension);
                $media_size = ub_functions::bt2hr($media_size, 2);
                $admin->add_data($media_size['size'].' '.$this->unibox->translate($media_size['unit']), false, $media_size);

                if ($rights_preview)
                {
                    if ($mime_type == 'image')
                    {
                        if (!$this->unibox->session->uses_cookies)
                            $admin->add_option('preview_true.gif', 'TRL_ALT_PREVIEW_NEW_WINDOW', 'JavaScript:;', 'show_image('.$media_id.', '.$media_width.', '.$media_height.', '.SESSION_NAME.', '.$this->unibox->session->session_id.')');
                        else
                            $admin->add_option('preview_true.gif', 'TRL_ALT_PREVIEW_NEW_WINDOW', 'JavaScript:;', 'show_image('.$media_id.', '.$media_width.', '.$media_height.')');
                    }
                    else
                        $admin->add_option('preview_true.gif', 'TRL_ALT_PREVIEW', 'media.php5?media_id='.$media_id, null, false);
                }
                else
                    $admin->add_option('preview_false.gif', 'TRL_ALT_PREVIEW_FORBIDDEN');

                if ($rights_lang)
                    $admin->add_option('content_edit_true.gif', 'TRL_ALT_ADMINISTRATE_CONTENT', 'media_lang_administrate');
                else
                    $admin->add_option('content_edit_false.gif', 'TRL_ALT_ADMINISTRATE_CONTENT_FORBIDDEN');

                if ($rights_edit_file)
                    $admin->add_option('exchange_file_true.gif', 'TRL_ALT_EDIT_FILE', 'media_edit_file/step/1');
                else
                    $admin->add_option('exchange_file_false.gif', 'TRL_ALT_EDIT_FILE_FORBIDDEN');

                if ($rights_edit)
                    $admin->add_option('container_edit_true.gif', 'TRL_ALT_EDIT', 'media_edit');
                else
                    $admin->add_option('container_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($rights_delete)
                    $admin->add_option('container_delete_true.gif', 'TRL_ALT_DELETE', 'media_delete');
                else
                    $admin->add_option('container_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');

                $admin->end_dataset();
            }
            $admin->add_multi_option('content_edit_true.gif', 'TRL_ALT_MULTI_ADMINISTRATE_CONTENT', 'media_lang_administrate');
            $admin->add_multi_option('container_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'media_edit');
            $admin->add_multi_option('container_delete_true.gif', 'TRL_ALT_MULTI_DELETE', 'media_delete');
            $admin->set_multi_descr('TRL_MEDIA');
        }
        $admin->show('media_administrate');
    }

    public function delete()
    {
        // try if the user is allowed to perform the requested action
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              media_id
                            FROM
                              data_media_base
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_delete')).')';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('media_id'));
                if (!$validator->validate('STACK', 'media_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('media_delete');
        else
            $stack->switch_to_administration();
    }

    public function delete_confirm()
    {
        $stack = ub_stack::get_instance();

        $sql_string  = 'SELECT
                          media_name
                        FROM
                          data_media_base
                        WHERE
                          media_id IN ('.implode(', ', $stack->get_stack('media_id')).')';

        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_MEDIA_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($media_name) = $result->fetch_row())
                $msg->add_listentry($media_name, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('media_delete', 'media_delete');
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'media_delete');
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'discard_all');
            $msg->end_form();
        }
        else
        {
            ub_form_creator::reset('media_delete');
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
            $this->unibox->switch_alias('media_administrate', true);
        }
        $msg->display();
    }

    public function delete_process()
    {
        $stack = ub_stack::get_instance();
        $this->unibox->db->begin_transaction();

        try
        {
            $sql_string  = 'SELECT
                              media_id,
                              file_extension,
                              category_id,
                              media_link
                            FROM
                              data_media_base
                            WHERE
                              media_id IN ('.implode(', ', $stack->get_stack('media_id')).')';
            $result = $this->unibox->db->query($sql_string, 'failed to select media data');
    
            $sql_string  = 'DELETE FROM
                              data_media_base
                            WHERE
                              media_id IN ('.implode(', ', $stack->get_stack('media_id')).')';
            $this->unibox->db->query($sql_string, 'failed to delete media descriptions');
            if ($this->unibox->db->affected_rows() == 0)
                throw new ub_exception_transaction('failed to delete from media base');

            // check if files are writable and delete them
            if ($result->num_rows() > 0)
            {
                while (list($media_id, $file_extension, $category_id, $media_link) = $result->fetch_row())
                    if ($media_link === null && file_exists(DIR_MEDIA_BASE.$category_id.'/'.$media_id.'.'.$file_extension) && !is_writable(DIR_MEDIA_BASE.$category_id.'/'.$media_id.'.'.$file_extension))
                        throw new ub_exception_transaction('media not writable: '.$media_id);
                $result->goto(0);
                while (list($media_id, $file_extension, $category_id, $media_link) = $result->fetch_row())
                    if (file_exists(DIR_MEDIA_BASE.$category_id.'/'.$media_id.'.'.$file_extension))
                        unlink(DIR_MEDIA_BASE.$category_id.'/'.$media_id.'.'.$file_extension);
            }
        }
        catch (ub_exception_transaction $exception)
        {
            $$exception->process('TRL_DELETE_FAILED');
            ub_form_creator::reset('media_delete');
            $this->unibox->switch_alias('media_administrate', true);
            return;
        }

        // success
        $this->unibox->db->commit();
        ub_form_creator::reset('media_delete');
        
        $msg = new ub_message(MSG_SUCCESS);
        $msg->add_text('TRL_DELETE_SUCCESSFUL');
        $msg->display();
        $this->unibox->switch_alias('media_administrate', true);
    }

    public function edit()
    {
        // try if the user is allowed to perform the requested action
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              media_id
                            FROM
                              data_media_base
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_edit')).')';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('media_id'));
                if (!$validator->validate('STACK', 'media_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('media_edit');
        else
            $stack->switch_to_administration();
    }

    public function edit_refill()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
		$form = ub_form_creator::get_instance();

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'media_edit');

        $form->begin_form('media_edit', 'media_edit');
        $sql_string  = 'SELECT
                          category_id,
                          media_name,
                          file_name,
                          file_extension,
                          media_hits,
                          media_link,
                          mime_file_extension
                        FROM
                          data_media_base
                        WHERE
                          media_id = '.$dataset->media_id;
        $data = $form->set_values($sql_string);
        $form->set_destructor('ub_stack', 'discard_top');

        $form->begin_fieldset('TRL_GENERAL');
        $form->begin_select('category_id', 'TRL_CATEGORY');
        $sql_string  = 'SELECT
                          a.category_id,
                          b.string_value
                        FROM
                          sys_categories AS a
                            INNER JOIN sys_translations AS b
							  ON
							  (
							  b.string_ident = a.si_category_name
							  AND
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                        WHERE
                          a.module_ident = \'media\'';
        $form->add_option_sql($sql_string);
        $form->end_select();
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('media_name', 'TRL_LANGUAGE_INDEPENDANT_DESCR', '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('file_name', 'TRL_FILE_NAME', '', 40);
        $form->comment('TRL_INFO', 'TRL_WITHOUT_FILE_EXTENSION');
        if ($data['media_link'] !== null)
            $form->set_disabled();
        else
            $form->set_condition(CHECK_NOTEMPTY);

        // only for linked media
        $form->begin_select('mime_file_extension', 'TRL_MEDIA_TYPE');
        $sql_string  = 'SELECT DISTINCT
                          mime_file_extension,
                          CONCAT(mime_type, \'/\', mime_subtype) AS display
                        FROM
                          sys_mime_types
                        ORDER BY
                          mime_type ASC,
                          mime_subtype ASC';
        $form->add_option_sql($sql_string);
        $form->end_select();
        if ($data['media_link'] === null)
            $form->set_disabled();

        $form->text('media_hits', 'TRL_HIT_COUNTER', '0', 15);
        $form->set_type(TYPE_INTEGER);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_EDIT_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'media_administrate');
        $form->end_buttonset();
        $form->end_form();
        
        $this->unibox->set_content_title('TRL_MEDIA_EDIT', array($data['media_name']));
        $this->unibox->session->var->register('media_edit_linked', $data['media_link']);
    }
    
    public function edit_process()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        if ($this->unibox->session->var->media_edit_linked !== null)
            $sql_string  = 'UPDATE
                              data_media_base
                            SET
                              media_name = \''.$this->unibox->session->env->form->media_edit->data->media_name.'\',
                              mime_file_extension = \''.$this->unibox->session->env->form->media_edit->data->mime_file_extension.'\',
                              media_hits = '.$this->unibox->session->env->form->media_edit->data->media_hits.',
                              category_id = '.$this->unibox->session->env->form->media_edit->data->category_id.'
                            WHERE
                              media_id = '.$dataset->media_id;
        else
            $sql_string  = 'UPDATE
                              data_media_base
                            SET
                              media_name = \''.$this->unibox->session->env->form->media_edit->data->media_name.'\',
                              file_name = \''.$this->unibox->session->env->form->media_edit->data->file_name.'\',
                              media_hits = '.$this->unibox->session->env->form->media_edit->data->media_hits.',
                              category_id = '.$this->unibox->session->env->form->media_edit->data->category_id.'
                            WHERE
                              media_id = '.$dataset->media_id;

		$this->unibox->session->var->unregister('media_edit_linked');
        $this->unibox->db->query($sql_string, 'failed to update media data');
        if ($this->unibox->db->affected_rows() == 1)
        {
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_EDIT_SUCCESSFUL');
            $msg->display();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_EDIT_FAILED');
            $msg->display();
        }
        ub_form_creator::reset('media_edit');
    }

    public function lang_edit()
    {
        $stack = ub_stack::get_instance();
        $validator = ub_validator::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              media_id
                            FROM
                              data_media_base
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_lang_edit')).')';
    
            $sql_string_language = 'SELECT
                                      lang_ident
                                    FROM
                                      sys_languages
                                    WHERE
                                      lang_active = 1';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('media_id', 'lang_ident'));
                if (!$validator->validate('STACK', 'media_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string) || !$validator->validate('STACK', 'lang_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string_language))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('media_lang_edit');
        else
            $stack->switch_to_administration();
    }

    public function lang_edit_form()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'media_lang_edit');

        $form = ub_form_creator::get_instance();
        $form->begin_form('media_lang_edit', 'media_lang_edit');
        $sql_string  = 'SELECT
                          a.media_name AS name,
                          b.media_name,
                          b.media_descr,
                          b.media_descr_short,
                          d.string_value AS language
                        FROM
                          data_media_base AS a
                            LEFT JOIN data_media_base_descr AS b
                              ON
                              (
                              b.media_id = a.media_id
                              AND
                              b.lang_ident = \''.$dataset->lang_ident.'\'
                              )
                            LEFT JOIN sys_languages AS c
                              ON c.lang_ident = \''.$dataset->lang_ident.'\'
                            LEFT JOIN sys_translations AS d
                              ON
                              (
                              d.string_ident = c.si_lang_descr
                              AND
                              d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              )
                        WHERE
                          a.media_id = '.$dataset->media_id;
        $values = $form->set_values($sql_string, array(), true);
        $form->set_destructor('ub_stack', 'discard_top');

        // set page title
        $this->unibox->set_content_title('TRL_LANGUAGE_EDIT', array($values['name'], $values['language']));

        $form->begin_fieldset('TRL_GENERAL');
        $form->text('media_name', 'TRL_NAME', '', 40);
        $form->textarea('media_descr_short', 'TRL_DESCR_SHORT', '', 70, 13);
        $form->textarea('media_descr', 'TRL_DESCR', '', 70, 13);
        $form->end_fieldset();
        $form->begin_buttonset();
        if ($this->unibox->session->env->system->action_ident == 'media_lang_add')
            $form->submit('TRL_ADD_UCASE');
        else
            $form->submit('TRL_EDIT_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'media_lang_edit');
        $form->end_buttonset();
        $form->end_form();
    }

    public function lang_edit_process()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string  = 'REPLACE INTO
                          data_media_base_descr
                        SET
                          media_id = '.$dataset->media_id.',
                          lang_ident = \''.$dataset->lang_ident.'\',
                          media_name = \''.$this->unibox->session->env->form->media_lang_edit->data->media_name.'\',
                          media_descr = \''.$this->unibox->session->env->form->media_lang_edit->data->media_descr.'\',
                          media_descr_short = \''.$this->unibox->session->env->form->media_lang_edit->data->media_descr_short.'\'';
        $this->unibox->db->query($sql_string, 'failed to update content for passed media');
        
        if ($this->unibox->db->affected_rows() > 0)
        {
            $msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_EDIT_SUCCESSFUL');
            $msg->display();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR, false);
            $msg->add_text('TRL_EDIT_FAILED');
            $msg->display();
        }
        ub_form_creator::reset('media_lang_edit');
    }

    public function lang_administrate()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              media_id
                            FROM
                              data_media_base
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('media_lang_edit')).')';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('media_id'));
                if (!$validator->validate('STACK', 'media_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('media_lang_administrate');
        else
            $stack->switch_to_administration();
    }

    /**
    * lang_administrate()
    *
    * content administration - show content
    */
    public function lang_administrate_show_content()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->pop();

        $sql_string  = 'SELECT
                          media_name
                        FROM
                          data_media_base
                        WHERE
                          media_id = '.$dataset->media_id;
        $result = $this->unibox->db->query($sql_string, 'failed to get container descr');
        list($media_name) = $result->fetch_row();
        $this->unibox->set_content_title('TRL_MEDIA_LANG_ADMINISTRATE', array($media_name));

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
                        WHERE
                          a.lang_active = 1
                        ORDER BY
                          b.string_value';
        $result = $this->unibox->db->query($sql_string, 'failed to select containers');

        $pagebrowser = ub_pagebrowser::get_instance('media_lang_administrate');
        $pagebrowser->process($sql_string, 25);

        if ($result->num_rows() == 1)
        {
            $stack_lang = ub_stack::get_instance('media_lang_edit');
            $stack_lang->clear();
            $stack_lang->set_administration('media_lang_administrate');
            list($lang_ident, $lang_descr) = $result->fetch_row();
            $stack_lang->push(array('media_id' => $dataset->media_id, 'lang_ident' => $lang_ident));
            $stack->set_valid(true);
            $this->unibox->switch_action('media_lang_edit');
        }
        elseif ($result->num_rows() > 0)
        {
            $this->unibox->load_template('shared_administration');
            $admin = ub_administration::get_instance('media_lang_administrate');
            $admin->add_field('TRL_LANGUAGE');
            if ($stack->count() > 0)
                $admin->add_link('media_lang_administrate', 'TRL_NEXT_DATASET');
            else
                $admin->add_link('media_administrate', 'TRL_BACK_TO_ADMINISTRATION');

            while (list($lang_ident, $lang_descr) = $result->fetch_row())
            {
                $admin->begin_dataset();
                
                $admin->add_dataset_ident('media_id', $dataset->media_id);
                $admin->add_dataset_ident('lang_ident', $lang_ident);
                
                $admin->add_data($lang_descr);
                $admin->add_option('content_edit_true.gif', 'TRL_ALT_EDIT', 'media_lang_edit');
                $admin->end_dataset();
            }
            $admin->add_multi_option('content_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'media_lang_edit');
            $admin->set_multi_descr('TRL_LANGUAGES');
            $admin->show();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_FAILED_TO_GET_LANGUAGES');
            $msg->add_newline(2);
            $msg->add_link('media_administrate', 'TRL_BACK_TO_ADMINISTRATION');
            $msg->display();
        }
    }

	public function media_browse()
	{
		$validator = ub_validator::get_instance();
		$view_type = 'details';
		if ($validator->form_validate('media_editor_browse_view'))
		{
			$view_type = $this->unibox->session->env->form->media_editor_browse_view->data->view;
			$this->unibox->session->var->register('media_editor_browse_view', $view_type);
		}
		elseif (isset($this->unibox->session->var->media_editor_browse_view))
			$view_type = $this->unibox->session->var->media_editor_browse_view;

		$this->unibox->xml->add_value('view', $view_type);

		$form = ub_form_creator::get_instance('media_editor_browse_view');
		$form->begin_form('media_editor_browse_view', 'media_editor_browse');
		$form->begin_radio('view', '');
		$form->add_option('details', '');
		$form->add_option('thumbnails', '');
		$form->end_radio();
		$form->submit('');
		$form->end_form();

        if (isset($this->unibox->session->env->form->media_editor_browse_view->spec->hash))
            $this->unibox->xml->add_value('form_hash_view', $this->unibox->session->env->form->media_editor_browse_view->spec->hash);

		if ($view_type == 'details')
			return 0;
		else
			return 1;
	}
	
	public function media_browse_details()
	{
		$validator = ub_validator::get_instance();
		
		$allowed_categories = $this->unibox->session->get_allowed_categories('media_show');
        $allowed_categories[] = 0;

		$this->unibox->load_template('shared_administration');

		// check if a category was submitted
		$parent_category = 0;
		if ($validator->validate('GET', 'category_id', TYPE_INTEGER, CHECK_INSET, null, $allowed_categories))
		{
			$parent_category = $this->unibox->session->env->input->category_id;
			$this->unibox->session->var->register('media_editor_browse_category_id', $parent_category);
		}
		elseif (isset($this->unibox->session->var->media_editor_browse_category_id))
			$parent_category = $this->unibox->session->var->media_editor_browse_category_id;

        $admin = ub_administration::get_instance('media_editor_browse');
        $admin->add_field('TRL_NAME');
        $admin->add_field('TRL_FILENAME');
        $admin->add_field('TRL_DIMENSIONS');

		// get parent category of current category if the current category isn't the root category
		if ($parent_category != 0)
		{
			$sql_string =  'SELECT
							  category_parent_id
							FROM
							  sys_categories
							WHERE
							  category_id = '.$parent_category;
			$result = $this->unibox->db->query($sql_string, 'failed to get parent category');
			list($category_parent_id) = $result->fetch_row();

            $admin->begin_dataset(false, true);
            $admin->add_dataset_ident('category_id', ($category_parent_id === null ? 0 : $category_parent_id));
			$admin->add_data('TRL_GOTO_PARENT_CATEGORY', true);
			$admin->add_option('container_up_true.gif', 'TRL_GOTO_PARENT_CATEGORY', 'media_editor_browse');
			$admin->end_dataset();
		}

		$sql_string =  'SELECT
						  a.category_id,
						  b.string_value
						FROM
						  sys_categories AS a
						  LEFT JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						WHERE
						  a.category_parent_id '.($parent_category == 0 ? 'IS NULL' : '= \''.$parent_category.'\'').'
						  AND
						  a.module_ident = \'media\'
						  AND
                          (
						  a.category_id IN ('.implode(', ', $allowed_categories).')
                          OR
                          a.category_id IS NULL
                          )
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						ORDER BY
						  b.string_value ASC';
		$result = $this->unibox->db->query($sql_string, 'failed to retrieve categories');
		$categories_count = $result->num_rows();
		while (list($category_id, $category_name) = $result->fetch_row())
		{
            $admin->begin_dataset(false, true);
			$admin->add_data($category_name);
			$admin->add_dataset_ident('category_id', $category_id);
			$admin->add_option('container_down_true.gif', 'TRL_SHOW_CATEGORY', 'media_editor_browse');
			$admin->end_dataset();
		}

		if ($parent_category != 0)
		{
			$sql_string =  'SELECT
							  a.media_id,
	                          a.category_id,
							  a.media_name,
	                          a.file_name,
							  a.file_extension,
							  c.media_name,
	                          a.media_width,
	                          a.media_height
							FROM
							  data_media_base AS a
	                          INNER JOIN sys_mime_types AS b
	                            ON b.mime_file_extension = a.mime_file_extension
							  LEFT JOIN data_media_base_descr AS c
								ON
	                            (
	                            c.media_id = a.media_id
	                            AND
	                            c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
	                            )
							WHERE
							  a.category_id = '.$parent_category.'
	                          AND
	                          a.category_id IN ('.implode(', ', $allowed_categories).')
							  AND
	                          b.mime_type = \'image\'';
			$result = $this->unibox->db->query($sql_string, 'failed to retrieve files');
	
			$count_per_page = (($count = 22 - $categories_count) < 15) ? 15 : $count;
	        $pagebrowser = ub_pagebrowser::get_instance('media_editor_browse');
	        if ($pagebrowser->process($sql_string, $count_per_page, true) > 1 && isset($this->unibox->session->env->form->pagebrowser_media_editor_browse_page->spec->hash))
	            $this->unibox->xml->add_value('form_hash_pagebrowser', $this->unibox->session->env->form->pagebrowser_media_editor_browse_page->spec->hash);
	
	        if ($result->num_rows() > 0)
	        {
	    		while (list($media_id, $category_id, $media_name, $media_file_name, $media_file_extension, $media_name_lang, $media_width, $media_height) = $result->fetch_row())
	    		{
	                $admin->begin_dataset(false);
	                $admin->set_dataset_descr((trim($media_name_lang) != '') ? $media_name_lang : $media_name);
	    			$admin->add_data((trim($media_name_lang) != '') ? $media_name_lang : $media_name);
	    			$admin->add_data($media_file_name);
	                $admin->add_data($media_width.'x'.$media_height);
	
					$click = 'show_image('.$media_id.', '.$media_width.', '.$media_height;
					$set = 'top.uniBoxMedia.set_media('.$media_id.', '.$media_width.', '.$media_height;
	                if (!$this->unibox->session->uses_cookies)
	                {
	                    $click .= ', '.SESSION_NAME.', '.$this->unibox->session->session_id;
	                    $set .= ', '.SESSION_NAME.', '.$this->unibox->session->session_id;
	                }
	                $admin->add_option('preview_true.gif', 'TRL_ALT_PREVIEW_NEW_WINDOW', 'JavaScript:;', $click.');');
	                $admin->add_option('plus_true.gif', 'TRL_ALT_SELECT', 'JavaScript:;', $set.');');
	    			
	                $admin->end_dataset();
	    		}
	        }
		}
        $admin->show('media_editor_browse');
	} // end media_browse_details()

	public function media_browse_thumbnails()
	{
		$validator = ub_validator::get_instance();

		$allowed_categories = $this->unibox->session->get_allowed_categories('media_show');
		$allowed_categories[] = 0;

		$this->unibox->load_template('media_browse_thumbnails');
		
		// check if a category was submitted
		$parent_category = 0;
		if ($validator->validate('GET', 'category_id', TYPE_INTEGER, CHECK_INSET, null, $allowed_categories))
		{
			$parent_category = $this->unibox->session->env->input->category_id;
			$this->unibox->session->var->register('media_editor_browse_category_id', $parent_category);
		}
		elseif (isset($this->unibox->session->var->media_editor_browse_category_id))
			$parent_category = $this->unibox->session->var->media_editor_browse_category_id;

		// get parent category of current category if the current category isn't the root category
		if ($parent_category != 0)
		{
			$sql_string =  'SELECT
							  category_parent_id
							FROM
							  sys_categories
							WHERE
							  category_id = \''.$parent_category.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to get parent category');
			list($category_parent_id) = $result->fetch_row();

			$this->unibox->xml->add_node('container');
			$this->unibox->xml->add_value('id', ($category_parent_id === null ? 0 : $category_parent_id));
			$this->unibox->xml->add_value('name', 'TRL_GOTO_PARENT_CATEGORY', true);
            $this->unibox->xml->add_value('parent', '1');
			$this->unibox->xml->parse_node();
		}

		$sql_string =  'SELECT
						  a.category_id,
						  b.string_value
						FROM
						  sys_categories AS a
						  LEFT JOIN sys_translations AS b
							ON
                            (
                            b.string_ident = a.si_category_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
						WHERE
                          a.category_parent_id '.($parent_category == 0 ? 'IS NULL' : '= \''.$parent_category.'\'').'
                          AND
                          a.module_ident = \'media\'
                          AND
                          (
                          a.category_id IN ('.implode(', ', $allowed_categories).')
                          OR
                          a.category_id IS NULL
                          )
						ORDER BY
						  b.string_value ASC';
		$result = $this->unibox->db->query($sql_string, 'failed to retrieve categories');
		$categories_count = $result->num_rows();
		while (list($category_id, $category_name) = $result->fetch_row())
		{
			$this->unibox->xml->add_node('container');
			$this->unibox->xml->add_value('id', $category_id);
			$this->unibox->xml->add_value('name', $category_name);
			$this->unibox->xml->parse_node();
		}

		if ($parent_category != 0)
		{
			$sql_string =  'SELECT
							  a.media_id,
	                          a.category_id,
							  a.media_name,
	                          a.file_name,
							  a.file_extension,
							  c.media_name,
	                          a.media_width,
	                          a.media_height
							FROM
							  data_media_base AS a
	                          INNER JOIN sys_mime_types AS b
	                            ON b.mime_file_extension = a.mime_file_extension
							  LEFT JOIN data_media_base_descr AS c
								ON
	                            (
	                            c.media_id = a.media_id
	                            AND
	                            c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
	                            )
							WHERE
							  a.category_id = '.$parent_category.'
	                          AND
	                          a.category_id IN ('.implode(', ', $allowed_categories).')
							  AND
	                          b.mime_type = \'image\'';
			$result = $this->unibox->db->query($sql_string, 'failed to retrieve files');
	
	        $pagebrowser = ub_pagebrowser::get_instance('media_editor_browse');
	        if ($pagebrowser->process($sql_string, 0, true) > 0 && isset($this->unibox->session->env->form->media_editor_browse->spec->hash))
	            $this->unibox->xml->add_value('form_hash_pagebrowser', $this->unibox->session->env->form->media_editor_browse->spec->hash);
			$pagebrowser->show('media_editor_browse');

			if ($result->num_rows() > 0)
			{
				if (!$this->unibox->session->uses_cookies)
				{
					$this->unibox->xml->add_value('session_name', SESSION_NAME);
					$this->unibox->xml->add_value('session_id', $this->unibox->session->session_id);
				}
				while (list($media_id, $category_id, $media_name, $media_file_name, $media_file_extension, $media_name_lang, $media_width, $media_height) = $result->fetch_row())
				{
					$this->unibox->xml->add_node('item');
					$this->unibox->xml->add_value('id', $media_id);
					$this->unibox->xml->add_value('name', $media_name);
					$this->unibox->xml->add_value('width', $media_width);
					$this->unibox->xml->add_value('height', $media_height);
					
					list($tn_width, $tn_height) = $this->calculate($media_width, $media_height, 100, 100);
					$this->unibox->xml->add_value('tn_width', $tn_width);
					$this->unibox->xml->add_value('tn_height', $tn_height);
					
					$this->unibox->xml->parse_node();
				}
			}
		}
	} // end media_browse_thumbnails()

	protected function calculate($old_width, $old_height, $new_width, $new_height)
	{
		// max size = old size
		if ($new_width !== null && $new_width > $old_width)
			$new_width = $old_width;
		if ($new_height !== null && $new_height > $old_height)
			$new_height = $old_height;

		// calculate new size
		if (($new_width === null && $new_height !== null) || ($new_width !== null && $new_height !== null && $old_height > $old_width))
			$new_width = $old_width * ($new_height / $old_height);
        elseif ($new_width !== null && $new_height === null || ($new_width !== null && $new_height !== null && $old_height < $old_width))
			$new_height = $old_height * ($new_width / $old_width);
        return array(ceil($new_width), ceil($new_height));
	}

	public function media_theme_browse()
	{
		$this->unibox->load_template('media_theme_browse');
		if (!$this->unibox->session->uses_cookies)
		{
			$this->unibox->xml->add_value('session_name', SESSION_NAME);
			$this->unibox->xml->add_value('session_id', $this->unibox->session->session_id);
		}

		$sql_string  = 'SELECT
						  a.subtheme_ident,
						  a.theme_ident,
						  c.string_value,
						  d.string_value
						FROM
						  sys_subthemes AS a
						  LEFT JOIN sys_themes AS b
							ON b.theme_ident = a.theme_ident
						  LEFT JOIN sys_translations AS c
							ON c.string_ident = a.si_subtheme_descr
						  LEFT JOIN sys_translations AS d
							ON d.string_ident = b.si_theme_descr
						WHERE
						  (c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							OR
						  c.lang_ident IS NULL)
						  AND
						  (d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							OR
						  d.lang_ident IS NULL)
						ORDER BY
						  d.string_value, c.string_value';
		$result = $this->unibox->db->query($sql_string, 'failed to select themes');
		while (list($subtheme_ident, $theme_ident, $subtheme_descr, $theme_descr) = $result->fetch_row())
		{
			$this->unibox->xml->add_node('theme');
			$this->unibox->xml->add_value('ident', $theme_ident);
			$this->unibox->xml->add_value('descr', $theme_descr);
			$this->unibox->xml->add_node('subtheme');
			$this->unibox->xml->add_value('ident', $subtheme_ident);
			$this->unibox->xml->add_value('descr', $subtheme_descr);
			$this->unibox->xml->parse_node();
			$this->unibox->xml->parse_node();
		}

		return 0;
	} // end media_theme_browse()

    /**
    * retrieves size of a remote file
    * 
    * @param   string      url
    * @return  int         file size in bytes
    */
    protected function get_remote_filesize($url)
    {
        // break url down to its parts
        $url_array = parse_url($url);

        // try to determine filesize depending on scheme
        if (isset($url_array['scheme']) && $url_array['scheme'] == 'ftp')
        {
            // try to connect to ftp server
            $ftp_port = isset($url_array['port']) ? $url_array['port'] : 21;
            if (($resource = @ftp_connect($url_array['host'], $ftp_port)) == false)
                return false;
            
            // login
            $ftp_user = isset($url_array['user']) ? $url_array['user'] : (isset($this->unibox->config->system->ftp_anonymous_user)) ? $this->unibox->config->system->ftp_anonymous_user : 'anonymous@media-soma.de';
            $ftp_pass = isset($url_array['pass']) ? $url_array['pass'] : (isset($this->unibox->config->system->ftp_anonymous_pass)) ? $this->unibox->config->system->ftp_anonymous_pass : 'anonymous';
            if (!@ftp_login($resource, $ftp_user, $ftp_pass))
                return false;
            
            // get file size
            $size = @ftp_size($resource, $url_array['path']);
            
            // close connection
            @ftp_close($resource);
            
            if ((string)(int)$size !== (string)$size)
                $size = 0;
            return $size;
        }
        elseif (function_exists('curl_init'))
        {
			ob_start();
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_NOBODY, 1);

			$ok = curl_exec($ch);
			curl_close($ch);
			$head = ob_get_contents();
			ob_end_clean();

			$regex = '/Content-Length:\s([0-9].+?)\s/';
			$matches = array();
			$count = preg_match($regex, $head, $matches);

			if (isset($matches[1]))
				return $matches[1];
        }
        return 0;
    } // end media_get_remote_filesize()

    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_MEDIA_BASE_WELCOME_TEXT');
        $msg->display();
        return 0;
    }

}

?>