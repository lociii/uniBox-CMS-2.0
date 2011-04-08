<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_menu
{
    /**
    * variable that contains the class version
    * 
    * @access   protected
    */
    const version = '0.1.0';

    /**
    * instance of own class
    * 
    * @access   protected
    */
    private static $instance = NULL;

    /**
    * complete unibox framework
    * 
    * @access   protected
    */
    protected $unibox;

    /**
    * variable holding menu between functions
    * 
    * @access   protected
    */
    protected $menu;

    /**
    * current alias' getvars
    * 
    * @access   protected
    */
    protected $get;

    /**
    * already denied menu items
    * 
    * @access   protected
    */
    protected $forbidden_menu_items = array();

    /**
    * returns class version
    * 
    * @access   public
    * @return   float               version-number
    */
    public static function get_version()
    {
        return ub_menu::version;
    }

    /**
    * return class instance
    * 
    * @access   public
    * @return   object              object of current class
    */
    public static function get_instance()
    {
        if (is_null(self::$instance))
            self::$instance = new ub_menu;
        return self::$instance;
    }

    /**
    * session constructor - gets called everytime the object gets instantiated
    * 
    * @access   public
    */
    public function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->config->load_config('menu');
    }

    /**
    * get menu information and call menu processor
    * 
    * @access   public
    */
    public function process_menu()
    {
        if (isset($this->unibox->session->env->alias->get['menu_id']))
        {
            $sql_string  = 'SELECT
                              a.menu_id,
                              b.string_value
                            FROM
                              sys_menu AS a
                                INNER JOIN sys_translations AS b
                                  ON 
                                    (
                                    b.string_ident = a.si_menu_name
                                    AND
                                    b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                                    )
                            WHERE
                              a.menu_id = '.$this->unibox->db->cleanup($this->unibox->session->env->alias->get['menu_id']).'
                              AND
                              a.menu_active = 1';
            $result = $this->unibox->db->query($sql_string, 'failed to get menu');
            if ($result->num_rows() == 1)
            {
                $this->unibox->load_template('menu');

                $this->unibox->xml->add_value('show_type', $this->unibox->config->system->menu_show_type);
                list($menu_id, $menu_item_name) = $result->fetch_row();
                $this->unibox->xml->add_value('menu_id', $menu_id);
                $this->unibox->xml->add_value('name', $menu_item_name);
                $this->process_items($menu_id);
                $this->gen_menu();
            }
        }
    }

    /**
    * read menu items and build array
    * 
    * @param    $menu_id            menu to process
    * @access   public
    */
    public function process_items($menu_id = null)
    {
        if ($menu_id !== null)
        {
            $this->menu = array();

            $sql_string  = 'SELECT
                              b.alias,
							  a.link,
                              c.action_ident,
                              a.menu_item_id,
                              a.menu_item_parent_id,
                              a.si_menu_item_name,
                              e.string_value AS menu_item_name,
                              f.string_value AS menu_item_descr,
                              a.menu_item_hotkey,
                              a.menu_item_show_always,
                              g.name AS alias_get_name,
                              g.value AS alias_get_value,
                              i.name AS menu_get_name,
                              i.value AS menu_get_value,
                              d.module_ident,
                              h.entity_type_definition
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
                                LEFT JOIN sys_menu_items_get AS i
                                  ON i.menu_item_id = a.menu_item_id
                                LEFT JOIN sys_sex AS h
                                  ON
                                  (
                                  h.entity_class = c.action_ident
                                  AND
                                  (
                                  h.entity_ident = g.name
                                  OR
                                  h.entity_ident = i.name
                                  )
                                  AND
                                  h.module_ident_from = d.module_ident
                                  AND
                                  h.module_ident_to = \'menu\'
                                  AND
                                  h.entity_type = \'menu_rights\'
                                  )
                                WHERE
                                  (
                                    d.module_active = 1
                                    OR
                                    d.module_ident IS NULL
                                  )
                                  AND
                                  a.menu_id = '.(int)$menu_id.'
                                ORDER BY
                                  a.menu_item_parent_id,
                                  a.menu_item_sort';
            $result = $this->unibox->db->query($sql_string, 'failed to get menu items for menu_id '.$menu_id);
            while (list($alias, $link, $action_ident, $menu_item_id, $menu_item_parent_id, $si_menu_item_name, $menu_item_name, $menu_item_descr, $menu_item_hotkey, $menu_item_show_always, $alias_get_name, $alias_get_value, $menu_get_name, $menu_get_value, $module_ident, $sex_value) = $result->fetch_row())
            {
                // fix parent id for root childs
                if ($menu_item_parent_id === null)
                    $menu_item_parent_id = 0;
                // prepare and fill dataset
                if (!isset($menu[$menu_item_id]))
                {
                    // initalize node
                    $this->menu[$menu_item_id]['get'] = $this->menu[$menu_item_id]['menu_get'] = $this->menu[$menu_item_id] = array();
                    $this->menu[$menu_item_id]['alias'] = $alias;
                    $this->menu[$menu_item_id]['link'] = $link;
                    $this->menu[$menu_item_id]['action_ident'] = $action_ident;
                    $this->menu[$menu_item_id]['item_name'] = $menu_item_name;
                    $this->menu[$menu_item_id]['show_always'] = $menu_item_show_always;
                    $this->menu[$menu_item_id]['sex_value'] = $sex_value;
                    $this->menu[$menu_item_id]['module_ident'] = $module_ident;
                    $this->menu[$menu_item_id]['item_descr'] = $menu_item_descr;
                    $this->menu[$menu_item_id]['hotkey'] = $menu_item_hotkey;

                    // prepare parent element
                    if (!isset($this->menu[$menu_item_parent_id]))
                        $this->menu[$menu_item_parent_id] = array();

                    // prepare parent's children node
                    if (!isset($this->menu[$menu_item_parent_id]['children']))
                        $this->menu[$menu_item_parent_id]['children'] = array();

                    // reference dataset to parent's children
                    $this->menu[$menu_item_parent_id]['children'][] = &$this->menu[$menu_item_id];
                }
                // add alias/menu get
                if (!isset($this->menu[$menu_item_id]['get'][$alias_get_name]) && $alias_get_name != null && $alias_get_value != null)
                    $this->menu[$menu_item_id]['get'][$alias_get_name] = $alias_get_value;
                if (!isset($this->menu[$menu_item_id]['menu_get'][$menu_get_name]) && $menu_get_name != null && $menu_get_value != null)
                    $this->menu[$menu_item_id]['menu_get'][$menu_get_name] = $menu_get_value;
            }

            // cut off root node
            if (isset($this->menu[0]['children']))
                $this->menu = $this->menu[0]['children'];
            else
                $this->menu = array();

            // check menu
            $this->check_menu($this->menu);
        }
    }

    /**
    * check each menu node for rights and active state
    * 
    * @param    $items              menu item array to check (reference)
    * @param    $parent             current elements parent node (reference)
    * @access   public
    */
    protected function check_menu(&$items, &$parent = null)
    {
        foreach ($items as $menu_item_key => $menu_item)
        {
            // merge alias get and menu get
            $menu_get_vars = $menu_item['get'];
            foreach ($menu_item['menu_get'] as $name => $value)
                if (!isset($menu_get_vars[$name]))
                    $menu_get_vars[$name] = $value;

            // link to parent item
            if (!is_null($parent))
                $menu_item['parent'] = &$parent;

            // check rights
            $view = true;
            // check if user is allowed to view the content

            if (!$menu_item['show_always'] && $menu_item['action_ident'] != null && count($menu_get_vars) > 0 && $menu_item['sex_value'] != null)
            {
                $this->unibox->session->var->register('menu_getvars', $menu_get_vars, true);
                $view = $this->check_menu_right($menu_item['action_ident'], $menu_item['sex_value']);
            }
            elseif (!$menu_item['show_always'] && $menu_item['action_ident'] !== null)
                $view = $this->unibox->session->has_right($menu_item['action_ident']);

            if (!$view)
            {
                unset($items[$menu_item_key]);
                continue;
            }

            // check if element is active
            if ($this->unibox->session->env->alias->name == $menu_item['alias'] && is_array($menu_get_vars) && count($menu_get_vars) > 0)
                foreach ($menu_get_vars as $name => $value)
                    if (isset($menu_item['active']))
                        $menu_item['active'] = ($menu_item['active'] & (isset($this->unibox->session->env->alias->main_get[$name]) && $this->unibox->session->env->alias->main_get[$name] == $value));
                    else
                        $menu_item['active'] = (isset($this->unibox->session->env->alias->main_get[$name]) && $this->unibox->session->env->alias->main_get[$name] == $value);
            else
                $menu_item['active'] = ($this->unibox->session->env->alias->name == $menu_item['alias']);

            // set parent elements selected
            if ($menu_item['active'] && isset($menu_item['parent']))
                $this->set_parent_active($menu_item['parent']);

            // save processed menu node
            $items[$menu_item_key] = $menu_item;
            
            // check child nodes
            if (isset($menu_item['children']) && is_array($menu_item['children']) && count($menu_item['children']) > 0)
                $this->check_menu($items[$menu_item_key]['children'], $items[$menu_item_key]);
        }
    }

    /**
    * sets all the parents as 'selected' if a child is 'active'
    * 
    * @param    $item               item to be marked as 'selected' (reference)
    * @access   public
    */
    protected function set_parent_active(&$item)
    {
        $item['child_active'] = true;

        if (isset($item['parent']))
            $this->set_parent_active($item['parent']);
    }

    /**
    * check for access rights via sex entity
    * 
    * @param    $action_ident       action ident to be checked
    * @param    $sex_entity         sex entity to get category
    * @access   public
    */
    protected function check_menu_right($action_ident, $sex_value)
    {
        $result = $this->unibox->db->query($sex_value, '', true);
        if ($result->num_rows() == 1)
        {
            list ($category_id) = $result->fetch_row();
            return $this->unibox->session->has_right($action_ident, $category_id);
        }
        else
            return true;
    }

    /**
    * generate menu xml off given array
    * 
    * @param    $menu               finally processed menu array
    * @access   public
    */
    public function gen_menu($menu = null)
    {
        if (is_null($menu))
            $menu = $this->menu;
        if (is_array($menu) && count($menu) > 0)
        {
            foreach ($menu as $arr)
            {
                if ($arr['alias'] !== null || $arr['link'] !== null || (isset($arr['children']) && !empty($arr['children'])) || $arr['show_always'] == 1)
                {
                    // add new item
                    $this->unibox->xml->add_node('menu_item');
                    if (isset($arr['alias']) && $arr['alias'] !== null)
                        $this->unibox->xml->add_value('alias', $arr['alias']);
                    if (isset($arr['link']) && $arr['link'] !== null)
                        $this->unibox->xml->add_value('link', $arr['link']);
                    if (isset($arr['active']) && $arr['active'])
                        $this->unibox->xml->add_value('active', (string)$arr['active']);
                    if (isset($arr['child_active']) && $arr['child_active'])
                        $this->unibox->xml->add_value('child_active', (string)$arr['child_active']);
                    if (isset($arr['lang_ident']) && $arr['lang_ident'] !== null)
                        $this->unibox->xml->add_value('lang_ident', $arr['lang_ident']);
                    $this->unibox->xml->add_value('item_name', $arr['item_name']);
                    if (isset($arr['item_descr']) && $arr['item_descr'] !== null)
                        $this->unibox->xml->add_value('item_descr', $arr['item_descr']);
                    if (isset($arr['hotkey']) && $arr['hotkey'] !== null)
                        $this->unibox->xml->add_value('hotkey', $arr['hotkey']);
                    if (isset($arr['menu_get']) && is_array($arr['menu_get']) && count($arr['menu_get']) > 0)
                    {
                        $this->unibox->xml->add_node('get');
                        foreach ($arr['menu_get'] as $name => $value)
                        {
                            $this->unibox->xml->add_node('getvar');
                            $this->unibox->xml->add_value('name', $name);
                            $this->unibox->xml->add_value('value', $value);
                            $this->unibox->xml->parse_node();
                        }
                        $this->unibox->xml->parse_node();
                    }
                    if (isset($arr['children']))
                        $this->gen_menu($arr['children']);
                    $this->unibox->xml->parse_node();
                }
            }
        }
    } // end generate_menu();
}

?>