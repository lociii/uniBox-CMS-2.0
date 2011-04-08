<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_articles_backend
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
        return ub_articles_backend::version;
    }

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      object of current class
    */
    public static function get_instance()
    {
        if (is_null(self::$instance))
            self::$instance = new ub_articles_backend;
        return self::$instance;
    }

    /**
    * __construct()
    *
    * session constructor - gets called everytime the objects get instantiated
    */
    public function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->config->load_config('articles');
    }

    /**
    * welcome()
    *
    * shows the welcome message
    */
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_ARTICLES_WELCOME_TEXT');
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
    	// get preselect object
    	$preselect = ub_preselect::get_instance('articles_administrate');
    	
    	// check if we got a new category via url
    	$validator = ub_validator::get_instance();
    	if ($validator->validate('GET', 'category_id', TYPE_STRING, CHECK_ISSET))
    	{
    		$sql_string  = 'SELECT
							  category_id
							FROM
							  sys_categories
							WHERE
							  module_ident = \'articles\'
							  AND
							  category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_edit', 'articles_delete', 'articles_lang_edit')).')';
			if ($validator->validate('GET', 'category_id', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
			{
    			$this->unibox->session->env->form->articles_administrate->data->category_id = $this->unibox->session->env->input->category_id;
            	$preselect->set_value('category_id', $this->unibox->session->env->form->articles_administrate->data->category_id);
			}
    	}

        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'articles_administrate');

        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('articles_administrate', $this->unibox->identify_alias('articles_administrate'));

        $preselect->add_field('category_id', 'a.category_id', true);
        $preselect->add_field('articles_container_descr', 'a.articles_container_descr');
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
                          a.category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_edit', 'articles_delete', 'articles_lang_edit', 'articles_publish')).')
                        ORDER BY
                          b.string_value ASC';
        $form->add_option_sql($sql_string);
        $form->end_select();
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('articles_container_descr', 'TRL_LANGUAGE_INDEPENDANT_DESCR');
        $form->end_fieldset();

        $form->begin_buttonset();
        $form->submit('TRL_SHOW_UCASE', 0);
        $form->submit('TRL_CANCEL_PRESELECT_UCASE', 1);
        $form->end_buttonset();
        $form->end_form();

        return $preselect->process();
    }

    /**
    * administrate()
    *
    * container administration - show content
    */
    public function administrate_show_content()
    {
        // get preselect
        $preselect = ub_preselect::get_instance('articles_administrate');
        $admin = ub_administration_ng::get_instance('articles_administrate');

        // add header fields
        $admin->add_field('TRL_TITLE', 'a.articles_container_descr');
        $admin->add_field('TRL_SHOW_BEGIN', 'a.articles_container_show_begin');
        $admin->add_field('TRL_SHOW_END', 'a.articles_container_show_end');
        $admin->add_field('TRL_SORT', 'a.articles_container_sort');
        $admin->sort_by('a.articles_container_sort');

        $sql_string  = 'SELECT
                          MIN(a.articles_container_sort) AS minimum,
                          MAX(a.articles_container_sort) AS maximum
                        FROM
                          data_articles_container AS a
                        WHERE
                          '.$preselect->get_string();
        $result = $this->unibox->db->query($sql_string, 'failed to select min and max sort');
        if ($result->num_rows() == 1)
            list($min, $max) = $result->fetch_row();

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
						  a.category_parent_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_edit', 'articles_delete', 'articles_lang_edit', 'articles_publish')).')';
		$result = $this->unibox->db->query($sql_string, 'failed to select parent category');
		if ($result->num_rows() > 0)
			list($category_parent_id, $category_parent_name) = $result->fetch_row();

		if (isset($category_parent_id) && $category_parent_id !== null)
		{
			$admin->begin_dataset(false, true);
			$admin->add_dataset_ident('category_id', $category_parent_id);
			$admin->set_dataset_descr($category_parent_name);
			$admin->add_data($category_parent_name);
			$admin->add_option('container_up_true.gif', 'TRL_GOTO_PARENT_CATEGORY', $this->unibox->identify_alias('articles_administrate'));
			$admin->end_dataset();
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
						  a.category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_edit', 'articles_delete', 'articles_lang_edit', 'articles_publish')).')';
		$result = $this->unibox->db->query($sql_string, 'failed to select subcategories');
		if ($result->num_rows() > 0)
			while (list($category_id, $category_name) = $result->fetch_row())
			{
				$admin->begin_dataset(false, true);
				$admin->add_dataset_ident('category_id', $category_id);
				$admin->set_dataset_descr($category_name);
				$admin->add_data($category_name);
				$admin->add_option('container_down_true.gif', 'TRL_GOTO_CHILD_CATEGORY', $this->unibox->identify_alias('articles_administrate'));
				$admin->end_dataset();
			}

		// select data to administrate
        $sql_string  = 'SELECT
                          a.articles_container_id,
                          a.articles_container_descr,
                          a.articles_container_show_begin,
                          a.articles_container_show_end,
                          a.articles_container_sort
                        FROM
                          data_articles_container AS a
                        WHERE
                          '.$preselect->get_string();

		$result = $admin->process_sql($sql_string, 25);
        if ($result->num_rows() > 0)
        {
            $time = new ub_time(TIME_TYPE_DB, TIME_TYPE_USER);
            $rights_lang = ($this->unibox->session->has_right('articles_lang_add', $preselect->get_value('category_id')) || $this->unibox->session->has_right('articles_lang_edit', $preselect->get_value('category_id')));
            $rights_edit = ($this->unibox->session->has_right('articles_edit', $preselect->get_value('category_id')));
            $rights_delete = ($this->unibox->session->has_right('articles_delete', $preselect->get_value('category_id')));
            $rights_sort = ($this->unibox->session->has_right('articles_sort', $preselect->get_value('category_id')));
            while (list($id, $descr, $begin, $end, $sort) = $result->fetch_row())
            {
                $admin->begin_dataset();

                $admin->add_dataset_ident('articles_container_id', $id);
                $admin->set_dataset_descr($descr);

                $admin->add_data($descr);
                
                // add begin date
                if ($begin === null)
                    $admin->add_data('TRL_NO_BEGIN', true, $begin);
                else
                {
                    $time->reset();
                    $time->parse_datetime($begin);
                    $admin->add_data($time->get_datetime(), false, $begin);
                }
                
                // add end date
                if ($end === null)
                    $admin->add_data('TRL_NO_END', true, $begin);
                else
                {
                    $time->reset();
                    $time->parse_datetime($end);
                    $admin->add_data($time->get_datetime(), false, $end);
                }

                $admin->add_data($sort);

                if ($rights_sort)
                {
                	if ($sort == $min && $sort == $max)
                    {
                        $admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
                        $admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
                    }
                    elseif ($sort == $min)
                    {
                        $admin->add_option('sort_down_true.gif', 'TRL_ALT_SORT_DOWN', $this->unibox->identify_alias('articles_sort', array('direction' => 'down')));
                        $admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
                    }
                    elseif ($sort == $max)
                    {
                        $admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
                        $admin->add_option('sort_up_true.gif', 'TRL_ALT_SORT_UP', $this->unibox->identify_alias('articles_sort', array('direction' => 'up')));
                    }
                    else
                    {
                        $admin->add_option('sort_down_true.gif', 'TRL_ALT_SORT_DOWN', $this->unibox->identify_alias('articles_sort', array('direction' => 'down')));
                        $admin->add_option('sort_up_true.gif', 'TRL_ALT_SORT_UP', $this->unibox->identify_alias('articles_sort', array('direction' => 'up')));
                    }
                }
                else
                {
                    $admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
                    $admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
                }

                if ($rights_lang)
                    $admin->add_option('content_edit_true.gif', 'TRL_ALT_ADMINISTRATE_CONTENT', $this->unibox->identify_alias('articles_lang_administrate'));
                else
                    $admin->add_option('content_edit_false.gif', 'TRL_ALT_ADMINISTRATE_CONTENT_FORBIDDEN');

                if ($rights_edit)
                    $admin->add_option('container_edit_true.gif', 'TRL_ALT_EDIT_BASE_DATA', $this->unibox->identify_alias('articles_edit'));
                else
                    $admin->add_option('container_edit_false.gif', 'TRL_ALT_EDIT_BASE_DATA_FORBIDDEN');

                if ($rights_delete)
                    $admin->add_option('container_delete_true.gif', 'TRL_ALT_DELETE', $this->unibox->identify_alias('articles_delete'));
                else
                    $admin->add_option('container_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');

                $admin->end_dataset();
            }
            $admin->add_multi_option('sort_down_true.gif', 'TRL_ALT_MULTI_SORT_DOWN', $this->unibox->identify_alias('articles_sort', array('direction' => 'down')));
            $admin->add_multi_option('sort_up_true.gif', 'TRL_ALT_MULTI_SORT_UP', $this->unibox->identify_alias('articles_sort', array('direction' => 'up')));
            $admin->add_multi_option('content_edit_true.gif', 'TRL_ALT_MULTI_ADMINISTRATE_CONTENT', $this->unibox->identify_alias('articles_lang_administrate'));
            $admin->add_multi_option('container_edit_true.gif', 'TRL_ALT_MULTI_EDIT_BASE_DATA', $this->unibox->identify_alias('articles_edit'));
            $admin->add_multi_option('container_delete_true.gif', 'TRL_ALT_MULTI_DELETE', $this->unibox->identify_alias('articles_delete'));
            $admin->set_multi_descr('TRL_ARTICLES');
        }
        $admin->show($this->unibox->identify_alias('articles_administrate'));
    }

    /**
    * lang_administrate()
    *
    * content administration - show/process preselect
    */
    public function lang_administrate()
    {
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        $validator->reset();
        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          articles_container_id
	                        FROM
	                          data_articles_container
	                        WHERE
	                          category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_lang_edit')).')';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('articles_container_id'));
                if (!$validator->validate('STACK', 'articles_container_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
			return 1;
        else
            $stack->switch_to_administration(false);
    }

    /**
    * lang_administrate()
    *
    * content administration - show content
    */
    public function lang_administrate_show_content()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $sql_string  = 'SELECT
                          a.lang_ident,
                          b.string_value,
						  c.article_editorial_title,
						  c.article_editorial_message,
						  c.article_live_title,
						  c.article_live_message
                        FROM
                          sys_languages AS a
                            INNER JOIN sys_translations AS b
                              ON b.string_ident = a.si_lang_descr
							LEFT JOIN data_articles AS c
							  ON
							  (
							  c.lang_ident = a.lang_ident
							  AND
							  c.articles_container_id = '.$dataset->articles_container_id.'
							  )
                        WHERE
                          a.lang_active = 1
                          AND
                          b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                        ORDER BY
                          b.string_value';
        $result = $this->unibox->db->query($sql_string, 'failed to select containers');

        $pagebrowser = ub_pagebrowser::get_instance('articles_lang_administrate');
        $pagebrowser->process($sql_string, 25);

        if ($result->num_rows() == 1 && !$this->unibox->config->system->articles_workflow)
        {
            $stack_lang = ub_stack::get_instance('articles_lang_edit');
            $stack_lang->clear();
            $stack_lang->set_administration('articles_lang_administrate');
            list($lang_ident, $lang_descr) = $result->fetch_row();
            $stack_lang->push(array('articles_container_id' => $dataset->articles_container_id, 'lang_ident' => $lang_ident));
            $stack_lang->set_valid(true);
            $stack->clear();
            $this->unibox->switch_action('articles_lang_edit');
            return;
        }
        $sql_string  = 'SELECT
                          articles_container_descr,
						  category_id
                        FROM
                          data_articles_container
                        WHERE
                          articles_container_id = '.$dataset->articles_container_id;
        $result_descr = $this->unibox->db->query($sql_string, 'failed to get container descr');
        list($descr, $category_id) = $result_descr->fetch_row();
        $this->unibox->set_content_title('TRL_ADMINISTRATE_LANGUAGE_CONTENT', array($descr));

        $this->unibox->load_template('shared_administration');
        $admin = ub_administration::get_instance('articles_lang_administrate');
        $admin->add_field('TRL_LANGUAGE');

        // add static link to next dataset / base administration
        if ($stack->count() > 1)
        {
			$admin->begin_dataset(false, true);
			$admin->add_dataset_ident('stack_pop', 'true');
			$admin->add_data($this->unibox->translate('TRL_NEXT_DATASET'));
			$admin->add_option('container_up_true.gif', 'TRL_NEXT_DATASET', $this->unibox->identify_alias('articles_lang_administrate'));
			$admin->end_dataset();
        }
		$admin->begin_dataset(false, true);
		$admin->add_dataset_ident('stack_clear', 'true');
		$admin->add_data($this->unibox->translate('TRL_BACK_TO_ADMINISTRATION'));
		$admin->add_option('container_up_true.gif', 'TRL_BACK_TO_ADMINISTRATION', $this->unibox->identify_alias('articles_lang_administrate'));
		$admin->end_dataset();

		$right_preview = $this->unibox->session->has_right('articles_show', $category_id);
		$right_publish = $this->unibox->session->has_right('articles_publish', $category_id);
		$right_edit = $this->unibox->session->has_right('articles_lang_edit', $category_id);

		// show languages
        while (list($lang_ident, $lang_descr, $editorial_title, $editorial_message, $live_title, $live_message) = $result->fetch_row())
        {
            $admin->begin_dataset();

            $admin->add_dataset_ident('articles_container_id', $dataset->articles_container_id);
            $admin->add_dataset_ident('lang_ident', $lang_ident);
            $admin->set_dataset_descr($lang_descr);

			if ($live_title === null || $live_message === null)
				if ($editorial_title === null && $editorial_message === null)
					$admin->add_icon('outdated.gif', 'TRL_PUBLISHING_NO_CONTENT');
				else
					$admin->add_icon('outdated.gif', 'TRL_PUBLISHING_NOTHING_PUBLISHED_YET');
			elseif ($editorial_title !== null && $editorial_message !== null)
				$admin->add_icon('outdated.gif', 'TRL_PUBLISHING_EDITORIAL_VERSION_NOT_PUBLISHED');

            $admin->add_data($lang_descr);

			if ($right_preview)
				if ($editorial_title !== null && $editorial_message !== null)
            		$admin->add_option('preview_true.gif', 'TRL_ALT_PREVIEW', 'JavaScript:;', 'window.open(\'articles_show/article_id/'.$dataset->articles_container_id.'/lang_ident/'.$lang_ident.'/editorial_version\')');
            	else
            		$admin->add_option('preview_false.gif', 'TRL_ALT_PREVIEW_NO_EDITORIAL_VERSION');
            else
            	$admin->add_option('preview_false.gif', 'TRL_ALT_PREVIEW_FORBIDDEN');

			if ($right_publish)
				if ($editorial_title !== null && $editorial_message !== null)
            		$admin->add_option('assign_true.gif', 'TRL_ALT_PUBLISH', $this->unibox->identify_alias('articles_publish'));
            	else
            		$admin->add_option('assign_false.gif', 'TRL_ALT_PUBLISH_NO_EDITORIAL_VERSION');
            else
            	$admin->add_option('assign_false.gif', 'TRL_ALT_PUBLISH_FORBIDDEN');

			if ($right_edit)
            	$admin->add_option('content_edit_true.gif', 'TRL_ALT_EDIT', $this->unibox->identify_alias('articles_lang_edit'));
            else
            	$admin->add_option('content_edit_true.gif', 'TRL_ALT_EDIT_FORBIDDEN');
            $admin->end_dataset();
        }
        $admin->add_multi_option('assign_true.gif', 'TRL_MULTI_PUBLISH_UCASE', $this->unibox->identify_alias('articles_publish'));
        $admin->add_multi_option('content_edit_true.gif', 'TRL_MULTI_EDIT_UCASE', $this->unibox->identify_alias('articles_lang_edit'));
        $admin->set_multi_descr('TRL_LANGUAGES');
        $admin->show($this->unibox->identify_alias('articles_lang_administrate'));
    }

    /**
    * add()
    *
    * add dataset
    */
    public function add()
    {
        $validator = ub_validator::get_instance();
        if (!$validator->form_validate('articles_add'))
            return 0;

        $time = new ub_time(TIME_TYPE_USER, TIME_TYPE_DB);
        $time->now();
        // process begin date if set
        if (!empty($this->unibox->session->env->form->articles_add->data->articles_container_show_begin_date))
        {
            $time->parse_date($this->unibox->session->env->form->articles_add->data->articles_container_show_begin_date);
            // process begin time if set
            if (!empty($this->unibox->session->env->form->articles_add->data->articles_container_show_begin_time))
                $time->parse_time($this->unibox->session->env->form->articles_add->data->articles_container_show_begin_time);
        }            
        $begin = '\''.$time->get_datetime().'\'';

        // process end date if set
        if (!empty($this->unibox->session->env->form->articles_add->data->articles_container_show_end_date))
        {
            $time->reset();
            $time->parse_date($this->unibox->session->env->form->articles_add->data->articles_container_show_end_date);
            // process end time if set
            if (!empty($this->unibox->session->env->form->articles_add->data->articles_container_show_end_time))
                $time->parse_time($this->unibox->session->env->form->articles_add->data->articles_container_show_end_time);
            $end = '\''.$time->get_datetime().'\'';
        }            
        else
            $end = 'null';

        // check if end is later then begin
        if ($end != 'null' && $time->compare_datetime($begin, $end) != 1)
        {
            $validator->set_form_failed('articles_add', 'articles_container_show_begin_date', 'TRL_ERR_SHOW_BEGIN_LATER_THEN_END');
            $validator->set_form_failed('articles_add', 'articles_container_show_begin_time');
            $validator->set_form_failed('articles_add', 'articles_container_show_end_date');
            $validator->set_form_failed('articles_add', 'articles_container_show_end_time');
            return 0;
        }

        $this->unibox->session->env->form->articles_add->data->show_begin = $begin;
        $this->unibox->session->env->form->articles_add->data->show_end = $end;
        return 1;
    }

    /**
    * add_form()
    *
    * add dataset - show form
    */
    public function add_form()
    {
        // load template and add form information
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'articles_add');
        $form = ub_form_creator::get_instance();
        $form->begin_form('articles_add', $this->unibox->identify_alias('articles_add'));
    }

    /**
    * add_process()
    *
    * add dataset - save
    */
    public function add_process()
    {
        $sql_string  = 'SELECT
                          MAX(articles_container_sort)
                        FROM
                          data_articles_container
                        WHERE
                          category_id = '.$this->unibox->session->env->form->articles_add->data->category_id;
        $result = $this->unibox->db->query($sql_string, 'failed to select highest sort');
        if ($result->num_rows() == 1)
            list($sort) = $result->fetch_row();
        else
            $sort = 0;
        $sort++;

        // insert container
        $sql_string  = 'INSERT INTO
                          data_articles_container
                        SET
                          articles_container_sort = '.$sort.',
                          articles_container_descr = \''.$this->unibox->session->env->form->articles_add->data->articles_container_descr.'\',
                          category_id = '.$this->unibox->session->env->form->articles_add->data->category_id.',
                          articles_container_show_begin = '.$this->unibox->session->env->form->articles_add->data->show_begin.',
                          articles_container_show_end = '.$this->unibox->session->env->form->articles_add->data->show_end;
        $this->unibox->db->query($sql_string, 'failed to insert article container');
        if ($this->unibox->db->affected_rows() == 1)
        {
            $id = $this->unibox->db->last_insert_id();
            // save current category for following insertions
            $this->unibox->session->var->register('articles_category_id', $this->unibox->session->env->form->articles_add->data->category_id);
            
            // switch administration category
            $preselect = ub_preselect::get_instance('articles_administrate');
            $preselect->set_value('category_id', $this->unibox->session->env->form->articles_add->data->category_id);

            $this->unibox->log(LOG_ALTER, 'container add', $this->unibox->db->last_insert_id());

            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_ADD_SUCCESSFUL');
            $msg->add_newline(2);
            $msg->add_link($this->unibox->identify_alias('articles_lang_administrate').'/articles_container_id/'.$id, 'TRL_ADMINISTRATE_LANGUAGE_CONTENT', array($this->unibox->session->env->form->articles_add->data->articles_container_descr));
            $msg->add_newline(2);
            $msg->add_link($this->unibox->identify_alias('articles_add'), 'TRL_INSERT_ANOTHER_DATASET');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ADD_FAILED');
        }
        $msg->display();
        $this->unibox->switch_alias($this->unibox->identify_alias('articles_administrate'), true);
    }

    /**
    * edit()
    *
    * edit dataset
    */
    public function edit()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              articles_container_id
                            FROM
                              data_articles_container
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_edit')).')';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('articles_container_id'));
                if (!$validator->validate('STACK', 'articles_container_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
        {
            if (!$validator->form_validate('articles_edit'))
                return 0;
    
            $time = new ub_time(TIME_TYPE_USER, TIME_TYPE_DB);
            $time->now();
            // process begin date if set
            if (!empty($this->unibox->session->env->form->articles_edit->data->articles_container_show_begin_date))
            {
                $time->parse_date($this->unibox->session->env->form->articles_edit->data->articles_container_show_begin_date);
                // process begin time if set
                if (!empty($this->unibox->session->env->form->articles_edit->data->articles_container_show_begin_time))
                    $time->parse_time($this->unibox->session->env->form->articles_edit->data->articles_container_show_begin_time);
            }            
            $begin = '\''.$time->get_datetime().'\'';
    
            // process end date if set
            if (!empty($this->unibox->session->env->form->articles_edit->data->articles_container_show_end_date))
            {
                $time->reset();
                $time->parse_date($this->unibox->session->env->form->articles_edit->data->articles_container_show_end_date);
                // process end time if set
                if (!empty($this->unibox->session->env->form->articles_edit->data->articles_container_show_end_time))
                    $time->parse_time($this->unibox->session->env->form->articles_edit->data->articles_container_show_end_time);
                $end = '\''.$time->get_datetime().'\'';
            }            
            else
                $end = 'null';
    
            // check if end is later then begin
            if ($end != 'null' && $time->compare_datetime($begin, $end) != 1)
            {
                $validator->set_form_failed('articles_edit', 'articles_container_show_begin_date', 'TRL_ERR_SHOW_BEGIN_LATER_THEN_END');
                $validator->set_form_failed('articles_edit', 'articles_container_show_begin_time');
                $validator->set_form_failed('articles_edit', 'articles_container_show_end_date');
                $validator->set_form_failed('articles_edit', 'articles_container_show_end_time');
                return 0;
            }

            $this->unibox->session->env->form->articles_edit->data->show_begin = $begin;
            $this->unibox->session->env->form->articles_edit->data->show_end = $end;

            return 1;
        }
        else
            $stack->switch_to_administration();
    }

    /**
    * edit_refill()
    *
    * edit dataset - refill and show form
    */
    public function edit_refill()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        // refill the form
        $sql_string  = 'SELECT
                          category_id,
                          articles_container_descr,
                          articles_container_show_begin,
                          articles_container_show_end
                        FROM
                          data_articles_container
                        WHERE
                          articles_container_id = '.$dataset->articles_container_id;
        $result = $this->unibox->db->query($sql_string, 'failed to select data');
        if ($result->num_rows() == 1)
        {
            $data = $result->fetch_row(FETCHMODE_ASSOC);
            $time = new ub_time(TIME_TYPE_DB, TIME_TYPE_USER);
            
            // process begin
            $time->parse_datetime($data['articles_container_show_begin']);
            unset($data['articles_container_show_begin']);
            $data['articles_container_show_begin_date'] = $time->get_date();
            $data['articles_container_show_begin_time'] = $time->get_time();

            // process end
            if ($data['articles_container_show_end'] !== null)
            {
                $time->reset();
                $time->parse_datetime($data['articles_container_show_end']);
                unset($data['articles_container_show_end']);
                $data['articles_container_show_end_date'] = $time->get_date();
                $data['articles_container_show_end_time'] = $time->get_time();
            }
            else
            {
                $data['articles_container_show_end_date'] = '';
                $data['articles_container_show_end_time'] = '';
            }
        }
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'articles_edit');
        $form->begin_form('articles_edit', $this->unibox->identify_alias('articles_edit'));
        
        $form->set_values_array($data, array(), true);
        $form->set_destructor('ub_stack', 'discard_top');

        // set content title
        $this->unibox->set_content_title('TRL_ARTICLES_EDIT', array($data['articles_container_descr']));
    }

    /**
    * edit_process()
    *
    * edit dataset - save
    */
    public function edit_process()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        // update container
        $sql_string  = 'UPDATE
                          data_articles_container
                        SET
                          articles_container_descr = \''.$this->unibox->session->env->form->articles_edit->data->articles_container_descr.'\',
                          category_id = '.$this->unibox->session->env->form->articles_edit->data->category_id.',
                          articles_container_show_begin = '.$this->unibox->session->env->form->articles_edit->data->show_begin.',
                          articles_container_show_end = '.$this->unibox->session->env->form->articles_edit->data->show_end.'
                        WHERE
                          articles_container_id = '.$dataset->articles_container_id;
        $this->unibox->db->query($sql_string, 'failed to update articles container');
        if ($this->unibox->db->affected_rows() == 1)
        {
            $this->unibox->log(LOG_ALTER, 'container edit', $dataset->articles_container_id);
            
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
        ub_form_creator::reset('articles_edit');
    }

    /**
    * delete()
    *
    * delete dataset
    */
    public function delete()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              articles_container_id
                            FROM
                              data_articles_container
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_delete')).')';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('articles_container_id'));
                if (!$validator->validate('STACK', 'articles_container_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('articles_delete');
        else
            $stack->switch_to_administration();
    }

    /**
    * delete_confirm()
    *
    * delete dataset - show confirmation
    */
    public function delete_confirm()
    {
        $stack = ub_stack::get_instance();
        
        // determine if the user is allowed to delete articles of the passed category
        $sql_string  = 'SELECT
                          articles_container_descr
                        FROM
                          data_articles_container
                        WHERE
                          articles_container_id IN ('.implode(', ', $stack->get_stack('articles_container_id')).')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_ARTICLE_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($descr) = $result->fetch_row())
                $msg->add_listentry($descr, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('articles_delete', $this->unibox->identify_alias('articles_delete'));
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', $this->unibox->identify_alias('articles_delete'));
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
    public function delete_process()
    {
        $stack = ub_stack::get_instance();
        $datasets = implode(', ', $stack->get_stack('articles_container_id'));

        $sql_string  = 'SELECT DISTINCT
                          category_id
                        FROM
                          data_articles_container
                        WHERE
                          articles_container_id IN ('.$datasets.')';
        $result = $this->unibox->db->query($sql_string, 'failed to delete container');
        if ($result->num_rows() != 1)
        {
            $msg = new ub_message(MSG_ERROR, false);
            $msg->add_text('TRL_DELETE_FAILED');
            $msg->display();
            return;
        }
        $category_ids = array();
        list($category_id) = $result->fetch_row();

        // delete all articles within the passed container
        // delete the container itself
        $sql_string  = 'DELETE FROM
                          data_articles_container
                        WHERE
                          articles_container_id IN ('.$datasets.')';
        $this->unibox->db->query($sql_string, 'failed to delete container');
        if ($this->unibox->db->affected_rows() > 0)
        {
            foreach ($stack->get_stack('articles_container_id') as $dataset)
                $this->unibox->log(LOG_ALTER, 'container delete', $dataset);
            $msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_DELETE_SUCCESSFUL');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR, false);
            $msg->add_text('TRL_DELETE_FAILED');
        }
        $msg->display();

        // resort
        $this->unibox->session->var->register('articles_sort_category_id', $category_id);
        $this->resort();

        ub_form_creator::reset('articles_delete');
    }

    /**
    * lang_edit()
    *
    * edit dataset content
    */
    public function lang_edit()
    {
        $stack = ub_stack::get_instance();
        $validator = ub_validator::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              articles_container_id
                            FROM
                              data_articles_container
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_lang_edit')).')';
    
            $sql_string_language = 'SELECT
                                      lang_ident
                                    FROM
                                      sys_languages
                                    WHERE
                                      lang_active = 1';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('articles_container_id', 'lang_ident'));
                if (!$validator->validate('STACK', 'articles_container_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string) || !$validator->validate('STACK', 'lang_ident', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string_language))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('articles_lang_edit');
        else
            $stack->switch_to_administration();
    }

    /**
    * lang_edit_form()
    *
    * edit dataset content - show/refill form
    */
    public function lang_edit_form()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'articles_lang_edit');

        $form = ub_form_creator::get_instance();
        $form->begin_form('articles_lang_edit', $this->unibox->identify_alias('articles_lang_edit'));
        $sql_string  = 'SELECT
                          b.article_live_title,
                          b.article_live_message,
						  b.article_editorial_title AS article_title,
                          b.article_editorial_message AS article_message,
                          a.articles_container_descr AS descr,
                          d.string_value AS language
                        FROM
                          data_articles_container AS a
                            LEFT JOIN data_articles AS b
							  ON
							  (
                              b.articles_container_id = a.articles_container_id
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
							  a.articles_container_id = '.$dataset->articles_container_id.'';
		$result = $this->unibox->db->query($sql_string, 'failed to select content');
		if ($result->num_rows() == 1)
		{
			$row = $result->fetch_row(FETCHMODE_ASSOC);
			if (!$this->unibox->config->system->articles_workflow || $row['article_title'] === null)
				$row['article_title'] = $row['article_live_title'];
			if (!$this->unibox->config->system->articles_workflow || $row['article_message'] === null)
				$row['article_message'] = $row['article_live_message'];
			
			$form->set_values_array($row, array(), true);
		}
        
        $form->set_destructor('ub_stack', 'discard_top');
        
        // set page title
        $this->unibox->set_content_title('TRL_LANGUAGE_EDIT', array($row['descr'], $row['language']));
    }

    /**
    * lang_edit_process()
    *
    * edit dataset content - save
    */
    public function lang_edit_process()
    {
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();

        $ubc = new ub_ubc();
        //$content = $ubc->html2xml($this->unibox->session->env->form->articles_lang_edit->data->article_message);
        //$content = $this->unibox->db->cleanup($content->get_tree());
        $content = $this->unibox->db->cleanup($ubc->html2ubc($this->unibox->session->env->form->articles_lang_edit->data->article_message));

		// decide where to write
		if ($this->unibox->config->system->articles_workflow)
		{
			$sql_string  = 'SELECT
							  article_live_title,
							  article_live_message
							FROM
							  data_articles
							WHERE
							  articles_container_id = '.$dataset->articles_container_id.'
							  AND
							  lang_ident = \''.$dataset->lang_ident.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to select current live version');
			if ($result->num_rows() == 1)
			{
				list($live_title, $live_message) = $result->fetch_row();
				$live_title = '\''.$this->unibox->db->cleanup($live_title).'\'';
				$live_message = '\''.$this->unibox->db->cleanup($live_message).'\'';
			}
			else
				$live_title = $live_message = 'NULL';

	        $sql_string  = 'REPLACE INTO
	                          data_articles
	                        SET
	                          articles_container_id = '.$dataset->articles_container_id.',
	                          lang_ident = \''.$dataset->lang_ident.'\',
	                          user_id = '.$this->unibox->session->user_id.',
	                          article_editorial_title = \''.$this->unibox->session->env->form->articles_lang_edit->data->article_title.'\',
	                          article_editorial_message = \''.$content.'\',
							  article_live_title = '.$live_title.',
							  article_live_message = '.$live_message;
		}
		else
	        $sql_string  = 'REPLACE INTO
	                          data_articles
	                        SET
	                          articles_container_id = '.$dataset->articles_container_id.',
	                          lang_ident = \''.$dataset->lang_ident.'\',
	                          user_id = '.$this->unibox->session->user_id.',
	                          article_live_title = \''.$this->unibox->session->env->form->articles_lang_edit->data->article_title.'\',
	                          article_live_message = \''.$content.'\'';

        $result = $this->unibox->db->query($sql_string, 'failed to update content for passed article');
        if ($this->unibox->db->affected_rows() > 0)
        {
            $this->unibox->log(LOG_ALTER, 'content add/edit', $dataset->articles_container_id, $dataset->lang_ident);
            
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
        ub_form_creator::reset('articles_lang_edit');
    }

	public function publish()
	{
		$validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        $validator->reset();
        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          articles_container_id
	                        FROM
	                          data_articles_container
	                        WHERE
	                          category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_publish')).')';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('articles_container_id', 'lang_ident'));
                if (!$validator->validate('STACK', 'articles_container_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->reset();
            do
            {
            	$dataset = $stack->current();
            	$sql_string  = 'SELECT
								  article_editorial_title,
								  article_editorial_message
								FROM
								  data_articles
								WHERE
								  articles_container_id = '.$dataset['articles_container_id'].'
								  AND
								  lang_ident = \''.$this->unibox->db->cleanup($dataset['lang_ident']).'\'';
				$result = $this->unibox->db->query($sql_string, 'failed to verify that an editorial version exists');
				if ($result->num_rows() == 1)
				{
					list($title, $message) = $result->fetch_row();
					if ($title === null || $message === null)
                    	$stack->element_invalid();
				}
				else
					$stack->element_invalid();
            }
            while ($stack->next() !== false);
            $stack->validate();
        }

        if ($stack->is_valid())
             return (int)$validator->form_validate('articles_publish');
        else
        {
            $this->unibox->error('TRL_ERR_NO_COMPLETE_EDITORIAL_VERSION_FOR_HIGHLIGHTED_DATASETS');
            $stack->switch_to_administration();
        }
	}
	
	public function publish_confirm()
	{
        $stack = ub_stack::get_instance();

        // build data array
        foreach ($stack->get_stack() as $dataset)
        	$datasets[$dataset['articles_container_id']][] = $dataset['lang_ident'];

        $sql_string  = 'SELECT
						  articles_container_id,
                          articles_container_descr
                        FROM
                          data_articles_container
                        WHERE
                          articles_container_id IN ('.implode(', ', array_keys($datasets)).')';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_ARTICLE_PUBLISH_CONFIRM', true);
            $msg->add_newline(2);
            while (list($article_id, $article_descr) = $result->fetch_row())
            {
	            $sql_string  = 'SELECT
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
								  a.lang_ident IN (\''.implode('\', \'', $datasets[$article_id]).'\')';
				$result_lang = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
				if ($result_lang->num_rows() > 0)
				{
					$msg->add_text($article_descr);
		            $msg->begin_list();
		            while (list($language) = $result_lang->fetch_row())
		                $msg->add_listentry($language, array(), false);
		            $msg->end_list();
		            $msg->add_newline(2);
				}
            }
            $msg->begin_form('articles_publish', $this->unibox->identify_alias('articles_publish'));
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', $this->unibox->identify_alias('articles_publish'));
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'discard_all');
            $msg->end_form();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ERR_INVALID_DATA_PASSED');
        }
	}
	
	public function publish_process()
	{
        $stack = ub_stack::get_instance();

        // build data array
        foreach ($stack->get_stack() as $dataset)
        	$datasets[$dataset['articles_container_id']][] = $dataset['lang_ident'];

		$sql_string  = 'UPDATE
						  data_articles
						SET
						  article_live_title = article_editorial_title,
						  article_live_message = article_editorial_message,
						  article_editorial_title = NULL,
						  article_editorial_message = NULL
						WHERE ';

		foreach ($datasets as $article_id => $languages)
			if (!empty($languages))
				$sql_where[] = '(articles_container_id = '.$article_id.' AND (lang_ident = \''.implode('\' OR lang_ident = \'', $languages).'\'))';
		$sql_string .= implode(' OR ', $sql_where);

        $result = $this->unibox->db->query($sql_string, 'failed to publish datasets');
        if ($this->unibox->db->affected_rows() != 0)
        {
            $msg = new ub_message(MSG_SUCCESS, false);
            $msg->add_text('TRL_PUBLISH_SUCCESS');
        }
        else
        {
            $msg = new ub_message(MSG_ERROR, false);
            $msg->add_text('TRL_PUBLISH_FAILED');
        }
        $msg->display();

        ub_form_creator::reset('articles_publish');
	}

    /**
    * sort()
    *
    * sort dataset(s) - build stack
    */
    public function sort()
    {
        // try if the user is allowed to edit articles for the passed category
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              articles_container_id
                            FROM
                              data_articles_container
                            WHERE
                              category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_sort')).')';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('articles_container_id'));
                if (!$validator->validate('STACK', 'articles_container_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $sql_string =  'SELECT
                              articles_container_id,
                              articles_container_sort,
                              category_id
                            FROM
                              data_articles_container
                            WHERE
                              articles_container_id IN ('.implode(', ', $stack->get_stack('articles_container_id')).')';
            $result = $this->unibox->db->query($sql_string, 'failed to select element sort');
            while ($row = $result->fetch_row())
            {
                list($id, $sort, $category_id) = $row;
                $sort_array[$id] = $sort;
            }

            $sql_string =  'SELECT
                              MAX(articles_container_sort) AS maximum
                            FROM
                              data_articles_container
                            WHERE
                              category_id = '.$category_id;
            $result = $this->unibox->db->query($sql_string, 'failed to select max sort');
            list($max) = $result->fetch_row();

            $stack->reset();
            do
            {
                $current = $stack->current();
                if ($this->unibox->session->env->alias->get['direction'] == 'up' && $sort_array[$current['articles_container_id']] == 1)
                {
                    $stack->element_invalid();
                    unset($sort_array[$current['articles_container_id']]);
                }
                elseif ($this->unibox->session->env->alias->get['direction'] == 'down' && $sort_array[$current['articles_container_id']] == $max)
                {
                    $stack->element_invalid();
                    unset($sort_array[$current['articles_container_id']]);
                }
            }
            while ($stack->next() !== false);
            $stack->validate();

            $this->unibox->session->var->register('articles_sort_category_id', $category_id, true);
            $this->unibox->session->var->register('articles_sort_array', $sort_array, true);
        }

        if ($stack->is_valid())
            return 1;
        else
        {
            $this->unibox->error('TRL_ERR_INVALID_SORT_FOR_HIGHLIGHTED_DATASETS');
            $stack->switch_to_administration();
        }
    }

    /**
    * sort_process()
    *
    * sort dataset(s) - execute
    */
    public function sort_process()
    {
        ub_stack::discard_all();

        if ($this->unibox->session->env->alias->get['direction'] == 'down')
        {
            $order = 'ASC';
            $operator = '>';
            arsort($this->unibox->session->var->articles_sort_array);
        }
        else
        {
            $order = 'DESC';
            $operator = '<';
            asort($this->unibox->session->var->articles_sort_array);
        }

        foreach ($this->unibox->session->var->articles_sort_array as $id => $sort)
        {
            $sql_string  = 'SELECT
                              articles_container_id,
                              articles_container_sort
                            FROM
                              data_articles_container
                            WHERE
                              articles_container_sort '.$operator.$sort.'
                              AND
                              category_id = '.$this->unibox->session->var->articles_sort_category_id.'
                            ORDER BY
                              articles_container_sort '.$order.'
                            LIMIT 0, 1';
            $result = $this->unibox->db->query($sql_string, 'failed to select sort of next dataset');
            list($db_id, $db_sort) = $result->fetch_row();

            $sql_string  = 'UPDATE
                              data_articles_container
                            SET
                              articles_container_sort = '.$sort.'
                            WHERE
                              articles_container_id = '.$db_id;
            $this->unibox->db->query($sql_string, 'failed to update dataset: '.$db_id);

            $sql_string  = 'UPDATE
                              data_articles_container
                            SET
                              articles_container_sort = '.$db_sort.'
                            WHERE
                              articles_container_id = '.$id;
            $this->unibox->db->query($sql_string, 'failed to update dataset: '.$id);
        }

        // resort
        $this->resort();

        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_SORT_SUCCESS');
        $msg->display();
    }

    protected function resort()
    {
        $sql_string  = 'SELECT
                          articles_container_sort,
						  articles_container_descr
                        FROM
                          data_articles_container
                        WHERE
                          category_id = '.$this->unibox->session->var->articles_sort_category_id;
        $this->unibox->resort_column($sql_string);
    }

    /**
    * form_articles()
    *
    * form to process dataset containers
    */
    public function form_articles()
    {
        $form = ub_form_creator::get_instance();
        $form->begin_fieldset('TRL_GENERAL');
        $form->text('articles_container_descr', 'TRL_LANGUAGE_INDEPENDANT_DESCR', '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_INRANGE, array(0, 255));
        $form->begin_select('category_id', 'TRL_CATEGORY', true);
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
                          a.module_ident = \'articles\'
                          AND
                          a.category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories($this->unibox->session->env->system->action_ident)).')
                        ORDER BY
                          b.string_value ASC';
        if (isset($this->unibox->session->var->articles_category_id))
            $form->add_option_sql($sql_string, $this->unibox->session->var->articles_category_id);
        else
            $form->add_option_sql($sql_string);
        $form->end_select();
        $form->set_condition(CHECK_NOTEMPTY);
        $form->end_fieldset();
        $form->begin_fieldset('TRL_ANNOUNCEMENT_PERIOD');
        $form->text('articles_container_show_begin_date', 'TRL_SHOW_BEGIN_DATE', '', 40);
        $form->set_type(TYPE_DATE);
        $form->text('articles_container_show_begin_time', 'TRL_SHOW_BEGIN_TIME', '', 40);
        $form->set_type(TYPE_TIME);
        $form->text('articles_container_show_end_date', 'TRL_SHOW_END_DATE', '', 40);
        $form->set_type(TYPE_DATE);
        $form->text('articles_container_show_end_time', 'TRL_SHOW_END_TIME', '', 40);
        $form->set_type(TYPE_TIME);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        if ($this->unibox->session->env->system->action_ident == 'articles_edit')
            $form->cancel('TRL_CANCEL_UCASE', $this->unibox->identify_alias('articles_edit'));
        else
            $form->cancel('TRL_CANCEL_UCASE', $this->unibox->identify_alias('articles_administrate'));
        $form->end_buttonset();
        $form->end_form();
    }

    /**
    * form_lang()
    *
    * form to process dataset content
    */
    public function form_lang()
    {
        $form = ub_form_creator::get_instance();
        $form->begin_fieldset('TRL_GENERAL');
        $form->text('article_title', 'TRL_TITLE', '', 40);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_INRANGE, array(0, 255));
        $form->end_fieldset();
        $form->editor('article_message', 'TRL_CONTENT', '', 500);
        $form->set_condition(CHECK_INRANGE, array(0, 4294967295));
        $form->set_condition(CHECK_NOTEMPTY);
        $form->begin_buttonset();
        $form->submit('TRL_SAVE_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->identify_alias('articles_lang_edit'));
        $form->end_buttonset();
        $form->end_form();
    }
}

?>