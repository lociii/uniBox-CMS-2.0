<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_menumanager
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
        return ub_menumanager::version;
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
            self::$instance = new ub_menumanager;
        return self::$instance;
    } // end get_instance()

    /**
    * prints welcome message
    */
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_MENUMANAGER_WELCOME_TEXT');
        $msg->display();
        return 0;
    } // end welcome()

	public function menu_add()
	{
		$validator = ub_validator::get_instance();
		return $validator->form_validate('menumanager_menu_add');
	}
	
	public function menu_add_form()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('title', 'TRL_MENU_ADD', true);
		$this->unibox->xml->add_value('form_name', 'menumanager_menu_add');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_menu_add', 'menumanager_menu_add');
		$form->text_multilanguage('menu_name', 'TRL_NAME', 30);
        $form->set_condition_multilanguage(CHECK_MULTILANG);
        $form->set_condition_multilanguage(CHECK_NOTEMPTY);
		$form->textarea_multilanguage('menu_descr', 'TRL_DESCRIPTION', 40, 5);
        $form->begin_fieldset('TRL_GENERAL');
        $form->checkbox('menu_active', 'TRL_MENU_ACTIVE', true);
        $form->end_fieldset();
		$form->begin_buttonset();
		$form->submit('TRL_ADD_UCASE');
		$form->cancel('TRL_CANCEL_UCASE', 'menumanager_menu_administrate');
		$form->end_buttonset();
		$form->end_form();
		return 0;
	}
	
	public function menu_add_process()
	{
		try
		{
	        $this->unibox->db->begin_transaction();
	
			$sql_string =  'INSERT INTO
							  sys_menu
							SET
							  menu_active = \''.$this->unibox->session->env->form->menumanager_menu_add->data->menu_active.'\'';
			$this->unibox->db->query($sql_string, 'failed to insert menu');
			if ($this->unibox->db->affected_rows() != 1)
	        	throw new ub_exception_transaction();
	
			$menu_id = $this->unibox->db->last_insert_id();
	
			// insert menu name translation
			$si_menu_name = 'TRL_MENU_NAME_'.$menu_id;
			if (!$this->unibox->insert_translation($si_menu_name, $this->unibox->session->env->form->menumanager_menu_add->data->menu_name, 'menu'))
	        	throw new ub_exception_transaction();
	
			// check if menu description is set
			if (!ub_functions::array_empty($this->unibox->session->env->form->menumanager_menu_add->data->menu_descr))
			{
				$si_menu_descr = 'TRL_MENU_DESCR_'.$menu_id;
				if (!$this->unibox->insert_translation($si_menu_descr, $this->unibox->session->env->form->menumanager_menu_add->data->menu_descr, 'menu'))
					throw new ub_exception_transaction();
			}
			else
			{
				$si_menu_descr = null;
				$this->unibox->delete_translation('TRL_MENU_DESCR_'.$menu_id, 'menu');
			}
			
			// update menu with menu string identifiers
			$sql_string =  'UPDATE sys_menu SET
							  si_menu_name = \''.$si_menu_name.'\',
							  si_menu_descr = '.(($si_menu_descr === null) ? 'NULL' : '\''.$si_menu_descr.'\'').'
							WHERE
							  menu_id = \''.$menu_id.'\'';
			if (!$this->unibox->db->query($sql_string, 'failed to update menu string identifiers'))
				throw new ub_exception_transaction();

	        $this->unibox->db->commit();
	        ub_form_creator::reset('menumanager_menu_add');
	
	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_ADD_SUCCESSFUL');
	        $msg->add_newline(2);
	        $msg->add_link('menumanager_menu_add', 'TRL_INSERT_ANOTHER_DATASET');
	        $msg->display();
	        
			$this->unibox->switch_alias('menumanager_menu_administrate', true);
		}
		catch (ub_exception_transaction $exception)
		{
            $exception->process('TRL_ADD_FAILED');
            ub_form_creator::reset('menumanager_menu_add');
            $this->unibox->switch_alias('menumanager_menu_administrate', true);
		}
	}
	
	public function menu_edit()
	{
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
			$sql_string =  'SELECT
							  a.menu_id
							FROM sys_menu AS a
							  LEFT JOIN sys_modules AS b
								ON b.module_actionbar_menu_id = a.menu_id';
	
			// check if user is allowed to administrate actiobar menus
			if (!$this->unibox->session->has_right('menumanager_actionbar_administrate'))
				$sql_string .= ' WHERE b.module_actionbar_menu_id IS NULL ';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('menu_id'));
                if (!$validator->validate('STACK', 'menu_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('menumanager_menu_edit');
        else
            $stack->switch_to_administration();
	}
	
	public function menu_edit_form()
	{
        $stack = ub_stack::get_instance();
        $dataset = $stack->top();
        $form = ub_form_creator::get_instance();

        $this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('title', 'TRL_MENU_EDIT', true);
        $this->unibox->xml->add_value('form_name', 'menumanager_menu_edit');

        // set content title
        $sql_string =  'SELECT
						  b.string_value
						FROM sys_menu AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_menu_name
						WHERE
						  a.menu_id = \''.$dataset->menu_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to get menu name');
		list($menu_name) = $result->fetch_row();
        $this->unibox->set_content_title('TRL_MENU_EDIT', array($menu_name));

        // refill the form
		$sql_string =  'SELECT
						  si_menu_name AS menu_name,
						  si_menu_descr AS menu_descr,
						  menu_active
						FROM
                          sys_menu
						WHERE
						  menu_id = \''.$dataset->menu_id.'\'';
        $form->begin_form('menumanager_menu_edit', 'menumanager_menu_edit');
        $form->set_values($sql_string, array('menu_name', 'menu_descr'));

		$form->text_multilanguage('menu_name', 'TRL_NAME', 30);
        $form->set_condition_multilanguage(CHECK_MULTILANG);
        $form->set_condition_multilanguage(CHECK_NOTEMPTY);
		$form->textarea_multilanguage('menu_descr', 'TRL_DESCRIPTION', 40, 5);
        $form->set_condition_multilanguage(CHECK_MULTILANG);
        $form->begin_fieldset('TRL_GENERAL');
        $form->checkbox('menu_active', 'TRL_MENU_ACTIVE');
        $form->end_fieldset();
		$form->begin_buttonset();
		$form->submit('TRL_SAVE_UCASE');
		$form->cancel('TRL_CANCEL_UCASE', 'menumanager_menu_edit');
		$form->end_buttonset();
        $form->set_destructor('ub_stack', 'discard_top');
		$form->end_form();
		return 0;
	}

	public function menu_edit_process()
	{
		try
		{
	        $this->unibox->db->begin_transaction();
	        
	        $stack = ub_stack::get_instance();
	        $dataset = $stack->top();
	        
	        // for compatability to manually created menus, get string identifiers
	        $sql_string =  'SELECT
							  si_menu_name,
							  si_menu_descr
							FROM sys_menu
							WHERE
							  menu_id = \''.$dataset->menu_id.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to get menu string identifiers');
			if ($result->num_rows() != 1)
				throw new ub_exception_transaction();
				
			list($si_menu_name, $si_menu_descr) = $result->fetch_row();
						
	        // update menu name
	        if (!$this->unibox->insert_translation($si_menu_name, $this->unibox->session->env->form->menumanager_menu_edit->data->menu_name, 'menu'))
				throw new ub_exception_transaction();
	                	
	        // check if menu description is set
			if (!ub_functions::array_empty($this->unibox->session->env->form->menumanager_menu_edit->data->menu_descr))
			{
				// if no menu description string identifier exists, create generic one
				$si_menu_descr = ($si_menu_descr !== null) ? $si_menu_descr : 'TRL_MENU_DESCR_'.$dataset->menu_id;
				
				if (!$this->unibox->insert_translation($si_menu_descr, $this->unibox->session->env->form->menumanager_menu_edit->data->menu_descr, 'menu'))
					throw new ub_exception_transaction();
			}
			else
				$si_menu_descr = null;
	        
	        // update string identifiers
			$sql_string =  'UPDATE
	                          sys_menu
	                        SET
							  si_menu_descr = '.(($si_menu_descr === null) ? 'NULL' : '\''.$si_menu_descr.'\'').',
							  menu_active = \''.$this->unibox->session->env->form->menumanager_menu_edit->data->menu_active.'\'
							WHERE
							  menu_id = \''.$dataset->menu_id.'\'';
	        $result = $this->unibox->db->query($sql_string, 'failed to update menu');
	
			// delete translation if its not needed anymore
			if ($si_menu_descr === null)
				$this->unibox->delete_translation('TRL_MENU_DESCR_'.$dataset->menu_id, 'menu');
	
			// commit changes to database		
	        $this->unibox->db->commit();
	        
	        $msg = new ub_message(MSG_SUCCESS, false);
	        $msg->add_text('TRL_EDIT_SUCCESSFUL');
	        $msg->display();
	        
	        ub_form_creator::reset('menumanager_menu_edit');
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_EDIT_FAILED');
            $stack->clear();
            $this->unibox->switch_alias('menumanager_menu_administrate', true);
		}
	}
	
	public function menu_delete()
    {
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

        if (!$stack->is_valid())
        {
			$sql_string =  'SELECT
							  a.menu_id
							FROM sys_menu AS a
							  LEFT JOIN sys_modules AS b
								ON b.module_actionbar_menu_id = a.menu_id';
	
			// check if user is allowed to administrate actiobar menus
			if (!$this->unibox->session->has_right('menumanager_actionbar_administrate'))
				$sql_string .= ' WHERE b.module_actionbar_menu_id IS NULL ';

            $stack->reset();
            do
            {
                $stack->keep_keys(array('menu_id'));
                if (!$validator->validate('STACK', 'menu_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('menumanager_menu_delete');
        else
            $stack->switch_to_administration();
    }

	public function menu_delete_confirm()
	{
        $stack = ub_stack::get_instance();

		$sql_string =  'SELECT
						  b.string_value
						FROM sys_menu AS a
						  INNER JOIN sys_translations AS b
                            ON
                            (
                            b.string_ident = a.si_menu_name
                            AND
                            b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                            )
						WHERE
						  a.menu_id IN ('.implode(', ', $stack->get_stack('menu_id')).')
                        ORDER BY
                          b.string_value';
        $result = $this->unibox->db->query($sql_string, 'failed to select data to confirm delete');
        if ($result->num_rows() > 0)
        {
            $msg = new ub_message(MSG_QUESTION);
            $msg->add_text('TRL_MENU_DELETE_CONFIRM');
            $msg->add_newline(2);
            $msg->begin_list();
            while (list($menu_name) = $result->fetch_row())
                $msg->add_listentry($menu_name, array(), false);
            $msg->end_list();
            $msg->add_newline();
            $msg->begin_form('menumanager_menu_delete', 'menumanager_menu_delete');
            $msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'menumanager_menu_delete');
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
	
	public function menu_delete_process()
	{
		try
		{
	        $this->unibox->db->begin_transaction();
	        $stack = ub_stack::get_instance();
	
			// delete menus
			$sql_string =  'DELETE FROM
	                          sys_menu
	                        WHERE
							  menu_id IN ('.implode(', ', $stack->get_stack('menu_id')).')';
			$result = $this->unibox->db->query($sql_string, 'failed to delete menus');
			if ($this->unibox->db->affected_rows() == 0)
				throw new ub_exception_transaction();

			// delete translations
			foreach ($stack->get_stack('menu_id') as $menu_id)
			{
				if (!$this->unibox->delete_translation('TRL_MENU_NAME_'.$menu_id, 'menu'))
					throw new ub_exception_transaction();

				// TODO: validate if a menu descr exists and if it was correctly deleted
				$this->unibox->delete_translation('TRL_MENU_DESCR_'.$menu_id, 'menu');
			}
	
			// commit changes to database
	        $this->unibox->db->commit();
	        ub_form_creator::reset('menumanager_menu_delete');
	        
			$msg = new ub_message(MSG_SUCCESS, false);
			$msg->add_text('TRL_DELETE_SUCCESSFUL');
			$msg->display();
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_DELETE_FAILED');
            $stack->clear();
			$this->unibox->switch_alias('menumanager_menu_administrate', true);
		}
	}
	
	public function menu_administrate()
	{
        $this->unibox->load_template('shared_administration');
        
        $sql_string =  'SELECT
						  a.menu_id,
						  b.string_value,
						  c.string_value,
						  a.menu_active
						FROM sys_menu AS a
						  INNER JOIN sys_translations AS b
							ON b.string_ident = a.si_menu_name
						  LEFT JOIN sys_translations AS c
							ON c.string_ident = a.si_menu_descr
						  LEFT JOIN sys_modules AS d
							ON d.module_actionbar_menu_id = a.menu_id
						WHERE
						  b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
						  AND
						  (
							c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							OR
							c.lang_ident IS NULL
						  )';

		// check if user is allowed to administrate actiobar menus
		if (!$this->unibox->session->has_right('menumanager_actionbar_administrate'))
			$sql_string .= ' AND d.module_actionbar_menu_id IS NULL ';
			
		$sql_string .= 'ORDER BY
						  b.string_value ASC,
						  c.string_value ASC';

        $result = $this->unibox->db->query($sql_string, 'failed to get menus');

        $pagebrowser = ub_pagebrowser::get_instance('menumanager_menu_administrate');
        $pagebrowser->process($sql_string, 25);

        $admin = ub_administration::get_instance('menumanager_menu_administrate');
		$admin->add_field('TRL_NAME');
		$admin->add_field('TRL_DESCRIPTION');

		if ($result->num_rows() > 0)
		{
			$right_edit = $this->unibox->session->has_right('menumanager_menu_edit');
			$right_delete = $this->unibox->session->has_right('menumanager_menu_delete');
			$right_items_administrate = $this->unibox->session->has_right('menumanager_items_administrate');
			while (list($menu_id, $menu_name, $menu_descr, $menu_active) = $result->fetch_row())
			{
				$admin->begin_dataset();
				
				$admin->add_dataset_ident('menu_id', $menu_id);
				$admin->set_dataset_descr($menu_name);
				
				if ($menu_active)
					$admin->add_icon('../activate_true.gif', 'TRL_MENU_IS_ACTIVE', array($menu_name));
				else
					$admin->add_icon('../activate_deactivate_true.gif', 'TRL_MENU_IS_INACTIVE', array($menu_name));

				$admin->add_data($menu_name);
				$admin->add_data($menu_descr);
				
				if ($right_items_administrate)
					$admin->add_option('menu_edit_true.gif', 'TRL_ALT_EDIT_MENU_ITEMS', 'menumanager_items_administrate');
				else
					$admin->add_option('menu_edit_false.gif', 'TRL_ALT_EDIT_MENU_ITEMS_FORBIDDEN');

                if ($right_edit)
                    $admin->add_option('container_edit_true.gif', 'TRL_ALT_EDIT', 'menumanager_menu_edit');
                else
                    $admin->add_option('container_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

                if ($right_delete)
                    $admin->add_option('container_delete_true.gif', 'TRL_ALT_DELETE', 'menumanager_menu_delete');
                else
                    $admin->add_option('container_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');
					
				$admin->end_dataset();
			}
            $admin->add_multi_option('container_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'menumanager_menu_edit');
	        $admin->add_multi_option('container_delete_true.gif', 'TRL_ALT_MULTI_DELETE', 'menumanager_menu_delete');
            $admin->set_multi_descr('TRL_MENUS');
        }
        $admin->show('menumanager_menu_administrate');
    }

	#################################################################################################
	### items administrate
	#################################################################################################

	public function items_administrate()
	{
		$validator = ub_validator::get_instance();
		$sql_string =  'SELECT
						  a.menu_id
						FROM sys_menu AS a
						  LEFT JOIN sys_modules AS b
							ON b.module_actionbar_menu_id = a.menu_id';

		// check if user is allowed to administrate actiobar menus
		if (!$this->unibox->session->has_right('menumanager_actionbar_administrate'))
			$sql_string .= ' WHERE b.module_actionbar_menu_id IS NULL ';
		
		if ($validator->validate('GET', 'menu_id', TYPE_STRING, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
		{
			$this->unibox->session->var->register('menumanager_items_administrate_menu_id', $this->unibox->session->env->input->menu_id);
			return 1;
		}
		elseif (isset($this->unibox->session->var->menumanager_items_administrate_menu_id))
			return 1;
		else
			$this->unibox->display_error();
	}

    public function items_administrate_show_content()
    {
        $this->unibox->load_template('shared_administration');
		
        $sql_string  = 'SELECT
                          b.alias,
                          c.action_ident,
                          a.menu_item_id,
                          a.menu_item_parent_id,
                          a.si_menu_item_name,
                          e.string_value AS menu_item_name,
                          f.string_value AS menu_item_descr,
						  j.string_value AS action_descr,
						  a.menu_item_sort,
                          a.menu_item_hotkey,
                          a.menu_item_show_always,
                          g.name AS alias_get_name,
                          g.value AS alias_get_value,
                          i.name AS menu_get_name,
                          i.value AS menu_get_value,
                          d.module_ident,
                          d.module_active,
                          h.entity_value,
						  MIN(k.menu_item_sort) AS minimum,
						  MAX(k.menu_item_sort) AS maximum,
						  COUNT(DISTINCT l.menu_item_id) AS child_count,
						  n.string_value AS menu_name
                        FROM
                          sys_menu_items AS a
                            LEFT JOIN sys_alias AS b
                              ON b.alias = a.alias
                            LEFT JOIN sys_actions AS c
                              ON
                              (
                                c.action_ident = b.action_ident
                                OR
                                c.action_ident IS NULL
                              )
                            LEFT JOIN sys_modules AS d
                              ON d.module_ident = c.module_ident
                            LEFT JOIN sys_translations AS e
                              ON
                              (
                                e.string_ident = a.si_menu_item_name
                                AND
                                (
                                  e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                                  OR
                                  e.lang_ident IS NULL
                                )
                              )
                            LEFT JOIN sys_translations AS f
                              ON
                              (
                                f.string_ident = a.si_menu_item_descr
                                AND
                                (
                                  f.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                                  OR
                                  f.lang_ident IS NULL
                                )
                              )
                            LEFT JOIN sys_alias_get AS g
                              ON g.alias = a.alias
                            LEFT JOIN sys_sex AS h
                              ON
                              (
                              h.entity_class = c.action_ident
                              AND
                              h.entity_ident = g.name
                              AND
                              h.module_ident_from = d.module_ident
                              AND
                              h.entity_type = \'menu_rights\'
                              )
                            LEFT JOIN sys_menu_items_get AS i
                              ON i.menu_item_id = a.menu_item_id
							LEFT JOIN sys_translations AS j
    						  ON
	                          (
								j.string_ident = c.si_action_descr
                                AND
                                (
                                  j.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                                  OR
                                  j.lang_ident IS NULL
                                )
                              )
							LEFT JOIN sys_menu_items AS k
							  ON
							  (
							  IF (a.menu_item_parent_id IS NULL, k.menu_item_parent_id IS NULL, k.menu_item_parent_id = a.menu_item_parent_id)
							  AND
							  k.menu_id = a.menu_id
							  )
							LEFT JOIN sys_menu_items AS l
							  ON l.menu_item_parent_id = a.menu_item_id
							INNER JOIN sys_menu AS m
							  ON m.menu_id = a.menu_id
							INNER JOIN sys_translations AS n
							  ON
							  (
							  n.string_ident = m.si_menu_name
							  AND
							  n.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							  )
                            WHERE
                              (
                                d.module_active = 1
                                OR
                                d.module_ident IS NULL
                              )
                              AND
                              a.menu_item_parent_id IS NULL
							  AND
							  a.menu_id = '.$this->unibox->session->var->menumanager_items_administrate_menu_id.'
							GROUP BY
							  a.menu_item_parent_id,
							  a.menu_item_id';
        $admin = ub_administration_ng::get_instance('menumanager_items_administrate');
		$admin->add_field('TRL_NAME', 'e.string_value');
		$admin->add_field('TRL_ACTION', 'j.string_value');
		$admin->add_field('TRL_SORT', 'a.menu_item_sort');

		// sort initially by sort
		$admin->sort_by('a.menu_item_sort');
		$admin->add_link('menumanager_menu_administrate', 'TRL_BACK_TO_MENU_ADMINISTRATION');

		// add 'add new menu-item'-entry
		$admin->begin_dataset(false, true);
		$admin->add_dataset_ident('menu_item_id', 0);
		$admin->add_data('TRL_ADD_MENU_ITEM_ON_TOP_LEVEL', true);
		if ($this->unibox->session->has_right('menumanager_item_add'))
			$admin->add_option('menu_item_add_true.gif', 'TRL_ADD_MENU_ITEM_ON_TOP_LEVEL', 'menumanager_item_add');
		else
			$admin->add_option('menu_item_add_false.gif', 'TRL_ADD_MENU_ITEM_ON_TOP_LEVEL_FORBIDDEN');
		$admin->end_dataset();

		// initialize menu array
		$menu = array();

		$result = $admin->process_sql($sql_string);

		if ($result->num_rows() > 0)
		{
			$row = $result->fetch_row(FETCHMODE_ASSOC);
			$result->goto(0);
			$this->unibox->set_content_title('TRL_MENU_ITEMS_ADMINISTRATE', array($row['menu_name']));
	
	        if ($result->num_rows() > 0)
	            while ($row = $result->fetch_row())
	            	$this->items_administrate_print_node($row);
	
	        $admin->add_multi_option('sort_down_true.gif', 'TRL_ALT_MULTI_SORT_DOWN', 'menumanager_item_sort_down');
	        $admin->add_multi_option('sort_up_true.gif', 'TRL_ALT_MULTI_SORT_UP', 'menumanager_item_sort_up');
			$admin->add_multi_option('menu_item_edit_true.gif', 'TRL_ALT_MULTI_EDIT', 'menumanager_item_edit');
			$admin->add_multi_option('menu_item_delete_true.gif', 'TRL_ALT_MULTI_DELETE', 'menumanager_item_delete');
	        $admin->set_multi_descr('TRL_MENU_ITEMS');
		}
        $admin->show();
    }
    
    protected function items_administrate_generate_content($menu_item_parent_id = null)
    {
		// get maximum and minimum sort value for current level
        $sql_string  = 'SELECT
                          b.alias,
                          c.action_ident,
                          a.menu_item_id,
                          a.menu_item_parent_id,
                          a.si_menu_item_name,
                          e.string_value AS menu_item_name,
                          f.string_value AS menu_item_descr,
						  j.string_value AS action_descr,
						  a.menu_item_sort,
                          a.menu_item_hotkey,
                          a.menu_item_show_always,
                          g.name AS alias_get_name,
                          g.value AS alias_get_value,
                          i.name AS menu_get_name,
                          i.value AS menu_get_value,
                          d.module_ident,
                          d.module_active,
                          h.entity_value,
						  MIN(k.menu_item_sort) AS minimum,
						  MAX(k.menu_item_sort) AS maximum,
						  COUNT(DISTINCT l.menu_item_id) AS child_count
                        FROM
                          sys_menu_items AS a
                            LEFT JOIN sys_alias AS b
                              ON b.alias = a.alias
                            LEFT JOIN sys_actions AS c
                              ON
                              (
                                c.action_ident = b.action_ident
                                OR
                                c.action_ident IS NULL
                              )
                            LEFT JOIN sys_modules AS d
                              ON d.module_ident = c.module_ident
                            LEFT JOIN sys_translations AS e
                              ON
                              (
                                e.string_ident = a.si_menu_item_name
                                AND
                                (
                                  e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                                  OR
                                  e.lang_ident IS NULL
                                )
                              )
                            LEFT JOIN sys_translations AS f
                              ON
                              (
                                f.string_ident = a.si_menu_item_descr
                                AND
                                (
                                  f.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                                  OR
                                  f.lang_ident IS NULL
                                )
                              )
                            LEFT JOIN sys_alias_get AS g
                              ON g.alias = a.alias
                            LEFT JOIN sys_sex AS h
                              ON
                              (
                              h.entity_class = c.action_ident
                              AND
                              h.entity_ident = g.name
                              AND
                              h.module_ident_from = d.module_ident
                              AND
                              h.entity_type = \'menu_rights\'
                              )
                            LEFT JOIN sys_menu_items_get AS i
                              ON i.menu_item_id = a.menu_item_id
							LEFT JOIN sys_translations AS j
    						  ON
	                          (
								j.string_ident = c.si_action_descr
                                AND
                                (
                                  j.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                                  OR
                                  j.lang_ident IS NULL
                                )
                              )
							LEFT JOIN sys_menu_items AS k
							  ON
							  (
							  IF (a.menu_item_parent_id IS NULL, k.menu_item_parent_id IS NULL, k.menu_item_parent_id = a.menu_item_parent_id)
							  AND
							  k.menu_id = a.menu_id
							  )
							LEFT JOIN sys_menu_items AS l
							  ON l.menu_item_parent_id = a.menu_item_id
                            WHERE
                              (
                                d.module_active = 1
                                OR
                                d.module_ident IS NULL
                              )
                              AND
                              a.menu_item_parent_id '.($menu_item_parent_id === null ? 'IS NULL' : ' = '.$menu_item_parent_id).'
							GROUP BY
							  a.menu_item_parent_id,
							  a.menu_item_id';

		// loop through menu array
		$result = $this->unibox->db->query($sql_string, 'failed to select menu items');
		while ($row = $result->fetch_row())
			$this->items_administrate_print_node($row);
    }

	protected function items_administrate_print_node($row)
	{
		$admin = ub_administration_ng::get_instance('menumanager_items_administrate');

		$right_sort = $this->unibox->session->has_right('menumanager_item_sort');
		$right_add = $this->unibox->session->has_right('menumanager_item_add');
		$right_edit = $this->unibox->session->has_right('menumanager_item_edit');
		$right_delete = $this->unibox->session->has_right('menumanager_item_delete');

		list($alias, $action_ident, $menu_item_id, $menu_item_parent_id, $si_menu_item_name, $menu_item_name, $menu_item_descr, $action_descr, $menu_item_sort, $menu_item_hotkey, $menu_item_show_always, $alias_get_name, $alias_get_value, $menu_get_name, $menu_get_value, $module_ident, $sex_value, $min, $max, $child_count) = $row;
		$admin->begin_dataset();

        $admin->add_dataset_ident('menu_item_id', $menu_item_id);
        $admin->set_dataset_descr($menu_item_name);

		$admin->add_data($menu_item_name);
		$admin->add_data($action_descr);
		$admin->add_data($menu_item_sort);

        if ($right_sort)
        {
        	if ($menu_item_sort == $min && $menu_item_sort == $max)
            {
                $admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
                $admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
            }
            elseif ($menu_item_sort == $min)
            {
                $admin->add_option('sort_down_true.gif', 'TRL_ALT_SORT_DOWN', 'menumanager_item_sort_down');
                $admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
            }
            elseif ($menu_item_sort == $max)
            {
                $admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
                $admin->add_option('sort_up_true.gif', 'TRL_ALT_SORT_UP', 'menumanager_item_sort_up');
            }
            else
            {
                $admin->add_option('sort_down_true.gif', 'TRL_ALT_SORT_DOWN', 'menumanager_item_sort_down');
                $admin->add_option('sort_up_true.gif', 'TRL_ALT_SORT_UP', 'menumanager_item_sort_up');
            }
        }
        else
        {
            $admin->add_option('sort_down_false.gif', 'TRL_ALT_SORT_DOWN_FORBIDDEN');
            $admin->add_option('sort_up_false.gif', 'TRL_ALT_SORT_UP_FORBIDDEN');
        }


		if ($right_add)
			$admin->add_option('menu_item_add_true.gif', 'TRL_ADD_MENU_SUBITEM', 'menumanager_item_add');
		else
			$admin->add_option('menu_item_add_false.gif', 'TRL_ADD_MENU_SUBITEM_FORBIDDEN');
			
		if ($right_edit)
			$admin->add_option('menu_item_edit_true.gif', 'TRL_ALT_EDIT', 'menumanager_item_edit');
		else
			$admin->add_option('menu_item_edit_false.gif', 'TRL_ALT_EDIT_FORBIDDEN');

		if ($right_delete)
			$admin->add_option('menu_item_delete_true.gif', 'TRL_ALT_DELETE', 'menumanager_item_delete');
		else
			$admin->add_option('menu_item_delete_false.gif', 'TRL_ALT_DELETE_FORBIDDEN');

		if ($child_count > 0)
			$this->items_administrate_generate_content($menu_item_id);

		$admin->end_dataset();
	}

	#################################################################################################
	### item add
	#################################################################################################

    public function item_add()
    {
		// get current dialog instance
    	$dialog = ub_dialog::get_instance();

    	// check if a menu_id is set and a menu_item_parent_id was passed
    	if (isset($this->unibox->session->var->menumanager_items_administrate_menu_id))
    	{
    		$sql_string =  'SELECT
							  menu_item_id
							FROM sys_menu_items
							WHERE
							  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'
							UNION
							SELECT
							  0';
	    	$validator = ub_validator::get_instance();
			if ($validator->validate('GET', 'menu_item_id', TYPE_INTEGER, CHECK_INSET_SQL, null, $sql_string))
			{
				// save passed values at the respective location
				$this->unibox->session->env->form->menumanager_item_menu->data->menu_id = $this->unibox->session->var->menumanager_items_administrate_menu_id;
				$this->unibox->session->env->form->menumanager_item_parent->data->menu_item_parent_id = $this->unibox->session->env->input->menu_item_id;

				// register 'faked' forms with dialog
				$dialog->register_form('menumanager_item_menu', 2);
				$dialog->register_form('menumanager_item_parent', 3);
				
				// disable dialog steps 2 & 3
				$dialog->disable_step(array(2, 3));
			}
    	}
    	
		// disable steps 4, 5 & 6 if structural or external menu item is selected
    	$dialog->set_condition(1, 'item_type', CHECK_EQUAL, 'structural', DIALOG_STEPS_DISABLE, array(4, 5, 6));
    	$dialog->set_condition(1, 'item_type', CHECK_EQUAL, 'external', DIALOG_STEPS_DISABLE, array(4, 5, 6));
    	$dialog->set_condition(1, 'item_type', CHECK_EQUAL, 'linked', DIALOG_STEPS_ENABLE, array(4, 5, 6));
    	
    	// disable step 5 if no details for the selected action exist
		$sql_string =  'SELECT DISTINCT
						  a.entity_class
						FROM sys_sex AS a
						  INNER JOIN sys_actions AS b
							ON b.action_ident = a.entity_class
						  INNER JOIN sys_modules AS c
							ON c.module_ident = b.module_ident
						WHERE
						  a.module_ident_to = \'alias\'
						  AND
						  a.module_ident_from = c.module_ident';
    	$dialog->set_condition(4, 'action_ident', CHECK_NOTINSET_SQL, $sql_string, DIALOG_STEPS_DISABLE, array(5));
    	$dialog->set_condition(4, 'action_ident', CHECK_INSET_SQL, $sql_string, DIALOG_STEPS_ENABLE, array(5));

    	return 0;
    }
    
    public function item_add_type()
    {
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_type');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_type', 'menumanager_item_add');
    }
        	
	public function item_add_menu()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_menu');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_menu', 'menumanager_item_add');
	}
	
	public function item_add_parent()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_parent');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_parent', 'menumanager_item_add');
	}
	
	public function item_add_action()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_action');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_action', 'menumanager_item_add');
	}
	
	public function item_add_action_details()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_action_details');

		// reset form elements if form exists
		if (isset($this->unibox->session->env->form->menumanager_item_action_details->spec))
			$this->unibox->session->env->form->menumanager_item_action_details->spec->elements = new stdClass;

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_action_details', 'menumanager_item_add');
	}

	public function item_add_alias()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_alias');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_alias', 'menumanager_item_add');
	}
	
	public function item_add_details()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_details');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_details', 'menumanager_item_add');
	}
	
	public function item_add_process()
	{
		try
		{
			$this->unibox->db->begin_transaction();
			
			// fix zero -> null for menu_item_parent_id
			$menu_item_parent_id = ($this->unibox->session->env->form->menumanager_item_parent->data->menu_item_parent_id == 0) ? 'NULL' : '\''.$this->unibox->session->env->form->menumanager_item_parent->data->menu_item_parent_id.'\'';
			
			// get sort value for new menu item
			$sql_string =  'SELECT
							  MAX(menu_item_sort) + 1
							FROM sys_menu_items
							WHERE
							  menu_item_parent_id '.(($menu_item_parent_id == 'NULL') ? 'IS' : '=').' '.$menu_item_parent_id.'
							  AND
							  menu_id = \''.$this->unibox->session->env->form->menumanager_item_menu->data->menu_id.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to get new sort value for menu item');
			if ($result->num_rows() != 1)
				throw new ub_exception_transaction();

			list($menu_item_sort) = $result->fetch_row();
			if ($menu_item_sort === null)
				$menu_item_sort = 1;
			
			// begin sql string
			$sql_string =  'INSERT INTO
							  sys_menu_items
							SET
							  menu_item_parent_id = '.$menu_item_parent_id.',
							  menu_id = \''.$this->unibox->session->env->form->menumanager_item_menu->data->menu_id.'\',';
			
			// check what type of item we're inserting
			if ($this->unibox->session->env->form->menumanager_item_type->data->item_type == 'linked')
				$sql_string .= '  alias = \''.$this->unibox->session->env->form->menumanager_item_alias->data->alias.'\',';
			elseif ($this->unibox->session->env->form->menumanager_item_type->data->item_type == 'external')
				$sql_string .= '  link = \''.$this->unibox->session->env->form->menumanager_item_details->data->link.'\',';
			else
				$sql_string .= '  alias = NULL, link = NULL,';
			
			// ... continue sql_string
			$sql_string .= '  menu_item_sort = \''.$menu_item_sort.'\',';
	
			// check if a hotkey is set for this item
			if (!empty($this->unibox->session->env->form->menumanager_item_details->data->menu_item_hotkey))
				$sql_string .= '  menu_item_hotkey = \''.$this->unibox->session->env->form->menumanager_item_details->data->menu_item_hotkey.'\',';
			
			// ... and finish it
			$sql_string .= '  menu_item_show_always = \''.$this->unibox->session->env->form->menumanager_item_details->data->menu_item_show_always.'\'';
	
			// now execute the query
			$this->unibox->db->query($sql_string, 'failed to insert menu item');
			if ($this->unibox->db->affected_rows() != 1)
				throw new ub_exception_transaction();
	
			// get id of the inserted menu item
			$menu_item_id = $this->unibox->db->last_insert_id();
	
			// insert menu item name translation
			$si_menu_item_name = 'TRL_MENU_ITEM_NAME_'.$menu_item_id;
			if (!$this->unibox->insert_translation($si_menu_item_name, $this->unibox->session->env->form->menumanager_item_details->data->menu_item_name, 'menu'))
				throw new ub_exception_transaction();
	
			// check if menu item description is set
			if (!ub_functions::array_empty($this->unibox->session->env->form->menumanager_item_details->data->menu_item_descr))
			{
				$si_menu_item_descr = 'TRL_MENU_ITEM_DESCR_'.$menu_item_id;
				if (!$this->unibox->insert_translation($si_menu_item_descr, $this->unibox->session->env->form->menumanager_item_details->data->menu_item_descr, 'menu'))
					throw new ub_exception_transaction();
			}
			else
			{
				$si_menu_item_descr = null;
				$this->unibox->delete_translation('TRL_MENU_ITEM_DESCR_'.$menu_item_id, 'menu');
			}
			
			// update menu with menu string identifiers
			$sql_string =  'UPDATE sys_menu_items SET
							  si_menu_item_name = \''.$si_menu_item_name.'\',
							  si_menu_item_descr = '.(($si_menu_item_descr === null) ? 'NULL' : '\''.$si_menu_item_descr.'\'').'
							WHERE
							  menu_item_id = \''.$menu_item_id.'\'';
			$this->unibox->db->query($sql_string, 'failed to update menu item string identifiers');
	
			// insert menu get vars, if menu item is a linked one
			if ($this->unibox->session->env->form->menumanager_item_type->data->item_type == 'linked')
			{
				// TODO: insert all get vars at once!
				$sql_string  = 'SELECT DISTINCT
								  a.entity_ident
								FROM sys_sex AS a
								  INNER JOIN sys_actions AS b
									ON b.action_ident = \''.$this->unibox->session->env->form->menumanager_item_action->data->action_ident.'\'
								  INNER JOIN sys_modules AS c
									ON c.module_ident = b.module_ident
								WHERE
								  a.module_ident_to = \'alias\'
								  AND
								  a.module_ident_from = c.module_ident
								  AND
								  a.entity_class = \''.$this->unibox->session->env->form->menumanager_item_action->data->action_ident.'\'
								ORDER BY
								  a.entity_detail_int ASC';
				$result = $this->unibox->db->query($sql_string);
				while (list($entity_ident) = $result->fetch_row())
				{
					if (isset($this->unibox->session->env->form->menumanager_item_action_details->data->$entity_ident))
					{
						$sql_string  = 'INSERT INTO
										  sys_menu_items_get
										SET
										  menu_item_id = \''.$menu_item_id.'\',
										  name = \''.$entity_ident.'\',
										  value = \''.$this->unibox->session->env->form->menumanager_item_action_details->data->$entity_ident.'\'';
						$this->unibox->db->query($sql_string, 'failed to insert menu item get variable');
						if ($this->unibox->db->affected_rows() != 1)
							throw new ub_exception_transaction();
					}
				}
			}
			
			// commit changes to database
	        $this->unibox->db->commit();
	        ub_form_creator::reset('menumanager_item_type');
	
	        $msg = new ub_message(MSG_SUCCESS);
	        $msg->add_text('TRL_ADD_SUCCESSFUL');
	        $msg->display();
	        
			$this->unibox->switch_alias('menumanager_items_administrate', true);
		}
		catch (ub_exception_transaction $exception)
		{
			$exception->process('TRL_ADD_FAILED');
            ub_form_creator::reset('menumanager_item_type');
            $this->unibox->switch_alias('menumanager_items_administrate', true);
		}
	}

	#################################################################################################
	### item edit
	#################################################################################################

	public function item_edit()
	{
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

		// check if a menu_id is available
		if (!isset($this->unibox->session->var->menumanager_items_administrate_menu_id))
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_ERR_NO_MENU_ID_PASSED');
			$msg->display();
			return;
		}
		
        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              menu_item_id
                            FROM
                              sys_menu_items
							WHERE
							  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('menu_item_id'));
                if (!$validator->validate('STACK', 'menu_item_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
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

	public function item_edit_prepare_dialog()
	{
		// get stack
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();
		
		// get dialog
		$dialog = ub_dialog::get_instance();

		// get current menu item values
		$sql_string =  'SELECT
						  IF (a.alias IS NULL, (IF (a.link IS NULL, \'structural\', \'external\')), \'linked\') AS item_type,
						  a.menu_item_parent_id,
						  a.menu_id,
						  c.action_ident,
						  a.alias,
						  a.link,
						  a.si_menu_item_name AS menu_item_name,
						  a.si_menu_item_descr AS menu_item_descr,
						  a.menu_item_hotkey,
						  a.menu_item_show_always,
						  b.string_value
						FROM sys_menu_items AS a
						  INNER JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_menu_item_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  LEFT JOIN sys_alias AS c
							ON c.alias = a.alias
						WHERE
						  a.menu_item_id = \''.$dataset->menu_item_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to get menu item data');
		$values = $result->fetch_row(MYSQL_ASSOC);

		// check if first form has already been displayed
		if (!isset($this->unibox->session->env->form->menumanager_item_type->spec))
		{
			// refill all forms and mark steps as finished
			$form = ub_form_creator::get_instance();
			
			// refill item type form
			$form->set_current_form('menumanager_item_type');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('item_type'))));
			$dialog->finish_step(1, 'menumanager_item_type');
	
			// refill menu form
			$form->set_current_form('menumanager_item_menu');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('menu_id'))));
			$dialog->finish_step(2, 'menumanager_item_menu');
			
			// refill parent form
			$form->set_current_form('menumanager_item_parent');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('menu_item_parent_id'))));
			$dialog->finish_step(3, 'menumanager_item_parent');
			
			// refill action form
			$form->set_current_form('menumanager_item_action');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('action_ident'))));
			$dialog->finish_step(4, 'menumanager_item_action');
			
			// refill action details form
			$form->set_current_form('menumanager_item_action_details');
			$details = array();
	        $sql_string =  'SELECT
							  name,
							  value
							FROM sys_menu_items_get
							WHERE
							  menu_item_id = \''.$dataset->menu_item_id.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to menu item get vars');
			while (list($name, $value) = $result->fetch_row())
				$details[$name] = $value;
			$form->set_values_array($details);
			$dialog->finish_step(5, 'menumanager_item_action_details');
			
			// refill alias form
			$form->set_current_form('menumanager_item_alias');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('alias'))));
			$dialog->finish_step(6, 'menumanager_item_alias');
			
			// refill details form
			$form->set_current_form('menumanager_item_details');
			$form->set_values_array(ub_functions::array_intersect_key($values, array_flip(array('menu_item_name', 'menu_item_descr', 'link', 'menu_item_hotkey', 'menu_item_show_always'))), array('menu_item_name', 'menu_item_descr'));
			$dialog->finish_step(7, 'menumanager_item_details');
		}
		
		// disable steps 4, 5 & 6 if structural or external menu item is selected
    	$dialog->set_condition(1, 'item_type', CHECK_EQUAL, 'structural', DIALOG_STEPS_DISABLE, array(4, 5, 6));
    	$dialog->set_condition(1, 'item_type', CHECK_EQUAL, 'external', DIALOG_STEPS_DISABLE, array(4, 5, 6));
    	$dialog->set_condition(1, 'item_type', CHECK_EQUAL, 'linked', DIALOG_STEPS_ENABLE, array(4, 5, 6));
    	
    	// disable step 5 if no details for the selected action exist
		$sql_string =  'SELECT DISTINCT
						  a.entity_class
						FROM sys_sex AS a
						  INNER JOIN sys_actions AS b
							ON b.action_ident = a.entity_class
						  INNER JOIN sys_modules AS c
							ON c.module_ident = b.module_ident
						WHERE
						  a.module_ident_to = \'alias\'
						  AND
						  a.module_ident_from = c.module_ident';
    	$dialog->set_condition(4, 'action_ident', CHECK_NOTINSET_SQL, $sql_string, DIALOG_STEPS_DISABLE, array(5));
    	$dialog->set_condition(4, 'action_ident', CHECK_INSET_SQL, $sql_string, DIALOG_STEPS_ENABLE, array(5));
    	
    	// set content title
    	$this->unibox->set_content_title('TRL_MENU_ITEM_EDIT', array($values['string_value']));
    	
    	return 0;
	}
	
    public function item_edit_type()
    {
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_type');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_type', 'menumanager_item_edit');
		$form->set_destructor('ub_stack', 'discard_top');
    }

    public function item_edit_menu()
    {
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_menu');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_menu', 'menumanager_item_edit');
    }

    public function item_edit_parent()
    {
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_parent');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_parent', 'menumanager_item_edit');
    }

	public function item_edit_action()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_action');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_action', 'menumanager_item_edit');
	}
	
	public function item_edit_action_details()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_action_details');

		// reset form elements if form exists
		if (isset($this->unibox->session->env->form->menumanager_item_action_details->spec))
			$this->unibox->session->env->form->menumanager_item_action_details->spec->elements = new stdClass;

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_action_details', 'menumanager_item_edit');
	}
	
	public function item_edit_alias()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_alias');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_alias', 'menumanager_item_edit');
	}
	
	public function item_edit_details()
	{
		$this->unibox->load_template('shared_form_display');
		$this->unibox->xml->add_value('form_name', 'menumanager_item_details');

		$form = ub_form_creator::get_instance();
		$form->begin_form('menumanager_item_details', 'menumanager_item_edit');
	}

	public function item_edit_process()
	{
		try
		{
			$this->unibox->db->begin_transaction();
			
			// get stack
			$stack = ub_stack::get_instance();
			$dataset = $stack->top();
			
			// get current values
			$sql_string =  'SELECT
							  menu_id,
							  menu_item_parent_id,
							  si_menu_item_name,
							  si_menu_item_descr
							FROM sys_menu_items
							WHERE
							  menu_item_id = \''.$dataset->menu_item_id.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to get current parent id');
			if ($result->num_rows() != 1)
				throw new ub_exception_transaction();

			list($cur_menu_id, $cur_menu_item_parent_id, $cur_si_menu_item_name, $cur_si_menu_item_descr) = $result->fetch_row();
			if ($cur_menu_item_parent_id === null)
				$cur_menu_item_parent_id = 0;
			
			// fix zero -> null for menu_item_parent_id
			$menu_item_parent_id = ($this->unibox->session->env->form->menumanager_item_parent->data->menu_item_parent_id == 0) ? 'NULL' : '\''.$this->unibox->session->env->form->menumanager_item_parent->data->menu_item_parent_id.'\'';
	
			// check if we're moving to a new menu or parent node
			if ($cur_menu_id != $this->unibox->session->env->form->menumanager_item_menu->data->menu_id || $cur_menu_item_parent_id != $this->unibox->session->env->form->menumanager_item_parent->data->menu_item_parent_id)
			{
				// get sort value for menu item if its moving to a new parent node
				$sql_string =  'SELECT
								  MAX(menu_item_sort) + 1
								FROM sys_menu_items
								WHERE
								  menu_item_parent_id '.(($menu_item_parent_id == 'NULL') ? 'IS' : '=').' '.$menu_item_parent_id.'
								  AND
								  menu_id = \''.$this->unibox->session->env->form->menumanager_item_menu->data->menu_id.'\'';
				$result = $this->unibox->db->query($sql_string, 'failed to get new sort value for menu item');
				if ($result->num_rows() != 1)
					throw new ub_exception_transaction();

				list($menu_item_sort) = $result->fetch_row();
				if ($menu_item_sort === null)
					$menu_item_sort = 1;
			}
			
			// begin sql string
			$sql_string =  'UPDATE
							  sys_menu_items
							SET
							  menu_item_parent_id = '.$menu_item_parent_id.',
							  menu_id = \''.$this->unibox->session->env->form->menumanager_item_menu->data->menu_id.'\',';

			// check what type of item we're inserting
			if ($this->unibox->session->env->form->menumanager_item_type->data->item_type == 'linked')
				$sql_string .= '  alias = \''.$this->unibox->session->env->form->menumanager_item_alias->data->alias.'\',';
			elseif ($this->unibox->session->env->form->menumanager_item_type->data->item_type == 'external')
				$sql_string .= '  link = \''.$this->unibox->session->env->form->menumanager_item_details->data->link.'\',';
			else
				$sql_string .= '  alias = NULL, link = NULL,';

			// update sort if necessary
			if (isset($menu_item_sort))
				$sql_string .= '  menu_item_sort = \''.$menu_item_sort.'\',';
	
			// check if a hotkey is set for this item
			if (!empty($this->unibox->session->env->form->menumanager_item_details->data->menu_item_hotkey))
				$sql_string .= '  menu_item_hotkey = \''.$this->unibox->session->env->form->menumanager_item_details->data->menu_item_hotkey.'\',';
			else
				$sql_string .= '  menu_item_hotkey = NULL,';
			
			// ... and finish it
			$sql_string .= '  menu_item_show_always = \''.$this->unibox->session->env->form->menumanager_item_details->data->menu_item_show_always.'\'
							WHERE
							  menu_item_id = \''.$dataset->menu_item_id.'\'';
	
			// now execute the query
			$this->unibox->db->query($sql_string, 'failed to update menu item');

			// update menu item name translation
			if (!$this->unibox->insert_translation($cur_si_menu_item_name, $this->unibox->session->env->form->menumanager_item_details->data->menu_item_name, 'menu'))
				throw new ub_exception_transaction();
	
			// check if menu item description is set
			if (!ub_functions::array_empty($this->unibox->session->env->form->menumanager_item_details->data->menu_item_descr))
			{
				// check if a description currently exists
				if ($cur_si_menu_item_descr === null)
					$si_menu_item_descr = 'TRL_MENU_ITEM_DESCR_'.$dataset->menu_item_id;
				else
					$si_menu_item_descr = $cur_si_menu_item_descr;
					
				if (!$this->unibox->insert_translation($si_menu_item_descr, $this->unibox->session->env->form->menumanager_item_details->data->menu_item_descr, 'menu'))
					throw new ub_exception_transaction();
			}
			else
				$si_menu_item_descr = null;

			// update menu with menu string identifiers
			$sql_string =  'UPDATE
							  sys_menu_items
							SET
							  si_menu_item_descr = '.(($si_menu_item_descr === null) ? 'NULL' : '\''.$si_menu_item_descr.'\'').'
							WHERE
							  menu_item_id = \''.$dataset->menu_item_id.'\'';
			$this->unibox->db->query($sql_string, 'failed to update menu item string identifiers');

			// delete description translation if its not needed anymore
			if ($si_menu_item_descr === null && !$this->unibox->delete_translation($cur_si_menu_item_descr, 'menu'))
				throw new ub_exception_transaction();

			// drop all existing get vars for this menu item
			$sql_string =  'DELETE FROM
							  sys_menu_items_get
							WHERE
							  menu_item_id = \''.$dataset->menu_item_id.'\'';
			$this->unibox->db->query($sql_string, 'failed to delete existing get vars');
			
			// insert menu get vars, if menu item is a linked one
			if ($this->unibox->session->env->form->menumanager_item_type->data->item_type == 'linked')
			{
				// TODO: insert all get vars at once!
				$sql_string  = 'SELECT DISTINCT
								  a.entity_ident
								FROM sys_sex AS a
								  INNER JOIN sys_actions AS b
									ON b.action_ident = \''.$this->unibox->session->env->form->menumanager_item_action->data->action_ident.'\'
								  INNER JOIN sys_modules AS c
									ON c.module_ident = b.module_ident
								WHERE
								  a.module_ident_to = \'alias\'
								  AND
								  a.module_ident_from = c.module_ident
								  AND
								  a.entity_class = \''.$this->unibox->session->env->form->menumanager_item_action->data->action_ident.'\'
								ORDER BY
								  a.entity_detail_int ASC';
				$result = $this->unibox->db->query($sql_string);
				while (list($entity_ident) = $result->fetch_row())
				{
					if (isset($this->unibox->session->env->form->menumanager_item_action_details->data->$entity_ident))
					{
						$sql_string  = 'INSERT INTO
										  sys_menu_items_get
										SET
										  menu_item_id = \''.$dataset->menu_item_id.'\',
										  name = \''.$entity_ident.'\',
										  value = \''.$this->unibox->session->env->form->menumanager_item_action_details->data->$entity_ident.'\'';
						$this->unibox->db->query($sql_string, 'failed to insert menu item get variable');
						if ($this->unibox->db->affected_rows() != 1)
							throw new ub_exception_transaction();
					}
				}
			}

			// if the item moved to a new parent, fix menu item sort on old and new parent level
			if (isset($menu_item_sort))
			{
				$sql_string =  'SELECT
								  menu_item_sort
								FROM sys_menu_items
								WHERE
								  menu_id = \''.$cur_menu_id.'\'
								  AND
								  menu_item_parent_id '.(($cur_menu_item_parent_id == 0) ? 'IS NULL' : '= \''.$cur_menu_item_parent_id.'\'');
				if (!$this->unibox->resort_column($sql_string))
					throw new ub_exception_transaction();
					
				$sql_string =  'SELECT
								  menu_item_sort
								FROM sys_menu_items
								WHERE
								  menu_id = \''.$this->unibox->session->env->form->menumanager_item_menu->data->menu_id.'\'
								  AND
								  menu_item_parent_id '.(($menu_item_parent_id == 'NULL') ? 'IS' : '=').' '.$menu_item_parent_id;
				if (!$this->unibox->resort_column($sql_string))
					throw new ub_exception_transaction();
			}

			// commit changes to database
	        $this->unibox->db->commit();
	        ub_form_creator::reset('menumanager_item_type');
	
	        $msg = new ub_message(MSG_SUCCESS, false);
	        $msg->add_text('TRL_EDIT_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception $exception)
		{
            $exception->process('TRL_EDIT_FAILED');
            ub_form_creator::reset('menumanager_item_type');
            $stack->clear();
            $this->unibox->switch_alias('menumanager_items_administrate', true);
		}
	}
	
	#################################################################################################
	### item delete
	#################################################################################################

	public function item_delete()
	{
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

		// check if a menu_id is available
		if (!isset($this->unibox->session->var->menumanager_items_administrate_menu_id))
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_ERR_NO_MENU_ID_PASSED');
			$msg->display();
			return;
		}
		
        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              menu_item_id
                            FROM
                              sys_menu_items
							WHERE
							  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('menu_item_id'));
                if (!$validator->validate('STACK', 'menu_item_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

            $stack->validate();
        }

        if ($stack->is_valid())
            return (int)$validator->form_validate('menumanager_item_delete');
        else
            $stack->switch_to_administration();
	}
	
	public function item_delete_confirm()
	{
		$stack = ub_stack::get_instance();
		$dataset = $stack->top();
		
		// get menu item name
		$sql_string =  'SELECT
						  b.string_value
						FROM sys_menu_items AS a
						  INNER JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_menu_item_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						WHERE
						  a.menu_item_id = \''.$dataset->menu_item_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to get menu item name');
		list($menu_item_name) = $result->fetch_row();
		
		// check if the current menu item has subitems
		$sql_string =  'SELECT
						  menu_item_id
						FROM sys_menu_items
						WHERE
						  menu_item_parent_id = \''.$dataset->menu_item_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to get menu items children');
		$has_subitems = $result->num_rows() > 0;
		
		// check if other menu items are available
		$sql_string =  'SELECT
						  a.menu_item_id,
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
						FROM sys_menu_items AS a
						  INNER JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_menu_item_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  LEFT JOIN sys_menu_items AS c
							ON c.menu_item_id = a.menu_item_parent_id
						  LEFT JOIN sys_translations AS d
							ON
							(
							d.string_ident = c.si_menu_item_name
                            AND
                              (
                              d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              OR
                              d.lang_ident IS NULL
                              )
							)
						WHERE
						  a.menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'
						  AND
						  a.menu_item_id NOT IN (\''.$dataset->menu_item_id.'\', \''.implode('\', \'', $this->get_menu_subitems($dataset->menu_item_id)).'\')
						ORDER BY
						  d.string_value ASC,
						  b.string_value ASC';
		$result = $this->unibox->db->query($sql_string, 'failed to check for other menu items');
		$menu_items_available = $result->num_rows() > 0;
			
		// if the menu item has subitems and other menu items are available
		if ($has_subitems && $menu_items_available)
		{
			$msg = new ub_message(MSG_WARNING);
			$msg->begin_form('menumanager_item_delete', 'menumanager_item_delete');
			$msg->form->plaintext('TRL_MENU_ITEMS_HAS_SUBITEMS', true, array($menu_item_name));
			$msg->form->begin_radio('subitems', 'TRL_SUBITEMS');
			$msg->form->add_option('delete', 'TRL_DELETE_ALL_SUBITEMS', true);
			$msg->form->add_option('move', 'TRL_MOVE_TO_MENU_ITEM');
			$msg->form->begin_select('target_menu_item_id', 'TRL_TARGET_MENU_ITEM', false);
			$msg->form->add_option(0, 'TRL_MOVE_TO_TOP_LEVEL');
			$msg->form->add_option_sql($sql_string);
			$msg->form->end_select();
			$msg->form->end_radio();
			$msg->form->set_condition(CHECK_NOTEMPTY);
			$msg->form->begin_buttonset();
            $msg->form->submit('TRL_DELETE_UCASE');
            $msg->form->cancel('TRL_CANCEL_UCASE', 'menumanager_item_delete');
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'discard_top');
            $msg->end_form();
		}
		else
		{
			$msg = new ub_message(MSG_QUESTION);
			$msg->add_text('TRL_MENU_ITEM_DELETE_CONFIRM', array($menu_item_name));
			if ($has_subitems)
			{
				$msg->add_newline();
				$msg->add_text('TRL_MENU_ITEM_SUBITEMS_WILL_BE_DELETED');
			}
			$msg->add_newline(2);
			$msg->begin_form('menumanager_item_delete', 'menumanager_item_delete');
			$msg->form->begin_buttonset(false);
            $msg->form->submit('TRL_YES');
            $msg->form->cancel('TRL_NO', 'menumanager_item_delete');
            $msg->form->end_buttonset();
            $msg->form->set_destructor('ub_stack', 'discard_top');
            $msg->end_form();
		}
		$msg->display();
		return 0;
	}
	
	public function item_delete_process()
	{
		try
		{
			$this->unibox->db->begin_transaction();
			
			$stack = ub_stack::get_instance();
			$dataset = $stack->top();
			
			// get menu item parent id
			$sql_string =  'SELECT
							  menu_item_parent_id
							FROM sys_menu_items
							WHERE
							  menu_item_id = \''.$dataset->menu_item_id.'\'';
			$result = $this->unibox->db->query($sql_string, 'failed to get menu item parent id');
			if ($result->num_rows() != 1)
				throw new ub_exception_transaction();

			list($menu_item_parent_id) = $result->fetch_row();
	
			// check if any subitems need to be treated
			if (isset($this->unibox->session->env->form->menumanager_item_delete->data->subitems))
			{
				// check if we need to move the subitems
				if ($this->unibox->session->env->form->menumanager_item_delete->data->subitems == 'move')
				{
					// get highest sort value for target parent item
					$sql_string =  'SELECT
									  MAX(menu_item_sort)
									FROM sys_menu_items
									WHERE
									  menu_item_parent_id '.(($this->unibox->session->env->form->menumanager_item_delete->data->target_menu_item_id == 0) ? 'IS NULL' : '= \''.$this->unibox->session->env->form->menumanager_item_delete->data->target_menu_item_id.'\'');
					$result = $this->unibox->db->query($sql_string, 'failed to get max sort of target parent item');
					if ($result->num_rows() != 1)
						throw new ub_exception_transaction();

					list($max_sort) = $result->fetch_row();
	
					// move subitems to target menu item
					$sql_string =  'UPDATE
									  sys_menu_items
									SET
									  menu_item_parent_id = '.(($this->unibox->session->env->form->menumanager_item_delete->data->target_menu_item_id == 0) ? 'NULL' : '\''.$this->unibox->session->env->form->menumanager_item_delete->data->target_menu_item_id.'\'').',
									  menu_item_sort = menu_item_sort + '.$max_sort.'
									WHERE
									  menu_item_parent_id = \''.$dataset->menu_item_id.'\'';
					$this->unibox->db->query($sql_string, 'failed to move menu items');
					if ($this->unibox->db->affected_rows() < 1)
						throw new ub_exception_transaction();
					
					// fix menu item sort on parent level
					$sql_string =  'SELECT
									  menu_item_sort
									FROM sys_menu_items
									WHERE
									  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'
									  AND
									  menu_item_parent_id '.(($this->unibox->session->env->form->menumanager_item_delete->data->target_menu_item_id == 0) ? 'IS NULL' : '= \''.$this->unibox->session->env->form->menumanager_item_delete->data->target_menu_item_id.'\'');
					if (!$this->unibox->resort_column($sql_string))
						throw new ub_exception_transaction();
				}
			}
			
			// now delete the menu items along with all its subitems
			$this->item_delete_recursive($dataset->menu_item_id);
			
			// fix menu item sort on parent level
			$sql_string =  'SELECT
							  menu_item_sort
							FROM sys_menu_items
							WHERE
							  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'
							  AND
							  menu_item_parent_id '.(($menu_item_parent_id === null) ? 'IS NULL' : '= \''.$menu_item_parent_id.'\'');
			if (!$this->unibox->resort_column($sql_string))
				throw new ub_exception_transaction();

			// commit changes to database
			$this->unibox->db->commit();
	        ub_form_creator::reset('menumanager_item_delete');
			
	        $msg = new ub_message(MSG_SUCCESS, false);
	        $msg->add_text('TRL_DELETE_SUCCESSFUL');
	        $msg->display();
		}
		catch (ub_exception $exception)
		{
			$exception->process('TRL_DELETE_FAILED');
            ub_form_creator::reset('menumanager_item_delete');
            $stack->clear();
            $this->unibox->switch_alias('menumanager_items_administrate', true);
		}
	}

	protected function item_delete_recursive($menu_item_id)
	{
		// delete all possible subitems of passed menu item
		$sql_string =  'SELECT
						  menu_item_id
						FROM sys_menu_items
						WHERE
						  menu_item_parent_id = \''.$menu_item_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to get menu subitems');
		while (list($menu_subitem_id) = $result->fetch_row())
			$this->item_delete_recursive($menu_subitem_id);

		// get string identifiers of passed menu item
		$sql_string =  'SELECT
						  si_menu_item_name,
						  si_menu_item_descr
						FROM sys_menu_items
						WHERE
						  menu_item_id = \''.$menu_item_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to get menu item\'s string identifiers');
		if ($result->num_rows() != 1)
			throw new ub_exception_transaction();

		list($si_menu_item_name, $si_menu_item_descr) = $result->fetch_row();
			
		// delete menu item
		$sql_string =  'DELETE FROM
						  sys_menu_items
						WHERE
						  menu_item_id = \''.$menu_item_id.'\'';
		$this->unibox->db->query($sql_string, 'failed to delete menu item');
		if ($this->unibox->db->affected_rows() != 1)
			throw new ub_exception_transaction();
		
		// delete translations
		if (!$this->unibox->delete_translation($si_menu_item_name, 'menu') || ($si_menu_item_descr !== null && !$this->unibox->delete_translation($si_menu_item_descr, 'menu')))
			throw new ub_exception_transaction();

		// everything seems ok
		return true;
	}

	#################################################################################################
	### item sort
	#################################################################################################

    public function item_sort()
    {
        // check right and fill stack
        $validator = ub_validator::get_instance();
        $stack = ub_stack::get_instance();

		// check if a menu_id is available
		if (!isset($this->unibox->session->var->menumanager_items_administrate_menu_id))
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_ERR_NO_MENU_ID_PASSED');
			$msg->display();
			return;
		}
		
        if (!$stack->is_valid())
        {
            $sql_string  = 'SELECT
                              menu_item_id
                            FROM
                              sys_menu_items
							WHERE
							  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'';
            $stack->reset();
            do
            {
                $stack->keep_keys(array('menu_item_id'));
                if (!$validator->validate('STACK', 'menu_item_id', TYPE_INTEGER, CHECK_INSET_SQL, 'TRL_ERR_INVALID_DATA_PASSED', $sql_string))
                    $stack->element_invalid();
            }
            while ($stack->next() !== false);

			// check that all selected menu items are on the same level
			$cur_parent_id = -1;
            $sql_string =  'SELECT
                              menu_item_id,
							  menu_item_parent_id,
                              menu_item_sort
                            FROM
                              sys_menu_items
                            WHERE
                              menu_item_id IN ('.implode(', ', $stack->get_stack('menu_item_id')).')';
            $result = $this->unibox->db->query($sql_string, 'failed to select menu item sort value');
            while (list($id, $parent_id, $sort) = $result->fetch_row())
            {
            	if ($cur_parent_id != -1 && $parent_id != $cur_parent_id)
            	{
            		$stack->set_valid(false);
		            $this->unibox->error('TRL_ERR_SORT_ONLY_APPLICABLE_ON_ONE_LEVEL');
		            $stack->clear();
		            $stack->switch_to_administration();
		            return;
            	}
            	
                $sort_array[$id] = $sort;
                $cur_parent_id = $parent_id;
            }

			// get maximum sort value
            $sql_string =  'SELECT
                              MIN(menu_item_sort) AS minimum,
                              MAX(menu_item_sort) AS maximum
                            FROM
                              sys_menu_items
                            WHERE
							  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'
							  AND
                              menu_item_parent_id '.(($cur_parent_id === null) ? 'IS NULL' : '= \''.$cur_parent_id.'\'');
            $result = $this->unibox->db->query($sql_string, 'failed to select maximum sort');
            list($min, $max) = $result->fetch_row();
			
            $stack->reset();
            do
            {
                $current = $stack->current();
                if ($this->unibox->session->env->alias->get['direction'] == 'up' && $sort_array[$current['menu_item_id']] == $min)
                {
                    $stack->element_invalid();
                    unset($sort_array[$current['menu_item_id']]);
                }
                elseif ($this->unibox->session->env->alias->get['direction'] == 'down' && $sort_array[$current['menu_item_id']] == $max)
                {
                    $stack->element_invalid();
                    unset($sort_array[$current['menu_item_id']]);
                }
            }
            while ($stack->next() !== false);
            $stack->validate();

            $this->unibox->session->var->register('menu_item_sort_parent_id', $cur_parent_id, true);
            $this->unibox->session->var->register('menu_item_sort_array', $sort_array, true);
        }

        if ($stack->is_valid())
            return 1;
        else
        {
            $this->unibox->error('TRL_ERR_INVALID_SORT_FOR_HIGHLIGHTED_DATASETS');
            $stack->switch_to_administration();
        }
    }

    public function item_sort_process()
    {
    	try
    	{
    		$this->unibox->db->begin_transaction();
    		
			// determine sort direction
	        if ($this->unibox->session->env->alias->get['direction'] == 'down')
	        {
	            $order = 'ASC';
	            $operator = '>';
	            arsort($this->unibox->session->var->menu_item_sort_array);
	        }
	        else
	        {
	            $order = 'DESC';
	            $operator = '<';
	            asort($this->unibox->session->var->menu_item_sort_array);
	        }
	
			// loop through all items that need to be sorted
	        foreach ($this->unibox->session->var->menu_item_sort_array as $id => $sort)
	        {
	        	// get sort value of next dataset
	            $sql_string  = 'SELECT
	                              menu_item_id,
	                              menu_item_sort
	                            FROM
	                              sys_menu_items
	                            WHERE
	                              menu_item_sort '.$operator.$sort.'
	                              AND
								  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'
								  AND
	                              menu_item_parent_id '.(($this->unibox->session->var->menu_item_sort_parent_id === null) ? 'IS NULL' : '= \''.$this->unibox->session->var->menu_item_sort_parent_id.'\'').'
	                            ORDER BY
	                              menu_item_sort '.$order.'
	                            LIMIT 0, 1';
	            $result = $this->unibox->db->query($sql_string, 'failed to select sort of next dataset');
	            if ($result->num_rows() != 1)
	            	throw new ub_exception_transaction();

	            list($db_id, $db_sort) = $result->fetch_row();
				
				// update next item
	            $sql_string  = 'UPDATE
	                              sys_menu_items
	                            SET
	                              menu_item_sort = '.$sort.'
	                            WHERE
	                              menu_item_id = '.$db_id;
	            $this->unibox->db->query($sql_string, 'failed to update dataset: '.$db_id);
	            if ($this->unibox->db->affected_rows() != 1)
	            	throw new ub_exception_transaction();

				// update current item
	            $sql_string  = 'UPDATE
	                              sys_menu_items
	                            SET
	                              menu_item_sort = '.$db_sort.'
	                            WHERE
	                              menu_item_id = '.$id;
	            $this->unibox->db->query($sql_string, 'failed to update dataset: '.$id);
	            if ($this->unibox->db->affected_rows() != 1)
	            	throw new ub_exception_transaction();
	        }
	
			// fix sort
			$sql_string =  'SELECT
							  menu_item_sort
							FROM
							  sys_menu_items
							WHERE
							  menu_id = \''.$this->unibox->session->var->menumanager_items_administrate_menu_id.'\'
							  AND
	                          menu_item_parent_id '.(($this->unibox->session->var->menu_item_sort_parent_id === null) ? 'IS NULL' : '= \''.$this->unibox->session->var->menu_item_sort_parent_id.'\'');
	        if (!$this->unibox->resort_column($sql_string))
	        	throw new ub_exception_transaction();
	        
	        // commit changes to database
	        $this->unibox->db->commit();
	        ub_stack::discard_all();
	        
	        $msg = new ub_message(MSG_SUCCESS, false);
	        $msg->add_text('TRL_SORT_SUCCESS');
	        $msg->display();
    	}
    	catch (ub_exception $exception)
    	{
    		$exception->process('TRL_SORT_FAILED');
    		ub_stack::discard_all();
    		$this->unibox->switch_alias('menumanager_items_administrate', true);
    	}
    }

	#################################################################################################
	### dialog forms
	#################################################################################################

	public function item_type_form()
	{
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();
		$form->begin_radio('item_type', 'TRL_MENU_ITEM_TYPE');
		$form->add_option('linked', 'TRL_MENU_ITEM_LINKED');
		$form->add_option('structural', 'TRL_MENU_ITEM_STRUCTURAL');
		$form->add_option('external', 'TRL_MENU_ITEM_EXTERNAL');
		$form->end_radio();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->begin_buttonset();
		$form->submit('TRL_NEXT_UCASE', 'next');
		if ($this->unibox->session->env->system->action_ident == 'menumanager_item_edit')
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_item_edit');
		else
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_items_administrate');
		$form->end_buttonset();
		$form->end_form();
		return 0;
	}

	public function item_menu_form()
	{
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();
		$form->begin_fieldset('TRL_SELECT_MENU');
		$form->begin_select('menu_id', 'TRL_MENU');
		
		if ($this->unibox->session->has_right('menumanager_actionbar_administrate'))
			$sql_string =  'SELECT
							  a.menu_id,
							  b.string_value
							FROM sys_menu AS a
							  INNER JOIN sys_translations AS b
								ON
								(
								b.string_ident = a.si_menu_name
								AND
								b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								)
							ORDER BY
							  b.string_value ASC';
		else
			$sql_string =  'SELECT
							  a.menu_id,
							  b.string_value
							FROM sys_menu AS a
							  INNER JOIN sys_translations AS b
								ON
								(
								b.string_ident = a.si_menu_name
								AND
								b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								)
							  LEFT JOIN sys_modules AS c
								ON c.module_actionbar_menu_id = a.menu_id
							WHERE
							  c.module_ident IS NULL
							ORDER BY
							  b.string_value ASC';
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->end_fieldset();
		$form->begin_buttonset();
		$form->submit('TRL_NEXT_UCASE', 'next');
		if ($this->unibox->session->env->system->action_ident == 'menumanager_item_edit')
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_item_edit');
		else
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_items_administrate');
		$form->end_buttonset();
		$form->end_form();
		return 0;
	}

	public function item_parent_form()
	{
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();
		$form->begin_fieldset('TRL_SELECT_PARENT_MENU_ITEM');
		$form->begin_select('menu_item_parent_id', 'TRL_PARENT_MENU_ITEM', false);
		$form->add_option(0, 'TRL_INSERT_AT_TOP_LEVEL');
		$sql_string =  'SELECT
						  a.menu_item_id,
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
						FROM sys_menu_items AS a
						  INNER JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_menu_item_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  LEFT JOIN sys_menu_items AS c
							ON c.menu_item_id = a.menu_item_parent_id
						  LEFT JOIN sys_translations AS d
							ON
							(
							d.string_ident = c.si_menu_item_name
                            AND
                              (
                              d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              OR
                              d.lang_ident IS NULL
                              )
							)
						WHERE
						  a.menu_id = \''.$this->unibox->session->env->form->menumanager_item_menu->data->menu_id.'\'';

		if ($this->unibox->session->env->system->action_ident == 'menumanager_item_edit')
		{
			// get stack
			$stack = ub_stack::get_instance();
			$dataset = $stack->top();
			
			$sql_string .= ' AND a.menu_item_id NOT IN (\''.$dataset->menu_item_id.'\', \''.implode('\', \'', $this->get_menu_subitems($dataset->menu_item_id)).'\')';
		}

		$sql_string .= 'ORDER BY
						  d.string_value ASC,
						  b.string_value ASC';
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->end_fieldset();
		$form->begin_buttonset();
		$form->submit('TRL_NEXT_UCASE', 'next');
		if ($this->unibox->session->env->system->action_ident == 'menumanager_item_edit')
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_item_edit');
		else
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_items_administrate');
		$form->end_buttonset();
		$form->end_form();
		return 0;
	}
	
	public function item_action_form()
	{
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();
		$form->begin_fieldset('TRL_SELECT_ACTION');
		$form->begin_select('action_ident', 'TRL_ACTION');
		$sql_string =  'SELECT
						  a.action_ident,
						  b.string_value,
						  d.string_value
						FROM sys_actions AS a
						  INNER JOIN sys_translations AS b
							ON
							(
							b.string_ident = a.si_action_descr
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  INNER JOIN sys_modules AS c
							ON c.module_ident = a.module_ident
						  INNER JOIN sys_translations AS d
							ON
							(
							d.string_ident = c.si_module_name
							AND
							d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						ORDER BY
						  d.string_value ASC,
						  b.string_value ASC';
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->end_fieldset();
		$form->begin_buttonset();
		$form->submit('TRL_NEXT_UCASE', 'next');
		if ($this->unibox->session->env->system->action_ident == 'menumanager_item_edit')
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_item_edit');
		else
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_items_administrate');
		$form->end_buttonset();
		$form->end_form();
		return 0;
	}

	public function item_action_details_form()
	{
		$form = ub_form_creator::get_instance();
        $form->register_with_dialog();
		$form->begin_fieldset('TRL_DETAILS');
		$sql_string  = 'SELECT DISTINCT
						  a.entity_ident,
						  a.entity_type_definition
						FROM sys_sex AS a
						  INNER JOIN sys_actions AS b
							ON b.action_ident = a.entity_class
						  INNER JOIN sys_modules AS c
							ON c.module_ident = b.module_ident
						WHERE
						  a.module_ident_to = \'alias\'
						  AND
						  a.module_ident_from = c.module_ident
						  AND
						  a.entity_class = \''.$this->unibox->session->env->form->menumanager_item_action->data->action_ident.'\'
						ORDER BY
						  a.entity_detail_int ASC';
		$result = $this->unibox->db->query($sql_string, 'failed to retrieve action entities');
		if ($result->num_rows() > 0)
			while (list($entity_ident, $entity_type_definition) = $result->fetch_row())
				$form->import_form_spec($entity_type_definition);

		$form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_NEXT_UCASE', 'next');
		if ($this->unibox->session->env->system->action_ident == 'menumanager_item_edit')
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_item_edit');
		else
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_items_administrate');
        $form->end_buttonset();
		$form->end_form();
        return 0;
	}

	public function item_alias_form()
	{
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();
		$form->begin_fieldset('TRL_SELECT_ALIAS');
		$form->begin_select('alias', 'TRL_ALIAS');
		$sql_string =  'SELECT
						  a.alias,
						  CONCAT(c.string_value, \' - \', a.alias) AS alias_descr
						FROM sys_alias AS a
						  INNER JOIN sys_alias_groups AS b
							ON b.alias_group_ident = a.alias_group_ident
						  INNER JOIN sys_translations AS c
							ON
							(
							c.string_ident = b.si_alias_group_name
							AND
							c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						WHERE
						  a.action_ident = \''.$this->unibox->session->env->form->menumanager_item_action->data->action_ident.'\'
						ORDER BY
						  alias_descr ASC';
		$form->add_option_sql($sql_string);
		$form->end_select();
		$form->set_condition(CHECK_NOTEMPTY);
		$form->end_fieldset();
		$form->begin_buttonset();
		$form->submit('TRL_NEXT_UCASE', 'next');
		if ($this->unibox->session->env->system->action_ident == 'menumanager_item_edit')
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_item_edit');
		else
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_items_administrate');
		$form->end_buttonset();
		$form->end_form();
		return 0;
	}
	
	public function item_details_form()
	{
		$form = ub_form_creator::get_instance();
		$form->register_with_dialog();
		$form->text_multilanguage('menu_item_name', 'TRL_LABELING', 30);
        $form->set_condition_multilanguage(CHECK_MULTILANG);
        $form->set_condition_multilanguage(CHECK_NOTEMPTY);
		$form->textarea_multilanguage('menu_item_descr', 'TRL_DESCRIPTION', 40, 5);
		$form->begin_fieldset('TRL_DETAILS');
		if ($this->unibox->session->env->form->menumanager_item_type->data->item_type == 'external')
		{
			$form->text('link', 'TRL_MENU_EXTERNAL_DESTINATION', '', 30);
			$form->set_condition(CHECK_NOTEMPTY);
        	$form->set_condition(CHECK_URL, array('http', 'https', 'ftp'));
		}
		$form->text('menu_item_hotkey', 'TRL_HOTKEY', '', 1);
		$form->checkbox('menu_item_show_always', 'TRL_MENU_ITEM_SHOW_ALWAYS');
		$form->end_fieldset();
		$form->begin_buttonset();
		if ($this->unibox->session->env->system->action_ident == 'menumanager_item_edit')
		{
			$form->submit('TRL_SAVE_UCASE', 'next');
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_item_edit');
		}
		else
		{
			$form->submit('TRL_ADD_UCASE', 'next');
			$form->cancel('TRL_CANCEL_UCASE', 'menumanager_items_administrate');
		}
		$form->end_buttonset();
		$form->end_form();
		return 0;
	}

	#################################################################################################
	### auxiliary functions
	#################################################################################################

	protected function get_menu_subitems($menu_item_id)
	{
    	$menu_item_ids = array();
    	$sql_string =  'SELECT
						  menu_item_id
						FROM sys_menu_items
						WHERE
						  menu_item_parent_id = \''.$menu_item_id.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to select menu subitems');
		while (list($menu_item_id) = $result->fetch_row())
		{
			$menu_item_ids[] = $menu_item_id;
			$menu_item_ids = array_merge($menu_item_ids, $this->get_menu_subitems($menu_item_id));
		}
		
		return $menu_item_ids;
	}
}

?>