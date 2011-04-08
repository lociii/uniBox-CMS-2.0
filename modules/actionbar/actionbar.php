<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_actionbar
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
        return ub_actionbar::version;
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
            self::$instance = new ub_actionbar;
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
    }

    public function show()
    {
        $this->unibox->load_template('actionbar');

        $sql_string  = 'SELECT DISTINCT
                          a.actionbar_group_ident,
                          b.string_value,
						  c.module_ident,
						  c.module_actionbar_menu_id,
                          c.extends_module_ident,
                          d.string_value,
                          c.module_builtin
						FROM sys_actionbar_groups AS a
                          INNER JOIN sys_translations AS b
                            ON
							(
							b.string_ident = a.si_name
							AND
							b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  INNER JOIN sys_modules AS c
							ON c.module_actionbar_group_ident = a.actionbar_group_ident
						  INNER JOIN sys_translations AS d
							ON
							(
							d.string_ident = c.si_module_name
							AND
							d.lang_ident = \''.$this->unibox->session->lang_ident.'\'
							)
						  INNER JOIN sys_actions AS e
							ON e.module_ident = c.module_ident
						  INNER JOIN sys_alias AS f
							ON f.action_ident = e.action_ident
						  INNER JOIN sys_menu_items AS g
							ON
							(
							g.alias = f.alias
							AND
							g.menu_id = c.module_actionbar_menu_id
							)
						  INNER JOIN sys_menu AS h
							ON h.menu_id = c.module_actionbar_menu_id
						WHERE
						  c.module_actionbar_menu_id IS NOT NULL
						  AND
						  c.module_active = 1
						  AND
						  h.menu_active = 1
						  AND
						  e.action_ident IN (\''.implode('\', \'', $this->unibox->session->get_rights()).'\')
						ORDER BY
						  a.sort ASC,
						  d.string_value';
        $result = $this->unibox->db->query($sql_string, 'failed to select actionbar groups');
        $cur_actionbar_group_ident = null;
        while (list($actionbar_group_ident, $actionbar_group_name, $module_ident, $actionbar_menu_id, $extends_module_ident, $module_name, $module_builtin) = $result->fetch_row())
        {
        	if ($cur_actionbar_group_ident === null)
        	{
        		$cur_actionbar_group_ident = $actionbar_group_ident;
	            $this->unibox->xml->add_node('menu');
            	$this->unibox->xml->set_attribute('name', $actionbar_group_name);
        	}
        	elseif ($cur_actionbar_group_ident != $actionbar_group_ident)
        	{
        		$this->unibox->xml->parse_node();
	            $this->unibox->xml->add_node('menu');
        		$this->unibox->xml->set_attribute('name', $actionbar_group_name);
        		$cur_actionbar_group_ident = $actionbar_group_ident;
        	}

            $this->unibox->xml->add_node('module');
            $this->unibox->xml->add_value('module_name', $module_name);
            $this->unibox->xml->add_value('module_ident', $module_ident);
            if ($module_ident == $this->unibox->session->env->system->module_ident || $extends_module_ident == $this->unibox->session->env->system->module_ident)
            {
                if ($this->unibox->session->env->alias->name == $module_ident.'_welcome')
                    $this->unibox->xml->add_value('active', 1);
                else
                    $this->unibox->xml->add_value('child_active', 1);

                $menu = ub_menu::get_instance();
                $menu->process_items($actionbar_menu_id);
                $menu->gen_menu();
            }
            $this->unibox->xml->parse_node();

        }
        $this->unibox->xml->parse_node();
    }
}

?>