<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
*/

class ub_categories
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
        return ub_categories::version;
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
            self::$instance = new ub_categories;
        return self::$instance;
    } // end get_instance()

    /**
    * prints welcome message
    */
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_CATEGORIES_WELCOME_TEXT');
        $msg->display();
        return 0;
    } // end welcome()

	public function edit()
	{
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          a.category_id
	                        FROM sys_categories AS a
							  INNER JOIN sys_translations AS b
								ON b.string_ident = a.si_category_name
							WHERE
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'';							

            $stack->reset();
            do
            {
                $stack->keep_keys(array('category_id'));
                if (!$validator->validate('STACK', 'category_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);
            $stack->validate();
        }

        if ($stack->is_valid())
        {
        	$dataset = $stack->top();

	    	// set module ident
	    	$sql_string  = 'SELECT
							  module_ident
							FROM sys_categories
							WHERE
							  category_id = \''.$dataset->category_id.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to get module ident for selected category');
			list($module_ident) = $result->fetch_row();

			$this->unibox->session->env->form->categories_module_selector = new stdClass();
			$this->unibox->session->env->form->categories_module_selector->data = new stdClass();
			$this->unibox->session->env->form->categories_module_selector->data->module_ident = $module_ident;
		    return $validator->form_validate('categories_details_selector');
        }
        else
            $stack->switch_to_administration();
	}

	public function add_module_selector()
	{
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'categories_module_selector');
        $form = ub_form_creator::get_instance();
        $form->begin_form('categories_module_selector', 'categories_add');
	}

	public function module_selector_form()
	{
		// get preselect from category administration
		$preselect = ub_preselect::get_instance('categories_administrate');
		
		$form = ub_form_creator::get_instance();
        $form->register_with_dialog();
		$form->begin_fieldset('TRL_SELECT_MODULE');
		$sql_string  = 'SELECT DISTINCT
						  b.module_ident,
						  c.string_value
						FROM sys_sex AS a
						  INNER JOIN sys_modules AS b
							ON b.module_ident = a.module_ident_from
						  INNER JOIN sys_translations AS c
							ON
							(
							c.string_ident = b.si_module_name
							AND
							c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						WHERE
						  a.module_ident_to = \'categories\'
						ORDER BY
						  c.string_value ASC';
		$form->begin_select('module_ident', 'TRL_MODULE', true);
		$form->add_option_sql($sql_string, $preselect->get_value('module_ident'));
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
        $form->cancel('TRL_CANCEL_UCASE', 'categories_administrate');
        $form->end_buttonset();
		$form->end_form();
        return 0;
	}
	
	public function add_details_selector()
	{
		// load config for selected module
		$this->unibox->config->load_config($this->unibox->session->env->form->categories_module_selector->data->module_ident);

		// reset form elements if form exists
		if (isset($this->unibox->session->env->form->categories_details_selector->spec))
			$this->unibox->session->env->form->categories_details_selector->spec->elements = new stdClass;
			
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_SELECT_DETAILS', true);
        $this->unibox->xml->add_value('form_name', 'categories_details_selector');
        $form = ub_form_creator::get_instance();
        $form->begin_form('categories_details_selector', 'categories_add');
	}

	public function edit_details_selector()
	{
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();
		
		// load config for selected module
		$this->unibox->config->load_config($this->unibox->session->env->form->categories_module_selector->data->module_ident);

		// reset form elements if form exists
		if (isset($this->unibox->session->env->form->categories_details_selector->spec))
			$this->unibox->session->env->form->categories_details_selector->spec->elements = new stdClass;

        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'categories_details_selector');
        $form = ub_form_creator::get_instance();
        $form->begin_form('categories_details_selector', 'categories_edit');
		$sql_string =  'SELECT
						  category_parent_id,
						  si_category_name AS category_name
						FROM sys_categories
						WHERE
						  category_id = \''.$dataset->category_id.'\'';
		$form->set_values($sql_string, array('category_name'));
		
		// set content title
		$sql_string =  'SELECT
						  b.string_value
						FROM
						  sys_categories AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						WHERE
						  a.category_id = \''.$dataset->category_id.'\'
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to retrieve category name');
		if ($result->num_rows() == 1)
		{
			list($category_name) = $result->fetch_row();
			$this->unibox->set_content_title('TRL_CATEGORY_EDIT', array($category_name));
		}

		$details = array();
        $sql_string =  'SELECT
						  detail_ident,
						  detail_value
						FROM sys_category_details
						WHERE
						  category_id = \''.$dataset->category_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to retrieve detail idents');
		while (list($detail_ident, $detail_value) = $result->fetch_row())
			$details[$detail_ident] = $detail_value;
		$form->set_values_array($details);
        $form->set_destructor('ub_stack', 'discard_top');
	}
	
	public function details_selector_form()
	{
		if ($this->unibox->session->env->system->action_ident == 'categories_edit')
			$stack = ub_stack::get_instance();

		$form = ub_form_creator::get_instance();
        $form->register_with_dialog();
        $form->text_multilanguage('category_name', 'TRL_NAME', 30);
        $form->set_condition_multilanguage(CHECK_MULTILANG);
        $form->set_condition_multilanguage(CHECK_NOTEMPTY);
        $sql_string =  'SELECT
						  b.string_value
						FROM sys_categories AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						WHERE
						  a.module_ident = \''.$this->unibox->session->env->form->categories_module_selector->data->module_ident.'\'
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'';
		if (isset($stack) && !$stack->is_empty())
		{
			$dataset = $stack->top();
			$sql_string .= ' AND a.category_id != \''.$dataset->category_id.'\'';
		}
		$form->set_condition_multilanguage(CHECK_NOTINSET_SQL, $sql_string, 'TRL_CATEGORY_NAME_ALREADY_EXISTS');
        $form->begin_fieldset('TRL_SELECT_PARENT_CATEGORY');
        $form->begin_select('category_parent_id', 'TRL_PARENT_CATEGORY', false);
        $form->add_option('0', 'TRL_INSERT_CATEGORY_AT_TOP_LEVEL');
		$sql_string =  'SELECT
						  a.category_id,
						  b.string_value,
						  IF (d.string_value IS NULL, 
							(
							SELECT
							  string_value
							FROM sys_translations
							WHERE
							  string_ident = \'TRL_PRESENT_AT_TOP_LEVEL\'
							  AND
							  lang_ident = \''.$this->unibox->session->lang_ident.'\'
							), d.string_value)
						FROM sys_categories AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						  LEFT JOIN sys_categories AS c
							ON c.category_id = a.category_parent_id
						  LEFT JOIN sys_translations AS d
							ON d.string_ident = c.si_category_name
						WHERE
						  a.module_ident = \''.$this->unibox->session->env->form->categories_module_selector->data->module_ident.'\'
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						  AND
						  (
						  d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						  OR
						  d.lang_ident IS NULL
						  ) ';
		if (isset($stack) && !$stack->is_empty())
		{
			$dataset = $stack->top();
			$sql_string .= 'AND a.category_id NOT IN (\''.$dataset->category_id.'\', \''.implode('\', \'', $this->get_subcategories($dataset->category_id)).'\') ';
		}
		$sql_string .= 'ORDER BY
						  d.string_value ASC, b.string_value ASC';
		$form->add_option_sql($sql_string);
        $form->end_select();
        $form->end_fieldset();
		$sql_string  = 'SELECT DISTINCT
						  entity_ident,
						  entity_type_definition
						FROM sys_sex
						WHERE
						  module_ident_to = \'categories\'
						  AND
						  module_ident_from = \''.$this->unibox->session->env->form->categories_module_selector->data->module_ident.'\'
						  AND
						  entity_ident != \'\'
						ORDER BY
						  entity_detail_int ASC';
		$result = $this->unibox->db->query($sql_string);
		if ($result->num_rows() > 0)
		{
			$form->begin_fieldset('TRL_DETAILS');
			while (list($entity_ident, $entity_type_definition) = $result->fetch_row())
				$form->import_form_spec($entity_type_definition);
			$form->end_fieldset();
		}

        $form->begin_buttonset();
        if ($this->unibox->session->env->system->action_ident == 'categories_edit')
        {
        	$form->submit('TRL_SAVE_UCASE', 'next');
	        $form->cancel('TRL_CANCEL_UCASE', 'categories_edit');
        }
        else
        {
        	$form->submit('TRL_NEXT_UCASE', 'next');
	        $form->cancel('TRL_CANCEL_UCASE', 'categories_administrate');
        }
        $form->end_buttonset();
		$form->end_form();
        return 0;
	}

	public function add_process()
	{
        $this->unibox->db->begin_transaction();
        
        if ($this->unibox->session->env->form->categories_details_selector->data->category_parent_id == 0)
            $this->unibox->session->env->form->categories_details_selector->data->category_parent_id = 'null';
        
		$sql_string =  'INSERT INTO sys_categories SET
						  module_ident = \''.$this->unibox->session->env->form->categories_module_selector->data->module_ident.'\',
						  category_parent_id = '.$this->unibox->session->env->form->categories_details_selector->data->category_parent_id;
		if (!$this->unibox->db->query($sql_string, 'failed to insert category') || $this->unibox->db->affected_rows() != 1)
        {
            ub_form_creator::reset('categories_details_selector');
            $this->unibox->db->rollback('TRL_ADD_FAILED');
            $this->unibox->switch_alias('categories_administrate', true);
            return;
        }

		$category_id = $this->unibox->db->last_insert_id();

		$si_category_name = 'TRL_CAT_'.strtoupper($this->unibox->session->env->form->categories_module_selector->data->module_ident).'_'.$category_id;
		if (!$this->unibox->insert_translation($si_category_name, $this->unibox->session->env->form->categories_details_selector->data->category_name))
        {
            ub_form_creator::reset('categories_details_selector');
            $this->unibox->db->rollback('TRL_ADD_FAILED');
            $this->unibox->switch_alias('categories_administrate', true);
            return;
        }
			
		$sql_string =  'UPDATE sys_categories SET
						  si_category_name = \''.$si_category_name.'\'
						WHERE
						  category_id = \''.$category_id.'\'';
		if (!$this->unibox->db->query($sql_string, 'failed to update category string identifiers'))
        {
            ub_form_creator::reset('categories_details_selector');
            $this->unibox->db->rollback('TRL_ADD_FAILED');
            $this->unibox->switch_alias('categories_administrate', true);
            return;
        }
		
		$sql_string  = 'SELECT DISTINCT
						  entity_ident,
						  entity_type
						FROM sys_sex
						WHERE
						  module_ident_to = \'categories\'
						  AND
						  module_ident_from = \''.$this->unibox->session->env->form->categories_module_selector->data->module_ident.'\'
						ORDER BY
						  entity_detail_int ASC';
		if (!($result = $this->unibox->db->query($sql_string)))
        {
            ub_form_creator::reset('categories_details_selector');
            $this->unibox->db->rollback('TRL_ADD_FAILED');
            $this->unibox->switch_alias('categories_administrate', true);
            return;
        }

		$details_to_insert = false;
		$sql_string  = 'INSERT INTO sys_category_details VALUES ';
		while (list($entity_ident, $entity_type) = $result->fetch_row())
		{
			if (isset($this->unibox->session->env->form->categories_details_selector->data->$entity_ident))
			{
				if ($entity_type == 'multilang' && is_array($this->unibox->session->env->form->categories_details_selector->data->$entity_ident))
				{
					$string_ident = 'TRL_CATEGORY_DETAIL_'.strtoupper($entity_ident).'_'.$category_id;
					if (!$this->unibox->insert_translation($string_ident, $this->unibox->session->env->form->categories_details_selector->data->$entity_ident))
                    {
                        ub_form_creator::reset('categories_details_selector');
                        $this->unibox->db-rollback('TRL_ADD_FAILED');
                        $this->unibox->switch_alias('categories_administrate', true);
                        return;
                    }
					$this->unibox->session->env->form->categories_details_selector->data->$entity_ident = $string_ident;
				}
				$sql_string .= '(\''.$category_id.'\', \''.$entity_ident.'\', \''.$this->unibox->session->env->form->categories_details_selector->data->$entity_ident.'\'), ';
				$details_to_insert = true;
			}
		}
		
		if ($details_to_insert)
		{
			$sql_string = substr($sql_string, 0, -2);
			if (!$this->unibox->db->query($sql_string, 'failed to insert category details'))
            {
                ub_form_creator::reset('categories_details_selector');
                $this->unibox->db->rollback('TRL_ADD_FAILED');
                $this->unibox->switch_alias('categories_administrate', true);
                return;
            }
		}

        $this->unibox->db->commit();

		// set values for preselects
        $preselect = ub_preselect::get_instance('categories_administrate');
        $preselect->set_value('module_ident', $this->unibox->session->env->form->categories_module_selector->data->module_ident);
		$preselect = ub_preselect::get_instance('preselect_users_category_rights');
		$preselect->set_value('module_ident', $this->unibox->session->env->form->categories_module_selector->data->module_ident);
		$preselect = ub_preselect::get_instance('preselect_groups_category_rights');
		$preselect->set_value('module_ident', $this->unibox->session->env->form->categories_module_selector->data->module_ident);

        ub_form_creator::reset('categories_details_selector');

        $msg = new ub_message(MSG_SUCCESS);
        $msg->add_text('TRL_ADD_SUCCESSFUL');
        $msg->add_newline(2);
        $msg->add_link('usermanager_users_administrate', 'TRL_ASSIGN_USER_RIGHTS_FOR_THIS_CATEGORY');
        $msg->add_newline();
        $msg->add_link('usermanager_groups_administrate', 'TRL_ASSIGN_GROUP_RIGHTS_FOR_THIS_CATEGORY');
        $msg->add_newline();
        $msg->add_link('categories_add', 'TRL_INSERT_ANOTHER_DATASET');
        $msg->display();
		$this->unibox->switch_alias('categories_administrate', true);
	}
	
	public function edit_process()
	{
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();
        $this->unibox->db->begin_transaction();

        if ($this->unibox->session->env->form->categories_details_selector->data->category_parent_id == 0)
            $this->unibox->session->env->form->categories_details_selector->data->category_parent_id = 'null';

		$sql_string  = 'UPDATE
                          sys_categories
                        SET
						  category_parent_id = '.$this->unibox->session->env->form->categories_details_selector->data->category_parent_id.'
						WHERE
						  category_id = '.$dataset->category_id;
		if (!$this->unibox->db->query($sql_string, 'failed to update category'))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED');
            ub_form_creator::reset('categories_details_selector');
            return;
        }

		// update name
		$si_category_name = 'TRL_CAT_'.strtoupper($this->unibox->session->env->form->categories_module_selector->data->module_ident).'_'.$dataset->category_id;
		if (!$this->unibox->insert_translation($si_category_name, $this->unibox->session->env->form->categories_details_selector->data->category_name))
        {
            $this->unibox->db->rollback('TRL_EDIT_FAILED');
            ub_form_creator::reset('categories_details_selector');
            return;
        }

		// drop all category details ...
		$sql_string =  'DELETE FROM
                          sys_category_details
						WHERE
						  category_id = '.$dataset->category_id;
		if (!$this->unibox->db->query($sql_string))
        {
            ub_form_creator::reset('categories_details_selector');
            $this->unibox->db->rollback('TRL_EDIT_FAILED');
            return;
        }

		// ... and reinsert them
		$sql_string  = 'SELECT DISTINCT
						  entity_ident,
						  entity_type
						FROM sys_sex
						WHERE
						  module_ident_from = \''.$this->unibox->session->env->form->categories_module_selector->data->module_ident.'\'
						  AND
						  module_ident_to = \'categories\'
						ORDER BY
						  entity_detail_int ASC';
		if (!($result = $this->unibox->db->query($sql_string, 'failed to select category detail entities')))
        {
            ub_form_creator::reset('categories_details_selector');
            $this->unibox->db->rollback('TRL_EDIT_FAILED');
            return;
        }

		$details_to_insert = false;
		$sql_string  = 'INSERT INTO sys_category_details VALUES ';
		while (list($entity_ident, $entity_type) = $result->fetch_row())
		{
			if (isset($this->unibox->session->env->form->categories_details_selector->data->$entity_ident))
			{
				if ($entity_type == 'multilang' && is_array($this->unibox->session->env->form->categories_details_selector->data->$entity_ident))
				{
					$string_ident = 'TRL_CATEGORY_DETAIL_'.strtoupper($entity_ident).'_'.$dataset->category_id;
					if (!$this->unibox->insert_translation($string_ident, $this->unibox->session->env->form->categories_details_selector->data->$entity_ident))
                    {
                        ub_form_creator::reset('categories_details_selector');
                        $this->unibox->db->rollback('TRL_EDIT_FAILED');
                        return;
                    }
					$this->unibox->session->env->form->categories_details_selector->data->$entity_ident = $string_ident;
				}
				$sql_string .= '(\''.$dataset->category_id.'\', \''.$entity_ident.'\', \''.$this->unibox->session->env->form->categories_details_selector->data->$entity_ident.'\'), ';
				$details_to_insert = true;
			}
			elseif ($entity_type == 'multilang')
				$this->unibox->delete_translation('TRL_CATEGORY_DETAIL_'.strtoupper($entity_ident).'_'.$dataset->category_id);
				
		}

		if ($details_to_insert)
		{
			$sql_string = substr($sql_string, 0, -2);
			if (!$this->unibox->db->query($sql_string, 'failed to insert category details'))
            {
                ub_form_creator::reset('categories_details_selector');
                $this->unibox->db->rollback('TRL_EDIT_FAILED');
                return;
            }
		}

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS, false);
        $msg->add_text('TRL_EDIT_SUCCESSFUL');
        $msg->display();
		
		ub_form_creator::reset('categories_details_selector');
	}
	
	public function delete()
	{
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
	        $sql_string  = 'SELECT
	                          a.category_id
	                        FROM sys_categories AS a
							  INNER JOIN sys_translations AS b
								ON b.string_ident = a.si_category_name
							WHERE
							  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'';							

            $stack->reset();
            do
            {
                $stack->keep_keys(array('category_id'));
                if (!$validator->validate('STACK', 'category_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);
            $stack->validate();
        }

        if ($stack->is_valid())
		    return $validator->form_validate('categories_delete');
        else
            $stack->switch_to_administration();
	}
	
	public function delete_confirm()
	{
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();
		
    	// get module ident
    	$sql_string  = 'SELECT
						  a.module_ident,
						  b.string_value
						FROM sys_categories AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						WHERE
						  a.category_id = \''.$dataset->category_id.'\'
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to get module ident for selected category');
		list($module_ident, $category_name) = $result->fetch_row();

		// check for contents
		$ucm = new ub_ucm($module_ident);
		$collection = $ucm->get_content_by_category($dataset->category_id);
		$has_content = ($collection->count() > 0);
		
		// check for subcategories
		$sql_string =  'SELECT		
						  category_id
						FROM sys_categories
						WHERE
						  category_parent_id = \''.$dataset->category_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to select subcategories for selected category');
		$has_subcategories = ($result->num_rows() > 0);
		
		// check if other categories are available
		$sql_string =  'SELECT
						  a.category_id,
						  b.string_value,
						  d.string_value
						FROM sys_categories AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						  LEFT JOIN sys_categories AS c
							ON c.category_id = a.category_parent_id
						  LEFT JOIN sys_translations AS d
							ON d.string_ident = c.si_category_name
						WHERE
						  a.module_ident = \''.$module_ident.'\'
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						  AND
						  (d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						  OR
						  d.lang_ident IS NULL)
						  AND
						  a.category_id NOT IN (\''.$dataset->category_id.'\', \''.implode('\', \'', $this->get_subcategories($dataset->category_id)).'\')
						ORDER BY
						  d.string_value ASC, b.string_value ASC';
		$result = $this->unibox->db->query($sql_string, 'failed to get categories');
		$categories_available = ($result->num_rows() > 0);

		if ($categories_available && ($has_content || $has_subcategories))
		{
			$msg = new ub_message(MSG_WARNING);
			$msg->begin_form('categories_delete', 'categories_delete');
			
			if ($has_content)
			{
				$msg->form->plaintext('TRL_CATEGORY_HAS_CONTENTS', true, array($category_name));
				$msg->form->begin_radio('content', 'TRL_CONTENTS');
				$msg->form->add_option('delete', 'TRL_DELETE_ALL_CONTENS', true);
				$msg->form->add_option('move', 'TRL_MOVE_TO_CATEGORY');
		        $msg->form->begin_select('content_category_id', 'TRL_TARGET_CATEGORY', false);
				$sql_string =  'SELECT
								  a.category_id,
								  b.string_value,
								  IF (d.string_value IS NULL, 
									(
									SELECT
									  string_value
									FROM sys_translations
									WHERE
									  string_ident = \'TRL_PRESENT_AT_TOP_LEVEL\'
									  AND
									  lang_ident = \''.$this->unibox->session->lang_ident.'\'
									), d.string_value)
								FROM sys_categories AS a
								  INNER JOIN sys_translations AS b
									ON
									(
									b.string_ident = a.si_category_name
									AND
									b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
									)
								  LEFT JOIN sys_categories AS c
									ON c.category_id = a.category_parent_id
								  LEFT JOIN sys_translations AS d
									ON
									(
									d.string_ident = c.si_category_name
									AND
									d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
									)
								WHERE
								  a.module_ident = \''.$module_ident.'\'
								  AND
								  a.category_id NOT IN (\''.$dataset->category_id.'\', \''.implode('\', \'', $this->get_subcategories($dataset->category_id)).'\')
								ORDER BY
								  d.string_value ASC, b.string_value ASC';
				$form->add_option_sql($sql_string);
		        $msg->form->end_select();
		        $msg->form->end_radio();
	        	$msg->form->set_condition(CHECK_NOTEMPTY);
			}
			
			if ($has_subcategories)
			{
				if ($has_content)
					$msg->form->newline();

				$msg->form->plaintext('TRL_CATEGORY_HAS_SUBCATEGORIES', true, array($category_name));
				$msg->form->begin_radio('subcategories', 'TRL_SUBCATEGORIES');
				$msg->form->add_option('delete', 'TRL_DELETE_ALL_SUBCATEGORIES', true);
				$msg->form->add_option('move', 'TRL_MOVE_TO_CATEGORY');
		        $msg->form->begin_select('subcategories_category_id', 'TRL_TARGET_CATEGORY', false);
		        $msg->form->add_option('0', 'TRL_MOVE_TO_TOP_LEVEL');
				$sql_string =  'SELECT
								  a.category_id,
								  b.string_value,
								  d.string_value
								FROM sys_categories AS a
								  INNER JOIN sys_translations AS b
									ON
									(
									b.string_ident = a.si_category_name
									AND
									b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
									)
								  LEFT JOIN sys_categories AS c
									ON c.category_id = a.category_parent_id
								  LEFT JOIN sys_translations AS d
									ON
									(
									d.string_ident = c.si_category_name
									AND
									d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
									)
								WHERE
								  a.module_ident = \''.$module_ident.'\'
								  AND
								  a.category_id NOT IN (\''.$dataset->category_id.'\', \''.implode('\', \'', $this->get_subcategories($dataset->category_id)).'\')
								ORDER BY
								  d.string_value ASC, b.string_value ASC';
				$form->add_option_sql($sql_string);
		        $msg->form->end_select();
		        $msg->form->end_radio();
		        $msg->form->set_condition(CHECK_NOTEMPTY);
			}
			
			$msg->form->begin_buttonset();
			$msg->form->submit('TRL_DELETE_UCASE');
			$msg->form->cancel('TRL_CANCEL_UCASE', 'categories_delete');
			$msg->form->end_buttonset();
			$msg->form->set_destructor('ub_stack', 'discard_top');
			$msg->end_form();
		}
		else
		{
			$msg = new ub_message(MSG_QUESTION);
			$msg->add_text('TRL_CATEGORY_DELETE_CONFIRM', array($category_name));
			if ($has_content || $has_subcategories)
			{
				$msg->add_newline();
				$msg->add_text('TRL_CONTENTS_AND_SUBCATEGORIES_WILL_BE_DELETED');
			}
			$msg->add_newline(2);
			$msg->begin_form('categories_delete', 'categories_delete');
			$msg->form->begin_buttonset(false);
			$msg->form->submit('TRL_YES_UCASE');
			$msg->form->cancel('TRL_NO_UCASE', 'categories_delete');
			$msg->form->end_buttonset();
			$msg->form->set_destructor('ub_stack', 'discard_top');
			$msg->end_form();
		}
		
		$msg->display();
		return 0;
	}
	
	public function delete_process()
	{
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();
		
		$contents_deleted = 0;
		$contents_moved = 0;
		$subcategories_deleted = 0;
		$subcategories_moved = 0;
		
    	// get module ident
    	$sql_string  = 'SELECT
						  a.module_ident,
						  a.si_category_name,
						  b.string_value
						FROM sys_categories AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						WHERE
						  a.category_id = \''.$dataset->category_id.'\'
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to get module ident for selected category');
		list($module_ident, $si_category_name, $category_name) = $result->fetch_row();

		// check if we have contents to process
		if (isset($this->unibox->session->env->form->categories_delete) && isset($this->unibox->session->env->form->categories_delete->data->content))
		{
			$ucm = new ub_ucm($module_ident);
			$collection = $ucm->get_content_by_category($dataset->category_id);
			if ($this->unibox->session->env->form->categories_delete->data->content == 'delete')
				$contents_deleted += $collection->delete();
			else
			{
				$collection->set('category', $this->unibox->session->env->form->categories_delete->data->content_category_id);
				$contents_moved += $collection->update();
			}
		}
		
		// check if we have subcategories to process
		if (isset($this->unibox->session->env->form->message) && isset($this->unibox->session->env->form->message->data->subcategories))
		{
			$subcategories = $this->get_subcategories($dataset->category_id);
			if ($this->unibox->session->env->form->message->data->subcategories == 'delete')
			{
				// delete all subcategory contents
				$ucm = new ub_ucm($module_ident);
				foreach ($subcategories as $category_id)
				{
					$collection = $ucm->get_content_by_category($category_id);
					$contents_deleted += $collection->delete();
				}
				
				// delete category name translation
				$sql_string =  'SELECT
								  si_category_name
								FROM sys_categories
								WHERE
								  category_id IN (\''.implode('\', \'', $subcategories).'\')';
				$result = $this->unibox->db->query($sql_string, 'failed to select category names');
				while (list($si_subcategory_name) = $result->fetch_row())
					$this->unibox->delete_translation($si_subcategory_name, $module_ident);

				// delete translations of multilang details
				$sql_string  = 'SELECT DISTINCT
								  entity_ident,
								  entity_type
								FROM sys_sex
								WHERE
								  module_ident_from = \''.$module_ident.'\'
								  AND
								  module_ident_to = \'categories\'
								ORDER BY
								  entity_detail_int ASC';
				$result = $this->unibox->db->query($sql_string, 'failed to select category detail entities');
				while (list($entity_ident, $entity_type) = $result->fetch_row())
				{
					if ($entity_type == 'multilang')
					{
						foreach ($subcategories as $category_id)
						{
							$sql_string =  'SELECT
											  detail_value
											FROM '.TABLE_SYS_CATEGORY_DETAILS.'
											WHERE
											  category_id = \''.$category_id.'\'
											  AND
											  detail_value = \''.$entity_ident.'\'';
							$result_detail = $this->unibox->db->query($sql_string, 'failed to select detail value');
							if ($result_detail->num_rows() == 1)
							{
								list($detail_value) = $result_detail->fetch_row();
								$this->unibox->delete_translation($detail_value, $module_ident);
							}
						}
					}
				}

				// delete categories
				$sql_string =  'DELETE FROM sys_categories
								WHERE
								  category_id IN (\''.implode('\', \'', $subcategories).'\')';
				$this->unibox->db->query($sql_string, 'failed to delete subcategories');
				$subcategories_deleted = $this->unibox->db->affected_rows();
				
				// delete category details
				$sql_string =  'DELETE FROM sys_category_details
								WHERE
								  category_id IN (\''.implode('\', \'', $subcategories).'\')';
				$this->unibox->db->query($sql_string, 'failed to delete subcategory details');
			}
			else
			{
				$sql_string =  'UPDATE sys_categories SET
								  category_parent_id = \''.$this->unibox->session->env->form->message->data->subcategories_category_id.'\'
								WHERE
								  category_id IN (\''.implode('\', \'', $subcategories).'\')';
				$this->unibox->db->query($sql_string, 'failed to move subcategories');
				$subcategories_moved = $this->unibox->db->affected_rows();
			}
		}

		// delete category
		$sql_string  = 'DELETE FROM
						  sys_categories
						WHERE
						  category_id = \''.$dataset->category_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to delete category');
		if ($this->unibox->db->affected_rows() == 1)
		{
			// delete category name translation
			$this->unibox->delete_translation($si_category_name, $module_ident);
			
			// delete translations of multilang details
			$sql_string  = 'SELECT DISTINCT
							  entity_ident,
							  entity_type
							FROM sys_sex
							WHERE
							  module_ident_from = \''.$module_ident.'\'
							  AND
							  module_ident_to = \'categories\'
							ORDER BY
							  entity_detail_int ASC';
			$result = $this->unibox->db->query($sql_string, 'failed to select category detail entities');
			while (list($entity_ident, $entity_type) = $result->fetch_row())
			{
				if ($entity_type == 'multilang')
				{
					$sql_string =  'SELECT
									  detail_value
									FROM sys_category_details
									WHERE
									  category_id = \''.$dataset->category_id.'\'
									  AND
									  detail_value = \''.$entity_ident.'\'';
					$result_detail = $this->unibox->db->query($sql_string, 'failed to select detail value');
					if ($result_detail->num_rows() == 1)
					{
						list($detail_value) = $result_detail->fetch_row();
						$this->unibox->delete_translation($detail_value, $module_ident);
					}
				}
			}

			// delete category details
			$sql_string =  'DELETE FROM sys_category_details
							WHERE
							  category_id = \''.$dataset->category_id.'\'';
			$this->unibox->db->query($sql_string, 'failed to delete category details');
			
			$msg = new ub_message(MSG_SUCCESS, false);
			$msg->add_text('TRL_CATEGORY_DELETE_SUCCESSFUL', array($category_name));
			if ($contents_deleted > 0 || $contents_moved > 0 || $subcategories_deleted > 0 || $subcategories_moved > 0)
			{
				$msg->add_newline(2);
				$msg->add_text('TRL_FOLLOWING_OPERATIONS_WERE_EXECUTED');
				$msg->begin_list();
				if ($contents_deleted > 0)
					$msg->add_listentry('TRL_CONTENTS_DELETED', array($contents_deleted));
				if ($contents_moved > 0)
					$msg->add_listentry('TRL_CONTENTS_MOVED', array($contents_moved));
				if ($subcategories_deleted > 0)
					$msg->add_listentry('TRL_SUBCATEGORIES_DELETED', array($subcategories_deleted));
				if ($subcategories_moved > 0)
					$msg->add_listentry('TRL_SUBCATEGORIES_MOVED', array($subcategories_moved));
				$msg->end_list();
				$msg->add_newline();
			}
			$msg->display();
		}
		else
		{
			$msg = new ub_message(MSG_ERROR, false);
			$msg->add_text('TRL_CATEGORY_DELETE_FAILED');
			if ($contents_deleted > 0 || $contents_moved > 0 || $subcategories_deleted > 0 || $subcategories_moved > 0)
			{
				$msg->add_newline(2);
				$msg->add_text('TRL_FOLLOWING_OPERATIONS_WERE_EXECUTED');
				$msg->begin_list();
				if ($contents_deleted > 0)
					$msg->add_listentry('TRL_CONTENTS_DELETED', array($contents_deleted));
				if ($contents_moved > 0)
					$msg->add_listentry('TRL_CONTENTS_MOVED', array($contents_moved));
				if ($subcategories_deleted > 0)
					$msg->add_listentry('TRL_SUBCATEGORIES_DELETED', array($subcategories_deleted));
				if ($subcategories_moved > 0)
					$msg->add_listentry('TRL_SUBCATEGORIES_MOVED', array($subcategories_moved));
				$msg->end_list();
				$msg->add_newline();
			}
			$msg->display();
		}

		ub_form_creator::reset('categories_delete');
	}
	
	public function administrate()
	{
        $this->unibox->load_template('shared_administration');
        $this->unibox->xml->add_value('preselect_ident', 'categories_administrate');
        
        // begin form
        $form = ub_form_creator::get_instance();
        $form->begin_form('categories_administrate', 'categories_administrate');

        $preselect = ub_preselect::get_instance('categories_administrate');
        $preselect->add_field('module_ident', 'a.module_ident', true);
        $preselect->check();

        $form->begin_fieldset('TRL_PRESELECT');
   		$sql_string  = 'SELECT DISTINCT
						  b.module_ident,
						  c.string_value
						FROM sys_sex AS a
						  INNER JOIN sys_modules AS b
							ON b.module_ident = a.module_ident_from
						  INNER JOIN sys_translations AS c
							ON
							(
							c.string_ident = b.si_module_name
							AND
							c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
                          INNER JOIN sys_categories AS d
                            ON d.module_ident = b.module_ident
						WHERE
						  a.module_ident_to = \'categories\'
                          AND
                          b.module_active = 1
						ORDER BY
						  c.string_value ASC';
		$form->begin_select('module_ident', 'TRL_MODULE', true);
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

    public function administrate_show_content()
    {
        // get preselect
        $preselect = ub_preselect::get_instance('categories_administrate');

        $admin = ub_administration::get_instance('categories_administrate');
		$admin->add_field('TRL_NAME');
		$admin->add_field('TRL_CONTENT');

        // select data
        $sql_string  = 'SELECT DISTINCT
                          a.category_id,
						  b.string_value
                        FROM sys_categories AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						  LEFT JOIN sys_categories AS c
							ON c.category_parent_id = a.category_id
                        WHERE
                          '.$preselect->get_string().'
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                          AND
                          a.category_parent_id IS NULL';
        $result = $this->unibox->db->query($sql_string, 'failed to select categories');

        $pagebrowser = ub_pagebrowser::get_instance('categories_administrate');
        $pagebrowser->process($sql_string, 25);

		// create universal content management object for selected module
		$ucm = new ub_ucm($this->unibox->session->env->form->categories_administrate->data->module_ident);
		if (!$ucm)
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_NO_UCM_ENTITY_FOUND_FOR_MODULE', array($this->unibox->session->env->form->categories_administrate->data->module_ident));
			$msg->display();
			return;
		}

        if ($result->num_rows() > 0)
        {
			$rights_edit = $this->unibox->session->has_right('categories_edit');
			$rights_delete = $this->unibox->session->has_right('categories_delete');

            while (list($category_id, $category_name) = $result->fetch_row())
            {
                $admin->begin_dataset();
                $admin->add_dataset_ident('category_id', $category_id);
                $admin->set_dataset_descr($category_name);

    			// get number of contents in current category
                $ucm->reset();
    			$collection = $ucm->get_content_by_category($category_id);
                $admin->add_data($category_name);
    			$admin->add_data($collection->get_count_string());

                if ($rights_edit)
                    $admin->add_option('container_edit_true.gif', 'TRL_ALT_EDIT', 'categories_edit');
                else
                    $admin->add_option('container_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');
    
                if ($rights_delete)
                    $admin->add_option('container_delete_true.gif', 'TRL_ALT_DELETE', 'categories_delete');
                else
                    $admin->add_option('container_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');

				// add children
				$this->administrate_show_subcategories($category_id, $ucm);

                $admin->end_dataset();
            }
            $admin->add_multi_option('container_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'categories_edit');
            $admin->add_multi_option('container_delete_true.gif', 'TRL_ALT_MULTI_DELETE', 'categories_delete');
            $admin->set_multi_descr('TRL_CATEGORIES');
        }
        $admin->show('categories_administrate');
    }

	protected function administrate_show_subcategories($parent_id, $ucm)
	{
		// get preselect
        $preselect = ub_preselect::get_instance('categories_administrate');
        $admin = ub_administration::get_instance('categories_administrate');

        // select data
        $sql_string  = 'SELECT DISTINCT
                          a.category_id,
						  b.string_value
                        FROM sys_categories AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_category_name
						  LEFT JOIN sys_categories AS c
							ON c.category_parent_id = a.category_id
                        WHERE
                          '.$preselect->get_string().'
						  AND
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                          AND
                          a.category_parent_id = '.$parent_id;
        $result = $this->unibox->db->query($sql_string, 'failed to select categories');
        if ($result->num_rows() > 0)
        {
			$rights_edit = $this->unibox->session->has_right('categories_edit');
			$rights_delete = $this->unibox->session->has_right('categories_delete');

            while (list($category_id, $category_name) = $result->fetch_row())
            {
                $admin->begin_dataset();
                $admin->add_dataset_ident('category_id', $category_id);
                $admin->set_dataset_descr($category_name);

    			// get number of contents in current category
                $ucm->reset();
    			$collection = $ucm->get_content_by_category($category_id);
                $admin->add_data($category_name);
    			$admin->add_data($collection->get_count_string());

                if ($rights_edit)
                    $admin->add_option('container_edit_true.gif', 'TRL_ALT_EDIT', 'categories_edit');
                else
                    $admin->add_option('container_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');
    
                if ($rights_delete)
                    $admin->add_option('container_delete_true.gif', 'TRL_ALT_DELETE', 'categories_delete');
                else
                    $admin->add_option('container_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');

				// add children
				$this->administrate_show_subcategories($category_id, $ucm);

                $admin->end_dataset();
            }
        }
	}

    protected function get_subcategories($category_id)
    {
    	$category_ids = array();
    	$sql_string =  'SELECT
						  category_id
						FROM sys_categories
						WHERE
						  category_parent_id = \''.$category_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to select subcategories');
		while (list($category_id) = $result->fetch_row())
		{
			$category_ids[] = $category_id;
			$category_ids = array_merge($category_ids, $this->get_subcategories($category_id));
		}
		
		return $category_ids;
    }
}

?>
